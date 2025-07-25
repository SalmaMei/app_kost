<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}
require_once "../koneksi.php";

$error = '';
$success = '';

// Hapus pembayaran
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    $stmt = $conn->prepare("DELETE FROM bayar WHERE id = ?");
    if (!$stmt) {
        $error = 'Error prepare delete: ' . $conn->error;
    } else {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = 'Pembayaran berhasil dihapus!';
        } else {
            $error = 'Gagal menghapus pembayaran!';
        }
    }
    header('Location: pembayaran.php');
    exit;
}

// Proses edit pembayaran
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_data = $conn->query("SELECT * FROM bayar WHERE id = $edit_id")->fetch_assoc();
    if (!$edit_data) {
        $error = 'Data pembayaran tidak ditemukan!';
    }
}

if (isset($_POST['update'])) {
    $id = intval($_POST['id']);
    $jml_bayar = intval($_POST['jml_bayar']);
    $status = $_POST['status'] ?? 'cicil';
    if ($jml_bayar <= 0) {
        $error = 'Jumlah bayar harus lebih dari 0!';
    } else {
        $stmt = $conn->prepare("UPDATE bayar SET jml_bayar=?, status=? WHERE id=?");
        $stmt->bind_param("isi", $jml_bayar, $status, $id);
        if ($stmt->execute()) {
            $success = 'Data pembayaran berhasil diupdate!';
            header('Location: pembayaran.php');
            exit;
        } else {
            $error = 'Gagal update pembayaran!';
        }
    }
}

// Proses tambah pembayaran
if (isset($_POST['tambah'])) {
    $id_tagihan = intval($_POST['id_tagihan']);
    $jml_bayar = intval($_POST['jml_bayar']);
    $status = $_POST['status'] ?? 'cicil';
    if ($id_tagihan <= 0 || $jml_bayar <= 0) {
        $error = 'Tagihan dan jumlah bayar wajib diisi!';
    } else {
        $stmt = $conn->prepare("INSERT INTO bayar (id_tagihan, jml_bayar, status) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $id_tagihan, $jml_bayar, $status);
        if ($stmt->execute()) {
            $success = 'Pembayaran berhasil ditambahkan!';
            header('Location: pembayaran.php');
            exit;
        } else {
            $error = 'Gagal menambah pembayaran!';
        }
    }
}
// Ambil data pembayaran
$pembayaran = $conn->query("
    SELECT b.*, t.bulan, t.jml_tagihan, k.nomor as nomor_kamar, p.nama as nama_penghuni,
           (t.jml_tagihan - COALESCE((
               SELECT SUM(b2.jml_bayar) 
               FROM bayar b2 
               WHERE b2.id_tagihan = t.id
           ), 0)) as sisa_tagihan
    FROM bayar b
    JOIN tagihan t ON b.id_tagihan = t.id
    JOIN kmr_penghuni kp ON t.id_kmr_penghuni = kp.id
    JOIN kamar k ON kp.id_kamar = k.id
    JOIN penghuni p ON kp.id_penghuni = p.id
    ORDER BY b.id DESC
");

if (!$pembayaran) {
    $error = 'Error query pembayaran: ' . $conn->error;
}

// Ambil data tagihan untuk dropdown tambah pembayaran
$tagihan_opsi = $conn->query("
    SELECT t.id, t.bulan, k.nomor as nomor_kamar, p.nama as nama_penghuni, t.jml_tagihan
    FROM tagihan t
    JOIN kmr_penghuni kp ON t.id_kmr_penghuni = kp.id
    JOIN kamar k ON kp.id_kamar = k.id
    JOIN penghuni p ON kp.id_penghuni = p.id
    ORDER BY t.bulan DESC, k.nomor ASC
");

// Statistik pembayaran
$total_pembayaran = $conn->query("SELECT SUM(jml_bayar) as total FROM bayar")->fetch_assoc()['total'] ?? 0;
$pembayaran_lunas = $conn->query("SELECT COUNT(*) as total FROM bayar WHERE status = 'lunas'")->fetch_assoc()['total'] ?? 0;
$pembayaran_cicil = $conn->query("SELECT COUNT(*) as total FROM bayar WHERE status = 'cicil'")->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Pembayaran</title>
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
    <h2 class="fw-bold text-center mb-4">Kelola Pembayaran</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <!-- Statistik Pembayaran -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h4>Rp<?= number_format($total_pembayaran) ?></h4>
                    <p class="mb-0">Total Pembayaran</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h4><?= $pembayaran_lunas ?></h4>
                    <p class="mb-0">Pembayaran Lunas</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h4><?= $pembayaran_cicil ?></h4>
                    <p class="mb-0">Pembayaran Cicil</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Edit Pembayaran -->
    <?php if (isset($edit_data)): ?>
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <strong>Edit Pembayaran</strong>
        </div>
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
                <div class="mb-3">
                    <label>Jumlah Bayar</label>
                    <input type="number" name="jml_bayar" class="form-control" value="<?= $edit_data['jml_bayar'] ?>" required>
                </div>
                <div class="mb-3">
                    <label>Status</label>
                    <select name="status" class="form-control" required>
                        <option value="lunas" <?= $edit_data['status']==='lunas'?'selected':'' ?>>Lunas</option>
                        <option value="cicil" <?= $edit_data['status']==='cicil'?'selected':'' ?>>Cicil</option>
                    </select>
                </div>
                <button type="submit" name="update" class="btn btn-success">Update</button>
                <a href="pembayaran.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Form Tambah Pembayaran -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <strong>Tambah Pembayaran</strong>
        </div>
        <div class="card-body">
            <form method="post">
                <div class="mb-3">
                    <label>Tagihan</label>
                    <select name="id_tagihan" class="form-control" required>
                        <option value="">-- Pilih Tagihan --</option>
                        <?php if ($tagihan_opsi && $tagihan_opsi->num_rows > 0): ?>
                            <?php while($t = $tagihan_opsi->fetch_assoc()): ?>
                                <option value="<?= $t['id'] ?>">
                                    <?= date('F Y', strtotime($t['bulan'])) ?> - Kamar <?= htmlspecialchars($t['nomor_kamar']) ?> - <?= htmlspecialchars($t['nama_penghuni']) ?> (Rp<?= number_format($t['jml_tagihan']) ?>)
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Jumlah Bayar</label>
                    <input type="number" name="jml_bayar" class="form-control" required min="1">
                </div>
                <div class="mb-3">
                    <label>Status</label>
                    <select name="status" class="form-control" required>
                        <option value="lunas">Lunas</option>
                        <option value="cicil">Cicil</option>
                    </select>
                </div>
                <button type="submit" name="tambah" class="btn btn-success">Tambah</button>
            </form>
        </div>
    </div>

    <!-- Tabel Pembayaran -->
    <div class="card shadow">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">
                <i class="bi bi-credit-card me-2"></i>Daftar Pembayaran
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Kamar</th>
                            <th>Penghuni</th>
                            <th>Bulan Tagihan</th>
                            <th>Total Tagihan</th>
                            <th>Jumlah Bayar</th>
                            <th>Sisa Tagihan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($pembayaran && $pembayaran->num_rows > 0): ?>
                            <?php $no = 1; while($row = $pembayaran->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($row['id'])) ?></td>
                                    <td><strong><?= htmlspecialchars($row['nomor_kamar']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['nama_penghuni']) ?></td>
                                    <td><?= date('F Y', strtotime($row['bulan'])) ?></td>
                                    <td>Rp<?= number_format($row['jml_tagihan']) ?></td>
                                    <td><strong>Rp<?= number_format($row['jml_bayar']) ?></strong></td>
                                    <td>
                                        <?php if ($row['sisa_tagihan'] > 0): ?>
                                            <span class="badge bg-danger">Rp<?= number_format($row['sisa_tagihan']) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Lunas</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['status'] == 'lunas'): ?>
                                            <span class="badge bg-success">Lunas</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Cicil</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="pembayaran.php?edit=<?= $row['id'] ?>" class="btn btn-primary btn-sm">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a href="pembayaran.php?hapus=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus pembayaran ini?')">
                                            <i class="bi bi-trash"></i> Hapus
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    <i class="bi bi-credit-card h3 d-block mb-2"></i>
                                    Tidak ada data pembayaran
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
body {
    background: linear-gradient(135deg,rgb(242, 242, 245) 0%,rgb(102, 189, 240) 100%);
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