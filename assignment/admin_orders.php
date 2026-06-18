<?php
session_start();
require_once "DBConn.php";

if (empty($_SESSION['userID'])) {
    header("Location: login.php");
    exit();
}

$userID = (int)$_SESSION['userID'];

// check admin role from DB (use bind_result for compatibility)
$role = '';
if ($ur = $conn->prepare('SELECT role FROM tblUser WHERE userID = ? LIMIT 1')) {
    $ur->bind_param('i', $userID);
    $ur->execute();
    $ur->bind_result($role);
    $ur->fetch();
    $ur->close();
}
if (($role ?? '') !== 'admin') {
    echo 'Access denied';
    exit;
}

// handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['orderID']) && isset($_POST['status'])) {
    $oid = (int)$_POST['orderID'];
    $status = (string)$_POST['status'];

    // determine actual orders table name
    $orderTable = null;
    $candidates = ['tblAorder','tblaorder','tblAOrder','tblAorders','tblOrder','tblorder'];
    foreach ($candidates as $cand) {
        $tres = $conn->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . $conn->real_escape_string($cand) . "' LIMIT 1");
        if ($tres && $tres->fetch_row()) { $orderTable = $cand; break; }
    }

    // if orders table found, ensure it has a `status` column before updating
    if ($orderTable) {
        $hasStatus = false;
        $cres = $conn->query("SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . $conn->real_escape_string($orderTable) . "' AND COLUMN_NAME = 'status'");
        if ($cres) { $crow = $cres->fetch_assoc(); $hasStatus = (!empty($crow['c']) && $crow['c'] > 0); }

        // whitelist acceptable statuses
        $allowed = array('paid','processing','shipped','out_for_delivery','delivered');
        if ($hasStatus && in_array($status, $allowed, true)) {
            $sql = "UPDATE `" . $orderTable . "` SET status = ? WHERE orderID = ?";
            if ($u = $conn->prepare($sql)) {
                $u->bind_param('si', $status, $oid);
                $u->execute();
                $u->close();
            }
        }
    }

    header('Location: admin_orders.php');
    exit;
}

// load orders
$orderItemsTable = null;
$candidatesItems = ['tblAorderItems','tblaorderitems','tblAorderitems','tblOrderItems','tblorderitems'];
foreach ($candidatesItems as $cand) {
    $tres = $conn->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . $conn->real_escape_string($cand) . "' LIMIT 1");
    if ($tres && $tres->fetch_row()) { $orderItemsTable = $cand; break; }
}

$orderTable = $orderTable ?? null;
if (empty($orderTable)) {
    // try to find order table if not found earlier
    $candidates = ['tblAorder','tblaorder','tblAOrder','tblAorders','tblOrder','tblorder'];
    foreach ($candidates as $cand) {
        $tres = $conn->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . $conn->real_escape_string($cand) . "' LIMIT 1");
        if ($tres && $tres->fetch_row()) { $orderTable = $cand; break; }
    }
}

if (empty($orderTable)) {
    echo 'Orders table not found in database.';
    exit;
}

// detect if orders table has a `status` column
$order_has_status = false;
$cres = $conn->query("SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . $conn->real_escape_string($orderTable) . "' AND COLUMN_NAME = 'status'");
if ($cres) { $crow = $cres->fetch_assoc(); $order_has_status = (!empty($crow['c']) && $crow['c'] > 0); }

$itemsSub = ($orderItemsTable) ? "COALESCE((SELECT SUM(ai.price * ai.quantity) FROM `" . $orderItemsTable . "` ai WHERE ai.orderID = o.orderID), 0) AS total" : "0 AS total";
$statusSelect = $order_has_status ? 'o.status' : "'' AS status";
$orders = $conn->query("SELECT o.orderID, o.userID, " . $itemsSub . ", " . $statusSelect . ", u.username, u.email FROM `" . $orderTable . "` o JOIN tblUser u ON u.userID = o.userID ORDER BY o.orderID DESC");

$statuses = ['paid'=>'Payment received','processing'=>'Processing','shipped'=>'Shipped','out_for_delivery'=>'Out for delivery','delivered'=>'Delivered'];
?>
<link rel="stylesheet" href="style.css">
<div class="layout">
    <div class="main-content">
        <h2>Manage Orders</h2>
        <?php if ($orders && $orders->num_rows): ?>
            <table style="width:100%;border-collapse:collapse;">
                <thead><tr><th>Order</th><th>User</th><th>Total</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                <?php while ($o = $orders->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $o['orderID']; ?></td>
                        <td><?php echo htmlspecialchars($o['username']) . '<br><small>' . htmlspecialchars($o['email']) . '</small>'; ?></td>
                        <td><?php echo 'R' . number_format((float)$o['total'],2); ?></td>
                        <td><?php echo htmlspecialchars($o['status'] ?: 'paid'); ?></td>
                        <td>
                            <form method="post" style="display:flex;gap:6px;align-items:center;">
                                <input type="hidden" name="orderID" value="<?php echo $o['orderID']; ?>">
                                <select name="status">
                                    <?php foreach ($statuses as $k => $label): ?>
                                        <option value="<?php echo $k; ?>" <?php echo ($o['status']==$k)?'selected':''; ?>><?php echo htmlspecialchars($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No orders found.</p>
        <?php endif; ?>
    </div>
</div>
