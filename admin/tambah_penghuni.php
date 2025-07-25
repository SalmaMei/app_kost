<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

require_once "../koneksi.php";

$error = '';
$success = '';

if (isset($_POST['tambah'])) {
    $nama = trim($_POST['nama'] ?? '');
    $no_ktp = trim($_POST['no_ktp'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($nama) || empty($no_ktp) || empty($no_hp)) {
        $error = 'Nama, No KTP, dan No HP harus diisi!';
    } else {
        // Cek apakah no KTP sudah ada
        $stmt = $conn->prepare("SELECT id FROM penghuni WHERE no_ktp = ?");
        $stmt->bind_param("s", $no_ktp);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'No KTP sudah terdaftar!';
        } else {
            $stmt = $conn->prepare("INSERT INTO penghuni (nama, no_ktp, no_hp, alamat, email) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $nama, $no_ktp, $no_hp, $alamat, $email);
            if ($stmt->execute()) {
                $success = 'Data penghuni berhasil ditambahkan!';
            } else {
                $error = 'Gagal menambahkan data penghuni!';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Penghuni</title>
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
                    <h5 class="mb-0">
                        <i class="bi bi-person-plus me-2"></i>Tambah Penghuni
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger small"><?= $error ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success small"><?= $success ?></div>
                    <?php endif; ?>
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">No KTP</label>
                            <input type="text" name="no_ktp" class="form-control" required maxlength="16">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">No HP</label>
                            <input type="text" name="no_hp" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea name="alamat" class="form-control" rows="3"></textarea>
                        </div>
                        <button type="submit" name="tambah" class="btn btn-primary w-100">
                            <i class="bi bi-plus-circle me-1"></i>Tambah Penghuni
                        </button>
                        <a href="penghuni.php" class="btn btn-secondary w-100 mt-2">
                            <i class="bi bi-arrow-left me-1"></i>Kembali
                        </a>
                    </form>
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
</style>
</body>
</html> 