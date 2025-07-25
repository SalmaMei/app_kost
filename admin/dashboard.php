<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}
require_once "../koneksi.php";

// Query sama seperti di halaman depan
$kamar_kosong = $conn->query("SELECT nomor, harga FROM kamar WHERE id NOT IN (SELECT id_kamar FROM kmr_penghuni WHERE tgl_keluar IS NULL)");
$tagihan_segera = $conn->query("SELECT k.nomor, p.nama, kp.tgl_masuk, t.bulan, t.jml_tagihan FROM tagihan t JOIN kmr_penghuni kp ON t.id_kmr_penghuni = kp.id JOIN kamar k ON kp.id_kamar = k.id JOIN penghuni p ON kp.id_penghuni = p.id WHERE t.bulan = DATE_FORMAT(CURDATE(), '%Y-%m-01') AND t.id NOT IN (SELECT id_tagihan FROM bayar WHERE status='lunas') AND DAY(CURDATE()) >= 25");
$tagihan_telat = $conn->query("SELECT k.nomor, p.nama, t.bulan, t.jml_tagihan FROM tagihan t JOIN kmr_penghuni kp ON t.id_kmr_penghuni = kp.id JOIN kamar k ON kp.id_kamar = k.id JOIN penghuni p ON kp.id_penghuni = p.id WHERE t.bulan = DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-01') AND t.id NOT IN (SELECT id_tagihan FROM bayar WHERE status='lunas')");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg" style="background-color:rgb(231, 140, 186);">
    <div class="container">
        <a class="navbar-brand text-white" href="#">Admin Kost</a>
        <div>
            <a href="dashboard.php" class="btn btn-outline-light">Dashboard</a>
            <a href="kamar.php" class="btn btn-outline-light">Kamar</a>
            <a href="penghuni.php" class="btn btn-outline-light">Penghuni</a>
            <a href="barang.php" class="btn btn-outline-light">Barang</a>
            <a href="tagihan.php" class="btn btn-outline-light">Tagihan</a>
            <a href="pembayaran.php" class="btn btn-outline-light">Pembayaran</a>
            <a href="../logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>
</nav>
<div class="container py-5">
    <h1 class="mb-5 text-center fw-bold display-4" style="letter-spacing:2px;">
        <i class="bi bi-speedometer2 me-2 text-primary"></i>Dashboard Admin
    </h1>
    <div class="row mb-5 g-4 justify-content-center">
        <div class="col-md-4">
            <div class="card shadow-lg border-0 h-100">
                <div class="card-body">
                    <h4 class="card-title mb-3 text-success"><i class="bi bi-door-open-fill me-2"></i>Kamar Kosong</h4>
                    <ul class="list-group list-group-flush">
                        <?php if ($kamar_kosong->num_rows > 0): ?>
                            <?php while($row = $kamar_kosong->fetch_assoc()): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><strong>Nomor:</strong> <?= $row['nomor'] ?></span>
                                    <span class="badge bg-success">Rp<?= number_format($row['harga']) ?></span>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li class="list-group-item text-muted text-center">Tidak ada kamar kosong</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-lg border-0 h-100">
                <div class="card-body">
                    <h4 class="card-title mb-3 text-warning"><i class="bi bi-cash-coin me-2"></i>Kamar Harus Bayar (Sebentar Lagi)</h4>
                    <ul class="list-group list-group-flush">
                        <?php if ($tagihan_segera->num_rows > 0): ?>
                            <?php while($row = $tagihan_segera->fetch_assoc()): ?>
                                <li class="list-group-item">
                                    <div><strong>Nomor:</strong> <?= $row['nomor'] ?></div>
                                    <div><strong>Nama:</strong> <?= $row['nama'] ?></div>
                                    <div><strong>Masuk:</strong> <?= date('d-m-Y', strtotime($row['tgl_masuk'])) ?></div>
                                    <div><strong>Tagihan:</strong> <span class="badge bg-warning text-dark">Rp<?= number_format($row['jml_tagihan']) ?></span></div>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li class="list-group-item text-muted text-center">Tidak ada kamar yang harus bayar dalam waktu dekat</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-lg border-0 h-100">
                <div class="card-body">
                    <h4 class="card-title mb-3 text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Kamar Terlambat Bayar</h4>
                    <ul class="list-group list-group-flush">
                        <?php if ($tagihan_telat->num_rows > 0): ?>
                            <?php while($row = $tagihan_telat->fetch_assoc()): ?>
                                <li class="list-group-item list-group-item-danger">
                                    <div><strong>Nomor:</strong> <?= $row['nomor'] ?></div>
                                    <div><strong>Nama:</strong> <?= $row['nama'] ?></div>
                                    <div><strong>Tagihan:</strong> <span class="badge bg-danger">Rp<?= number_format($row['jml_tagihan']) ?></span></div>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li class="list-group-item text-muted text-center">Tidak ada kamar yang terlambat bayar</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex flex-wrap justify-content-center gap-3 mt-4">
        <a href="kamar.php" class="btn btn-primary">Kelola Kamar</a>
        <a href="penghuni.php" class="btn btn-primary">Kelola Penghuni</a>
        <a href="barang.php" class="btn btn-primary">Kelola Barang</a>
        <a href="tagihan.php" class="btn btn-primary">Generate Tagihan</a>
    </div>
</div>
<style>
body, html {
    height: 100%;
    background: linear-gradient(135deg, #e0e7ff 0%,rgb(88, 159, 230) 100%);
}
.card {
    border-radius: 1rem;
}
.card-title i {
    font-size: 1.5rem;
    vertical-align: middle;
}
</style>
</body>
</html>
