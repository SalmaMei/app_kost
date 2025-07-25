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

// Ambil data kamar yang akan diedit
if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM kamar WHERE id = ?");
    if (!$stmt) {
        die("Error prepare statement: " . $conn->error);
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $kamar = $result->fetch_assoc();
    
    if (!$kamar) {
        header('Location: kamar.php');
        exit;
    }
} else {
    header('Location: kamar.php');
    exit;
}

// Proses update kamar
if (isset($_POST['update'])) {
    $nomor = trim($_POST['nomor'] ?? '');
    $harga = intval($_POST['harga'] ?? 0);
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $status = $_POST['status'] ?? 'tersedia';
    
    // Validasi input
    if (empty($nomor)) {
        $error = 'Nomor kamar harus diisi!';
    } elseif ($harga <= 0) {
        $error = 'Harga harus lebih dari 0!';
    } else {
        // Cek apakah nomor kamar sudah ada (kecuali kamar yang sedang diedit)
        $stmt = $conn->prepare("SELECT id FROM kamar WHERE nomor = ? AND id != ?");
        if (!$stmt) {
            $error = "Error prepare statement: " . $conn->error;
        } else {
            $stmt->bind_param("si", $nomor, $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'Nomor kamar sudah ada!';
            } else {
                // Cek apakah kolom status dan deskripsi ada
                $check_columns = $conn->query("SHOW COLUMNS FROM kamar LIKE 'status'");
                $has_status = $check_columns->num_rows > 0;
                
                $check_columns = $conn->query("SHOW COLUMNS FROM kamar LIKE 'deskripsi'");
                $has_deskripsi = $check_columns->num_rows > 0;
                
                if ($has_status && $has_deskripsi) {
                    // Update dengan semua kolom
                    $stmt = $conn->prepare("UPDATE kamar SET nomor = ?, harga = ?, deskripsi = ?, status = ? WHERE id = ?");
                } elseif ($has_status) {
                    // Update tanpa deskripsi
                    $stmt = $conn->prepare("UPDATE kamar SET nomor = ?, harga = ?, status = ? WHERE id = ?");
                } elseif ($has_deskripsi) {
                    // Update tanpa status
                    $stmt = $conn->prepare("UPDATE kamar SET nomor = ?, harga = ?, deskripsi = ? WHERE id = ?");
                } else {
                    // Update hanya nomor dan harga
                    $stmt = $conn->prepare("UPDATE kamar SET nomor = ?, harga = ? WHERE id = ?");
                }
                
                if (!$stmt) {
                    $error = "Error prepare update: " . $conn->error;
                } else {
                    if ($has_status && $has_deskripsi) {
                        $stmt->bind_param("sissi", $nomor, $harga, $deskripsi, $status, $id);
                    } elseif ($has_status) {
                        $stmt->bind_param("sisi", $nomor, $harga, $status, $id);
                    } elseif ($has_deskripsi) {
                        $stmt->bind_param("sisi", $nomor, $harga, $deskripsi, $id);
                    } else {
                        $stmt->bind_param("sii", $nomor, $harga, $id);
                    }
                    
                    if ($stmt->execute()) {
                        $success = 'Kamar berhasil diupdate!';
                        // Refresh data kamar
                        $stmt = $conn->prepare("SELECT * FROM kamar WHERE id = ?");
                        $stmt->bind_param("i", $id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $kamar = $result->fetch_assoc();
                    } else {
                        $error = 'Gagal mengupdate kamar: ' . $stmt->error;
                    }
                }
            }
        }
    }
}

// Pastikan nilai default untuk status dan deskripsi
$kamar['status'] = $kamar['status'] ?? 'tersedia';
$kamar['deskripsi'] = $kamar['deskripsi'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Kamar</title>
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
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-pencil-square me-2"></i>Edit Kamar
                    </h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    
                    <form method="post" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nomor Kamar</label>
                                    <input type="text" name="nomor" class="form-control" value="<?= htmlspecialchars($kamar['nomor']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Harga per Bulan</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="harga" class="form-control" value="<?= $kamar['harga'] ?>" required min="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="tersedia" <?= $kamar['status'] === 'tersedia' ? 'selected' : '' ?>>Tersedia</option>
                                <option value="tidak_tersedia" <?= $kamar['status'] === 'tidak_tersedia' ? 'selected' : '' ?>>Tidak Tersedia</option>
                                <option value="maintenance" <?= $kamar['status'] === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="4" placeholder="Deskripsi kamar (opsional)"><?= htmlspecialchars($kamar['deskripsi']) ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="gambar" class="form-label">Gambar Kamar</label>
                            <input type="file" class="form-control" id="gambar" name="gambar">
                            <?php if (!empty($kamar['gambar'])): ?>
                                <img src="../uploads/kamar/<?= htmlspecialchars($kamar['gambar']) ?>" alt="Gambar Kamar" style="width:100px; margin-top:10px;">
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="kamar.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Kembali
                            </a>
                            <button type="submit" name="update" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i>Update Kamar
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
    background: linear-gradient(135deg, #e0e7ff 0%,rgb(98, 165, 231) 100%);
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