<?php
session_start();
include 'db.php';

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM Orders WHERE order_id = $id");
    header("Location: orders_admin.php");
    exit;
}

// Handle create
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create'])) {
    $user_id = $_POST['user_id'];
    $game_id = $_POST['game_id'];
    $price = $_POST['price'];
    $conn->query("INSERT INTO Orders (user_id, game_id, total_price, order_date) VALUES ($user_id, $game_id, $price, NOW())");
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $order_id = $_POST['order_id'];
    $price = $_POST['price'];
    $conn->query("UPDATE Orders SET total_price = $price WHERE order_id = $order_id");
}

// Fetch all orders
$orders = $conn->query("SELECT * FROM orders");

$limit = 10; // rows per page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Get total count for pagination
$result = $conn->query("SELECT COUNT(*) AS total FROM review_log");
$row = $result->fetch_assoc();
$total = $row['total'];
$total_pages = ceil($total / $limit);

// Fetch logs with pagination
$stmt = $conn->prepare("SELECT log_id, user_id, game_id, comment, action_time FROM review_log ORDER BY action_time DESC LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$logs = $stmt->get_result();
?>


    <link rel="stylesheet" href="style.css">
    <div class="login-box" style="margin-right: 20px;  margin-left: auto; margin-top:20px;">


    
    <a href="Index.php"><button> Home </button></a>
<h1> Admin Orders</h1>
        </div>
<table >
    <tr>
        <th>ID</th><th>User</th><th>Game</th><th>Price</th><th>Date</th><th>Comment</th>
    </tr>
    <?php while ($row = $orders->fetch_assoc()): ?>
        <tr>
            <form method="POST">
                <td><?= $row['order_id'] ?></td>
                <td><?= $row['user_id'] ?></td>
                <td><?= $row['game_id'] ?></td>
                <td>
                    <input type="hidden" name="order_id" value="<?= $row['order_id'] ?>">
                    <input type="number" step="0.01" name="price" value="<?= $row['total_price'] ?>">
                </td>
                <td><?= $row['order_date'] ?></td>
                <td>
                    <button type="submit" name="update">Update</button>
                    <a href="?delete=<?= $row['order_id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </form>
        </tr>
    <?php endwhile; ?>
</table>

<h3>Create Order</h3>
<form method="POST">
    <label>User ID : </label><input type="number" name="user_id" required>
    <label>Game ID : </label><input type="number" name="game_id" required>
    <label>Price : </label><input type="number" name="price" required>
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
        <?php while ($log = $logs->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($log['log_id']) ?></td>
            <td><?= htmlspecialchars($log['user_id']) ?></td>
            <td><?= htmlspecialchars($log['game_id']) ?></td>
            <td><?= htmlspecialchars($log['comment']) ?></td>
            <td><?= htmlspecialchars($log['action_time']) ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<!-- Pagination controls -->
<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?>">Prev</a>
    <?php endif; ?>
    Page <?= $page ?> of <?= $total_pages ?>
    <?php if ($page < $total_pages): ?>
        <a href="?page=<?= $page + 1 ?>">Next</a>
    <?php endif; ?>
</div>
