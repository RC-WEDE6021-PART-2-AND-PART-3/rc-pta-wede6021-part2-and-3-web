<?php
include "DBConn.php";
session_start();

if(!isset($_SESSION['userID'])){
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['userID'];

/* -------------------------
   GET USER ADDRESS & CONTACT
   (defensive: handle missing columns)
-------------------------- */
$currentAddress = '';
$userEmail = '';
$userFullname = '';

// check which columns exist on tblUser
$cols = [];
$cres = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblUser' AND COLUMN_NAME IN ('address','email','fullname')");
if ($cres) {
    while ($crow = $cres->fetch_assoc()) {
        $cols[] = $crow['COLUMN_NAME'];
    }
}

if (!empty($cols)) {
    $selectCols = implode(', ', $cols);
    $userQ = $conn->prepare("SELECT $selectCols FROM tblUser WHERE userID=?");
    $userQ->bind_param("i", $userID);
    $userQ->execute();
    $userRes = $userQ->get_result();
    if ($userRes) {
        $userData = $userRes->fetch_assoc();
        if (in_array('address', $cols)) $currentAddress = $userData['address'] ?? '';
        if (in_array('email', $cols)) $userEmail = $userData['email'] ?? '';
        if (in_array('fullname', $cols)) $userFullname = $userData['fullname'] ?? '';
    }
}

/* -------------------------
   GET CART ITEMS (include size if tblAorder.size exists)
-------------------------- */
$aorder_has_size = false;
$ares = $conn->query("SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblAorder' AND COLUMN_NAME = 'size'");
if ($ares) { $arow = $ares->fetch_assoc(); $aorder_has_size = (!empty($arow['c']) && $arow['c'] > 0); }

if ($aorder_has_size) {
    $sql = "SELECT o.orderID, o.quantity, o.size, c.clothID, c.clothName, c.price FROM tblAorder o JOIN tblClothes c ON o.clothID = c.clothID WHERE o.userID = ?";
} else {
    $sql = "SELECT o.orderID, o.quantity, c.clothID, c.clothName, c.price FROM tblAorder o JOIN tblClothes c ON o.clothID = c.clothID WHERE o.userID = ?";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
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

// detect whether tblaorderItems supports a `size` column
$order_items_has_size = false;
$ores = $conn->query("SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblaorderItems' AND COLUMN_NAME = 'size'");
if ($ores) { $orow = $ores->fetch_assoc(); $order_items_has_size = (!empty($orow['c']) && $orow['c'] > 0); }

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
        $stmt = $conn->prepare("INSERT INTO tblaorder(userID, total, address) VALUES(?,?,?)");
        $stmt->bind_param("ids",$userID,$total,$address);
        $stmt->execute();

        $orderID = $stmt->insert_id;

        // INSERT ITEMS (include size if supported)
        foreach($cartItems as $item){
            if ($order_items_has_size) {
                $stmt = $conn->prepare("\
                    INSERT INTO tblaorderItems(orderID, clothID, quantity, price, size)\
                    VALUES(?,?,?,?,?)\
                ");
                $size = $item['size'] ?? null;
                $stmt->bind_param(
                    "iiids",
                    $orderID,
                    $item['clothID'],
                    $item['quantity'],
                    $item['price'],
                    $size
                );
            } else {
                $stmt = $conn->prepare("\
                    INSERT INTO tblaorderItems(orderID, clothID, quantity, price)\
                    VALUES(?,?,?,?)\
                ");
                $stmt->bind_param(
                    "iiid",
                    $orderID,
                    $item['clothID'],
                    $item['quantity'],
                    $item['price']
                );
            }
            $stmt->execute();
            $stmt->close();
        }

        // CLEAR CART
        $conn->query("DELETE FROM tblAorder WHERE userID=$userID");

        // Send order confirmation email (best-effort)
        if (!empty($userEmail)) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $basePath = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
            $trackingUrl = $protocol . '://' . $host . $basePath . '/order_tracking.php?id=' . $orderID;

            $subject = 'Order confirmation — #' . $orderID;
            $message = "Hello " . ($userFullname ?: "Customer") . ",\n\n";
            $message .= "Thank you for your order. Your order number is #" . $orderID . " and the total is R" . number_format($total,2) . ".\n";
            $message .= "You can track your order here: " . $trackingUrl . "\n\n";
            $message .= "If you have any questions, reply to this email.\n\nThanks,\nPastimes Clothing";

            // Use wrapper (prefers PHPMailer when configured)
            $mailer = __DIR__ . '/lib/mailer.php';
            if (file_exists($mailer)) require_once $mailer;
            if (function_exists('send_mail')) {
                @send_mail($userEmail, $subject, $message);
            } else {
                @mail($userEmail, $subject, $message);
            }
        }

        // Redirect to tracking page with order id
        header("Location: order_tracking.php?id=" . $orderID);
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
            <?= $item['clothName'] ?> <?php if(!empty($item['size'])): ?>(Size: <?= htmlspecialchars($item['size']) ?>)<?php endif; ?>
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