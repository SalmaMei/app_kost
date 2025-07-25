<?php
require_once "koneksi.php";

// Update password admin yang ada
$username = 'admin@gmail.com';
$new_password = 'admin123';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE user SET password = ? WHERE username = ?");
$stmt->bind_param("ss", $hashed_password, $username);

if ($stmt->execute()) {
    echo "Password berhasil diperbaiki!<br>";
    echo "Username: " . $username . "<br>";
    echo "Password baru: " . $new_password . "<br>";
    echo "Hashed password: " . $hashed_password . "<br>";
} else {
    echo "Error: " . $stmt->error;
}

// Tambah user test juga
$test_username = 'test';
$test_password = 'test123';
$test_hashed = password_hash($test_password, PASSWORD_DEFAULT);
$test_role = 'admin';

// Hapus user test jika sudah ada
$conn->query("DELETE FROM user WHERE username = 'test'");

$stmt2 = $conn->prepare("INSERT INTO user (username, password, nama, role) VALUES (?, ?, ?, ?)");
$stmt2->bind_param("ssss", $test_username, $test_hashed, $test_username, $test_role);

if ($stmt2->execute()) {
    echo "<br>User test berhasil dibuat!<br>";
    echo "Username: " . $test_username . "<br>";
    echo "Password: " . $test_password . "<br>";
} else {
    echo "Error: " . $stmt2->error;
}
?> 