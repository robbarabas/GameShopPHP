<?php
session_start();
include 'db.php';

// Get the game ID
if (!isset($_GET['id'])) {
    echo "Game not found.";
    exit;
}

$game_id = intval($_GET['id']);

// Fetch game details
$stmt = $conn->prepare("SELECT * FROM Games WHERE game_id = ?");
$stmt->bind_param("i", $game_id);
$stmt->execute();
$game = $stmt->get_result()->fetch_assoc();

if (!$game) {
    echo "Game not found.";
    exit;
}

// Handle review submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['review']) && isset($_SESSION['user_id'])) {
    $content = trim($_POST['review']);
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO Reviews (game_id, user_id, comment, review_date) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iis", $game_id, $user_id, $content);
    $stmt->execute();
}

// Handle buy action
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['buy']) && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Create order
    $stmt = $conn->prepare("INSERT INTO Orders (user_id,game_id,total_price, order_date) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iii", $user_id,$game_id,$game['price']);
    $stmt->execute();
    $order_id = $stmt->insert_id;


}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($game['title']) ?> - Game Details</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div style="display: flex;">
<h1><?= htmlspecialchars($game['title']) ?></h1>

<div class="login-box" style="margin-right: 20px;  margin-left: auto; margin-top:20px;">
    


        <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="login.php">Login</a> | <a href="register.php">Register</a>
        <?php else: ?>
            Welcome <?php echo( $_SESSION['user']) ?>! <a href="logout.php">Logout</a>
        <?php endif; ?>
    </div>
    </div>
<div style="display:flex;">

<img style="width:500px" src="<?= htmlspecialchars($game['image_url']) ?>" class="banner" alt="Game Image">
<div style>
<p>Price: <?= $game['price'] ?> EUR</p>
<p>Release Date: <?= $game['release_date'] ?></p>

<?php if (isset($_SESSION['user_id'])): ?>
    <!-- Buy Form -->
    <form method="post">
        <button type="submit" name="buy">Buy</button>
    </form>
<?php endif; ?>
</div>

<div style="padding: left 200px;">
<h2>Reviews</h2>

<?php
$review_stmt = $conn->prepare("
    SELECT r. rating ,r.comment, r.	review_date	,u.username 
    FROM reviews r 
    JOIN Users u ON r.user_id = u.user_id 
    WHERE r.game_id = ? 
    ORDER BY r.review_date DESC
");
$review_stmt->bind_param("i", $game_id);
$review_stmt->execute();
$reviews = $review_stmt->get_result();

while ($review = $reviews->fetch_assoc()):
?>
    <div class="review-box">
        <strong><?= htmlspecialchars($review['username']) ?></strong> 
        (<?= $review['review_date'] ?>):<br>
        <?= nl2br(htmlspecialchars($review['comment'])) ?>
    </div>
<?php endwhile; ?>

<!-- Leave a Review -->
<?php if (isset($_SESSION['user_id'])): ?>
    <h3>Leave a Review</h3>
    <form method="post">
        <textarea name="review" rows="4" cols="50" required></textarea><br>
        <button type="submit">Submit Review</button>
    </form>
<?php else: ?>
    <p><a href="login.php">Log in</a> to leave a review or buy the game.</p>
<?php endif; ?>
</div>
</div>
</body>
</html>
