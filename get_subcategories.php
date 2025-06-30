<?php
include 'db_connection.php';

$category_id = intval($_GET['category_id']);
$result = mysqli_query($conn, "SELECT * FROM subcategories WHERE category_id = $category_id");

$subcategories = [];
while ($row = mysqli_fetch_assoc($result)) {
    $subcategories[] = $row;
}

header('Content-Type: application/json');
echo json_encode($subcategories);
?>
