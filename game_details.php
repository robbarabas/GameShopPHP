<?php
session_start();
include 'db.php';

// Get the game ID
if (!isset($_GET['id'])) {
    echo "Game not found.";
    exit;
}

$game_id = intval($_GET['id']);
$i=1;
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
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['review'], $_POST['rating'], $_SESSION['user_id'])) {
    $comment = trim($_POST['review']);
    $rating = intval($_POST['rating']); // Convert to integer safely
    $user_id = $_SESSION['user_id'];

    if ($rating >= 1 && $rating <= 5) { // Assuming allowed range is 1–5
        // Delete previous review by this user for the game
        $conn->query("DELETE FROM reviews WHERE user_id = $user_id AND game_id = $game_id");

        // Insert new review
        $stmt = $conn->prepare("INSERT INTO reviews (user_id, game_id, rating, comment, review_date) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("iiss", $user_id, $game_id, $rating, $comment);
        $stmt->execute();
    } else {
        echo "Invalid rating. Please choose a number between 1 and 5.";
    }
}


// Handle buy action
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['buy']) && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    try {
        $stmt = $conn->prepare("INSERT INTO Orders (user_id, game_id, total_price, order_date) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iii", $user_id, $game_id, $game['price']);
    
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
    
        echo "<div class='message'>Order placed successfully!</div>";
    
    } catch (Exception $e) {
        if (str_contains($e->getMessage(), 'User has already purchased this game')) {
            echo "<div class='message'>⚠️ You already own this game.</div>";
        } else {
            echo "<div class='message error'>❌ An error occurred: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
    

}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($game['title']) ?> - Game Details</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
<h1><?= htmlspecialchars($game['title']) ?></h1>

<div class="login-box" style="margin-right: 20px;  margin-left: auto; margin-top:1px;">
    
        <a href="Index.php">Home</a>|

        <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="login.php">Login</a> | <a href="register.php">Register</a>
        <?php else: ?>
            Welcome <?php echo( $_SESSION['user']) ?>! <a href="logout.php">Logout</a>
        <?php endif; ?>
    </div>
    </header>
<div style="display: flex;">

    </div>
<div style="display:flex;">


<div style>
<img style="width:500px" src="<?= htmlspecialchars($game['image_url']) ?>" class="banner" alt="Game Image">
<p>Price: <?= $game['price'] ?> EUR</p>
<p>Release Date: <?= $game['release_date'] ?></p>

<?php if (isset($_SESSION['user_id'])): ?>
    <!-- Buy Form -->
    <form method="post">
        <button class="special_button"   type="submit" name="buy">Buy</button>
    </form>
<?php endif; ?>
</div>

<div style="padding-left:200px;">
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
$conn->query("CALL GetAverageRating($game_id, @avg_rating)");
$result = $conn->query("SELECT @avg_rating AS average");
$row = $result->fetch_assoc();
echo " Rating ".$game['title'] ." : ". ($row['average'] !== null ? $row['average'].'⭐': "No ratings yet.");

while ($review = $reviews->fetch_assoc()):
?>
    <div class="review-box">
        <strong><?= htmlspecialchars($review['username']) ?></strong> 
        (<?= $review['review_date'] ?>):<br>
        
        <?php 
        $i=0;
        while($i<$review['rating']):
        ?>
        ⭐
        <?php 
        $i=$i+1;
    endwhile;?>
        <br>
        <?= nl2br(htmlspecialchars($review['comment'])) ?>
    </div>
<?php endwhile; ?>

<!-- Leave a Review -->
<?php if (isset($_SESSION['user_id'])): ?>
    <h3>Leave a Review</h3>
    <form method="post">
        <textarea name="review" rows="4" cols="50" required></textarea><br>
        <label for="rating">Rating (1-5):</label>
        <input type="number" name="rating" min="1" max="5" required>
        <button class="special_button" type="submit">Submit Review</button>
    </form>
<?php else: ?>
    <p><a href="login.php">Log in</a> to leave a review or buy the game.</p>
<?php endif; ?>
</div>
</div>
</body>
</html>
<script>

</script>