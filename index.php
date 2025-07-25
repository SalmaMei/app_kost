<?php
session_start();

if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
        exit;
    } elseif ($_SESSION['role'] === 'user') {
        header('Location: user/dashboard.php');
        exit;
    }
}

// Proses login sebelum query database
$error = '';
if (isset($_POST['login'])) {
    require_once "koneksi.php";
    $user = trim($_POST['username'] ?? '');
    $pass = trim($_POST['password'] ?? '');
    
    // Cek admin hardcode
    if ($user === 'admin' && $pass === 'admin') {
        $_SESSION['role'] = 'admin';
        $_SESSION['username'] = 'admin';
        header('Location: admin/dashboard.php');
        exit;
    } else {
        // Cek user di database
        $stmt = $conn->prepare("SELECT id, username, password, role FROM user WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if (password_verify($pass, $row['password'])) {
                $_SESSION['role'] = $row['role'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['user_id'] = $row['id'];
                if ($row['role'] === 'admin') {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: user/dashboard.php');
                }
                exit;
            } else {
                $error = 'Username atau password salah!';
            }
        } else {
            $error = 'Username atau password salah!';
        }
    }
}

require_once "koneksi.php";

// Kamar kosong (tidak sedang ditempati)
$kamar_kosong = $conn->query("
    SELECT nomor, harga FROM kamar
    WHERE id NOT IN (
        SELECT id_kamar FROM kmr_penghuni WHERE tgl_keluar IS NULL
    )
");

// Kamar yang sebentar lagi harus bayar (misal, 5 hari sebelum akhir bulan, berdasarkan tgl_masuk tb_penghuni)
$tagihan_segera = $conn->query("
    SELECT k.nomor, p.nama, kp.tgl_masuk, t.bulan, t.jml_tagihan
    FROM tagihan t
    JOIN kmr_penghuni kp ON t.id_kmr_penghuni = kp.id
    JOIN kamar k ON kp.id_kamar = k.id
    JOIN penghuni p ON kp.id_penghuni = p.id
    WHERE t.bulan = DATE_FORMAT(CURDATE(), '%Y-%m-01')
      AND t.id NOT IN (SELECT id_tagihan FROM bayar WHERE status='lunas')
      AND DAY(CURDATE()) >= 25
");

// Kamar terlambat bayar (tagihan bulan lalu belum lunas)
$tagihan_telat = $conn->query("
    SELECT k.nomor, p.nama, t.bulan, t.jml_tagihan
    FROM tagihan t
    JOIN kmr_penghuni kp ON t.id_kmr_penghuni = kp.id
    JOIN kamar k ON kp.id_kamar = k.id
    JOIN penghuni p ON kp.id_penghuni = p.id
    WHERE t.bulan = DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-01')
      AND t.id NOT IN (SELECT id_tagihan FROM bayar WHERE status='lunas')
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Info Kost</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<div class="bg-gradient-primary min-vh-100 d-flex flex-column">
    <div class="container py-4 flex-grow-1">
        <h1 class="mb-5 text-center fw-bold display-4" style="letter-spacing:2px;">
            <i class="bi bi-house-door-fill me-2 text-primary"></i>Info Kost
        </h1>
        <div class="row mb-5 g-4 justify-content-center">
            <div class="col-md-4">
                <div class="card shadow-lg border-0 h-100">
                    <div class="card-body">
                        <h4 class="card-title mb-3 text-success"><i class="bi bi-door-open-fill me-2"></i>Kamar Kosong</h4>
                        <ul class="list-group list-group-flush">
                            <?php if ($kamar_kosong->num_rows > 0): ?>
                                <?php while($row = $kamar_kosong->fetch_assoc()): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><strong>Nomor:</strong> <?= $row['nomor'] ?></span>
                                        <span class="badge bg-success">Rp<?= number_format($row['harga']) ?></span>
                                    </li>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <li class="list-group-item text-muted text-center">Tidak ada kamar kosong</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-lg border-0 h-100">
                    <div class="card-body">
                        <h4 class="card-title mb-3 text-warning"><i class="bi bi-cash-coin me-2"></i>Kamar Harus Bayar (Sebentar Lagi)</h4>
                        <ul class="list-group list-group-flush">
                            <?php if ($tagihan_segera->num_rows > 0): ?>
                                <?php while($row = $tagihan_segera->fetch_assoc()): ?>
                                    <li class="list-group-item">
                                        <div><strong>Nomor:</strong> <?= $row['nomor'] ?></div>
                                        <div><strong>Nama:</strong> <?= $row['nama'] ?></div>
                                        <div><strong>Masuk:</strong> <?= date('d-m-Y', strtotime($row['tgl_masuk'])) ?></div>
                                        <div><strong>Tagihan:</strong> <span class="badge bg-warning text-dark">Rp<?= number_format($row['jml_tagihan']) ?></span></div>
                                    </li>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <li class="list-group-item text-muted text-center">Tidak ada kamar yang harus bayar dalam waktu dekat</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-lg border-0 h-100">
                    <div class="card-body">
                        <h4 class="card-title mb-3 text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Kamar Terlambat Bayar</h4>
                        <ul class="list-group list-group-flush">
                            <?php if ($tagihan_telat->num_rows > 0): ?>
                                <?php while($row = $tagihan_telat->fetch_assoc()): ?>
                                    <li class="list-group-item list-group-item-danger">
                                        <div><strong>Nomor:</strong> <?= $row['nomor'] ?></div>
                                        <div><strong>Nama:</strong> <?= $row['nama'] ?></div>
                                        <div><strong>Tagihan:</strong> <span class="badge bg-danger">Rp<?= number_format($row['jml_tagihan']) ?></span></div>
                                    </li>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <li class="list-group-item text-muted text-center">Tidak ada kamar yang terlambat bayar</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="row justify-content-center align-items-center" style="min-height: 40vh;">
            <div class="col-md-5 col-lg-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger text-center small mb-3"><?= $error ?></div>
                <?php endif; ?>
                <div class="card shadow-lg border-0 p-4">
                    <form method="post" action="index.php">
                        <h4 class="mb-3 text-center">Login </h4>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required autofocus autocomplete="username">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required autocomplete="current-password">
                        </div>
                        <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
                    </form>
                    <div class="text-center mt-3">
                        <span class="small">Belum punya akun? <a href="register.php">Register</a></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <footer class="text-center py-3 mt-auto text-muted small">
        &copy; <?= date('Y') ?> KostApp. All rights reserved.
    </footer>
</div>
<style>
body, html {
    height: 100%;
}
.bg-gradient-primary {
    background: linear-gradient(135deg, #e0e7ff 0%,rgb(99, 162, 226) 100%);
}
.card {
    border-radius: 1rem;
}
.card-title i {
    font-size: 1.5rem;
    vertical-align: middle;
}
</style>
</body>
</html>