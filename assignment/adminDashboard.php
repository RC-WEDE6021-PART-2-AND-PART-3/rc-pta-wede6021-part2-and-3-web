<?php
include "DBConn.php";
session_start();

/* -------------------------
   SECURITY
-------------------------- */
if(!isset($_SESSION['adminID'])){
    header("Location: adminLogin.php");
    exit();
}

$success = "";
$error = "";
$sellersFile = __DIR__ . '/sellers.json';

/* -------------------------
   ACTIONS
-------------------------- */

// APPROVE SELLER
if(isset($_POST['approve_seller'])){
    $index = (int)$_POST['seller_index'];
    $sellers = file_exists($sellersFile) ? json_decode(file_get_contents($sellersFile), true) : [];
    if (isset($sellers[$index])) {
        $sellers[$index]['approved'] = true;
        file_put_contents($sellersFile, json_encode($sellers, JSON_PRETTY_PRINT));
        $success = "Seller approved!";
    }
}

// REJECT SELLER
if(isset($_POST['reject_seller'])){
    $index = (int)$_POST['seller_index'];
    $sellers = file_exists($sellersFile) ? json_decode(file_get_contents($sellersFile), true) : [];
    if (isset($sellers[$index])) {
        array_splice($sellers, $index, 1);
        file_put_contents($sellersFile, json_encode($sellers, JSON_PRETTY_PRINT));
        $success = "Seller rejected!";
    }
}

// VERIFY USER
if(isset($_POST['verify'])){
    $id = (int)$_POST['id'];
    $conn->query("UPDATE tblUser SET status='verified' WHERE userID=$id");
    $success = "User verified!";
}

// DELETE USER
if(isset($_POST['delete'])){
    $id = (int)$_POST['id'];
    $conn->query("DELETE FROM tblUser WHERE userID=$id");
    $success = "User deleted!";
}

// UPDATE USER
if(isset($_POST['update'])){
    $id = (int)$_POST['id'];
    $username = $_POST['username'];
    $email = $_POST['email'];

    $stmt = $conn->prepare("UPDATE tblUser SET username=?, email=? WHERE userID=?");
    $stmt->bind_param("ssi",$username,$email,$id);
    $stmt->execute();

    $success = "User updated!";
}

/* -------------------------
   SEARCH
-------------------------- */
$search = $_GET['search'] ?? "";

if($search){
    $stmt = $conn->prepare("SELECT * FROM tblUser WHERE username LIKE ? OR email LIKE ?");
    $like = "%$search%";
    $stmt->bind_param("ss",$like,$like);
    $stmt->execute();
    $users = $stmt->get_result();
}else{
    $users = $conn->query("SELECT * FROM tblUser ORDER BY userID DESC");
}

/* -------------------------
   STATS
-------------------------- */
$totalUsers = $conn->query("SELECT COUNT(*) c FROM tblUser")->fetch_assoc()['c'];
$pendingUsers = $conn->query("SELECT COUNT(*) c FROM tblUser WHERE status='pending'")->fetch_assoc()['c'];
$totalOrders = $conn->query("SELECT COUNT(*) c FROM tblAorder")->fetch_assoc()['c'];
?>

<link rel="stylesheet" href="style.css">

<div class="layout">

<!-- SIDEBAR -->
<div class="taskbar">
    <h2>Admin Panel</h2>

    <div class="tb-nav">
        <a href="adminDashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<!-- MAIN -->
<div class="main-content">

<h2 class="title">Dashboard</h2>

<?php if($success): ?>
<div class="success"><?= $success ?></div>
<?php endif; ?>

<?php if($error): ?>
<div class="errors"><?= $error ?></div>
<?php endif; ?>

<!-- STATS -->
<div class="products" style="grid-template-columns: repeat(3,1fr);">

    <div class="item">
        <h3><?= $totalUsers ?></h3>
        <p>Total Users</p>
    </div>

    <div class="item">
        <h3><?= $pendingUsers ?></h3>
        <p>Pending Users</p>
    </div>

    <div class="item">
        <h3><?= $totalOrders ?></h3>
        <p>Total Orders</p>
    </div>

</div>

<!-- SELLER APPROVALS -->
<?php 
$sellers = file_exists($sellersFile) ? json_decode(file_get_contents($sellersFile), true) : [];
$pendingSellers = array_filter($sellers, function($s) { return !($s['approved'] ?? false); });
?>

<?php if (!empty($pendingSellers)): ?>
<h2 class="title">Pending Seller Applications (<?= count($pendingSellers) ?>)</h2>

<div class="products" style="grid-template-columns:1fr;">
<?php foreach ($pendingSellers as $index => $seller): ?>
    <div class="item" style="text-align:left;border:2px solid #ff9800;">
        <h3><?= htmlspecialchars($seller['store_name']) ?></h3>
        <p><strong>Owner:</strong> <?= htmlspecialchars($seller['owner_name']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($seller['email']) ?></p>
        <p><strong>Bank:</strong> <?= htmlspecialchars($seller['bank_name']) ?></p>
        <p><strong>Account:</strong> <?= htmlspecialchars($seller['account_name']) ?></p>
        <p style="font-size:0.85em;color:#aaa;"><strong>Applied:</strong> <?= htmlspecialchars($seller['created_at'] ?? '') ?></p>
        
        <div style="display:flex;gap:10px;margin-top:10px;">
            <form method="POST" style="flex:1;">
                <input type="hidden" name="seller_index" value="<?= array_search($seller, $sellers) ?>">
                <button class="btn" name="approve_seller" style="width:100%;background:#4caf50;">Approve</button>
            </form>
            <form method="POST" style="flex:1;">
                <input type="hidden" name="seller_index" value="<?= array_search($seller, $sellers) ?>">
                <button class="btn" name="reject_seller" style="width:100%;background:#f44336;">Reject</button>
            </form>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- SEARCH -->
<form method="GET" style="text-align:center;margin:20px;">
    <input type="text" name="search" placeholder="Search users..."
           value="<?= htmlspecialchars($search) ?>"
           style="padding:10px;width:300px;">
    <button class="btn">Search</button>
</form>

<h2 class="title">User Management</h2>

<div class="products" style="grid-template-columns:1fr;">

<?php while($u = $users->fetch_assoc()): ?>

<div class="item" style="text-align:left">

    <h3><?= htmlspecialchars($u['username']) ?></h3>
    <p><?= htmlspecialchars($u['email']) ?></p>

    <p>
        Status:
        <?= htmlspecialchars($u['status'] === 'verified' ? 'Verified' : 'Pending') ?>
    </p>

    <div style="display:flex;gap:10px;flex-wrap:wrap">

        <?php if($u['status'] !== 'verified'): ?>
        <form method="POST">
            <input type="hidden" name="id" value="<?= $u['userID'] ?>">
            <button class="btn" name="verify">Approve</button>
        </form>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="id" value="<?= $u['userID'] ?>">
            <button class="btn" name="delete" style="background:red;color:white">Delete</button>
        </form>

    </div>

    <!-- UPDATE -->
    <form method="POST" style="margin-top:10px">
        <input type="hidden" name="id" value="<?= $u['userID'] ?>">

        <input type="text" name="username"
               value="<?= htmlspecialchars($u['username']) ?>"
               style="width:100%;padding:8px;margin-bottom:5px">

        <input type="email" name="email"
               value="<?= htmlspecialchars($u['email']) ?>"
               style="width:100%;padding:8px;margin-bottom:5px">

        <button class="btn" name="update">Update</button>
    </form>

</div>

<?php endwhile; ?>

</div>

</div>
</div>