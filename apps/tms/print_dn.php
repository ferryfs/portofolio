<?php
session_name("TMS_APP_SESSION");
session_start();
include '../../koneksi.php';

if(!isset($_GET['id'])) { die("Error: ID Tidak ditemukan"); }
$dn_id = $_GET['id'];

// AMBIL DATA LENGKAP (JOIN SO, LOCATION, DRIVER, VEHICLE)
$query = "SELECT dn.*, 
          so.so_number, so.customer_name,
          l1.name as origin_name, l1.address as origin_addr,
          l2.name as dest_name, l2.address as dest_addr,
          v.plate_number, v.vehicle_type,
          d.fullname as driver_name
          FROM tms_delivery_notes dn
          JOIN tms_sales_orders so ON dn.so_id = so.id
          JOIN tms_locations l1 ON dn.origin_id = l1.id
          JOIN tms_locations l2 ON dn.dest_id = l2.id
          -- Left Join karena mungkin belum didispatch
          LEFT JOIN tms_orders o ON o.order_no = so.so_number 
          LEFT JOIN tms_shipment_stops ss ON ss.order_id = o.id
          LEFT JOIN tms_shipments shp ON ss.shipment_id = shp.id
          LEFT JOIN tms_vehicles v ON shp.vehicle_id = v.id
          LEFT JOIN tms_drivers dr ON shp.driver_id = dr.id
          LEFT JOIN tms_users d ON dr.user_id = d.id
          WHERE dn.id = '$dn_id' LIMIT 1";

// Catatan: Query di atas agak kompleks karena struktur kita simulasi. 
// Kalau error, kita simplify ambil basic data aja dari DN & SO.
$basic_q = "SELECT dn.*, so.so_number, so.customer_name,
            l1.name as origin_name, l1.address as origin_addr,
            l2.name as dest_name, l2.address as dest_addr
            FROM tms_delivery_notes dn
            JOIN tms_sales_orders so ON dn.so_id = so.id
            JOIN tms_locations l1 ON dn.origin_id = l1.id
            JOIN tms_locations l2 ON dn.dest_id = l2.id
            WHERE dn.id = '$dn_id'";

$run = mysqli_query($conn, $basic_q);
$header = mysqli_fetch_assoc($run);

// AMBIL ITEM
$items = mysqli_query($conn, "SELECT i.*, l.lpn_number FROM tms_items i JOIN tms_lpns l ON i.lpn_id = l.id WHERE l.dn_id = '$dn_id'");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak DN - <?=$header['dn_number']?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #ccc; font-family: 'Arial', sans-serif; font-size: 14px; }
        .page {
            background: white; width: 21cm; min-height: 29.7cm; display: block;
            margin: 0 auto; margin-bottom: 0.5cm; padding: 1cm;
            box-shadow: 0 0 0.5cm rgba(0,0,0,0.1);
        }
        @media print {
            body { background: none; margin: 0; }
            .page { margin: 0; box-shadow: none; width: 100%; }
            .no-print { display: none; }
        }
        .header-title { font-weight: bold; font-size: 24px; text-transform: uppercase; color: #333; }
        .box-info { border: 1px solid #333; padding: 10px; height: 120px; font-size: 13px; }
        .box-label { font-weight: bold; border-bottom: 1px solid #ccc; margin-bottom: 5px; padding-bottom: 2px; display: block; }
        .table-items thead { background: #eee; }
        .table-items th, .table-items td { border: 1px solid #333; padding: 8px; }
        .signature-box { height: 100px; border-bottom: 1px solid #333; text-align: center; }
        .signature-img { max-height: 80px; max-width: 100%; margin-top: 10px; }
    </style>
</head>
<body>

    <div class="text-center py-3 no-print">
        <button onclick="window.print()" class="btn btn-primary fw-bold">🖨️ Cetak Surat Jalan (Print)</button>
        <button onclick="window.close()" class="btn btn-secondary">Tutup</button>
    </div>

    <div class="page">
        <div class="row align-items-center mb-4 border-bottom pb-3">
            <div class="col-2">
                <div class="bg-dark text-white rounded p-3 text-center fw-bold">LOGO</div>
            </div>
            <div class="col-6">
                <h4 class="m-0 fw-bold">PT. LOGITRACK INDONESIA</h4>
                <div class="small">Jl. Kawasan Industri No. 99, Bekasi, Jawa Barat</div>
                <div class="small">Phone: (021) 8899-7766 | Email: ops@logitrack.com</div>
            </div>
            <div class="col-4 text-end">
                <div class="header-title">SURAT JALAN</div>
                <div class="fs-5 text-muted fw-bold">DELIVERY NOTE</div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-6">
                <div class="box-info">
                    <span class="box-label">SHIP TO (Penerima):</span>
                    <strong><?=$header['customer_name']?></strong><br>
                    <?=$header['dest_name']?><br>
                    <?=$header['dest_addr']?><br>
                    Bekasi, Indonesia
                </div>
            </div>
            <div class="col-6">
                <table class="table table-bordered table-sm mb-0">
                    <tr>
                        <td class="bg-light fw-bold">No. DN</td>
                        <td class="fw-bold fs-5"><?=$header['dn_number']?></td>
                    </tr>
                    <tr>
                        <td class="bg-light fw-bold">No. SO (Ref)</td>
                        <td><?=$header['so_number']?></td>
                    </tr>
                    <tr>
                        <td class="bg-light fw-bold">Tanggal Kirim</td>
                        <td><?=date('d F Y', strtotime($header['tgl_kirim']))?></td>
                    </tr>
                    <tr>
                        <td class="bg-light fw-bold">Armada / Driver</td>
                        <td>B 9090 TCO / Budi (Internal)</td> </tr>
                </table>
            </div>
        </div>

        <table class="table table-items w-100 mb-4">
            <thead>
                <tr>
                    <th width="5%" class="text-center">No</th>
                    <th width="20%">No. LPN / Pallet</th>
                    <th width="15%">Item Code</th>
                    <th>Nama Barang / Deskripsi</th>
                    <th width="10%" class="text-center">Qty</th>
                    <th width="10%" class="text-center">Satuan</th>
                    <th width="15%">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php $no=1; while($item = mysqli_fetch_assoc($items)): ?>
                <tr>
                    <td class="text-center"><?=$no++?></td>
                    <td><?=$item['lpn_number']?></td>
                    <td><?=$item['item_code']?></td>
                    <td><?=$item['item_name']?></td>
                    <td class="text-center fw-bold"><?=$item['qty_ordered']?></td>
                    <td class="text-center">PCS</td>
                    <td>-</td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="row mt-5">
            <div class="col-4 text-center">
                <div class="mb-2">Dibuat Oleh (Admin),</div>
                <div class="signature-box d-flex align-items-end justify-content-center">
                    <span class="text-muted small mb-2">( Ttd System )</span>
                </div>
                <div class="mt-2 fw-bold">Logistics Planner</div>
            </div>
            
            <div class="col-4 text-center">
                <div class="mb-2">Diserahkan Oleh (Driver),</div>
                <div class="signature-box">
                     <?php if(!empty($header['sign_sender'])): ?>
                        <img src="<?=$header['sign_sender']?>" class="signature-img">
                     <?php else: ?>
                        <br><br><small class="text-muted">Belum TTD</small>
                     <?php endif; ?>
                </div>
                <div class="mt-2 fw-bold"><?=$header['sign_sender_name'] ?? '.................'?></div>
            </div>

            <div class="col-4 text-center">
                <div class="mb-2">Diterima Oleh (Customer),</div>
                <div class="signature-box">
                    <?php if(!empty($header['sign_receiver'])): ?>
                        <img src="<?=$header['sign_receiver']?>" class="signature-img">
                     <?php else: ?>
                        <br><br><small class="text-muted">Belum TTD</small>
                     <?php endif; ?>
                </div>
                <div class="mt-2 fw-bold"><?=$header['sign_receiver_name'] ?? '.................'?></div>
            </div>
        </div>

        <div class="mt-5 text-center small text-muted fst-italic border-top pt-3">
            Dokumen ini dicetak otomatis oleh Sistem LogiTrack TMS pada tanggal <?=date('d/m/Y H:i')?>.<br>
            Mohon simpan dokumen ini sebagai bukti serah terima barang yang sah.
        </div>

    </div>
</body>
</html>