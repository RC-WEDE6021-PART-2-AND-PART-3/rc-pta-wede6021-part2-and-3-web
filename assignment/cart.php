<?php
include "DBConn.php";
session_start();

if(!isset($_SESSION['userID'])){
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['userID'];

// handle update quantity POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $orderID = (int)($_POST['orderID'] ?? 0);
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    if ($orderID > 0) {
        $u = $conn->prepare('UPDATE tblAorder SET quantity = ? WHERE orderID = ? AND userID = ?');
        $u->bind_param('iii', $qty, $orderID, $userID);
        $u->execute();
        $u->close();
    }
    header('Location: cart.php');
    exit();
}

/* REMOVE */
if(isset($_GET['remove'])){
    $id = (int)$_GET['remove'];
    $conn->query("DELETE FROM tblAorder WHERE orderID=$id AND userID=$userID");
    header("Location: cart.php");
    exit();
}

/* LOAD CART */
// detect whether tblAorder has a `size` column
$has_size_column = false;
$cres = @$conn->query("SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblAorder' AND COLUMN_NAME = 'size'");
if ($cres) { 
    $crow = $cres->fetch_assoc(); 
    $has_size_column = (!empty($crow['c']) && $crow['c'] > 0); 
}

$sql = "
SELECT o.orderID, o.quantity" . ($has_size_column ? ", o.size" : "") . ",
       c.clothName, c.price, c.image
FROM tblAorder o
JOIN tblClothes c ON o.clothID = c.clothID
WHERE o.userID = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$userID);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart</title>
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
        <h2 class="title">Your Cart</h2>

        <div class="cart">

<?php if($result->num_rows > 0): ?>

<?php while($row = $result->fetch_assoc()): ?>

<div class="cart-item">

    <div>
        <img src="images/<?= rawurlencode($row['image']) ?>"
             style="width:80px;border-radius:8px;">
    </div>

    <div>
        <h3><?= htmlspecialchars($row['clothName']) ?><?php if(!empty($row['size'])): ?> <small style="font-weight:normal;">(Size: <?= htmlspecialchars($row['size']) ?>)</small><?php endif; ?></h3>
        <p>Price: R<span class="price"><?= $row['price'] ?></span></p>
        <p>Subtotal: R<span class="subtotal">0</span></p>
    </div>

    <div>
        <form method="post" style="display:inline-block;">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="orderID" value="<?= (int)$row['orderID'] ?>">
            <input type="number"
                   class="qty"
                   name="qty"
                   value="<?= $row['quantity'] ?>"
                   min="1">
            <button type="submit" class="btn" style="margin-left:8px;">Update</button>
        </form>

        <br><br>

        <a href="cart.php?remove=<?= $row['orderID'] ?>" class="remove">
            Remove
        </a>
    </div>

</div>

<?php endwhile; ?>

<h2 style="text-align:center;margin-top:20px;">
    Total: R<span id="total">0</span>
</h2>

<div style="text-align:center; margin-top:20px;">
    <a href="checkout.php" class="btn checkout-btn">Checkout</a>
</div>

<?php else: ?>
<p style="text-align:center;">Your cart is empty.</p>
<?php endif; ?>

</div>
        </main>

    </div>

<!-- AUTO CALCULATION SCRIPT -->
<script>
function updateCart() {
    let total = 0;

    document.querySelectorAll('.cart-item').forEach(item => {
        let price = parseFloat(item.querySelector('.price').innerText);
        let qty = parseInt(item.querySelector('.qty').value);

        let subtotal = price * qty;
        item.querySelector('.subtotal').innerText = subtotal.toFixed(2);

        total += subtotal;
    });

    document.getElementById('total').innerText = total.toFixed(2);
}

// Run on load
updateCart();

// Run when quantity changes
document.querySelectorAll('.qty').forEach(input => {
    input.addEventListener('input', updateCart);
});
</script>

</body>
</html>