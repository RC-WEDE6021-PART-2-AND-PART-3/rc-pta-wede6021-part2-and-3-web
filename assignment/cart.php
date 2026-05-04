<?php
include "DBConn.php";
session_start();

if(!isset($_SESSION['userID'])){
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['userID'];

/* REMOVE */
if(isset($_GET['remove'])){
    $id = (int)$_GET['remove'];
    $conn->query("DELETE FROM tblAorder WHERE orderID=$id AND userID=$userID");
    header("Location: cart.php");
    exit();
}

/* LOAD CART */
$sql = "
SELECT o.orderID, o.quantity,
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

<link rel="stylesheet" href="style.css">

<div class="layout">

<div class="taskbar">
    <h2>Clothing Store</h2>
    <div class="tb-nav">
        <a href="index.php">Home</a>
        <a href="shop.php">Shop</a>
        <a href="cart.php">Cart</a>
        <a href="contact.php">Contact</a>
        <?php if(!empty($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'seller'): ?>
            <a href="seller_dashboard.php">Seller Dashboard</a>
        <?php else: ?>
            <a href="sellers.php">Become a Seller</a>
        <?php endif; ?>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="main-content">

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
        <h3><?= htmlspecialchars($row['clothName']) ?></h3>
        <p>Price: R<span class="price"><?= $row['price'] ?></span></p>
        <p>Subtotal: R<span class="subtotal">0</span></p>
    </div>

    <div>
        <input type="number"
               class="qty"
               value="<?= $row['quantity'] ?>"
               min="1">

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
</div>
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