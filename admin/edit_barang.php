<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

require_once "../koneksi.php";

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = '';
$success = '';

// Ambil data barang yang akan diedit
if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM barang WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $barang = $result->fetch_assoc();
    
    if (!$barang) {
        header('Location: barang.php');
        exit;
    }
} else {
    header('Location: barang.php');
    exit;
}

// Proses update barang
if (isset($_POST['update'])) {
    $nama = trim($_POST['nama'] ?? '');
    $harga = intval($_POST['harga'] ?? 0);
    
    // Validasi input
    if (empty($nama) || $harga <= 0) {
        $error = 'Nama dan harga harus diisi dengan benar!';
    } else {
        // Update barang
        $stmt = $conn->prepare("UPDATE barang SET nama = ?, harga = ? WHERE id = ?");
        $stmt->bind_param("sii", $nama, $harga, $id);
        
        if ($stmt->execute()) {
            $success = 'Barang berhasil diupdate!';
            // Refresh data barang
            $stmt = $conn->prepare("SELECT * FROM barang WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $barang = $result->fetch_assoc();
        } else {
            $error = 'Gagal mengupdate barang!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Barang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
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

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-pencil-square me-2"></i>Edit Barang
                    </h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Nama Barang</label>
                            <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($barang['nama']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Harga</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="harga" class="form-control" value="<?= $barang['harga'] ?>" required min="0">
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="barang.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Kembali
                            </a>
                            <button type="submit" name="update" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i>Update Barang
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
body {
    background: linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%);
    min-height: 100vh;
}
.card {
    border-radius: 1rem;
    border: none;
}
.card-header {
    border-radius: 1rem 1rem 0 0 !important;
}
</style>
</body>
</html> 