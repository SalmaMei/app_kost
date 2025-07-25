<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header('Location: ../index.php');
    exit;
}

require_once "../koneksi.php";

$user_id = $_SESSION['user_id'] ?? 0;
$error = '';
$success = '';

// Proses pembayaran
if (isset($_POST['bayar'])) {
    $id_tagihan = intval($_POST['id_tagihan']);
    $jml_bayar = intval($_POST['jml_bayar']);
    $status = $_POST['status'] ?? 'cicil';
    
    if ($jml_bayar <= 0) {
        $error = 'Jumlah pembayaran harus lebih dari 0!';
    } else {
        // Cek apakah tagihan milik user ini
        $check_stmt = $conn->prepare("
            SELECT t.*, kp.id_penghuni 
            FROM tagihan t 
            JOIN kmr_penghuni kp ON t.id_kmr_penghuni = kp.id 
            WHERE t.id = ? AND kp.id_penghuni = ?
        ");
        $check_stmt->bind_param("ii", $id_tagihan, $user_id);
        $check_stmt->execute();
        $tagihan_data = $check_stmt->get_result()->fetch_assoc();
        
        if (!$tagihan_data) {
            $error = 'Tagihan tidak ditemukan atau bukan milik Anda!';
        } else {
            // Insert pembayaran
            $stmt = $conn->prepare("INSERT INTO bayar (id_tagihan, jml_bayar, status) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $id_tagihan, $jml_bayar, $status);
            
            if ($stmt->execute()) {
                $success = 'Pembayaran berhasil dicatat!';
            } else {
                $error = 'Gagal mencatat pembayaran!';
            }
        }
    }
}

// Ambil data user
$user_stmt = $conn->prepare("SELECT * FROM user WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();

// Ambil data kamar yang dihuni user
$kamar_user = $conn->query("
    SELECT kp.*, k.nomor, k.harga, k.gambar
    FROM kmr_penghuni kp
    JOIN kamar k ON kp.id_kamar = k.id
    WHERE kp.id_penghuni = $user_id AND kp.tgl_keluar IS NULL
");

// Ambil tagihan user
$tagihan_user = $conn->query("
    SELECT t.*, k.nomor as nomor_kamar, k.harga as harga_kamar,
           COALESCE(SUM(b.jml_bayar), 0) as total_bayar,
           (t.jml_tagihan - COALESCE(SUM(b.jml_bayar), 0)) as sisa_tagihan
    FROM tagihan t
    JOIN kmr_penghuni kp ON t.id_kmr_penghuni = kp.id
    JOIN kamar k ON kp.id_kamar = k.id
    LEFT JOIN bayar b ON t.id = b.id_tagihan
    WHERE kp.id_penghuni = $user_id
    GROUP BY t.id
    ORDER BY t.bulan DESC
");

// Ambil katalog kamar kosong
$kamar_kosong = $conn->query("
    SELECT k.*, 
           CASE WHEN kp.id IS NOT NULL THEN 'Terisi' ELSE 'Kosong' END as status
    FROM kamar k
    LEFT JOIN kmr_penghuni kp ON k.id = kp.id_kamar AND kp.tgl_keluar IS NULL
    WHERE kp.id IS NULL
    ORDER BY k.nomor ASC
");

// Ambil riwayat pembayaran user
$riwayat_bayar = $conn->query("
    SELECT b.*, t.bulan, t.jml_tagihan, k.nomor as nomor_kamar
    FROM bayar b
    JOIN tagihan t ON b.id_tagihan = t.id
    JOIN kmr_penghuni kp ON t.id_kmr_penghuni = kp.id
    JOIN kamar k ON kp.id_kamar = k.id
    WHERE kp.id_penghuni = $user_id
    ORDER BY b.id DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="#">
            <i class="bi bi-house-door me-2"></i>Kost User
        </a>
        <div>
            <span class="text-white me-3">Selamat datang, <?= htmlspecialchars($user_data['nama'] ?? 'User') ?></span>
            <a href="../logout.php" class="btn btn-outline-light">Logout</a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <!-- Info Kamar User -->
    <?php if ($kamar_user && $kamar_user->num_rows > 0): ?>
        <?php $kamar = $kamar_user->fetch_assoc(); ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-house me-2"></i>Kamar Anda
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <?php if ($kamar['gambar']): ?>
                                    <img src="../uploads/<?= $kamar['gambar'] ?>" class="img-fluid rounded" alt="Kamar">
                                <?php else: ?>
                                    <div class="bg-light rounded p-4 text-center">
                                        <i class="bi bi-house h1 text-muted"></i>
                                        <p class="text-muted">Tidak ada gambar</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-9">
                                <h4>Kamar <?= $kamar['nomor'] ?></h4>
                                <p class="text-muted">Harga: <strong>Rp<?= number_format($kamar['harga']) ?>/bulan</strong></p>
                                <p class="text-muted">Tanggal Masuk: <?= date('d F Y', strtotime($kamar['tgl_masuk'])) ?></p>
                                <span class="badge bg-success">Aktif</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Tagihan User -->
        <div class="col-md-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="bi bi-receipt me-2"></i>Tagihan Anda
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($tagihan_user && $tagihan_user->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Bulan</th>
                                        <th>Tagihan</th>
                                        <th>Bayar</th>
                                        <th>Sisa</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($tagihan = $tagihan_user->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= date('M Y', strtotime($tagihan['bulan'])) ?></td>
                                            <td>Rp<?= number_format($tagihan['jml_tagihan']) ?></td>
                                            <td>Rp<?= number_format($tagihan['total_bayar']) ?></td>
                                            <td>
                                                <?php if ($tagihan['sisa_tagihan'] > 0): ?>
                                                    <span class="badge bg-danger">Rp<?= number_format($tagihan['sisa_tagihan']) ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Lunas</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($tagihan['sisa_tagihan'] > 0): ?>
                                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalBayar" 
                                                            data-id="<?= $tagihan['id'] ?>" 
                                                            data-sisa="<?= $tagihan['sisa_tagihan'] ?>"
                                                            data-bulan="<?= date('M Y', strtotime($tagihan['bulan'])) ?>">
                                                        <i class="bi bi-credit-card"></i> Bayar
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center">Tidak ada tagihan</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Riwayat Pembayaran -->
        <div class="col-md-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history me-2"></i>Riwayat Pembayaran
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($riwayat_bayar && $riwayat_bayar->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Bulan</th>
                                        <th>Bayar</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($bayar = $riwayat_bayar->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= date('d/m/Y', strtotime($bayar['id'])) ?></td>
                                            <td><?= date('M Y', strtotime($bayar['bulan'])) ?></td>
                                            <td>Rp<?= number_format($bayar['jml_bayar']) ?></td>
                                            <td>
                                                <?php if ($bayar['status'] == 'lunas'): ?>
                                                    <span class="badge bg-success">Lunas</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Cicil</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center">Belum ada riwayat pembayaran</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Katalog Kamar -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-grid me-2"></i>Katalog Kamar Tersedia
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($kamar_kosong && $kamar_kosong->num_rows > 0): ?>
                        <div class="row">
                            <?php while($kamar = $kamar_kosong->fetch_assoc()): ?>
                                <div class="col-md-3 mb-3">
                                    <div class="card h-100">
                                        <?php if ($kamar['gambar']): ?>
                                            <img src="../uploads/<?= $kamar['gambar'] ?>" class="card-img-top" alt="Kamar <?= $kamar['nomor'] ?>">
                                        <?php else: ?>
                                            <div class="card-img-top bg-light p-4 text-center">
                                                <i class="bi bi-house h1 text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="card-body">
                                            <h6 class="card-title">Kamar <?= $kamar['nomor'] ?></h6>
                                            <p class="card-text">
                                                <strong>Rp<?= number_format($kamar['harga']) ?>/bulan</strong>
                                            </p>
                                            <span class="badge bg-success"><?= $kamar['status'] ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center">Tidak ada kamar yang tersedia saat ini</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Pembayaran -->
<div class="modal fade" id="modalBayar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bayar Tagihan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="id_tagihan" id="id_tagihan">
                    <div class="mb-3">
                        <label class="form-label">Bulan Tagihan</label>
                        <input type="text" class="form-control" id="bulan_tagihan" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sisa Tagihan</label>
                        <input type="text" class="form-control" id="sisa_tagihan" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jumlah Bayar</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="jml_bayar" class="form-control" required min="1">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status Pembayaran</label>
                        <select name="status" class="form-select">
                            <option value="cicil">Cicil</option>
                            <option value="lunas">Lunas</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="bayar" class="btn btn-primary">Bayar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Script untuk modal pembayaran
document.addEventListener('DOMContentLoaded', function() {
    const modalBayar = document.getElementById('modalBayar');
    if (modalBayar) {
        modalBayar.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const sisa = button.getAttribute('data-sisa');
            const bulan = button.getAttribute('data-bulan');
            
            document.getElementById('id_tagihan').value = id;
            document.getElementById('sisa_tagihan').value = 'Rp' + parseInt(sisa).toLocaleString();
            document.getElementById('bulan_tagihan').value = bulan;
        });
    }
});
</script>

<style>
body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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