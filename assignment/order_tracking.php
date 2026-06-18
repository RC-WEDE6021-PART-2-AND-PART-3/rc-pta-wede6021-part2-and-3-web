<?php
include "DBConn.php";
session_start();

if(!isset($_SESSION['userID'])){
    header("Location: login.php");
    exit();
}



$status = $order['status'] ?? 'paid';
$steps = ['paid'=>'Payment received','processing'=>'Processing','shipped'=>'Shipped','out_for_delivery'=>'Out for delivery','delivered'=>'Delivered'];
$keys = array_keys($steps);
$currentIndex = array_search($status, $keys);
if ($currentIndex === false) $currentIndex = 0;

?>
<link rel="stylesheet" href="style.css">
<div class="layout">
    <div class="main-content">
        <h2>Order Tracking</h2>
        <p>Your order number: <strong>#<?php echo htmlspecialchars($orderID); ?></strong></p>
        <p>Current status: <strong><?php echo htmlspecialchars(ucwords(str_replace('_',' ',$status))); ?></strong></p>

        <div style="display:flex;gap:12px;">
        <?php foreach ($keys as $i => $k):
            $class = '';
            if ($i < $currentIndex) $class = 'done';
            elseif ($i === $currentIndex) $class = 'current';
        ?>
            <div style="flex:1;padding:12px;border-radius:6px;text-align:center;<?php echo $class==='done' ? 'background:#4caf50;color:#fff;' : ($class==='current' ? 'background:#2196f3;color:#fff;' : 'background:#f2f2f2;'); ?>">
                <?php echo htmlspecialchars($steps[$k]); ?>
            </div>
        <?php endforeach; ?>
        </div>

        <p style="margin-top:16px;color:#666">We'll email you updates about this order. Contact support if needed.</p>
        <p><a href="shop.php">Continue shopping</a></p>
    </div>
</div>
