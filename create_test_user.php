<?php
require_once "koneksi.php";

// Hapus user test jika sudah ada
$conn->query("DELETE FROM user WHERE username = 'test'");

// Buat user test baru
$username = 'test';
$password = 'test123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$role = 'admin';

$stmt = $conn->prepare("INSERT INTO user (username, password, role) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $username, $hashed_password, $role);

if ($stmt->execute()) {
    echo "User test berhasil dibuat!<br>";
    echo "Username: test<br>";
    echo "Password: test123<br>";
    echo "Hashed password: " . $hashed_password . "<br>";
} else {
    echo "Error: " . $stmt->error;
}
?> 