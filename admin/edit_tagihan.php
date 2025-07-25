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

// Ambil data tagihan yang akan diedit
if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM tagihan WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $tagihan = $result->fetch_assoc();
    
    if (!$tagihan) {
        header('Location: tagihan.php');
        exit;
    }
} else {
    header('Location: tagihan.php');
    exit;
}

// Proses update tagihan
if (isset($_POST['update'])) {
    $bulan = $_POST['bulan'] ?? '';
    $id_kmr_penghuni = intval($_POST['id_kmr_penghuni'] ?? 0);
    $jml_tagihan = intval($_POST['jml_tagihan'] ?? 0);
    
    // Validasi input
    if (empty($bulan) || $id_kmr_penghuni <= 0 || $jml_tagihan <= 0) {
        $error = 'Bulan, kamar penghuni, dan jumlah tagihan harus diisi dengan benar!';
    } else {
        // Update tagihan
        $stmt = $conn->prepare("UPDATE tagihan SET bulan = ?, id_kmr_penghuni = ?, jml_tagihan = ? WHERE id = ?");
        $stmt->bind_param("siii", $bulan, $id_kmr_penghuni, $jml_tagihan, $id);
        
        if ($stmt->execute()) {
            $success = 'Tagihan berhasil diupdate!';
            // Refresh data tagihan
            $stmt = $conn->prepare("SELECT * FROM tagihan WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $tagihan = $result->fetch_assoc();
        } else {
            $error = 'Gagal mengupdate tagihan!';
        }
    }
}

// Ambil data kamar penghuni untuk dropdown
$kamar_penghuni = $conn->query("
    SELECT kp.id, k.nomor as nomor_kamar, p.nama as nama_penghuni
    FROM kmr_penghuni kp
    JOIN kamar k ON kp.id_kamar = k.id
    JOIN penghuni p ON kp.id_penghuni = p.id
    WHERE kp.tgl_keluar IS NULL
    ORDER BY k.nomor ASC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Tagihan</title>
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
            <a href="pembayaran.php" class="btn btn-outline-light">Pembayaran</a>
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
                        <i class="bi bi-pencil-square me-2"></i>Edit Tagihan
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
                            <label class="form-label">Bulan</label>
                            <input type="month" name="bulan" class="form-control" value="<?= date('Y-m', strtotime($tagihan['bulan'])) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Kamar Penghuni</label>
                            <select name="id_kmr_penghuni" class="form-select" required>
                                <option value="">Pilih Kamar Penghuni</option>
                                <?php if ($kamar_penghuni && $kamar_penghuni->num_rows > 0): ?>
                                    <?php while($row = $kamar_penghuni->fetch_assoc()): ?>
                                        <option value="<?= $row['id'] ?>" <?= $tagihan['id_kmr_penghuni'] == $row['id'] ? 'selected' : '' ?>>
                                            Kamar <?= $row['nomor_kamar'] ?> - <?= $row['nama_penghuni'] ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Jumlah Tagihan</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="jml_tagihan" class="form-control" value="<?= $tagihan['jml_tagihan'] ?>" required min="0">
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="tagihan.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Kembali
                            </a>
                            <button type="submit" name="update" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i>Update Tagihan
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