<?php
require 'db_connection.php';
$message = "";

// Fetch categories
$cats = $conn->query("SELECT id, name, banner_image FROM categories");

// Handle image update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $catId = (int)$_POST['category_id'];
    $imagePath = null;

    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['banner_image']['tmp_name'];
        $fileName = uniqid() . '_' . basename($_FILES['banner_image']['name']);
        $targetPath = 'uploads/' . $fileName;

        if (!is_dir('uploads')) {
            mkdir('uploads', 0755, true);
        }

        if (move_uploaded_file($fileTmp, $targetPath)) {
            $imagePath = $targetPath;

            $stmt = $conn->prepare("UPDATE categories SET banner_image = ? WHERE id = ?");
            $stmt->bind_param("ss", $imagePath, $catId);
            if ($stmt->execute()) {
                $message = "✅ Banner image updated.";
            } else {
                $message = "❌ Failed to update.";
            }
        }
    } else {
        $message = "⚠️ Please upload an image.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Update Category Banner</title>
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
            max-width: 480px;
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

        select,
        input[type="file"] {
            width: 100%;
            padding: 0.7rem;
            font-size: 1rem;
            border: 1px solid #ccc;
            border-radius: 8px;
            margin-bottom: 1.3rem;
        }

        button,
        .back-button {
            display: inline-block;
            width: 100%;
            padding: 0.75rem;
            font-size: 1rem;
            background-color: blue;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: background-color 0.3s ease;
            margin-top: 0.4rem;
        }

        button:hover,
        .back-button:hover {
            background-color: cornflowerblue;
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

            button,
            .back-button {
                font-size: 0.95rem;
                padding: 0.65rem;
            }
        }
    </style>
</head>

<body>

    <div class="card">
        <h2>Update Category Banner</h2>

        <form method="POST" enctype="multipart/form-data">
            <label>Select Category:</label>
            <select name="category_id" required>
                <?php while ($row = $cats->fetch_assoc()): ?>
                    <option value="<?= $row['id'] ?>">
                        <?= htmlspecialchars($row['name']) ?> <?= $row['banner_image'] ? '🖼️' : '' ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label>New Banner Image:</label>
            <input type="file" name="banner_image" accept="image/*" required>

            <button type="submit">Update Image</button>
        </form>

        <?php if (!empty($message)): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <a href="admin_dashboard.php" class="back-button">⬅ Back to Dashboard</a>
    </div>

</body>

</html>