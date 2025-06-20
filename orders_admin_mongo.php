<?php
session_start();
include 'db_mongo.php';

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

// DELETE order
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $db->Orders->deleteOne(['_id' => new ObjectId($id)]);
    header("Location: orders_admin_mongo.php");
    exit;
}

// CREATE order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $user_id = new ObjectId(trim($_POST['user_id']));
    $game_id = new ObjectId(trim($_POST['game_id']));
    $price = floatval($_POST['price']);

    $db->Orders->insertOne([
        'user_id' => $user_id,
        'game_id' => $game_id,
        'total_price' => $price,
        'order_date' => new UTCDateTime()
    ]);
}

// UPDATE order price
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $order_id = $_POST['order_id'];
    $price = floatval($_POST['price']);

    $db->Orders->updateOne(
        ['_id' => new ObjectId($order_id)],
        ['$set' => ['total_price' => $price]]
    );
}

// PAGINATED ORDERS
$orderLimit = 10;
$orderPage = isset($_GET['order_page']) ? max(1, intval($_GET['order_page'])) : 1;
$orderSkip = ($orderPage - 1) * $orderLimit;

$orderTotal = $db->orders->countDocuments();
$orderTotalPages = ceil($orderTotal / $orderLimit);

$ordersCursor = $db->orders->find(
    [],
    ['sort' => ['order_date' => -1], 'limit' => $orderLimit, 'skip' => $orderSkip]
);

// PAGINATED REVIEW LOG
$logLimit = 10;
$logPage = isset($_GET['log_page']) ? max(1, intval($_GET['log_page'])) : 1;
$logSkip = ($logPage - 1) * $logLimit;

$logTotal = $db->review_log->countDocuments();
$logTotalPages = ceil($logTotal / $logLimit);

$logsCursor = $db->review_log->find(
    [],
    ['sort' => ['action_time' => -1], 'limit' => $logLimit, 'skip' => $logSkip]
);
?>

<link rel="stylesheet" href="style.css">
<div class="login-box" style="margin-right: 20px; margin-left: auto; margin-top: 20px;">
    <a href="index_mongo.php"><button>Home</button></a>
    <h1>Admin Orders</h1>
</div>

<table>
    <tr>
        <th>ID</th><th>User</th><th>Game</th><th>Price</th><th>Date</th><th>Actions</th>
    </tr>
    <?php foreach ($ordersCursor as $order): ?>
        <tr>
            <form method="POST">
                <td><?= $order['_id'] ?></td>
                <td><?= $order['user_id'] ?></td>
                <td><?= $order['game_id'] ?></td>
                <td>
                    <input type="hidden" name="order_id" value="<?= $order['_id'] ?>">
                    <input type="number" step="0.01" name="price" value="<?= $order['total_price'] ?>">
                </td>
                <td>
                    <?php
                    $dt = $order['order_date'] ?? null;
                    if ($dt instanceof UTCDateTime) {
                        echo $dt->toDateTime()->format('Y-m-d H:i');
                    } elseif (is_string($dt)) {
                        echo (new DateTime($dt))->format('Y-m-d H:i');
                    } else {
                        echo 'Invalid date';
                    }
                    ?>
                </td>
                <td>
                    <button type="submit" name="update">Update</button>
                    <a href="?delete=<?= $order['_id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </form>
        </tr>
    <?php endforeach; ?>
</table>

<!-- Orders Pagination -->
<div class="pagination">
    <?php if ($orderPage > 1): ?>
        <a href="?order_page=<?= $orderPage - 1 ?>&log_page=<?= $logPage ?>">Prev Orders</a>
    <?php endif; ?>
    Page <?= $orderPage ?> of <?= $orderTotalPages ?>
    <?php if ($orderPage < $orderTotalPages): ?>
        <a href="?order_page=<?= $orderPage + 1 ?>&log_page=<?= $logPage ?>">Next Orders</a>
    <?php endif; ?>
</div>

<h3>Create Order</h3>
<form method="POST">
    <label>User ID:</label>
    <input type="text" name="user_id" required>
    <label>Game ID:</label>
    <input type="text" name="game_id" required>
    <label>Price:</label>
    <input type="number" step="0.01" name="price" required>
    <button type="submit" name="create">Add</button>
</form>

<h1>Review Log</h1>
<table border="1" cellpadding="8" cellspacing="0">
    <thead>
        <tr>
            <th>Log ID</th>
            <th>User ID</th>
            <th>Game ID</th>
            <th>Comment</th>
            <th>Timestamp</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($logsCursor as $log): ?>
            <tr>
                <td><?= $log['_id'] ?></td>
                <td><?= $log['user_id'] ?></td>
                <td><?= $log['game_id'] ?></td>
                <td><?= htmlspecialchars($log['comment']) ?></td>
                <td>
                    <?php
                    $logTime = $log['action_time'] ?? null;
                    if ($logTime instanceof UTCDateTime) {
                        echo $logTime->toDateTime()->format('Y-m-d H:i:s');
                    } elseif (is_string($logTime)) {
                        echo (new DateTime($logTime))->format('Y-m-d H:i:s');
                    } else {
                        echo 'Invalid date';
                    }
                    ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Review Log Pagination -->
<div class="pagination">
    <?php if ($logPage > 1): ?>
        <a href="?order_page=<?= $orderPage ?>&log_page=<?= $logPage - 1 ?>">Prev Logs</a>
    <?php endif; ?>
    Page <?= $logPage ?> of <?= $logTotalPages ?>
    <?php if ($logPage < $logTotalPages): ?>
        <a href="?order_page=<?= $orderPage ?>&log_page=<?= $logPage + 1 ?>">Next Logs</a>
    <?php endif; ?>
</div>
