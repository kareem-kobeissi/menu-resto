<?php
require 'db_connection.php';

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category_id'])) {
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param("i", $_POST['category_id']);
    $stmt->execute();
    $message = $stmt->affected_rows > 0 ? "Category deleted successfully." : "Failed to delete category.";
}

// Fetch categories
$categories = [];
$result = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Menu Category</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            background-color: #f1f4f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .form-box {
            background-color: #fff;
            padding: 30px 20px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        h2 {
            margin-bottom: 25px;
            font-size: 1.8rem;
        }

        select {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            font-size: 1rem;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        button, .back-btn {
            display: block;
            width: 100%;
            padding: 12px;
            font-size: 1rem;
            border-radius: 6px;
            border: none;
            margin-top: 10px;
            cursor: pointer;
        }

        button {
            background-color: #0069d9;
            color: #fff;
        }

        .back-btn {
            background-color: #0069d9;
            color: white;
            text-decoration: none;
            line-height: 38px;
            text-align: center;
        }

        .message {
            margin-bottom: 15px;
            color: green;
            font-weight: bold;
        }

        @media (max-width: 480px) {
            h2 {
                font-size: 1.5rem;
            }

            select, button, .back-btn {
                font-size: 0.95rem;
                padding: 10px;
            }
        }
    </style>
</head>
<body>

<div class="form-box">
    <h2>Delete Menu Category</h2>

    <?php if (isset($message)) echo "<div class='message'>{$message}</div>"; ?>

    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this category and everything linked to it?');">
        <select name="category_id" required>
            <option value="" disabled selected>-- Choose Category --</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Delete Category</button>
    </form>

    <a class="back-btn" href="admin_dashboard.php">← Back to Dashboard</a>
</div>

</body>
</html>
