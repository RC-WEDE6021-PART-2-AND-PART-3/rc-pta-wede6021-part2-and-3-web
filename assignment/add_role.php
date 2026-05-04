<?php
$conn = new mysqli("localhost", "root", "", "ClothingStore");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->query("ALTER TABLE tblUser ADD COLUMN role ENUM('user', 'seller', 'admin') DEFAULT 'user'");

echo "Role column added.";
?>