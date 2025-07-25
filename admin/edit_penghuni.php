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

// Ambil data penghuni yang akan diedit
if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM penghuni WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $penghuni = $result->fetch_assoc();
    if (!$penghuni) {
        header('Location: penghuni.php');
        exit;
    }
} else {
    header('Location: penghuni.php');
    exit;
}

// Proses update penghuni
if (isset($_POST['update'])) {
    $nama = trim($_POST['nama'] ?? '');
    $no_ktp = trim($_POST['no_ktp'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');

    if (empty($nama) || empty($no_ktp) || empty($no_hp)) {
        $error = 'Nama, No KTP, dan No HP harus diisi!';
    } else {
        // Cek apakah no KTP sudah ada (kecuali yang sedang diedit)
        $stmt = $conn->prepare("SELECT id FROM penghuni WHERE no_ktp = ? AND id != ?");
        $stmt->bind_param("si", $no_ktp, $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'No KTP sudah terdaftar!';
        } else {
            $stmt = $conn->prepare("UPDATE penghuni SET nama = ?, no_ktp = ?, no_hp = ? WHERE id = ?");
            $stmt->bind_param("sssi", $nama, $no_ktp, $no_hp, $id);
            if ($stmt->execute()) {
                $success = 'Data penghuni berhasil diupdate!';
                // Refresh data
                $stmt = $conn->prepare("SELECT * FROM penghuni WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $penghuni = $result->fetch_assoc();
            } else {
                $error = 'Gagal mengupdate data penghuni!';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Penghuni</title>
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
                        <i class="bi bi-pencil-square me-2"></i>Edit Penghuni
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
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($penghuni['nama']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">No KTP</label>
                            <input type="text" name="no_ktp" class="form-control" value="<?= htmlspecialchars($penghuni['no_ktp']) ?>" required maxlength="16">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">No HP</label>
                            <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($penghuni['no_hp']) ?>" required>
                        </div>
                        <div class="d-flex justify-content-between">
                            <a href="penghuni.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Kembali
                            </a>
                            <button type="submit" name="update" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i>Update Penghuni
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