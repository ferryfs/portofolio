<?php
session_name("ESS_PORTAL_SESSION"); // <--- Kunci harus sama kayak auth.php
session_start();
$conn = mysqli_connect("localhost", "root", "", "portofolio_db");

// Cek Login
if(!isset($_SESSION['ess_user'])) { header("Location: landing.php"); exit(); }

// LOGIC SIMPAN DATA CUTI
if(isset($_POST['submit_cuti'])) {
    $nik   = $_SESSION['ess_user'];
    $nama  = $_SESSION['ess_name'];
    $div   = $_SESSION['ess_div']; // Pastikan di auth.php session ini diset (kalau belum, edit auth.php tambahin $_SESSION['ess_div'] = $data['division'];)
    
    // Fallback kalau session div kosong (opsional, jaga-jaga)
    if(empty($div)) $div = "General"; 

    $tipe  = $_POST['tipe'];
    $mulai = $_POST['mulai'];
    $akhir = $_POST['akhir'];
    $alasan= $_POST['alasan'];

    $sql = "INSERT INTO ess_leaves (employee_id, fullname, division, leave_type, start_date, end_date, reason) 
            VALUES ('$nik', '$nama', '$div', '$tipe', '$mulai', '$akhir', '$alasan')";
            
    if(mysqli_query($conn, $sql)) {
        echo "<script>alert('Pengajuan Berhasil! Menunggu Approval Atasan.'); window.location='index.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Form Pengajuan Cuti</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }</style>
</head>
<body>
    <div class="container py-4" style="max-width: 450px;">
        <div class="d-flex align-items-center mb-4">
            <a href="index.php" class="text-dark me-3"><i class="fa fa-arrow-left fa-lg"></i></a>
            <h5 class="fw-bold mb-0">Form Pengajuan Cuti/Izin</h5>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Jenis Pengajuan</label>
                        <select name="tipe" class="form-select" required>
                            <option value="Cuti Tahunan">Cuti Tahunan</option>
                            <option value="Sakit">Sakit (Dengan Surat Dokter)</option>
                            <option value="Izin Khusus">Izin Khusus</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold">Mulai Tanggal</label>
                            <input type="date" name="mulai" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold">Sampai Tanggal</label>
                            <input type="date" name="akhir" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold">Alasan / Keterangan</label>
                        <textarea name="alasan" class="form-control" rows="3" placeholder="Contoh: Urusan keluarga / Check up dokter" required></textarea>
                    </div>
                    <button type="submit" name="submit_cuti" class="btn btn-primary w-100 fw-bold py-2 rounded-pill">KIRIM PENGAJUAN</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>