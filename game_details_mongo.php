<?php
session_start();
include 'db_mongo.php'; // MongoDB connection

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

if (!isset($_GET['id'])) {
    echo "Game not found.";
    exit;
}
$db->orders->createIndex(
    ['user_id' => 1, 'game_id' => 1],
    ['unique' => true]
);

$game_id_str = $_GET['id'];

try {
    $objectId = new ObjectId($game_id_str);
} catch (Exception $e) {
    echo "Invalid Game ID format.";
    exit;
}

$game = $db->games->findOne(['_id' => $objectId]);

if (!$game || !isset($game['game_id'])) {
    echo "Game not found or missing game_id.";
    exit;
}

$game_int_id = intval($game['game_id']);
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
// Handle Buy Button
if (isset($_POST['buy']) && $user_id) {
    try {
        $user_id_obj = new ObjectId($user_id);
        $now = new UTCDateTime();

        // Check if the user already purchased this game (optional, but can improve UX)
        $existing = $db->orders->findOne([
            'user_id' => $user_id_obj,
            'game_id' => $game_int_id
        ]);

        if ($existing) {
            echo "<div class='message error'>❌ You have already purchased this game.</div>";
        } else {
            // Get next order_id
            $lastOrder = $db->orders->findOne([], ['sort' => ['order_id' => -1]]);
            $nextOrderId = isset($lastOrder['order_id']) ? $lastOrder['order_id'] + 1 : 1;

            $order = [
                'order_id' => $nextOrderId,
                'user_id' => $user_id_obj,
                'order_date' => $now,
                'total_price' => $game['price'],
                'game_id' => $game_int_id
            ];

            $db->orders->insertOne($order);
            echo "<div class='message'>✅ Game purchased successfully!</div>";
        }
    } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
        if ($e->getCode() === 11000) {
            echo "<div class='message error'>❌ You have already purchased this game.</div>";
        } else {
            echo "<div class='message error'>❌ Purchase failed: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } catch (Exception $e) {
        echo "<div class='message error'>❌ Purchase error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}


// Handle Review Submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['review'], $_POST['rating'], $_SESSION['user_id'])) {
    $comment = trim($_POST['review']);
    $rating = intval($_POST['rating']);

    if ($rating < 1 || $rating > 5) {
        echo "<div class='message error'>Invalid rating. Please use 1 to 5.</div>";
    } else {
        try {
            // Convert user_id to ObjectId if valid
            try {
                $user_id_obj = new ObjectId($_SESSION['user_id']);
            } catch (Exception $e) {
                echo "<div class='message error'>Invalid user session. Please log in again.</div>";
                exit;
            }

            $now = new UTCDateTime();

            $existingReview = $db->reviews->findOne([
                'user_id' => $user_id_obj,
                'game_id' => $game_int_id
            ]);

            if ($existingReview) {
                // Update review
                $updateResult = $db->reviews->updateOne(
                    ['_id' => $existingReview['_id']],
                    ['$set' => [
                        'rating' => $rating,
                        'comment' => $comment,
                        'review_date' => $now
                    ]]
                );

                echo $updateResult->getModifiedCount() === 1
                    ? "<div class='message'>Review updated successfully!</div>"
                    : "<div class='message error'>Review update failed or no changes detected.</div>";
            } else {
                // Get latest review_id
                $lastReview = $db->reviews->findOne([], ['sort' => ['review_id' => -1]]);
                $nextReviewId = isset($lastReview['review_id']) ? $lastReview['review_id'] + 1 : 1;

                $insertData = [
                    'review_id' => $nextReviewId,
                    'user_id' => $user_id_obj,
                    'game_id' => $game_int_id,
                    'rating' => $rating,
                    'comment' => $comment,
                    'review_date' => $now
                ];

                $insertResult = $db->reviews->insertOne($insertData);

                echo $insertResult->getInsertedCount() === 1
                    ? "<div class='message'>Review submitted successfully!</div>"
                    : "<div class='message error'>Review insert failed.</div>";
            }

            // Log action
            $db->review_log->insertOne([
                'user_id' => $user_id_obj,
                'game_id' => $game_int_id,
                'comment' => $comment,
                'action_time' => $now
            ]);
        } catch (Exception $e) {
            echo "<div class='message error'>Failed to submit review: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

// Get reviews (handling both user_id types in $lookup)
$reviewsCursor = $db->reviews->aggregate([
    ['$match' => ['game_id' => $game_int_id]],
    ['$sort' => ['review_date' => -1]],
    ['$group' => [
        '_id' => '$user_id',
        'latest_review' => ['$first' => '$$ROOT']
    ]],
    ['$replaceRoot' => ['newRoot' => '$latest_review']],
    ['$lookup' => [
        'from' => 'users',
        'let' => ['uid' => '$user_id'],
        'pipeline' => [[
            '$match' => [
                '$expr' => [
                    '$or' => [
                        ['$eq' => ['$_id', '$$uid']],
                        ['$eq' => ['$user_id', '$$uid']]
                    ]
                ]
            ]
        ]],
        'as' => 'user'
    ]],
    ['$unwind' => [
        'path' => '$user',
        'preserveNullAndEmptyArrays' => false
    ]],
    ['$sort' => ['review_date' => -1]]
]);

// Calculate average rating
$avgCursor = $db->reviews->aggregate([
    ['$match' => ['game_id' => $game_int_id]],
    ['$group' => ['_id' => '$game_id', 'avgRating' => ['$avg' => '$rating']]]
]);

$avgData = iterator_to_array($avgCursor, false);
$averageRating = !empty($avgData) && isset($avgData[0]['avgRating'])
    ? round($avgData[0]['avgRating'], 2)
    : null;
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
            <a href="login_mongo.php">Login</a> | <a href="register_mongo.php">Register</a>
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

        <?php
$index = 0;
foreach ($reviewsCursor as $review) {
    if ($index % 2 !== 0) {
        $index++;
        continue; // Skip every second (odd-indexed) review
    }
    ?>
    <div class="review-box">
        <strong><?= htmlspecialchars($review['user']['username']) ?></strong>
        (
        <?php
        if (is_string($review['review_date'])) {
            echo htmlspecialchars(substr($review['review_date'], 0, 10));
        } else {
            echo htmlspecialchars($review['review_date']->toDateTime()->format('Y-m-d'));
        }
        ?>):
        <br>
        <?= str_repeat("⭐", $review['rating']) ?><br>
        <?= nl2br(htmlspecialchars($review['comment'])) ?>
    </div>
    <?php
    $index++;
}
?>

        <?php if ($user_id): ?>
            <h3>Leave a Review</h3>
            <form method="post">
                <textarea name="review" rows="4" cols="50" required></textarea><br>
                <label for="rating">Rating (1-5):</label>
                <input type="number" name="rating" min="1" max="5" required>
                <button class="special_button" type="submit">Submit Review</button>
            </form>
        <?php else: ?>
            <p><a href="login_mongo.php">Log in</a> to leave a review or buy the game.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
