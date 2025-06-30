<?php
require 'db_connection.php';

$category_id = $_GET['category_id'] ?? 0;
$category_id = (int) $category_id;

$result = $conn->query("SELECT id, name FROM subcategories WHERE category_id = $category_id ORDER BY name");

$subcategories = [];
while ($row = $result->fetch_assoc()) {
    $subcategories[] = $row;
}

header('Content-Type: application/json');
echo json_encode($subcategories);
