<?php
// apps/wms/WMSPutawayService.php
// ENTERPRISE SERVICE LAYER: Putaway Execution Engine
// Features: Phantom Stock Prevention (Pessimistic Lock), Bin Regex Validation, Centralized Transaction, Clean Alert Formatting, Split Audit

class WMSPutawayService {
    private $pdo;
    private $user;

    public function __construct($pdo, $user) {
        $this->pdo = $pdo;
        $this->user = $user;
    }

    /**
     * Execute Putaway Task (Dipanggil oleh RF Scanner & Desktop Confirm)
     * * @param int $taskId ID dari wms_warehouse_tasks
     * @param string $targetBin Scan barcode lokasi rak tujuan
     * @param float $qtyGood Jumlah barang bagus (F1)
     * @param float $qtyBad Jumlah barang rusak (B6)
     * @param string $remarks Catatan operasional
     * @param string $clientSource Sumber eksekusi (RF / DESKTOP)
     * @return array Status dan Message
     */
    public function executePutaway($taskId, $targetBin, $qtyGood, $qtyBad, $remarks, $clientSource = 'SYSTEM') {
        try {
            $this->pdo->beginTransaction();

            $targetBin = strtoupper(trim($targetBin));
            $qtyGood = (float)$qtyGood;
            $qtyBad = (float)$qtyBad;
            $remarks = $remarks ?: '-';

            // 🔥 1. REGEX BIN FORMAT VALIDATION (Anti Rak Sampah)
            // Memaksa operator mematuhi format gudang, misal: Lorong-Rak-Tingkat (A-01-01 atau AA-99-99)
            // Pengecualian hanya untuk pembuangan ke BLOCK-ZONE
            if (!preg_match("/^[A-Z]{1,2}-[0-9]{1,2}-[0-9]{1,2}$/", $targetBin) && $targetBin !== 'BLOCK-ZONE') {
                throw new Exception("INVALID BIN FORMAT: '$targetBin'. Must follow standard format (e.g., A-01-01).");
            }

            // 🔥 2. TASK & CONCURRENCY LOCK
            // Mengunci task agar tidak dieksekusi 2 kali bersamaan
            $task = safeGetOne($this->pdo, "SELECT t.*, q.gr_ref, q.po_ref, q.batch, p.product_code 
                                            FROM wms_warehouse_tasks t 
                                            LEFT JOIN wms_quants q ON t.hu_id = q.hu_id 
                                            JOIN wms_products p ON t.product_uuid = p.product_uuid
                                            WHERE t.tanum = ? AND t.status = 'OPEN' FOR UPDATE", [$taskId]);
            
            if (!$task) {
                throw new Exception("ACCESS DENIED: Task #$taskId is invalid, already processed, or locked.");
            }

            // Validasi Input Qty vs Task Target
            if (($qtyGood + $qtyBad) <= 0) {
                throw new Exception("QUANTITY ERROR: Total checked quantity cannot be zero.");
            }

            // 🔥 3. SOURCE STOCK VALIDATION (Phantom Stock Check)
            // Memastikan fisik barang / HU benar-benar masih ada di lokasi asal sebelum dipindah
            $sourceQuant = safeGetOne($this->pdo, "SELECT qty FROM wms_quants WHERE hu_id = ? AND lgpla = ? FOR UPDATE", [$task['hu_id'], $task['source_bin']]);
            if (!$sourceQuant) {
                throw new Exception("PHANTOM STOCK ALERT: Handling Unit {$task['hu_id']} is missing from {$task['source_bin']}. It may have been voided or moved manually.");
            }

            if (($qtyGood + $qtyBad) > $sourceQuant['qty']) {
                throw new Exception("OVERAGE ERROR: You cannot putaway " . ($qtyGood + $qtyBad) . " items. This Pallet (HU) only contains " . (float)$sourceQuant['qty'] . " items!");
            }

            // Proses Auto-Create Bin yang sudah Lolos Regex
            $this->ensureBinExists($targetBin);

            $huBad = null; // Siapkan ID HU Bad

            // --- INTI PROSES BISNIS ---
            if ($task['process_type'] == 'PUTAWAY') {
                
                // A. Update Rekapan di GR Items & Cek Mismatch
                if ($task['gr_ref']) {
                    safeQuery($this->pdo, "UPDATE wms_gr_items 
                                           SET qty_actual_good = qty_actual_good + ?, 
                                               qty_actual_damaged = qty_actual_damaged + ? 
                                           WHERE gr_number = ? AND product_uuid = ?", 
                                           [$qtyGood, $qtyBad, $task['gr_ref'], $task['product_uuid']]);
                    
                    // Kalkulasi Mismatch
                    safeQuery($this->pdo, "UPDATE wms_gr_items 
                                           SET discrepancy_status = CASE WHEN ABS(qty_reported - (qty_actual_good + qty_actual_damaged)) < 0.001 THEN 'BALANCED' ELSE 'MISMATCH' END 
                                           WHERE gr_number = ? AND product_uuid = ?", 
                                           [$task['gr_ref'], $task['product_uuid']]);
                    
                    // Trigger Notifikasi jika terjadi Mismatch
                    $cekStatus = safeGetOne($this->pdo, "SELECT discrepancy_status, qty_reported, qty_actual_good, qty_actual_damaged FROM wms_gr_items WHERE gr_number = ? AND product_uuid = ?", [$task['gr_ref'], $task['product_uuid']]);
                    if ($cekStatus['discrepancy_status'] == 'MISMATCH') {
                        $totalAktual = (float)$cekStatus['qty_actual_good'] + (float)$cekStatus['qty_actual_damaged'];
                        $qtyReportedClean = (float)$cekStatus['qty_reported'];
                        $msg = "[$clientSource] Discrepancy Alert for PO {$task['po_ref']} (Item: {$task['product_code']}). Admin GR Qty: {$qtyReportedClean} | Operator Actual Qty: {$totalAktual}";
                        safeQuery($this->pdo, "INSERT INTO wms_inbound_notif (po_number, message, severity, created_at) VALUES (?, ?, 'DANGER', NOW())", [$task['po_ref'], $msg]);
                    }
                }

                // B. Hapus Stok dari Staging Area Asal (Menghancurkan Phantom Source)
                safeQuery($this->pdo, "DELETE FROM wms_quants WHERE hu_id = ? AND lgpla = ?", [$task['hu_id'], $task['source_bin']]);

                // C. Insert Stok Bagus (F1) ke Target Bin
                if ($qtyGood > 0) {
                    safeQuery($this->pdo, "INSERT INTO wms_quants (product_uuid, lgpla, batch, hu_id, qty, stock_type, gr_date, is_putaway, po_ref, gr_ref) 
                                           VALUES (?, ?, ?, ?, ?, 'F1', NOW(), 1, ?, ?)", 
                                           [$task['product_uuid'], $targetBin, $task['batch'], $task['hu_id'], $qtyGood, $task['po_ref'], $task['gr_ref']]);
                    safeQuery($this->pdo, "UPDATE wms_storage_bins SET status_bin='OCCUPIED' WHERE lgpla=?", [$targetBin]);
                }

                // D. Insert Stok Rusak (B6) ke Block Zone
                if ($qtyBad > 0) {
                    $huBad = "DMG-" . $task['hu_id'] . "-" . time(); // Generate sub-HU untuk barang rusak
                    $this->ensureBinExists('BLOCK-ZONE');
                    safeQuery($this->pdo, "INSERT INTO wms_quants (product_uuid, lgpla, batch, hu_id, qty, stock_type, gr_date, is_putaway, po_ref, gr_ref) 
                                           VALUES (?, 'BLOCK-ZONE', ?, ?, ?, 'B6', NOW(), 1, ?, ?)", 
                                           [$task['product_uuid'], $task['batch'], $huBad, $qtyBad, $task['po_ref'], $task['gr_ref']]);
                }
            }

            // E. Tutup Task
            safeQuery($this->pdo, "UPDATE wms_warehouse_tasks 
                                   SET status='CONFIRMED', dest_bin=?, confirmed_at=NOW(), operator_id=? 
                                   WHERE tanum=?", [$targetBin, $this->user, $taskId]);

            // 🔥 F. CATAT LOG MOVEMENT (DIBELAH DUA BIAR AKURAT)
            $moveType = "{$clientSource}_PUTAWAY";
            
            // Log 1: Barang Bagus masuk Rak (Jika ada)
            if ($qtyGood > 0) {
                safeQuery($this->pdo, "INSERT INTO wms_stock_movements (trx_ref, product_uuid, hu_id, qty_change, move_type, user, from_bin, to_bin, created_at) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())", 
                                       ["TASK-$taskId", $task['product_uuid'], $task['hu_id'], $qtyGood, $moveType, $this->user, $task['source_bin'], $targetBin]);
            }

            // Log 2: Barang Jelek masuk Block Zone (Jika ada)
            if ($qtyBad > 0) {
                safeQuery($this->pdo, "INSERT INTO wms_stock_movements (trx_ref, product_uuid, hu_id, qty_change, move_type, user, from_bin, to_bin, created_at) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())", 
                                       ["TASK-$taskId", $task['product_uuid'], $huBad, $qtyBad, $moveType."_BAD", $this->user, $task['source_bin'], 'BLOCK-ZONE']);
            }
            
            // G. Catat ke System Log (Audit IT)
            $logDesc = "Task #$taskId DONE via $clientSource. G:$qtyGood | B:$qtyBad | Bin:$targetBin | Note: $remarks";
            safeQuery($this->pdo, "INSERT INTO wms_system_logs (user_id, module, action_type, description, ip_address, log_date) 
                                   VALUES (?, 'PUTAWAY_SVC', 'EXECUTE', ?, ?, NOW())", [$this->user, $logDesc, $_SERVER['REMOTE_ADDR']]);

            // H. Kasih Feedback ke Layar Admin Inbound
            $msgSuccess = "Task #$taskId (Item: {$task['product_code']}) confirmed by {$this->user} via $clientSource. Note: $remarks";
            safeQuery($this->pdo, "INSERT INTO wms_inbound_notif (po_number, message, severity, created_at) VALUES (?, ?, 'SUCCESS', NOW())", [$task['po_ref'], $msgSuccess]);

            $this->pdo->commit();
            return ['status' => 'success', 'msg' => 'Task Confirmed Successfully'];

        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['status' => 'error', 'msg' => $e->getMessage()];
        }
    }

    /**
     * Helper Method: Memastikan Rak ada di master data
     */
    private function ensureBinExists($binCode) {
        $stmt = $this->pdo->prepare("SELECT lgpla FROM wms_storage_bins WHERE lgpla = ?");
        $stmt->execute([$binCode]);
        if (!$stmt->fetch()) {
            // Karena sudah di-filter Regex di atas, ini aman dari Garbage Data
            $ins = $this->pdo->prepare("INSERT INTO wms_storage_bins (lgpla, lgtyp, max_weight, status_bin) VALUES (?, 'DYNAMIC', 9999, 'OCCUPIED')");
            $ins->execute([$binCode]);
        }
    }
}
?>