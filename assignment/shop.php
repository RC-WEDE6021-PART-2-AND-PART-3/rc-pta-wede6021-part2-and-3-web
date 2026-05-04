<?php
session_start();
include "DBConn.php";

if (!isset($_SESSION['userID'])) {
    header("Location: login.php");
    exit;
}

$userID = (int)$_SESSION['userID'];
$message = '';
$error = '';

function getAvailableImages() {
    return glob(__DIR__ . '/images/*.{jpg,jpeg,png,gif}', GLOB_BRACE) ?: [];
}

if (isset($_GET['add'])) {
    $clothID = (int)$_GET['add'];

    $stmt = $conn->prepare("SELECT clothID FROM tblClothes WHERE clothID = ?");
    $stmt->bind_param("i", $clothID);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        $error = "Invalid product selected.";
    } else {
        $stmt->close();

        $stmt = $conn->prepare("SELECT orderID, quantity FROM tblAorder WHERE userID = ? AND clothID = ?");
        $stmt->bind_param("ii", $userID, $clothID);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $newQty = $row['quantity'] + 1;
            $update = $conn->prepare("UPDATE tblAorder SET quantity = ? WHERE orderID = ? AND userID = ?");
            $update->bind_param("iii", $newQty, $row['orderID'], $userID);
            if ($update->execute()) {
                $message = "Item quantity updated in cart.";
            } else {
                $error = "Could not update cart.";
            }
            $update->close();
        } else {
            $insert = $conn->prepare("INSERT INTO tblAorder (userID, clothID, quantity) VALUES (?, ?, 1)");
            $insert->bind_param("ii", $userID, $clothID);
            if ($insert->execute()) {
                $message = "Item added to cart.";
            } else {
                $error = "Could not add item to cart.";
            }
            $insert->close();
        }

        $stmt->close();
    }
}

$clothes = $conn->query("SELECT * FROM tblClothes");
if ($clothes === false) {
    die("Database error loading products: " . $conn->error);
}

if ($clothes->num_rows === 0) {
    $images = getAvailableImages();
    if (!empty($images)) {
        $insert = $conn->prepare("INSERT INTO tblClothes (clothName, price, image) VALUES (?, ?, ?)");
        foreach ($images as $path) {
            $filename = basename($path);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $price = 99.99;
            $insert->bind_param("sds", $name, $price, $filename);
            $insert->execute();
        }
        $insert->close();
    }
    $clothes = $conn->query("SELECT * FROM tblClothes");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop | Pastimes</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="layout">
    <aside class="taskbar">
        <div class="tb-top">
            <img src="images/logo.jpeg" alt="Pastimes logo" class="tb-logo-img">
            <h2 class="tb-title">Pastimes</h2>
        </div>

        <nav class="tb-nav">
            <a href="index.php">Home</a>
            <a href="shop.php">Shop</a>
            <a href="cart.php">Cart</a>
            <a href="contact.php">Contact</a>
            <?php if(!empty($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'seller'): ?>
                <a href="seller_dashboard.php">Seller Dashboard</a>
            <?php else: ?>
                <a href="sellers.php">Become a Seller</a>
            <?php endif; ?>
            <?php if(!empty($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin'): ?>
                <a href="adminDashboard.php">Admin</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        </nav>

        <div class="tb-user">
            <?php if (!empty($_SESSION['name'])): ?>
                Hello, <?= htmlspecialchars($_SESSION['name']) ?>
            <?php else: ?>
                Logged in
            <?php endif; ?>
        </div>
    </aside>

    <main class="main-content">
        <h1 class="title">Shop All Products</h1>
        <?php if ($message): ?>
            <div class="success" style="max-width:900px;margin:10px auto;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="errors" style="max-width:900px;margin:10px auto;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="products">
            <?php if ($clothes->num_rows === 0): ?>
                <p style="text-align:center; color:#ddd;">No products are available yet.</p>
            <?php else: ?>
                <?php while ($c = $clothes->fetch_assoc()): ?>
                    <?php $img = !empty($c['image']) ? $c['image'] : 'placeholder.png'; ?>
                    <div class="item">
                        <div class="badge">NEW</div>
                        <img src="images/<?= rawurlencode($img) ?>"
                             alt="<?= htmlspecialchars($c['clothName']) ?>"
                             class="product-img">
                        <h3><?= htmlspecialchars($c['clothName']) ?></h3>
                        <p>R<?= number_format((float)$c['price'], 2) ?></p>
                        <a class="btn" href="shop.php?add=<?= (int)$c['clothID'] ?>">Add to Cart</a>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

        <div style="text-align:center; margin:30px 0;">
            <a class="btn cart-btn" href="cart.php">Go to Cart</a>
        </div>
    </main>
</div>
<footer>
            <p>© 2026 Pastimes Clothing</p>
        </footer>
    </main>
</div>

<!-- FOOTER -->
<footer>
    <p>© 2026 Pastimes Clothing</p>
</footer>

</body>
</html>
