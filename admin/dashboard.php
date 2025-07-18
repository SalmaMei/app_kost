<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: ../login.php');
    exit;
}
$conn = new mysqli("localhost", "root", "", "kost");

// Query sama seperti di halaman depan
$kamar_kosong = $conn->query("SELECT nomor, harga FROM tb_kamar WHERE id NOT IN (SELECT id_kamar FROM tb_kmr_penghuni WHERE tgl_keluar IS NULL)");
$tagihan_segera = $conn->query("SELECT k.nomor, p.nama, kp.tgl_masuk, t.bulan, t.jml_tagihan FROM tb_tagihan t JOIN tb_kmr_penghuni kp ON t.id_kmr_penghuni = kp.id JOIN tb_kamar k ON kp.id_kamar = k.id JOIN tb_penghuni p ON kp.id_penghuni = p.id WHERE t.bulan = DATE_FORMAT(CURDATE(), '%Y-%m-01') AND t.id NOT IN (SELECT id_tagihan FROM tb_bayar WHERE status='lunas') AND DAY(CURDATE()) >= 25");
$tagihan_telat = $conn->query("SELECT k.nomor, p.nama, t.bulan, t.jml_tagihan FROM tb_tagihan t JOIN tb_kmr_penghuni kp ON t.id_kmr_penghuni = kp.id JOIN tb_kamar k ON kp.id_kamar = k.id JOIN tb_penghuni p ON kp.id_penghuni = p.id WHERE t.bulan = DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-01') AND t.id NOT IN (SELECT id_tagihan FROM tb_bayar WHERE status='lunas')");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="#">Admin Kost</a>
        <div>
            <a href="dashboard.php" class="btn btn-outline-light">Dashboard</a>
            <a href="kamar.php" class="btn btn-outline-light">Kamar</a>
            <a href="penghuni.php" class="btn btn-outline-light">Penghuni</a>
            <a href="barang.php" class="btn btn-outline-light">Barang</a>
            <a href="tagihan.php" class="btn btn-outline-light">Tagihan</a>
            <a href="../logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>
</nav>
<div class="container py-4">
    <h1>Dashboard Admin</h1>
    <div class="row">
        <div class="col-md-4">
            <h4>Kamar Kosong</h4>
            <ul class="list-group">
                <?php while($row = $kamar_kosong->fetch_assoc()): ?>
                    <li class="list-group-item">
                        Kamar <?= $row['nomor'] ?> - <span class="badge bg-success">Rp<?= number_format($row['harga']) ?></span>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
        <div class="col-md-4">
            <h4>Kamar Harus Bayar (Sebentar Lagi)</h4>
            <ul class="list-group">
                <?php while($row = $tagihan_segera->fetch_assoc()): ?>
                    <li class="list-group-item">
                        Kamar <?= $row['nomor'] ?> (<?= $row['nama'] ?>)<br>
                        Masuk: <?= date('d-m-Y', strtotime($row['tgl_masuk'])) ?><br>
                        Tagihan: <span class="badge bg-warning text-dark">Rp<?= number_format($row['jml_tagihan']) ?></span>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
        <div class="col-md-4">
            <h4>Kamar Terlambat Bayar</h4>
            <ul class="list-group">
                <?php while($row = $tagihan_telat->fetch_assoc()): ?>
                    <li class="list-group-item list-group-item-danger">
                        Kamar <?= $row['nomor'] ?> (<?= $row['nama'] ?>)<br>
                        Tagihan: <span class="badge bg-danger">Rp<?= number_format($row['jml_tagihan']) ?></span>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>
    <div class="mt-4">
        <a href="kamar.php" class="btn btn-primary">Kelola Kamar</a>
        <a href="penghuni.php" class="btn btn-primary">Kelola Penghuni</a>
        <a href="barang.php" class="btn btn-primary">Kelola Barang</a>
        <a href="tagihan.php" class="btn btn-primary">Generate Tagihan</a>
    </div>
</div>
</body>
</html>
