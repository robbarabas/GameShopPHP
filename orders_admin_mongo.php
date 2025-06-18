<?php
session_start();
include 'db_mongo.php';

use MongoDB\BSON\ObjectId;

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
        'order_date' => new MongoDB\BSON\UTCDateTime()
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

// FETCH all orders
$orders = $db->Orders->find([], ['sort' => ['order_date' => -1]]);

// PAGINATED review_log
$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$skip = ($page - 1) * $limit;

$total = $db->review_log->countDocuments();
$total_pages = ceil($total / $limit);

$logsCursor = $db->review_log->find(
    [],
    ['sort' => ['action_time' => -1], 'limit' => $limit, 'skip' => $skip]
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
    <?php foreach ($orders as $order): ?>
        <tr>
            <form method="POST">
                <td><?= $order['_id'] ?></td>
                <td><?= $order['user_id'] ?></td>
                <td><?= $order['game_id'] ?></td>
                <td>
                    <input type="hidden" name="order_id" value="<?= $order['_id'] ?>">
                    <input type="number" step="0.01" name="price" value="<?= $order['total_price'] ?>">
                </td>
                <td><?= $order['order_date']->toDateTime()->format('Y-m-d H:i') ?></td>
                <td>
                    <button type="submit" name="update">Update</button>
                    <a href="?delete=<?= $order['_id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </form>
        </tr>
    <?php endforeach; ?>
</table>

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
                <td><?= $log['action_time']->toDateTime()->format('Y-m-d H:i:s') ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Pagination -->
<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?>">Prev</a>
    <?php endif; ?>
    Page <?= $page ?> of <?= $total_pages ?>
    <?php if ($page < $total_pages): ?>
        <a href="?page=<?= $page + 1 ?>">Next</a>
    <?php endif; ?>
</div>
