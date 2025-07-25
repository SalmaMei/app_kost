<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}
require_once "../koneksi.php";

$error = '';
$success = '';

// Cek apakah tabel tagihan sudah ada
$check_tagihan = $conn->query("SHOW TABLES LIKE 'tagihan'");

if ($check_tagihan->num_rows == 0) {
    $error = 'Tabel tagihan belum ada. Silakan jalankan add_tagihan_tables.php terlebih dahulu.';
} else {
    // Generate tagihan bulanan
    if (isset($_POST['generate'])) {
        $bulan = $_POST['bulan'] ?? date('Y-m-01');
        
        // Ambil semua penghuni yang masih aktif (tgl_keluar IS NULL)
        $stmt = $conn->prepare("
            SELECT kp.id, kp.id_kamar, kp.id_penghuni, k.harga, p.nama, k.nomor
            FROM kmr_penghuni kp
            JOIN kamar k ON kp.id_kamar = k.id
            JOIN penghuni p ON kp.id_penghuni = p.id
            WHERE kp.tgl_keluar IS NULL
        ");
        
        if (!$stmt) {
            $error = 'Error prepare statement: ' . $conn->error;
        } else {
            $stmt->execute();
            $result = $stmt->get_result();
            
            $generated = 0;
            while ($row = $result->fetch_assoc()) {
                // Cek apakah tagihan untuk bulan ini sudah ada
                $check_stmt = $conn->prepare("
                    SELECT id FROM tagihan 
                    WHERE id_kmr_penghuni = ? AND bulan = ?
                ");
                
                if (!$check_stmt) {
                    $error = 'Error check statement: ' . $conn->error;
                    break;
                }
                
                $check_stmt->bind_param("is", $row['id'], $bulan);
                $check_stmt->execute();
                
                if ($check_stmt->get_result()->num_rows == 0) {
                    // Buat tagihan baru
                    $insert_stmt = $conn->prepare("
                        INSERT INTO tagihan (id_kmr_penghuni, bulan, jml_tagihan) 
                        VALUES (?, ?, ?)
                    ");
                    
                    if (!$insert_stmt) {
                        $error = 'Error insert statement: ' . $conn->error;
                        break;
                    }
                    
                    $insert_stmt->bind_param("isi", $row['id'], $bulan, $row['harga']);
                    if ($insert_stmt->execute()) {
                        $generated++;
                    }
                }
            }
            
            if ($generated > 0) {
                $success = "Berhasil generate $generated tagihan untuk bulan " . date('F Y', strtotime($bulan));
            } else {
                $error = "Tidak ada tagihan baru yang perlu dibuat untuk bulan " . date('F Y', strtotime($bulan));
            }
        }
    }

    // Hapus tagihan
    if (isset($_GET['hapus'])) {
        $id = intval($_GET['hapus']);
        $stmt = $conn->prepare("DELETE FROM tagihan WHERE id = ?");
        if (!$stmt) {
            $error = 'Error prepare delete: ' . $conn->error;
        } else {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $success = 'Tagihan berhasil dihapus!';
            } else {
                $error = 'Gagal menghapus tagihan!';
            }
        }
        header('Location: tagihan.php');
        exit;
    }

    // Ambil data tagihan
    $tagihan = $conn->query("
        SELECT t.*, k.nomor as nomor_kamar, p.nama as nama_penghuni, k.harga
        FROM tagihan t
        JOIN kmr_penghuni kp ON t.id_kmr_penghuni = kp.id
        JOIN kamar k ON kp.id_kamar = k.id
        JOIN penghuni p ON kp.id_penghuni = p.id
        ORDER BY t.bulan DESC, k.nomor ASC
    ");
    
    if (!$tagihan) {
        $error = 'Error query tagihan: ' . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Tagihan</title>
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
    <h2 class="fw-bold text-center mb-4">Kelola Tagihan</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= $error ?>
            <?php if (strpos($error, 'belum ada') !== false): ?>
                <br><a href="../add_tagihan_tables.php" class="btn btn-primary mt-2">Setup Database Tagihan</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <?php if (empty($error) || strpos($error, 'belum ada') === false): ?>
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-calendar-plus me-2"></i>Generate Tagihan
                    </h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Bulan</label>
                            <input type="month" name="bulan" class="form-control" value="<?= date('Y-m') ?>" required>
                        </div>
                        <button type="submit" name="generate" class="btn btn-primary w-100">
                            <i class="bi bi-plus-circle me-1"></i>Generate Tagihan
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-receipt me-2"></i>Daftar Tagihan
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Kamar</th>
                                    <th>Penghuni</th>
                                    <th>Bulan</th>
                                    <th>Tagihan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($tagihan && $tagihan->num_rows > 0): ?>
                                    <?php $no = 1; while($row = $tagihan->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><strong><?= htmlspecialchars($row['nomor_kamar']) ?></strong></td>
                                            <td><?= htmlspecialchars($row['nama_penghuni']) ?></td>
                                            <td><?= date('F Y', strtotime($row['bulan'])) ?></td>
                                            <td><strong>Rp<?= number_format($row['jml_tagihan']) ?></strong></td>
                                            <td>
                                                <a href="edit_tagihan.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                                <a href="tagihan.php?hapus=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus tagihan ini?')">
                                                    <i class="bi bi-trash"></i> Hapus
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="bi bi-receipt h3 d-block mb-2"></i>
                                            Tidak ada data tagihan
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
    <?php endif; ?>
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