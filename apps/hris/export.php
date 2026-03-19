<?php
session_name("HRIS_APP_SESSION");
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
if(!isset($_SESSION['hris_user'])) { header("Location: login.php"); exit(); }

$type  = sanitizeInput($_GET['type'] ?? '');
$month = (int)($_GET['month'] ?? date('n'));
$year  = (int)($_GET['year']  ?? date('Y'));
$period= sprintf('%04d-%02d', $year, $month);
$bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

function csv_row($arr) {
    return implode(',', array_map(fn($v) => '"'.str_replace('"','""',$v ?? '').'"', $arr)) . "\n";
}

if($type === 'attendance') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="rekap_absensi_'.$period.'.csv"');
    echo "\xEF\xBB\xBF"; // BOM UTF-8

    echo csv_row(['NIK','Nama','Divisi','Tanggal','Tipe','Jam Masuk','Jam Pulang','Status','Telat','Laporan Kerja']);

    global $pdo;
    $rows = safeGetAll($pdo,
        "SELECT a.*, u.division FROM ess_attendance a
         LEFT JOIN ess_users u ON a.employee_id=u.employee_id
         WHERE a.date_log LIKE ? ORDER BY a.date_log ASC, a.fullname ASC",
        [$period.'%']);

    foreach($rows as $r) {
        $masuk  = date('H:i', strtotime($r['check_in_time']));
        $pulang = $r['check_out_time'] ? date('H:i', strtotime($r['check_out_time'])) : '';
        $telat  = strtotime($r['check_in_time']) > strtotime($r['date_log'].' 08:30:00') ? 'Ya' : 'Tidak';
        $status = $r['check_out_time'] ? 'Selesai' : 'Aktif';
        echo csv_row([$r['employee_id'],$r['fullname'],$r['division'],$r['date_log'],$r['type'],$masuk,$pulang,$status,$telat,$r['tasks'] ?? '']);
    }
    exit();
}

if($type === 'payroll') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="rekap_payroll_'.$period.'.csv"');
    echo "\xEF\xBB\xBF";

    echo csv_row(['NIK','Nama','Divisi','Gaji Pokok','Transport','Makan','Lembur','BPJS','PPh21','Take Home Pay','Hari Hadir','Jam Lembur']);

    $rows = safeGetAll($pdo,
        "SELECT p.*, u.fullname, u.division FROM ess_payslips p JOIN ess_users u ON p.employee_id=u.employee_id
         WHERE p.period_month=? AND p.period_year=? ORDER BY u.fullname",
        [$month, $year]);

    foreach($rows as $r) {
        echo csv_row([$r['employee_id'],$r['fullname'],$r['division'],
            $r['basic_salary'],$r['transport_allowance'],$r['meal_allowance'],
            $r['overtime_pay'],$r['deduction_bpjs'],$r['deduction_tax'],
            $r['net_salary'],$r['attendance_days'],$r['overtime_hours']]);
    }
    exit();
}

if($type === 'employees') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="data_karyawan_'.date('Y-m-d').'.csv"');
    echo "\xEF\xBB\xBF";

    echo csv_row(['NIK','Nama','Gender','Divisi','Departemen','Jabatan','Role','Tipe Kontrak','Status','Gaji Pokok','Join Date','Kontrak Selesai','Sisa Cuti','NPWP','BPJS Kesehatan','BPJS TK']);

    $rows = safeGetAll($pdo, "SELECT * FROM ess_users ORDER BY fullname");
    foreach($rows as $r) {
        echo csv_row([$r['employee_id'],$r['fullname'],$r['gender']??'',$r['division'],$r['department']??'',$r['position']??'',$r['role'],$r['tipe_kontrak']??'',$r['employee_status']??'',$r['basic_salary'],$r['join_date'],$r['kontrak_end']??'',$r['annual_leave_quota'],$r['npwp']??'',$r['no_bpjs_kes']??'',$r['no_bpjs_tk']??'']);
    }
    exit();
}

// Redirect jika type tidak valid
header("Location: index.php");
exit();
