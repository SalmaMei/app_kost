<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

require_once "../koneksi.php";

$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);

// Proses tambah penghuni
if (isset($_POST['tambah'])) {
    $nama = trim($_POST['nama'] ?? '');
    $no_ktp = trim($_POST['no_ktp'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $tgl_masuk = $_POST['tgl_masuk'] ?? date('Y-m-d');

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
            $stmt = $conn->prepare("INSERT INTO penghuni (nama, no_ktp, no_hp, tgl_masuk) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nama, $no_ktp, $no_hp, $tgl_masuk);
            if ($stmt->execute()) {
                $success = 'Data penghuni berhasil ditambahkan!';
            } else {
                $error = 'Gagal menambahkan data penghuni!';
            }
        }
    }
}

// Proses hapus penghuni
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    $stmt = $conn->prepare("DELETE FROM penghuni WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Data penghuni berhasil dihapus!';
    } else {
        $_SESSION['error'] = 'Gagal menghapus data penghuni!';
    }
    header('Location: penghuni.php');
    exit;
}

// Ambil data penghuni
$penghuni = $conn->query("SELECT * FROM penghuni ORDER BY nama ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Penghuni</title>
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
    <div class="row">
        <div class="col-md-4">
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
                            <label class="form-label">Tanggal Masuk</label>
                            <input type="date" name="tgl_masuk" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <button type="submit" name="tambah" class="btn btn-primary w-100">
                            <i class="bi bi-plus-circle me-1"></i>Tambah Penghuni
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-people me-2"></i>Daftar Penghuni
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>No KTP</th>
                                    <th>No HP</th>
                                    <th>Tanggal Masuk</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($penghuni->num_rows > 0): ?>
                                    <?php $no = 1; while($row = $penghuni->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><strong><?= htmlspecialchars($row['nama']) ?></strong></td>
                                            <td><?= htmlspecialchars($row['no_ktp']) ?></td>
                                            <td><?= htmlspecialchars($row['no_hp']) ?></td>
                                            <td><?= date('d-m-Y', strtotime($row['tgl_masuk'])) ?></td>
                                            <td>
                                                <?php if ($row['tgl_keluar']): ?>
                                                    <span class="badge bg-secondary">Keluar</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Aktif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="edit_penghuni.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                                <a href="penghuni.php?hapus=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus penghuni ini?')">
                                                    <i class="bi bi-trash"></i> Hapus
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">Tidak ada data penghuni</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
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

