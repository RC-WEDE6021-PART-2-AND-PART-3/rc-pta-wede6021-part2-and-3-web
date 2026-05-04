<?php
include "DBConn.php";
session_start();

if(!isset($_SESSION['userID'])){
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['userID'];

/* -------------------------
   GET USER ADDRESS
-------------------------- */
$userQ = $conn->prepare("SELECT address FROM tblUser WHERE userID=?");
$userQ->bind_param("i",$userID);
$userQ->execute();
$userRes = $userQ->get_result();
$userData = $userRes->fetch_assoc();

$currentAddress = $userData['address'] ?? "";

/* -------------------------
   GET CART ITEMS
-------------------------- */
$sql = "
SELECT o.orderID, o.quantity,
       c.clothID, c.clothName, c.price
FROM tblAorder o
JOIN tblClothes c ON o.clothID = c.clothID
WHERE o.userID = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$userID);
$stmt->execute();
$result = $stmt->get_result();

$cartItems = [];
$total = 0;
$error = '';

while($row = $result->fetch_assoc()){
    $row['subtotal'] = $row['price'] * $row['quantity'];
    $total += $row['subtotal'];
    $cartItems[] = $row;
}

/* -------------------------
   CHECKOUT
-------------------------- */
if(isset($_POST['checkout'])){

    $address = trim($_POST['address']);

    if(empty($address)){
        $error = "Address is required!";
    } elseif(empty($cartItems)){
        $error = "Cart is empty!";
    } else {

        // SAVE ADDRESS TO USER PROFILE
        $stmt = $conn->prepare("UPDATE tblUser SET address=? WHERE userID=?");
        $stmt->bind_param("si",$address,$userID);
        $stmt->execute();

        // CREATE ORDER
        $stmt = $conn->prepare("INSERT INTO tblOrder(userID, total, address) VALUES(?,?,?)");
        $stmt->bind_param("ids",$userID,$total,$address);
        $stmt->execute();

        $orderID = $stmt->insert_id;

        // INSERT ITEMS
        foreach($cartItems as $item){
            $stmt = $conn->prepare("
                INSERT INTO tblOrderItems(orderID, clothID, quantity, price)
                VALUES(?,?,?,?)
            ");
            $stmt->bind_param(
                "iiid",
                $orderID,
                $item['clothID'],
                $item['quantity'],
                $item['price']
            );
            $stmt->execute();
        }

        // CLEAR CART
        $conn->query("DELETE FROM tblAorder WHERE userID=$userID");

        header("Location: success.php");
        exit();
    }
}
?>

<link rel="stylesheet" href="style.css">

<div class="layout">

<div class="taskbar">
    <h2>Clothing Store</h2>
    <div class="tb-nav">
        <a href="shop.php">Shop</a>
        <a href="cart.php">Cart</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="main-content">

<h2 class="title">Checkout</h2>

<div class="payment-container">

<?php if(!empty($error)): ?>
<div class="errors"><?= $error ?></div>
<?php endif; ?>

<?php if(!empty($cartItems)): ?>

<div class="summary">
    <h3>Order Summary</h3>

    <?php foreach($cartItems as $item): ?>
        <p>
            <?= $item['clothName'] ?> 
            (x<?= $item['quantity'] ?>)
            - R<?= $item['subtotal'] ?>
        </p>
    <?php endforeach; ?>

    <h2>Total: R<?= $total ?></h2>
</div>

<form method="POST" class="payment-form">

    <!-- ✅ ADDRESS FIELD -->
    <input type="text" name="address"
           placeholder="Delivery Address"
           value="<?= htmlspecialchars($currentAddress) ?>"
           required>

    <!-- Fake payment fields -->
    <input type="text" placeholder="Card Number" required>
    <input type="text" placeholder="Expiry Date" required>
    <input type="text" placeholder="CVV" required>

    <button name="checkout">Place Order</button>
</form>

<?php else: ?>
<p>Your cart is empty.</p>
<?php endif; ?>

</div>

</div>
</div>