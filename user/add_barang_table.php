<?php
require_once "koneksi.php";

// Buat tabel barang jika belum ada (struktur sederhana)
$sql = "CREATE TABLE IF NOT EXISTS barang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(255) NOT NULL,
    harga INT NOT NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Tabel barang berhasil dibuat atau sudah ada!<br>";
} else {
    echo "Error membuat tabel: " . $conn->error . "<br>";
}

// Cek apakah ada data sample
$result = $conn->query("SELECT COUNT(*) as total FROM barang");
$row = $result->fetch_assoc();

if ($row['total'] == 0) {
    // Tambah data sample
    $sample_data = [
        ['Kipas Angin', 150000],
        ['Kasur', 500000],
        ['Lemari', 800000],
        ['Lampu LED', 75000],
        ['Kursi', 200000]
    ];
    
    $stmt = $conn->prepare("INSERT INTO barang (nama, harga) VALUES (?, ?)");
    
    foreach ($sample_data as $data) {
        $stmt->bind_param("si", $data[0], $data[1]);
        if ($stmt->execute()) {
            echo "Data sample berhasil ditambahkan: " . $data[0] . " - Rp" . number_format($data[1]) . "<br>";
        } else {
            echo "Error menambah data sample: " . $stmt->error . "<br>";
        }
    }
    
    echo "<br>Data sample berhasil ditambahkan!<br>";
} else {
    echo "Tabel barang sudah berisi data.<br>";
}

// Tampilkan struktur tabel
echo "<br><strong>Struktur Tabel Barang:</strong><br>";
$result = $conn->query("DESCRIBE barang");
if ($result) {
    echo "<table border='1' style='border-collapse: collapse; margin-top: 10px;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Tampilkan data yang ada
echo "<br><strong>Data Barang yang Ada:</strong><br>";
$result = $conn->query("SELECT * FROM barang ORDER BY nama");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin-top: 10px;'>";
    echo "<tr><th>ID</th><th>Nama</th><th>Harga</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['nama'] . "</td>";
        echo "<td>Rp" . number_format($row['harga']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Tidak ada data barang.";
}

echo "<br><br><a href='admin/barang.php' class='btn btn-primary'>Kunjungi Halaman Barang</a>";
?> 