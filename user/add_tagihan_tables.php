<?php
require_once "koneksi.php";

// Buat tabel tagihan jika belum ada (struktur sesuai db_kost.sql)
$sql_tagihan = "CREATE TABLE IF NOT EXISTS tagihan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bulan DATE NOT NULL,
    id_kmr_penghuni INT NOT NULL,
    jml_tagihan INT NOT NULL,
    FOREIGN KEY (id_kmr_penghuni) REFERENCES kmr_penghuni(id)
)";

if ($conn->query($sql_tagihan) === TRUE) {
    echo "Tabel tagihan berhasil dibuat atau sudah ada!<br>";
} else {
    echo "Error membuat tabel tagihan: " . $conn->error . "<br>";
}

// Cek apakah ada data sample
$result = $conn->query("SELECT COUNT(*) as total FROM tagihan");
$row = $result->fetch_assoc();

if ($row['total'] == 0) {
    echo "<br>Membuat data sample tagihan...<br>";
    
    // Ambil beberapa data kmr_penghuni untuk sample
    $sample_penghuni = $conn->query("
        SELECT kp.id, k.nomor, p.nama, k.harga 
        FROM kmr_penghuni kp 
        JOIN kamar k ON kp.id_kamar = k.id 
        JOIN penghuni p ON kp.id_penghuni = p.id 
        WHERE kp.tgl_keluar IS NULL 
        LIMIT 3
    ");
    
    if ($sample_penghuni && $sample_penghuni->num_rows > 0) {
        $bulan_ini = date('Y-m-01');
        $bulan_lalu = date('Y-m-01', strtotime('-1 month'));
        
        $stmt = $conn->prepare("INSERT INTO tagihan (bulan, id_kmr_penghuni, jml_tagihan) VALUES (?, ?, ?)");
        
        while ($penghuni = $sample_penghuni->fetch_assoc()) {
            // Tagihan bulan ini
            $stmt->bind_param("sii", $bulan_ini, $penghuni['id'], $penghuni['harga']);
            if ($stmt->execute()) {
                echo "Tagihan sample dibuat: " . $penghuni['nama'] . " - Kamar " . $penghuni['nomor'] . " (Bulan ini)<br>";
            }
            
            // Tagihan bulan lalu
            $stmt->bind_param("sii", $bulan_lalu, $penghuni['id'], $penghuni['harga']);
            if ($stmt->execute()) {
                echo "Tagihan sample dibuat: " . $penghuni['nama'] . " - Kamar " . $penghuni['nomor'] . " (Bulan lalu)<br>";
            }
        }
        
        echo "<br>Data sample tagihan berhasil dibuat!<br>";
    } else {
        echo "Tidak ada penghuni aktif untuk membuat sample tagihan.<br>";
    }
} else {
    echo "Tabel tagihan sudah berisi data.<br>";
}

// Tampilkan struktur tabel
echo "<br><strong>Struktur Tabel Tagihan:</strong><br>";
$result = $conn->query("DESCRIBE tagihan");
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

// Tampilkan data tagihan yang ada
echo "<br><strong>Data Tagihan yang Ada:</strong><br>";
$result = $conn->query("
    SELECT t.*, k.nomor as nomor_kamar, p.nama as nama_penghuni 
    FROM tagihan t
    JOIN kmr_penghuni kp ON t.id_kmr_penghuni = kp.id
    JOIN kamar k ON kp.id_kamar = k.id
    JOIN penghuni p ON kp.id_penghuni = p.id
    ORDER BY t.bulan DESC
");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin-top: 10px;'>";
    echo "<tr><th>ID</th><th>Kamar</th><th>Penghuni</th><th>Bulan</th><th>Tagihan</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['nomor_kamar'] . "</td>";
        echo "<td>" . $row['nama_penghuni'] . "</td>";
        echo "<td>" . date('F Y', strtotime($row['bulan'])) . "</td>";
        echo "<td>Rp" . number_format($row['jml_tagihan']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Tidak ada data tagihan.";
}

echo "<br><br><a href='admin/tagihan.php' class='btn btn-primary'>Kunjungi Halaman Tagihan</a>";
?> 