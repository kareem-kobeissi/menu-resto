<?php
require 'db_connection.php';
session_start();

// Check if the user is an admin
if (!isset($_SESSION['admin'])) {
  header('Location: admin_login.php'); // Redirect to login page if not admin
  exit;
}

// Fetch all menu items
$sql = "SELECT 
            mi.id, 
            mi.name, 
            mi.price, 
            mi.description,
            sc.name AS subcategory_name,
            (SELECT image_url FROM menu_images WHERE menu_item_id = mi.id AND is_main = 1 LIMIT 1) AS image_url
        FROM menu_items mi
        JOIN subcategories sc ON mi.subcategory_id = sc.id
        ORDER BY mi.id ASC";


$result = $conn->query($sql);

if ($result->num_rows > 0) {
  $menu_items = [];
  while ($row = $result->fetch_assoc()) {
    $menu_items[] = $row;
  }
} else {
  $menu_items = [];
}

if (isset($_POST['id'], $_POST['name'], $_POST['price'], $_POST['description'])) {
  $id = (int)$_POST['id'];
  $name = $_POST['name'];
  $price = trim($_POST['price']);
  $description = trim($_POST['description']); // ✅ Add this

  $sql_update = "UPDATE menu_items SET name = ?, price = ?, description = ? WHERE id = ?";
  $stmt = $conn->prepare($sql_update);
  if ($stmt === false) {
    echo "Error preparing the query: " . $conn->error;
    exit;
  }

  $stmt->bind_param('sssi', $name, $price, $description, $id);
  $stmt->execute();
  $stmt->close();


  // Handle image upload if a new image is uploaded
  if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $img_tmp = $_FILES['image']['tmp_name'];
    $img_name = uniqid() . '_' . basename($_FILES['image']['name']);
    $target_dir = 'uploads/';
    $target_path = $target_dir . $img_name;

    if (!is_dir($target_dir)) {
      mkdir($target_dir, 0755, true);
    }

    if (move_uploaded_file($img_tmp, $target_path)) {
      // Remove old image (optional – get old URL from DB and unlink it)

      // Delete old main image entry
      $conn->query("DELETE FROM menu_images WHERE menu_item_id = $id AND is_main = 1");

      // Insert new image
      $stmt_img = $conn->prepare("INSERT INTO menu_images (menu_item_id, image_url, is_main) VALUES (?, ?, 1)");
      $stmt_img->bind_param("is", $id, $target_path);
      $stmt_img->execute();
      $stmt_img->close();
    }
  }

  echo "<script>alert('Item updated successfully!'); window.location.href='edit-menu.php';</script>";
  exit;
}



// Handle delete request
if (isset($_POST['delete_id'])) {
  $delete_id = (int)$_POST['delete_id'];

  // Delete related records in menu_images and item_options before deleting the menu item
  $sql_delete_images = "DELETE FROM menu_images WHERE menu_item_id = ?";
  $stmt_images = $conn->prepare($sql_delete_images);
  $stmt_images->bind_param('i', $delete_id);
  $stmt_images->execute();

  $sql_delete_options = "DELETE FROM item_options WHERE menu_item_id = ?";
  $stmt_options = $conn->prepare($sql_delete_options);
  $stmt_options->bind_param('i', $delete_id);
  $stmt_options->execute();

  // Finally, delete the menu item
  $sql_delete_item = "DELETE FROM menu_items WHERE id = ?";
  $stmt_item = $conn->prepare($sql_delete_item);
  $stmt_item->bind_param('i', $delete_id);
  $stmt_item->execute();

  if ($stmt_item->affected_rows > 0) {
    echo "<script>alert('Item deleted successfully!'); window.location.href='edit-menu.php';</script>";
  } else {
    echo "<script>alert('Failed to delete item.'); window.location.href='edit-menu.php';</script>";
  }

  $stmt_item->close();
  $stmt_images->close();
  $stmt_options->close();
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Edit Menu Items – Admin</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      margin: 0;
      padding: 20px;
      background-color: #f4f7fc;
    }

    .container {
      width: 95%;
      max-width: 1200px;
      margin: 0 auto;
    }

    h1 {
      margin-bottom: 1.2rem;
    }

    .search-bar {
      margin-bottom: 20px;
      text-align: right;
    }

    .search-bar input {
      padding: 10px;
      width: 100%;
      max-width: 300px;
      font-size: 16px;
      border-radius: 6px;
      border: 1px solid #ccc;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 30px;
      background-color: #ffffff;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      overflow-x: auto;
    }

    table th,
    table td {
      padding: 12px;
      text-align: left;
      border: 1px solid #ddd;
    }

    th {
      background-color: #e63946;
      color: white;
    }

    td {
      background-color: #f9f9f9;
    }

    td:hover {
      background-color: #f1f1f1;
    }

    .actions {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }

    .actions button {
      padding: 8px 15px;
      background-color: #e63946;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    .actions button:hover {
      background-color: #d62839;
    }

    .delete-btn {
      background-color: #f1faee;
      color: #e63946;
      border-radius: 5px;
      border: 1px solid #e63946;
    }

    .delete-btn:hover {
      background-color: #e63946;
      color: white;
    }

    #editForm {
      display: none;
      background-color: white;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      margin-bottom: 30px;
    }

    #editForm input,
    #editForm textarea {
      width: 100%;
      padding: 10px;
      margin-bottom: 20px;
      border-radius: 5px;
      border: 1px solid #ddd;
    }

    #editForm button {
      padding: 12px 20px;
      background-color: #e63946;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }

    #editForm button:hover {
      background-color: #d62839;
    }

    .search-bar {
      margin-bottom: 20px;
      text-align: right;
    }

    .search-bar input {
      padding: 10px;
      width: 100%;
      max-width: 300px;
      font-size: 16px;
      border-radius: 6px;
      border: 1px solid #ccc;
    }


    @media (max-width: 768px) {
      .actions {
        flex-direction: column;
      }

      table,
      thead,
      tbody,
      th,
      td,
      tr {
        display: block;
      }

      thead {
        display: none;
      }

      tr {
        margin-bottom: 1.5rem;
        border: 1px solid #ccc;
        padding: 10px;
        background: white;
        border-radius: 10px;
      }

      td {
        display: flex;
        justify-content: space-between;
        padding: 10px 5px;
        font-size: 0.95rem;
      }

      td::before {
        content: attr(data-label);
        font-weight: bold;
        color: #555;
      }
    }
  </style>
</head>

<body>

  <div class="container">
    <h1>Edit Menu Items</h1>
    <div class="search-bar">
      <input type="text" id="searchInput" placeholder="Search by item name..." onkeyup="filterTable()">
    </div>


    <!-- Display Menu Items -->
    <table>
     <thead>
  <tr>
    <th>ID</th>
    <th>Name</th>
    <th>Image</th>
    <th>Price</th>
    <th>Description</th> <!-- ✅ New Column -->
    <th>Subcategory</th>
    <th>Actions</th>
  </tr>
</thead>

      <tbody>

        <?php foreach ($menu_items as $item): ?>
          <tr>
            <td><?= $item['id'] ?></td>
            <td><?= htmlspecialchars($item['name']) ?></td>
            <td>
              <?php if (!empty($item['image_url'])): ?>
                <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="Item Image" style="max-height: 50px; border-radius: 5px;">
              <?php else: ?>
                <span style="color: #888;">No image</span>
              <?php endif; ?>
            </td>
           <td><?= htmlspecialchars($item['price']) ?></td>
<td><?= nl2br(htmlspecialchars($item['description'] ?? '')) ?></td> <!-- ✅ New Cell -->
<td><?= htmlspecialchars($item['subcategory_name']) ?></td>


            <td class="actions">
              <button onclick="openEditForm(
  <?= $item['id'] ?>, 
  '<?= htmlspecialchars($item['name'], ENT_QUOTES) ?>', 
  <?= $item['price'] ?>, 
  '<?= htmlspecialchars($item['description'] ?? '', ENT_QUOTES) ?>'
)">Edit</button>

              <form method="POST" style="display:inline;">
                <input type="hidden" name="delete_id" value="<?= $item['id'] ?>">
                <button type="submit" class="delete-btn" onclick="return confirm('Are you sure you want to delete this item?')">Delete</button>
              </form>
            </td>
          </tr>

        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- Edit Form Modal -->
    <div id="editForm">
      <h2>Edit Menu Item</h2>
      <form id="editFormContent" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" id="editItemId">

        <label for="name">Name:</label>
        <input type="text" name="name" id="editItemName" required>

        <label for="price">Price in $:</label>
        <input type="text" name="price" id="editItemPrice" required>

        <label for="image">Upload New Image (optional):</label>
        <input type="file" name="image" id="editItemImage" accept="image/*">
        <label for="description">Description:</label>
        <textarea name="description" id="editItemDescription" rows="3"></textarea>

        <button type="submit">Save Changes</button>
        <button type="button" onclick="closeEditForm()">Cancel</button>
      </form>

    </div>
  </div>

  <script>
    function openEditForm(id, name, price, description) {
      document.getElementById('editForm').style.display = 'block';
      document.getElementById('editItemId').value = id;
      document.getElementById('editItemName').value = name;
      document.getElementById('editItemPrice').value = price;
      document.getElementById('editItemDescription').value = description;
      document.getElementById('editForm').scrollIntoView({
        behavior: 'smooth'
      });
    }




    function closeEditForm() {
      document.getElementById('editForm').style.display = 'none';
    }
  </script>
  <script>
    function filterTable() {
      const input = document.getElementById("searchInput").value.toLowerCase();
      const rows = document.querySelectorAll("table tbody tr");

      rows.forEach(row => {
        const nameCell = row.querySelector("td:nth-child(2)");
        const name = nameCell ? nameCell.textContent.toLowerCase() : "";
        row.style.display = name.includes(input) ? "" : "none";
      });
    }
  </script>
  <div style="margin-bottom: 20px;">


</div>



<div style="text-align: center; margin-top: 30px;">
  <a href="admin_dashboard.php" style="
      display: inline-block;
      padding: 10px 20px;
      background-color:#e63946;
      color: white;
      text-decoration: none;
      border-radius: 5px;
      font-weight: bold;
      transition: background-color 0.3s ease;
    ">← Back to Admin Dashboard</a>
</div>


</body>


</html>