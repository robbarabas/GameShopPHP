<?php
session_start();
include 'db_mongo.php'; // MongoDB connection

if (!isset($_GET['id'])) {
    echo "Game not found.";
    exit;
}

use MongoDB\BSON\ObjectId;

$game_id_str = $_GET['id'];

try {
    $objectId = new MongoDB\BSON\ObjectId($game_id_str);
} catch (Exception $e) {
    echo "Invalid Game ID format.";
    exit;
}

$game = $db->games->findOne(['_id' => $objectId]);

if (!$game) {
    echo "Game not found.";
    exit;
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Make sure game_id integer exists (important for reviews)
if (!isset($game['game_id'])) {
    echo "Game integer ID (game_id) is missing in game document.";
    exit;
}

$game_int_id = intval($game['game_id']);

// Handle Review Submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['review'], $_POST['rating'], $_SESSION['user_id'])) {
    $comment = trim($_POST['review']);
    $rating = intval($_POST['rating']);

    try {
        $user_id_obj = new ObjectId($_SESSION['user_id']);
    } catch (Exception $e) {
        echo "<div class='message error'>Invalid user session. Please log in again.</div>";
        exit;
    }

    if ($rating < 1 || $rating > 5) {
        echo "<div class='message error'>Invalid rating. Please use 1 to 5.</div>";
    } else {
        try {
            // Optional: remove previous review by this user for this game
            $db->reviews->deleteMany(['user_id' => $user_id_obj, 'game_id' => $game_int_id]);

            // Insert new review
            $db->reviews->insertOne([
                'user_id' => $user_id_obj,
                'game_id' => $game_int_id,
                'rating' => $rating,
                'comment' => $comment,
                'review_date' => new MongoDB\BSON\UTCDateTime()
            ]);

            // Insert log entry
            $db->review_log->insertOne([
                'user_id' => $user_id_obj,
                'game_id' => $game_int_id,
                'comment' => $comment,
                'action_time' => new MongoDB\BSON\UTCDateTime()
            ]);

            echo "<div class='message'>Review submitted successfully!</div>";
        } catch (Exception $e) {
            echo "<div class='message error'>Failed to submit review: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

// Get reviews 
$reviewsCursor = $db->reviews->aggregate([
    ['$match' => ['game_id' => $game_int_id]],
    ['$lookup' => [
        'from' => 'users',
        'localField' => 'user_id',
        'foreignField' => 'user_id',
        'as' => 'user'
    ]],
    ['$unwind' => [
        'path' => '$user',
        'preserveNullAndEmptyArrays' => false
    ]],
    ['$sort' => ['review_date' => -1]]
]);


// Get average rating
$avgCursor = $db->reviews->aggregate([
    ['$match' => ['game_id' => $game_int_id]],
    ['$group' => ['_id' => '$game_id', 'avgRating' => ['$avg' => '$rating']]]
]);

$avgData = iterator_to_array($avgCursor, false);
$averageRating = null;

if (!empty($avgData) && isset($avgData[0]['avgRating'])) {
    $averageRating = round($avgData[0]['avgRating'], 2);
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
    <div class="login-box" style="margin-right: 20px; margin-left: auto; margin-top: 1px;">
        <a href="index_mongo.php">Home</a> |
        <?php if (!$user_id): ?>
            <a href="login_mongo.php">Login</a> | <a href="register.php">Register</a>
        <?php else: ?>
            Welcome <?= htmlspecialchars($_SESSION['user']) ?>! <a href="logout_mongo.php">Logout</a>
        <?php endif; ?>
    </div>
</header>

<div style="display: flex;">
    <div>
        <img style="width:500px" src="<?= htmlspecialchars($game['image_url']) ?>" class="banner" alt="Game Image">
        <p>Price: <?= $game['price'] ?> EUR</p>
        <p>Release Date: <?= $game['release_date'] ?></p>

        <?php if ($user_id): ?>
            <form method="post">
                <button class="special_button" type="submit" name="buy">Buy</button>
            </form>
        <?php endif; ?>
    </div>

    <div style="padding-left:200px;">
        <h2>Reviews</h2>
        <p>Rating <?= htmlspecialchars($game['title']) ?>:
            <?= $averageRating !== null ? "$averageRating ⭐" : "No ratings yet." ?>
        </p>

        <?php foreach ($reviewsCursor as $review): ?>
    <div class="review-box">
        <strong><?= htmlspecialchars($review['user']['username']) ?></strong>
        (
        <?php 
            if (is_string($review['review_date'])) {
                echo htmlspecialchars(substr($review['review_date'], 0, 10));
            } else {
                echo htmlspecialchars($review['review_date']->toDateTime()->format('Y-m-d'));
            }
        ?>
        ):
        <br>
        <?= str_repeat("⭐", $review['rating']) ?><br>
        <?= nl2br(htmlspecialchars($review['comment'])) ?>
    </div>
<?php endforeach; ?>


        <?php if ($user_id): ?>
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
