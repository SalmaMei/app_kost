<?php
require_once "koneksi.php";

// Cek apakah tabel user sudah ada
$check_user = $conn->query("SHOW TABLES LIKE 'user'");

if ($check_user->num_rows == 0) {
    echo "Tabel user belum ada. Silakan jalankan db_kost.sql terlebih dahulu.<br>";
    exit;
}

// Cek apakah sudah ada user
$result = $conn->query("SELECT COUNT(*) as total FROM user");
$row = $result->fetch_assoc();

if ($row['total'] == 0) {
    echo "Menambahkan user sample...<br>";
    
    // Hash password untuk user
    $password_hash = password_hash('user123', PASSWORD_DEFAULT);
    
    // Tambah user sample
    $users = [
        ['user1', $password_hash, 'Andi Saputra', 1, 'user'],
        ['user2', $password_hash, 'Budi Santoso', 2, 'user'],
        ['user3', $password_hash, 'Citra Dewi', 3, 'user']
    ];
    
    $stmt = $conn->prepare("INSERT INTO user (username, password, nama, id_kamar, role) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($users as $user) {
        $stmt->bind_param("sssis", $user[0], $user[1], $user[2], $user[3], $user[4]);
        if ($stmt->execute()) {
            echo "User berhasil ditambahkan: " . $user[0] . " - " . $user[2] . "<br>";
        } else {
            echo "Error menambah user: " . $stmt->error . "<br>";
        }
    }
    
    echo "<br>User sample berhasil ditambahkan!<br>";
    echo "<strong>Username:</strong> user1, user2, user3<br>";
    echo "<strong>Password:</strong> user123<br>";
} else {
    echo "Tabel user sudah berisi data.<br>";
}

// Tampilkan data user yang ada
echo "<br><strong>Data User yang Ada:</strong><br>";
$result = $conn->query("SELECT id, username, nama, role, id_kamar FROM user ORDER BY id");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin-top: 10px;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Nama</th><th>Role</th><th>ID Kamar</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . $row['nama'] . "</td>";
        echo "<td>" . $row['role'] . "</td>";
        echo "<td>" . $row['id_kamar'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Tidak ada data user.";
}

echo "<br><br><a href='index.php' class='btn btn-primary'>Kembali ke Login</a>";
echo "<br><a href='user/dashboard.php' class='btn btn-success'>Test Dashboard User</a>";
?> 