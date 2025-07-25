<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}
require_once "../koneksi.php";

$error = '';
$success = '';

// Tambah barang
if (isset($_POST['tambah'])) {
    $nama = trim($_POST['nama'] ?? '');
    $harga = intval($_POST['harga'] ?? 0);

    if (empty($nama) || $harga <= 0) {
        $error = 'Nama dan harga harus diisi dengan benar!';
    } else {
        $stmt = $conn->prepare("INSERT INTO barang (nama, harga) VALUES (?, ?)");
        $stmt->bind_param("si", $nama, $harga);
        if ($stmt->execute()) {
            $success = 'Barang berhasil ditambahkan!';
        } else {
            $error = 'Gagal menambah barang: ' . $stmt->error;
        }
    }
}

// Hapus barang
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    $stmt = $conn->prepare("DELETE FROM barang WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success = 'Barang berhasil dihapus!';
    } else {
        $error = 'Gagal menghapus barang!';
    }
    header('Location: barang.php');
    exit;
}

// Ambil data barang untuk ditampilkan
$barang = $conn->query("SELECT * FROM barang ORDER BY nama ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Barang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark" style="background-color:rgb(247, 132, 185);">

    <div class="container">
        <a class="navbar-brand" href="#">Admin Kost</a>
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
    <h2 class="fw-bold text-center mb-4">Daftar Barang</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-box-seam me-2"></i>Tambah Barang
                    </h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Nama Barang</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Harga</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="harga" class="form-control" required min="0">
                            </div>
                        </div>
                        <button type="submit" name="tambah" class="btn btn-primary w-100">
                            <i class="bi bi-plus-circle me-1"></i>Tambah Barang
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-boxes me-2"></i>Daftar Barang
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Nama Barang</th>
                                    <th>Harga</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($barang && $barang->num_rows > 0): ?>
                                    <?php $no = 1; while($row = $barang->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><strong><?= htmlspecialchars($row['nama']) ?></strong></td>
                                            <td><strong>Rp<?= number_format($row['harga']) ?></strong></td>
                                            <td>
                                                <a href="edit_barang.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                                <a href="barang.php?hapus=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus barang ini?')">
                                                    <i class="bi bi-trash"></i> Hapus
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            <i class="bi bi-box h3 d-block mb-2"></i>
                                            Tidak ada data barang
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
body {
    background: linear-gradient(135deg,rgb(122, 149, 243) 0%, #f8fafc 100%);
    min-height: 100vh;
}
.card {
    border-radius: 1rem;
    border: none;
}
.card-header {
    border-radius: 1rem 1rem 0 0 !important;
}
.table th {
    border-top: none;
    font-weight: 600;
}
.badge {
    font-size: 0.8em;
}
</style>
</body>
</html>
