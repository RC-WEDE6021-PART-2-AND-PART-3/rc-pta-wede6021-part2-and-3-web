<?php
include "DBConn.php";
session_start();

if(!isset($_SESSION['userID'])){
    header("Location: login.php");
    exit();
}

$userID = (int)$_SESSION['userID'];

// check admin role from DB
$ur = $conn->prepare('SELECT role FROM tblUser WHERE userID = ? LIMIT 1');
$ur->bind_param('i', $userID);
$ur->execute();
$r = $ur->get_result()->fetch_assoc();
$ur->close();
if (($r['role'] ?? '') !== 'admin') {
    echo 'Access denied'; exit;
}

// handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['orderID']) && !empty($_POST['status'])) {
    $oid = (int)$_POST['orderID'];
    $status = $_POST['status'];
    $u = $conn->prepare('UPDATE tblOrder SET status = ? WHERE orderID = ?');
    $u->bind_param('si', $status, $oid);
    $u->execute();
    $u->close();
    header('Location: admin_orders.php');
    exit;
}

// load orders
$orders = $conn->query('SELECT o.orderID, o.userID, o.total, o.status, u.username, u.email FROM tblOrder o JOIN tblUser u ON u.userID = o.userID ORDER BY o.orderID DESC');

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
