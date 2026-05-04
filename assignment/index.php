<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
    <title>BAS3NJI WORLD.CLO</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- LAYOUT: Taskbar + Main content -->
<div class="layout">
    <aside class="taskbar">
        <div class="tb-top">
                <img src="images/logo.jpeg" alt="Pastimes logo" class="tb-logo-img">
                <h1 class="tb-title">Pastimes</h1>
        </div>

        <form class="search" action="products.php" method="get">
            <input type="text" name="q" placeholder="Search products...">
        </form>

        <nav class="tb-nav">
            <a href="index.php">Home</a>
            <a href="shop.php">Shop</a>
            <a href="cart.php">Cart</a>
            <a href="contact.php">Contact</a>
            <?php if(isset($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'seller'): ?>
                <a href="seller_dashboard.php">Seller Dashboard</a>
            <?php endif; ?>
            <?php if(isset($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'admin'): ?>
                <a href="adminDashboard.php">Admin</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        </nav>

        <div class="tb-actions">
            <a class="cart-link" href="cart.php">🛒 Cart</a>
        </div>

        <?php if(isset($_SESSION['user'])): ?>
            <div class="tb-user">Hello, <?php echo htmlspecialchars($_SESSION['user']['name']); ?> <a href="logout.php">Logout</a></div>
        <?php else: ?>
            <a class="login-link btn" href="login.php">Login</a>
        <?php endif; ?>

        <div class="newsletter">
            <h4>Stay Updated</h4>
            <form action="" method="post">
                <input type="email" name="newsletter_email" placeholder="Your email">
                <button class="btn" type="submit">Subscribe</button>
            </form>
        </div>
    </aside>

    <main class="main-content">
        <!-- HERO SECTION -->
        <section class="hero">
            <div class="hero-center">
                <img src="images/logo.jpeg" alt="BASENJI logo" class="site-logo">
                <p class="hero-desc">High-end South African streetwear blending bold urban aesthetics with luxury craftsmanship. BASENJI world.clo redefines modern style through culture, quality, and character.</p>
                <a href="shop.php" class="btn">Shop Now</a>
            </div>
        </section>

        <!-- FEATURED PRODUCTS -->
        <section class="featured">
            <h2>Featured Collection</h2>

            <div class="featured-products">
                <?php
                $images = glob(__DIR__ . '/images/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
                foreach($images as $imgPath) {
                    $file = basename($imgPath);
                    echo "<div class=\"hover-card\" style=\"background-image:url('images/".htmlspecialchars($file)."')\">";
                    echo "<div class=\"hover-overlay\"><h3>".htmlspecialchars(pathinfo($file, PATHINFO_FILENAME))."</h3></div>";
                    echo "</div>";
                }
                ?>
            </div>
        </section>

        <!-- FOOTER -->
        <footer>
            <p>© 2026 BAS3NJI Clothing</p>
        </footer>
    </main>
</div>

<!-- FOOTER -->
<footer>
    <p>© 2026 BAS3NJI Clothing</p>
</footer>

</body>
</html>