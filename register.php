<?php
session_start();
require_once "koneksi.php";

// Ambil kamar kosong
$kamar = $conn->query("SELECT id, nomor FROM kamar WHERE id NOT IN (SELECT id_kamar FROM user WHERE id_kamar IS NOT NULL)");

$success = '';
$error = '';
if (isset($_POST['register'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $nama = trim($_POST['nama'] ?? '');
    $id_kamar = $_POST['id_kamar'] ?? '';
    if ($username && $password && $nama && $id_kamar) {
        // Cek username sudah ada
        $cek = $conn->prepare("SELECT id FROM user WHERE username = ?");
        $cek->bind_param("s", $username);
        $cek->execute();
        $cek->store_result();
        if ($cek->num_rows > 0) {
            $error = 'Username sudah terdaftar!';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO user (username, password, nama, id_kamar, role) VALUES (?, ?, ?, ?, 'user')");
            $stmt->bind_param("sssi", $username, $hash, $nama, $id_kamar);
            if ($stmt->execute()) {
                $success = 'Registrasi berhasil! Silakan login.';
                header('Location: index.php?register=success');
                exit;
            } else {
                $error = 'Registrasi gagal, coba lagi.';
            }
        }
    } else {
        $error = 'Semua field wajib diisi!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Register Akun Kost</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg border-0 p-4">
                <h3 class="mb-4 text-center">Registrasi Akun Kost</h3>
                <?php if ($error): ?>
                    <div class="alert alert-danger text-center small mb-3"><?= $error ?></div>
                <?php endif; ?>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required autocomplete="username">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required autocomplete="new-password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pilih Kamar Kosong</label>
                        <select name="id_kamar" class="form-select" required>
                            <option value="">-- Pilih Kamar --</option>
                            <?php while($row = $kamar->fetch_assoc()): ?>
                                <option value="<?= $row['id'] ?>">Kamar <?= $row['nomor'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" name="register" class="btn btn-success w-100">Register</button>
                </form>
                <div class="text-center mt-3">
                    <span class="small">Sudah punya akun? <a href="index.php">Login</a></span>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
