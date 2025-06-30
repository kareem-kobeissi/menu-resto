<?php
require 'db_connection.php';
session_start();

// Language toggle
if (isset($_GET['lang'])) {
  $_SESSION['lang'] = $_GET['lang'] === 'ar' ? 'ar' : 'en';
}
$lang = $_SESSION['lang'] ?? 'en';

// Fetch categories, subcategories, and cover images
$cats = [];
$catRes = $conn->query("SELECT id, name, banner_image FROM categories ORDER BY id ASC");

while ($c = $catRes->fetch_assoc()) {
  $covSql = "
        SELECT mi.image_url
        FROM menu_images mi
        JOIN menu_items i ON mi.menu_item_id = i.id
        JOIN subcategories s ON i.subcategory_id = s.id
        WHERE s.category_id = {$c['id']} AND mi.is_main = 1
        LIMIT 1
    ";
  $covRes = $conn->query($covSql);
  $cover = ($covRes && $covRes->num_rows)
    ? $covRes->fetch_assoc()['image_url']
    : 'assets/images/placeholder.jpg';

  $subs = [];
  $subRes = $conn->query("SELECT id, name FROM subcategories WHERE category_id = {$c['id']} ORDER BY name");
  while ($s = $subRes->fetch_assoc()) {
    $subs[] = $s;
  }

  $cats[] = [
    'id'    => $c['id'],
    'name'  => $c['name'],
    'cover' => $cover,
    'subs'  => $subs,
    'banner_image' => $c['banner_image'] ?? null  // ✅ prevents the warning
  ];
}

// AJAX: Load items by subcategory
if (isset($_GET['subcat'])) {
  $subcat = (int)$_GET['subcat'];
  $sql = "
        SELECT mi.name, mi.description, mi.price,
            COALESCE((
                SELECT image_url 
                FROM menu_images 
                WHERE menu_item_id=mi.id AND is_main=1 
                LIMIT 1
            ), '') AS image_url
        FROM menu_items mi
        WHERE mi.subcategory_id = $subcat
        ORDER BY mi.name
    ";
  $res = $conn->query($sql);
  $out = [];
  while ($row = $res->fetch_assoc()) {
    $out[] = $row;
  }
  header('Content-Type: application/json');
  echo json_encode($out);
  exit;
}

?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
  <meta charset="UTF-8">
  <title>Let's Eat – Menu</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(to right, #f2c94c, #f2994a);
      color: #1e1e1e;
      line-height: 1.6;
      font-size: 16px;

    }

    a {
      text-decoration: none;
      color: inherit;
    }

    .hero {
      height: 60vh;
      position: relative;
      background: url('images/logo.jpg') center/cover no-repeat;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .hero::after {
      content: '';
      position: absolute;
      inset: 0;
      background: rgba(0, 0, 0, 0.05);
    }

    .hero .overlay {
      position: relative;
      z-index: 2;
      text-align: center;
      color: #fff;
      padding: 1rem;
      animation: zoomIn 1.2s ease-in-out;
    }






    .hero .overlay p {
      font-size: 1.2rem;
      font-weight: 400;
      color: #ffe8c8;
      margin-top: 0.5rem;
      letter-spacing: 1px;
      text-shadow: 0 1px 4px rgba(0, 0, 0, 0.3);
    }


    .hero-controls {
      position: absolute;
      top: 20px;
      right: 20px;
      z-index: 3;
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
    }

    .hero-controls .btn {
      padding: 0.5rem 1.2rem;
      background: #fff;
      color: #e63946;
      border: 2px solid #e63946;
      border-radius: 8px;
      font-weight: 600;
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
      cursor: pointer;
    }

    .hero-controls .btn:hover {
      background: #e63946;
      color: white;
    }

    .btn.cart {
      background: #e63946;
      color: #fff;
      border-radius: 50%;
      width: 2.8rem;
      height: 2.8rem;
      font-size: 1.25rem;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .category-bar {
      background-color: transparent;
      padding: 2rem 0;
      text-align: center;
    }

    .category-bar-inner {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      justify-content: center;
    }

    .category-box {
      padding: 12px 24px;
      border: 2px solid white;
      border-radius: 12px;
      font-size: 18px;
      font-weight: 600;
      color: white;
      background-color: rgba(0, 0, 0, 0.1);
      width: 240px;
      text-align: center;
      transition: all 0.3s ease;
      animation: fadeInUp 0.8s ease;

    }

    .category-box:hover,
    .category-box.active {
      background-color: white;
      color: #d4af37;
      transform: scale(1.05);
      cursor: pointer;
    }

    section h2 {
      font-size: 2.3rem;
      color: #1e1e1e;
      margin-bottom: 1.5rem;
      border-left: 5px solid #e63946;
      padding-left: 1rem;
      animation: fadeInUp 1s ease;

    }

    section h3 {
      font-size: 1.4rem;
      color: #333;
      margin-bottom: 1rem;
      font-weight: 600;
    }

    .items-grid {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .item-card {
      background: #fffdf7;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
      transition: transform 0.25s ease, box-shadow 0.25s ease;
      padding: 1rem;
    }

    .item-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 10px 24px rgba(0, 0, 0, 0.1);
    }

    .menu-line {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 1.1rem;
      font-weight: 600;
      padding: 0.5rem 0;
      
      color: #111;
    }

    .menu-name {
      flex: 1;
      white-space: nowrap;
    }

   
    .menu-price {
      white-space: nowrap;
      color: #e63946;
      font-weight: bold;
    }

    .add-to-cart-btn {
      position: absolute;
      bottom: 10px;
      right: 10px;
      background: #e63946;
      color: #fff;
      border: none;
      border-radius: 50%;
      width: 40px;
      height: 40px;
      font-size: 18px;
      cursor: pointer;
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 0.3s ease;
    }

    .add-to-cart-btn:hover {
      background: #d62839;
    }

    #topBtn {

      position: fixed;
      bottom: 30px;
      right: 30px;
      z-index: 99;
      font-size: 18px;
      border: none;
      outline: none;
      background-color: black;
      color: white;
      cursor: pointer;
      padding: 14px 18px;
      border-radius: 50%;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
      transition: all 0.3s ease;
    }

    #topBtn:hover {
      background-color: #c92030;
    }


    @media (max-width: 768px) {
      .hero .overlay h1 {
        font-size: 2.4rem;
      }

      .hero .overlay p {
        font-size: 1rem;
      }

      .hero-controls {
        flex-direction: column;
        align-items: flex-end;
      }

      .hero-controls .btn {
        padding: 0.4rem 0.9rem;
      }

      .category-bar-inner {
        padding: 0 1rem;
        gap: 0.8rem;
      }

      .item-card {
        padding: 0.8rem;
        animation: fadeInUp 0.7s ease forwards;

      }
    }

    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes zoomIn {
      from {
        transform: scale(0.9);
        opacity: 0;
      }

      to {
        transform: scale(1);
        opacity: 1;
      }
    }

    @media (max-width: 576px) {
      section h2 {
        font-size: 1.6rem;
        padding-left: 0.6rem;
      }

      .category-box {
        font-size: 16px;
        padding: 10px 16px;
        width: 90%;
      }

      .menu-line {
        flex-direction: column;
        align-items: flex-start;
      }

      .menu-name,
      .menu-price {
        font-size: 1rem;
      }

      .hero {
        height: 40vh;
        background-position: top;
      }

      .hero-controls {
        top: 10px;
        right: 10px;
        gap: 0.3rem;
      }

      .hero-controls .btn {
        font-size: 0.85rem;
        padding: 0.3rem 0.6rem;
      }

      .btn.cart {
        width: 2.3rem;
        height: 2.3rem;
        font-size: 1rem;
      }

      .item-card {
        padding: 0.6rem;
      }

      .menu-price {
        font-size: 1rem;
      }
    }

    footer a:hover {
      text-decoration: underline;
    }

    @media (max-width: 576px) {
      .footer {
        font-size: 0.85rem;
        padding: 1.2rem 0.6rem;
      }

      .footer-lang {
        margin-top: 0.5rem;
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 0.5rem;
      }
    }
  </style>

</head>

<body>

  <!-- HERO SECTION -->
  <section class="hero">
    <div class="hero-controls">
      <a href="admin_login.php" class="btn"><?= $lang === 'ar' ? 'تسجيل الدخول' : 'Admin Login' ?></a>


    </div>

  </section>

  <!-- CATEGORY NAVIGATION BAR -->
  <div class="category-bar">
    <div class="category-bar-inner">
      <?php foreach ($cats as $c): ?>
        <a href="#cat-<?= $c['id'] ?>" class="category-box">
          <span><?= htmlspecialchars($c['name']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>


  <!-- FULL MENU DISPLAY -->
  <div style="max-width:1200px; margin:2rem auto; padding:0 20px;">
    <?php foreach ($cats as $c): ?>
      <section id="cat-<?= $c['id'] ?>" style="margin-bottom:3rem;">
        <h2><?= htmlspecialchars($c['name']) ?></h2>

        <?php if (strtolower($c['name']) === 'plates'): ?>
          <p style="font-size: 2rem; color: #444; margin: 0.5rem 0 1.5rem;">
            All plates include coleslaw, fries, and sauce.
          </p>
        <?php endif; ?>




        <?php if ($c['banner_image']): ?>
          <img src="<?= htmlspecialchars($c['banner_image']) ?>" alt="<?= htmlspecialchars($c['name']) ?> Banner"
            style="width:100%; max-width:100%; border-radius:12px; margin:1rem 0;">
        <?php endif; ?>



        <?php foreach ($c['subs'] as $s): ?>
          <div style="margin-bottom:2rem;">
            <h3><?= htmlspecialchars($s['name']) ?></h3>
            <div class="items-grid">
              <?php
              $subId = (int)$s['id'];
              $sql = "
                            SELECT mi.name, mi.description, mi.price,
                                COALESCE((
                                    SELECT image_url 
                                    FROM menu_images 
                                    WHERE menu_item_id=mi.id AND is_main=1 
                                    LIMIT 1
                                ), '') AS image_url
                            FROM menu_items mi
                            WHERE mi.subcategory_id = $subId
                            ORDER BY mi.name
                        ";
              $res = $conn->query($sql);
              while ($it = $res->fetch_assoc()):
              ?>
              <div class="item-card" style="
  display: flex;
  align-items: center;
  gap: 1rem;
  background: white;
  padding: 1rem;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  overflow: hidden;
">
  <!-- Left: Image -->
  <img src="<?= htmlspecialchars($it['image_url']) ?>" alt="<?= htmlspecialchars($it['name']) ?>" style="width: 80px; height: 80px; border-radius: 8px; object-fit: cover;">

  <!-- Center: Name & Description -->
  <div style="flex: 1;">
    <div class="menu-name" style="font-weight: bold; margin-bottom: 4px; cursor: pointer;"
      onclick="showImageModal('<?= htmlspecialchars($it['image_url']) ?>', '<?= htmlspecialchars($it['name']) ?>', '<?= htmlspecialchars($it['description']) ?>')">
      <?= htmlspecialchars($it['name']) ?>
    </div>
    <?php if (!empty($it['description'])): ?>
      <small style="color: #555; font-size: 0.85rem;">
        <?= htmlspecialchars($it['description']) ?>
      </small>
    <?php endif; ?>
  </div>

  <!-- Right: Price -->
  <div class="menu-price" style="font-weight: bold; color:#e63946; font-size: 1.1rem; white-space: nowrap;">
    <?php
      if (strpos($it['price'], '/') !== false) {
        echo htmlspecialchars($it['price']);
      } else {
        echo '$' . number_format((float)$it['price'], 2);
      }
    ?>
  </div>
</div>

              <?php endwhile; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </section>
    <?php endforeach; ?>
  </div>

  <div id="imageModal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.7); justify-content:center; align-items:center;">
  <div style="
  background: white;
  padding: 20px;
  border-radius: 10px;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  text-align: center;
  position: relative;
  overflow-y: auto;
  box-sizing: border-box;
">

      <span onclick="closeImageModal()" style="position:absolute; top:10px; right:15px; font-size:20px; font-weight:bold; cursor:pointer;">&times;</span>
     <h3 id="imageTitle" style="margin-bottom:1rem;"></h3>
<img id="modalImage" src="" alt="Menu Item" style="max-width:100%; max-height:70vh; border-radius:10px;">
<p id="imageDescription" style="margin-top:1rem; font-size:1rem; color:#333;"></p>

    </div>
  </div>
  <script>
    function showImageModal(url, title, description) {
  if (!url) return;
  document.getElementById("modalImage").src = url;
  document.getElementById("imageTitle").textContent = title;
  document.getElementById("imageDescription").textContent = description || '';
  document.getElementById("imageModal").style.display = "flex";
}


    function closeImageModal() {
      document.getElementById("imageModal").style.display = "none";
    }
  </script>

</body>
<!-- FOOTER -->
<footer style="background: url('images/back.jpg'); color: #000; padding: 2rem 1rem; text-align: center; font-size: 0.95rem;">
  <div style="max-width: 1200px; margin: 0 auto;">
    <p>&copy; <?= date('Y') ?> </p>
    <p>Mr. Farouj</p>
    <p>Made with ❤️ in Lebanon</p>
    <p>@Kali.dev</p>
    <button id="topBtn" title="Go to top">↑</button>
  </div>
</footer>

<script>
  // Show button when scrolled down
  window.onscroll = function() {
    const btn = document.getElementById("topBtn");
    if (document.body.scrollTop > 100 || document.documentElement.scrollTop > 100) {
      btn.style.display = "block";
    } else {
      btn.style.display = "none";
    }
  };

  // Scroll to top when clicked
  document.getElementById("topBtn").onclick = function() {
    window.scrollTo({
      top: 0,
      behavior: 'smooth'
    });
  };
</script>



</html>