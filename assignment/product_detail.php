<?php
session_start();
include "DBConn.php";

if (!isset($_SESSION['userID'])) {
    header("Location: login.php");
    exit;
}

$userID = (int)$_SESSION['userID'];
$clothID = isset($_GET['clothID']) ? (int)$_GET['clothID'] : 0;
if ($clothID <= 0) {
    echo 'Product not found';
    exit;
}

$stmt = $conn->prepare("SELECT * FROM tblClothes WHERE clothID = ? LIMIT 1");
$stmt->bind_param('i', $clothID);
$stmt->execute();
$res = $stmt->get_result();
$product = $res->fetch_assoc();
$stmt->close();

if (!$product) {
    echo 'Product not found';
    exit;
}

// try to find sizes: either `sizes` column on tblClothes or a tblClothSizes table
// gather sizes from possible sources: tblClothes.sizes (csv), tblClothes.size (single), or tblClothSizes
$sizes = [];
// prefer comma-separated `sizes` column
if (!empty($product['sizes'])) {
    $parts = array_filter(array_map('trim', explode(',', $product['sizes'])));
    if ($parts) $sizes = $parts;
}
// fallback to singular `size` column
if (empty($sizes) && !empty($product['size'])) {
    $sizes = [trim($product['size'])];
}
// fallback to separate table (if it exists)
if (empty($sizes)) {
    try {
        $ps = $conn->query("SELECT size FROM tblClothSizes WHERE clothID = " . (int)$clothID);
        if ($ps) {
            while ($r = $ps->fetch_assoc()) $sizes[] = $r['size'];
        }
    } catch (Exception $e) {
        // table doesn't exist, skip
    }
}

// build a link URL for add-to-cart (shop.php handles add). Use GET so existing flow works.
$addUrl = 'shop.php?add=' . $clothID;
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo htmlspecialchars($product['clothName']); ?></title>
    <link rel="stylesheet" href="style.css">
    <style>.detail{max-width:900px;margin:24px auto;display:flex;gap:24px}.detail img{max-width:360px;width:100%}</style>
</head>
<body>
<?php // simple nav reuse omitted for brevity ?>
<div class="detail">
    <div class="image">
        <?php
        $imgFile = !empty($product['image']) ? __DIR__ . '/images/' . $product['image'] : '';
        $imgSrc = 'images/placeholder.png';
        if ($imgFile && file_exists($imgFile)) {
            $imgSrc = 'images/' . rawurlencode($product['image']);
        } elseif (!empty($product['image'])) {
            // try unencoded filename
            $imgSrc = 'images/' . htmlspecialchars($product['image']);
        }
        ?>
        <img src="<?php echo $imgSrc; ?>" alt="<?php echo htmlspecialchars($product['clothName']); ?>">
    </div>
    <div class="info">
        <h1><?php echo htmlspecialchars($product['clothName']); ?></h1>
        <p style="font-weight:700;">R<?php echo number_format((float)$product['price'],2); ?></p>
        <?php if (!empty($product['description'])): ?>
            <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
        <?php endif; ?>

        <form method="get" action="shop.php">
            <input type="hidden" name="add" value="<?php echo $clothID; ?>">
            <label>Quantity: <input type="number" name="qty" value="1" min="1" style="width:72px;"></label>
            <?php if (!empty($sizes)): ?>
                <div style="margin-top:8px;">
                    <label>Size:
                        <select name="size">
                            <?php foreach ($sizes as $s): ?>
                                <option value="<?php echo htmlspecialchars($s); ?>"><?php echo htmlspecialchars($s); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
            <?php else: ?>
                <div style="margin-top:8px;color:#666;">No size information available.</div>
            <?php endif; ?>
            <div style="margin-top:12px;">
                <button type="submit" class="btn">Add to Cart</button>
                <a href="shop.php" class="btn">Back to shop</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
