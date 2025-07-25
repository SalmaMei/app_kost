<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}
require_once "../koneksi.php";

$error = '';
if (isset($_POST['simpan'])) {
    $nomor = trim($_POST['nomor'] ?? '');
    $harga = intval($_POST['harga'] ?? 0);
    $gambar = null;
    if ($nomor && $harga) {
        // Upload gambar jika ada
        if (!empty($_FILES['gambar']['name'])) {
            $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp'];
            if (in_array($ext, $allowed)) {
                $nama_file = uniqid('kamar_').'.'.$ext;
                $target = '../uploads/kamar/'.$nama_file;
                if (!is_dir('../uploads/kamar')) mkdir('../uploads/kamar', 0777, true);
                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $target)) {
                    $gambar = $nama_file;
                } else {
                    $error = 'Upload gambar gagal!';
                }
            } else {
                $error = 'Format gambar harus jpg, jpeg, png, atau webp!';
            }
        }
        if (!$error) {
            $stmt = $conn->prepare("INSERT INTO kamar (nomor, harga, gambar) VALUES (?, ?, ?)");
            $stmt->bind_param("sis", $nomor, $harga, $gambar);
            if ($stmt->execute()) {
                header('Location: kamar.php');
                exit;
            } else {
                $error = 'Gagal simpan kamar!';
            }
        }
    } else {
        $error = 'Nomor kamar dan harga wajib diisi!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Kamar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg border-0 p-4">
                <h3 class="mb-4 text-center">Tambah Kamar</h3>
                <?php if ($error): ?>
                    <div class="alert alert-danger text-center small mb-3"><?= $error ?></div>
                <?php endif; ?>
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Nomor Kamar</label>
                        <input type="text" name="nomor" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Harga</label>
                        <input type="number" name="harga" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gambar Kamar</label>
                        <input type="file" name="gambar" class="form-control" accept="image/*">
                    </div>
                    <button type="submit" name="simpan" class="btn btn-success w-100">Simpan</button>
                    <a href="kamar.php" class="btn btn-link w-100 mt-2">Kembali</a>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html> 