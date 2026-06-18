<?php
$conn = new mysqli("localhost", "root", "");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->query("CREATE DATABASE IF NOT EXISTS ClothingStore");
$conn->select_db("ClothingStore");

$sql = @file_get_contents("myClothingStore.sql");
if ($sql === false) {
    die("SQL file not found: myClothingStore.sql");
}

if ($conn->multi_query($sql)) {
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    echo "Database loaded successfully!";
} else {
    echo "Error loading database: " . $conn->error;
}
?>