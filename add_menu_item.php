<?php
require 'db_connection.php';

$message = "";

// Fetch categories for dropdown
$categories = [];
$result = $conn->query("SELECT id, name FROM categories ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subcategory_id = $_POST['subcategory_id'];
    $name = trim($_POST['name']);
    $price = $_POST['price'];
    $description = trim($_POST['description']);


    $image_url = null;

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $image_tmp = $_FILES['image']['tmp_name'];
    $image_name = uniqid() . '_' . basename($_FILES['image']['name']);
    $target_dir = 'uploads/';
    $target_path = $target_dir . $image_name;

    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    if (move_uploaded_file($image_tmp, $target_path)) {
        $image_url = $target_path;
    }
}

}

if (!empty($subcategory_id) && !empty($name) && !empty($price)) {
   $stmt = $conn->prepare("INSERT INTO menu_items (subcategory_id, name, description, price) VALUES (?, ?, ?, ?)");
$stmt->bind_param("issd", $subcategory_id, $name, $description, $price);




    if ($stmt->execute()) {
        $menu_item_id = $stmt->insert_id;
        if (!empty($image_url)) {
            $stmt_img = $conn->prepare("INSERT INTO menu_images (menu_item_id, image_url, is_main) VALUES (?, ?, true)");
            $stmt_img->bind_param("is", $menu_item_id, $image_url);
            $stmt_img->execute();
            $stmt_img->close();
        }
        $message = "✅ Menu item added successfully.";
    } else {
        $message = "❌ Failed to add menu item.";
    }
    $stmt->close();
} else {
    $message = "⚠️ Please fill all required fields.";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Menu Item - Let's Eat</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      padding: 1rem;
      font-family: "Segoe UI", sans-serif;
      background: #f3f4f6;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }

    .card {
      background: white;
      padding: 2rem;
      border-radius: 12px;
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
      max-width: 500px;
      width: 100%;
    }

    h2 {
      text-align: center;
      margin-bottom: 1.5rem;
      font-size: 1.6rem;
    }

    label {
      display: block;
      margin-bottom: 0.4rem;
      font-weight: 500;
      color: #333;
    }

    input[type="text"],
    input[type="number"],
    select {
      width: 100%;
      padding: 0.75rem;
      font-size: 1rem;
      border: 1px solid #ccc;
      border-radius: 8px;
      margin-bottom: 1.2rem;
    }

    .price-wrapper {
      position: relative;
    }

    .price-wrapper span {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      font-weight: bold;
      color: #777;
    }

    .price-wrapper input {
      padding-left: 25px;
    }
    

    input[type="submit"],
    .back-button {
      width: 100%;
      background-color: #007bff;
      color: white;
      font-size: 1rem;
      padding: 0.75rem;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      margin-top: 0.4rem;
      margin-bottom: 0.6rem;
      text-decoration: none;
      text-align: center;
      display: inline-block;
    }

    input[type="submit"]:hover,
    .back-button:hover {
      background-color: #0056b3;
    }

    .message {
      text-align: center;
      font-weight: 500;
      margin-top: 1rem;
      color: #333;
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

  <script>
    function fetchSubcategories(categoryId) {
      const subSelect = document.getElementById('subcategory_id');
      subSelect.innerHTML = '<option value="">Loading...</option>';

      fetch(`fetch_subcategories.php?category_id=${categoryId}`)
        .then(res => res.json())
        .then(data => {
          let options = '<option value="">-- Choose Subcategory --</option>';
          data.forEach(sub => {
            options += `<option value="${sub.id}">${sub.name}</option>`;
          });
          subSelect.innerHTML = options;
        });
    }
  </script>
</head>

<body>

  <div class="card">
    <h2>Add New Menu Item</h2>

    <form method="POST" action="" enctype="multipart/form-data">
      <label for="category_id">Select Category</label>
      <select name="category_id" id="category_id" required onchange="fetchSubcategories(this.value)">
        <option value="">-- Choose Category --</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <label for="subcategory_id">Select Subcategory</label>
      <select name="subcategory_id" id="subcategory_id" required>
        <option value="">-- Select a Category First --</option>
      </select>

      <label for="name">Item Name</label>
      <input type="text" name="name" id="name" placeholder="e.g., Chicken Biryani" required>
      <label for="description">Description</label>
<textarea name="description" id="description" rows="3" placeholder="Enter item description..." style="width:100%; padding:0.75rem; border-radius:8px; border:1px solid #ccc; margin-bottom:1.2rem;"></textarea>


     <label for="price">Price</label>
<div class="price-wrapper">
  
  <input type="number" step="0.5" name="price" id="price" placeholder="Enter Item Price" required>
</div>

<label for="image">Upload Image</label>
<input type="file" name="image" id="image" accept="image/*" onchange="previewImage(this)">
<img id="preview" src="" alt="Image Preview" style="display:none; max-width: 100%; margin-top: 10px; border-radius: 8px;">

      <input type="submit" value="Add Menu Item">
    </form>

    <?php if (!empty($message)): ?>
      <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <a href="admin_dashboard.php" class="back-button">⬅ Back to Dashboard</a>
  </div>
  <script>
function previewImage(input) {
  const preview = document.getElementById('preview');
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      preview.src = e.target.result;
      preview.style.display = 'block';
    }
    reader.readAsDataURL(input.files[0]);
  }
}
</script>

</body>
</html>
