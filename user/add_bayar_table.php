<?php
require_once "koneksi.php";

// Buat tabel bayar jika belum ada (struktur sesuai db_kost.sql)
$sql_bayar = "CREATE TABLE IF NOT EXISTS bayar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_tagihan INT NOT NULL,
    jml_bayar INT NOT NULL,
    status ENUM('lunas','cicil') NOT NULL,
    FOREIGN KEY (id_tagihan) REFERENCES tagihan(id)
)";

if ($conn->query($sql_bayar) === TRUE) {
    echo "Tabel bayar berhasil dibuat atau sudah ada!<br>";
} else {
    echo "Error membuat tabel bayar: " . $conn->error . "<br>";
}

// Cek apakah ada data sample
$result = $conn->query("SELECT COUNT(*) as total FROM bayar");
$row = $result->fetch_assoc();

if ($row['total'] == 0) {
    echo "<br>Membuat data sample pembayaran...<br>";
    
    // Ambil beberapa tagihan untuk sample
    $sample_tagihan = $conn->query("SELECT id, jml_tagihan FROM tagihan LIMIT 3");
    
    if ($sample_tagihan && $sample_tagihan->num_rows > 0) {
        $stmt = $conn->prepare("INSERT INTO bayar (id_tagihan, jml_bayar, status) VALUES (?, ?, ?)");
        
        while ($tagihan = $sample_tagihan->fetch_assoc()) {
            // Pembayaran lunas
            $jml_bayar = $tagihan['jml_tagihan'];
            $status = 'lunas';
            
            $stmt->bind_param("iis", $tagihan['id'], $jml_bayar, $status);
            if ($stmt->execute()) {
                echo "Pembayaran sample dibuat: Tagihan ID " . $tagihan['id'] . " - Rp" . number_format($jml_bayar) . " (Lunas)<br>";
            }
        }
        
        echo "<br>Data sample pembayaran berhasil dibuat!<br>";
    } else {
        echo "Tidak ada tagihan untuk membuat sample pembayaran.<br>";
    }
} else {
    echo "Tabel bayar sudah berisi data.<br>";
}

// Tampilkan struktur tabel
echo "<br><strong>Struktur Tabel Bayar:</strong><br>";
$result = $conn->query("DESCRIBE bayar");
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

// Tampilkan data pembayaran yang ada
echo "<br><strong>Data Pembayaran yang Ada:</strong><br>";
$result = $conn->query("
    SELECT b.*, t.bulan, t.jml_tagihan, k.nomor as nomor_kamar, p.nama as nama_penghuni
    FROM bayar b
    JOIN tagihan t ON b.id_tagihan = t.id
    JOIN kmr_penghuni kp ON t.id_kmr_penghuni = kp.id
    JOIN kamar k ON kp.id_kamar = k.id
    JOIN penghuni p ON kp.id_penghuni = p.id
    ORDER BY b.id DESC
");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin-top: 10px;'>";
    echo "<tr><th>ID</th><th>Kamar</th><th>Penghuni</th><th>Bulan</th><th>Tagihan</th><th>Bayar</th><th>Status</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['nomor_kamar'] . "</td>";
        echo "<td>" . $row['nama_penghuni'] . "</td>";
        echo "<td>" . date('F Y', strtotime($row['bulan'])) . "</td>";
        echo "<td>Rp" . number_format($row['jml_tagihan']) . "</td>";
        echo "<td>Rp" . number_format($row['jml_bayar']) . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Tidak ada data pembayaran.";
}

echo "<br><br><a href='user/dashboard.php' class='btn btn-primary'>Kunjungi Dashboard User</a>";
?> 