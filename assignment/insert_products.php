<?php
$conn = new mysqli("localhost", "root", "", "ClothingStore");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->query("INSERT IGNORE INTO tblClothes (clothName, price, image) VALUES
('Baggy Tie', 50.00, 'baggy tie.jpeg'),
('Basenji Blue', 60.00, 'basenji Blue.jpeg'),
('Heavenly', 70.00, 'heavenly.jpeg'),
('Hoodie', 80.00, 'hoodie.jpeg'),
('Night Wing', 90.00, 'night wing.jpeg'),
('Red', 40.00, 'red.jpeg'),
('Running Black', 55.00, 'running black.jpeg'),
('Soweto Towers', 65.00, 'soweto towers.jpeg'),
('Space Rocket', 75.00, 'space rocket.jpeg'),
('T-Shirt', 30.00, 'tshirt.jpeg'),
('White Senji', 45.00, 'whiteSenji.jpeg')");

echo "Products inserted.";
?>