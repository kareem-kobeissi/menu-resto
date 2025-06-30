<?php
session_start();
if (!isset($_SESSION['admin'])) {
  header("Location: admin_login.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard - Let's Eat</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet" />
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f4f7fc;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      width: 100%;
    }

    header {
      padding: 20px;
      background-color: #fff;
      text-align: center;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    header h1 {
      font-size: 2rem;
      color: #2c3e50;
    }

    .dashboard {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 30px 10px;
    }

    .nav {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      width: 100%;
      max-width: 900px;
    }

    .nav a {
      padding: 18px;
      background-color: #3498db;
      color: #fff;
      text-decoration: none;
      border-radius: 8px;
      font-weight: 600;
      text-align: center;
      font-size: 1.1rem;
      transition: background-color 0.3s ease, transform 0.3s ease;
    }

    .nav a:hover {
      background-color: #2980b9;
      transform: scale(1.02);
    }

    .nav a.logout {
      background-color: #e74c3c;
    }

    .nav a.logout:hover {
      background-color: #c0392b;
    }

    @media (max-width: 500px) {
      header h1 {
        font-size: 1.5rem;
      }

      .nav a {
        font-size: 1rem;
        padding: 14px;
      }
    }
  </style>
</head>

<body>

  <header>
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['admin']); ?> 👋</h1>
  </header>

  <section class="dashboard">
    <div class="nav">
      <a href="add_category.php">Add Category</a>
      <a href="DeleteCatagaory.php">Delete Catagory</a>

      <a href="add_subcategory.php">Add Subcategory</a>
      <a href="add_menu_item.php">Add Menu Item</a>
      <a href="edit-menu.php">View Menu</a>
      
      <a href="admin_update_category_image.php">Update Category Image</a>
      <a href="logout.php" class="logout">Logout</a>
    </div>
  </section>

</body>

</html>