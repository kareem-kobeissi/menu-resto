<?php
require 'db_connection.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name']);
  $imagePath = null;

  if (!empty($name)) {
    // Check if category already exists
    $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
      $message = "⚠️ Category already exists.";
    } else {
      // ✅ Image Upload Handling
      if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['banner_image']['tmp_name'];
        $fileName = uniqid() . '_' . basename($_FILES['banner_image']['name']);
        $targetPath = 'uploads/' . $fileName;

        if (!is_dir('uploads')) {
          mkdir('uploads', 0755, true);
        }

        if (move_uploaded_file($fileTmp, $targetPath)) {
          $imagePath = $targetPath;
        }
      }

      // ✅ Insert into database with banner_image
      $stmt = $conn->prepare("INSERT INTO categories (name, banner_image) VALUES (?, ?)");
      $stmt->bind_param("ss", $name, $imagePath);
      if ($stmt->execute()) {
        $message = "✅ Category added successfully.";
      } else {
        $message = "❌ Failed to add category.";
      }
    }

    $stmt->close();
  } else {
    $message = "⚠️ Please enter a category name.";
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Add Category - Let's Eat</title>
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
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
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

    input[type="text"] {
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
    <h2>Add New Menu Category</h2>

    <form method="POST" enctype="multipart/form-data">
      <label>Category Name:</label>
      <input type="text" name="name" required><br><br>



      <input type="submit" value="Add Category">


    </form>

    <?php if (!empty($message)): ?>
      <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <a href="admin_dashboard.php" class="back-button">⬅ Back to Dashboard</a>
  </div>

</body>

</html>