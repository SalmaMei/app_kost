<?php
require_once "koneksi.php";

echo "<h3>Struktur Tabel User:</h3>";
$result = $conn->query("DESCRIBE user");
if ($result) {
    echo "<table border='1'>";
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
} else {
    echo "Error: " . $conn->error;
}

echo "<h3>Data User:</h3>";
$result = $conn->query("SELECT id, username, role, LEFT(password, 20) as password_preview FROM user");
if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Username</th><th>Role</th><th>Password Preview</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . $row['role'] . "</td>";
        echo "<td>" . $row['password_preview'] . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . $conn->error;
}
?> 