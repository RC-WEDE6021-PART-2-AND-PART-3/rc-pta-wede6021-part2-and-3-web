<?php
session_start();
require_once "DBConn.php";

if (empty($_SESSION['userID'])) {
    header("Location: login.php");
    exit();
}

$userID = (int)$_SESSION['userID'];

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

$jsonFile = __DIR__ . DIRECTORY_SEPARATOR . 'sellers_products.json';
$imgDir = 'images/sellers';
if (!is_dir($imgDir)) { @mkdir($imgDir, 0755, true); }

// Load products
$products = [];
if (is_file($jsonFile)) {
    $raw = file_get_contents($jsonFile);
    $products = json_decode($raw, true) ?: [];
}

$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Helpers
function save_products($path, $data) {
    $tmp = tempnam(sys_get_temp_dir(), 'sp');
    file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    return rename($tmp, $path);
}

// Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'add' || $action === 'edit')) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $seller_name = trim($_POST['seller_name'] ?? '');
    $seller_email = trim($_POST['seller_email'] ?? '');

    // basic validation
    $errors = [];
    if ($name === '') $errors[] = 'Name required';
    if ($price <= 0) $errors[] = 'Price must be greater than 0';

    $imgPath = null;
    if (!empty($_FILES['image']['name'])) {
        $up = $_FILES['image'];
        if ($up['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($up['name'], PATHINFO_EXTENSION);
            $filename = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $target = $imgDir . '/' . $filename;
            if (!move_uploaded_file($up['tmp_name'], $target)) {
                $errors[] = 'Failed to move uploaded image';
            } else {
                $imgPath = $target;
            }
        } else {
            $errors[] = 'Image upload error';
        }
    }

    if (empty($errors)) {
        if ($action === 'add') {
            $products[] = [
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'img' => $imgPath ?: '',
                'seller_email' => $seller_email,
                'seller_name' => $seller_name,
                'created_at' => date(DATE_ATOM),
            ];
        } elseif ($action === 'edit' && is_int($id) && isset($products[$id])) {
            // replace fields
            if ($imgPath) {
                // remove old image if exists and not empty
                if (!empty($products[$id]['img']) && file_exists($products[$id]['img'])) {
                    @unlink($products[$id]['img']);
                }
                $products[$id]['img'] = $imgPath;
            }
            $products[$id]['name'] = $name;
            $products[$id]['description'] = $description;
            $products[$id]['price'] = $price;
            $products[$id]['seller_name'] = $seller_name;
            $products[$id]['seller_email'] = $seller_email;
            // keep created_at
        }

        save_products($jsonFile, $products);
        header('Location: admin_products.php');
        exit;
    }
}

// Delete
if ($action === 'delete' && is_int($id) && isset($products[$id])) {
    // remove image
    if (!empty($products[$id]['img']) && file_exists($products[$id]['img'])) {
        @unlink($products[$id]['img']);
    }
    array_splice($products, $id, 1);
    save_products($jsonFile, $products);
    header('Location: admin_products.php');
    exit;
}

?><link rel="stylesheet" href="style.css">
<<div class="layout">

<!-- SIDEBAR -->
<div class="taskbar">
    <h2>Admin Panel</h2>

    <div class="tb-nav">
        <a href="adminDashboard.php">Dashboard</a>
        <a href="admin_products.php">Products</a>
        <a href="admin_orders.php">Orders</a>
        <a href="logout.php">Logout</a>
        
    </div>
</div>

    <div class="main-content">
        
        <<h2 class="title">Manage Seller Products</h2>

        <p><a href="admin_products.php?action=add">Add New Product</a></p>

        <?php if ($action === 'add' || ($action === 'edit' && isset($products[$id]))):
            $editing = ($action === 'edit');
            $item = $editing ? $products[$id] : null;
        ?>
            <form method="post" enctype="multipart/form-data" style="max-width:700px;">
                <label>Name<br><input type="text" name="name" value="<?php echo htmlspecialchars($item['name'] ?? ''); ?>" required></label><br>
                <label>Description<br><textarea name="description" rows="4"><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea></label><br>
                <label>Price<br><input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($item['price'] ?? ''); ?>" required></label><br>
                <label>Seller Name<br><input type="text" name="seller_name" value="<?php echo htmlspecialchars($item['seller_name'] ?? ''); ?>"></label><br>
                <label>Seller Email<br><input type="email" name="seller_email" value="<?php echo htmlspecialchars($item['seller_email'] ?? ''); ?>"></label><br>
                <label>Image<br><input type="file" name="image" accept="image/*"></label><br>
                <?php if ($editing && !empty($item['img'])): ?>
                    <div>Current image:<br><img src="<?php echo htmlspecialchars($item['img']); ?>" style="max-width:150px;"></div>
                <?php endif; ?>
                <button type="submit" class="btn"><?php echo $editing ? 'Save' : 'Add'; ?></button>
                <a href="admin_products.php">Cancel</a>
            </form>
        <?php else: ?>
            <?php if (empty($products)): ?>
                <p>No seller products found.</p>
            <?php else: ?>
                <table style="width:100%;border-collapse:collapse;">
                    <thead><tr><th>#</th><th>Image</th><th>Name</th><th>Seller</th><th>Price</th><th>Created</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($products as $idx => $p): ?>
                        <tr>
                            <td><?php echo $idx; ?></td>
                            <td><?php if (!empty($p['img'])): ?><img src="<?php echo htmlspecialchars($p['img']); ?>" style="max-width:80px;"><?php endif; ?></td>
                            <td><?php echo htmlspecialchars($p['name']); ?><br><small><?php echo htmlspecialchars($p['description']); ?></small></td>
                            <td><?php echo htmlspecialchars($p['seller_name']) . '<br><small>' . htmlspecialchars($p['seller_email']) . '</small>'; ?></td>
                            <td><?php echo 'R' . number_format((float)$p['price'], 2); ?></td>
                            <td><?php echo htmlspecialchars($p['created_at'] ?? ''); ?></td>
                            <td>
                                <a href="admin_products.php?action=edit&id=<?php echo $idx; ?>">Edit</a> |
                                <a href="admin_products.php?action=delete&id=<?php echo $idx; ?>" onclick="return confirm('Delete this product?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
