<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}
require_once "../koneksi.php";

// Ambil data kamar
$kamar = $conn->query("SELECT * FROM kamar ORDER BY nomor ASC");

if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    $stmt = $conn->prepare("DELETE FROM kamar WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Kamar berhasil dihapus!</div>";
    } else {
        echo "<div class='alert alert-danger'>Gagal menghapus kamar!</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Kamar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .bg-pink { background-color: #e83e8c !important; }
    </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark" style="background-color:rgb(247, 132, 185);">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Admin Kost</a>
        <div>
            <a href="dashboard.php" class="btn btn-outline-light">Dashboard</a>
            <a href="kamar.php" class="btn btn-outline-light active">Kamar</a>
            <a href="penghuni.php" class="btn btn-outline-light">Penghuni</a>
            <a href="barang.php" class="btn btn-outline-light">Barang</a>
            <a href="tagihan.php" class="btn btn-outline-light">Tagihan</a>
            <a href="pembayaran.php" class="btn btn-outline-light">Pembayaran</a>
            <a href="../logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>
</nav>
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Daftar Kamar</h2>
        <a href="tambah_kamar.php" class="btn btn-success"><i class="bi bi-plus-circle"></i> Tambah Kamar</a>
    </div>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Nomor Kamar</th>
                        <th>Harga</th>
                        <th>Gambar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no=1; while($row = $kamar->fetch_assoc()): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['nomor']) ?></td>
                        <td>Rp<?= number_format($row['harga']) ?></td>
                        <td>
                            <?php if ($row['gambar']): ?>
                                <img src="../uploads/kamar/<?= htmlspecialchars($row['gambar']) ?>" alt="Kamar" style="width:60px; height:40px; object-fit:cover; border-radius:6px;">
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="edit_kamar.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <a href="kamar.php?hapus=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus kamar ini?')">
                                <i class="bi bi-trash"></i> Hapus
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html> 