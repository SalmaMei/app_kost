<?php
require_once "koneksi.php";

// Tambah kolom status jika belum ada
$result = $conn->query("SHOW COLUMNS FROM kamar LIKE 'status'");
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE kamar ADD COLUMN status ENUM('tersedia', 'tidak_tersedia', 'maintenance') DEFAULT 'tersedia'";
    if ($conn->query($sql)) {
        echo "Kolom status berhasil ditambahkan!<br>";
    } else {
        echo "Error menambah kolom: " . $conn->error . "<br>";
    }
} else {
    echo "Kolom status sudah ada!<br>";
}

// Tambah kolom deskripsi jika belum ada
$result = $conn->query("SHOW COLUMNS FROM kamar LIKE 'deskripsi'");
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE kamar ADD COLUMN deskripsi TEXT";
    if ($conn->query($sql)) {
        echo "Kolom deskripsi berhasil ditambahkan!<br>";
    } else {
        echo "Error menambah kolom: " . $conn->error . "<br>";
    }
} else {
    echo "Kolom deskripsi sudah ada!<br>";
}

// Update semua kamar yang belum punya status
$conn->query("UPDATE kamar SET status = 'tersedia' WHERE status IS NULL");

echo "Selesai!";
?> 