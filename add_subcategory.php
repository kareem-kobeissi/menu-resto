<?php
require 'db_connection.php';

$message = "";

// Fetch all categories for the dropdown
$categories = [];
$result = $conn->query("SELECT id, name FROM categories ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = $_POST['category_id'];
    $subcategory_name = trim($_POST['subcategory_name']);

    if (!empty($category_id) && !empty($subcategory_name)) {
        $stmt = $conn->prepare("SELECT id FROM subcategories WHERE category_id = ? AND name = ?");
        $stmt->bind_param("is", $category_id, $subcategory_name);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "⚠️ Subcategory already exists under this category.";
        } else {
            $stmt = $conn->prepare("INSERT INTO subcategories (category_id, name) VALUES (?, ?)");
            $stmt->bind_param("is", $category_id, $subcategory_name);
            if ($stmt->execute()) {
                $message = "✅ Subcategory added successfully.";
            } else {
                $message = "❌ Failed to add subcategory.";
            }
        }

        $stmt->close();
    } else {
        $message = "⚠️ Please select a category and enter subcategory name.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Subcategory - Let's Eat</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      padding: 0;
      font-family: "Segoe UI", sans-serif;
      background-color: #f0f2f5;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 1rem;
    }

    .card {
      background-color: #fff;
      padding: 2rem;
      border-radius: 12px;
      box-shadow: 0 8px 30px rgba(0,0,0,0.08);
      width: 100%;
      max-width: 420px;
    }

    h2 {
      margin-bottom: 1.5rem;
      font-size: 1.6rem;
      color: #333;
      text-align: center;
    }

    label {
      display: block;
      margin-bottom: 0.4rem;
      color: #444;
      font-weight: 500;
      font-size: 0.95rem;
    }

    input[type="text"],
    select {
      width: 100%;
      padding: 0.75rem;
      font-size: 1rem;
      border: 1px solid #ccc;
      border-radius: 8px;
      margin-bottom: 1.2rem;
    }

    input[type="submit"],
    .back-button {
      display: inline-block;
      width: 100%;
      padding: 0.75rem;
      font-size: 1rem;
      background-color: #0066cc;
      color: #fff;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.3s ease;
      text-align: center;
      margin-top: 0.3rem;
      text-decoration: none;
    }

    input[type="submit"]:hover,
    .back-button:hover {
      background-color: #004c99;
    }

    .message {
      margin-top: 1rem;
      font-weight: 500;
      color: #333;
      text-align: center;
      font-size: 0.95rem;
    }

    @media (max-width: 480px) {
      .card {
        padding: 1.5rem 1rem;
      }

      h2 {
        font-size: 1.4rem;
      }

      input[type="submit"],
      .back-button {
        font-size: 0.95rem;
        padding: 0.65rem;
      }
    }
  </style>
</head>
<body>

<div class="card">
  <h2>Add New Subcategory</h2>

  <form method="POST" action="">
    <label for="category_id">Select Category</label>
    <select name="category_id" id="category_id" required>
      <option value="">-- Choose Category --</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
      <?php endforeach; ?>
    </select>

    <label for="subcategory_name">Subcategory Name</label>
    <input type="text" name="subcategory_name" id="subcategory_name" placeholder="e.g., Vegetarian" required>

    <input type="submit" value="Add Subcategory">
  </form>

  <?php if (!empty($message)): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <a href="admin_dashboard.php" class="back-button">⬅ Back to Dashboard</a>
</div>

</body>
</html>
