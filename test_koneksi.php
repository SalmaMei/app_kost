<?php
require_once "koneksi.php";
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
} else {
    echo "Koneksi berhasil!";
    
    // Test query user
    $result = $conn->query("SELECT * FROM user LIMIT 1");
    if ($result) {
        echo "<br>Query user berhasil, jumlah data: " . $result->num_rows;
    } else {
        echo "<br>Error query: " . $conn->error;
    }
}
?> 