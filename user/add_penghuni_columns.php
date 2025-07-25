<?php
require_once "koneksi.php";

// Tambah kolom alamat jika belum ada
$result = $conn->query("SHOW COLUMNS FROM penghuni LIKE 'alamat'");
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE penghuni ADD COLUMN alamat TEXT";
    if ($conn->query($sql)) {
        echo "Kolom alamat berhasil ditambahkan!<br>";
    } else {
        echo "Error menambah kolom alamat: " . $conn->error . "<br>";
    }
} else {
    echo "Kolom alamat sudah ada!<br>";
}

// Tambah kolom email jika belum ada
$result = $conn->query("SHOW COLUMNS FROM penghuni LIKE 'email'");
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE penghuni ADD COLUMN email VARCHAR(255)";
    if ($conn->query($sql)) {
        echo "Kolom email berhasil ditambahkan!<br>";
    } else {
        echo "Error menambah kolom email: " . $conn->error . "<br>";
    }
} else {
    echo "Kolom email sudah ada!<br>";
}

echo "Selesai!";
?> 