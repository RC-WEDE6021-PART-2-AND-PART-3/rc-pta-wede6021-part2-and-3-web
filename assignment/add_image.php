<?php
$conn = new mysqli("localhost", "root", "", "ClothingStore");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->query("ALTER TABLE tblClothes ADD COLUMN image VARCHAR(255)");

echo "Image column added.";
?>