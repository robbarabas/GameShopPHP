<?php
session_start();
include 'db_mongo.php';

// Get genres for dropdown
$genres = $db->genres->find()->toArray();
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

// Handle filters
$genre_filter = isset($_GET['genre']) ? intval($_GET['genre']) : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : '';
$is_admin = false;

// Check if user is admin
if (isset($_SESSION['user_id']) && isset($_SESSION['user'])) {
    $user = $db->users->findOne(['username' => $_SESSION['user']]);
    if ($user && isset($user['is_admin'])) {
        $is_admin = $user['is_admin'] == 1;
    }
}

// Build filter for games
$filter = [];
if ($genre_filter > 0) {
    // Find genre name by genre_id
    $genreDoc = $db->genres->findOne(['genre_id' => $genre_filter]);
    if ($genreDoc) {
        // Assuming 'genres' field in games is an array of genre names
        $filter['genres'] = $genreDoc['genre_name'];
    }
}

// Build sort option
$sort_option = [];
switch ($sort) {
    case 'price_asc':
        $sort_option = ['price' => 1];
        break;
    case 'price_desc':
        $sort_option = ['price' => -1];
        break;
    case 'release_date':
        $sort_option = ['release_date' => -1];
        break;
    default:
        $sort_option = ['title' => 1];
}

// Fetch games with filter and sort
$gamesCursor = $db->games->find($filter, ['sort' => $sort_option]);
$games = iterator_to_array($gamesCursor);
// Use an associative array to filter out duplicates by game_id/_id
$uniqueGames = [];
foreach ($games as $game) {
    $id = (string)($game['game_id'] ?? $game['_id']);
    if (!isset($uniqueGames[$id])) {
        $uniqueGames[$id] = $game;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>GameStore</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h1>GameStore</h1>
    <div class="login-box">
    <?php if ($is_admin): ?>
        <a href="orders_admin_mongo.php">Orders-Manip AND Review Logs </a> |
        <a href="manage_games_mongo.php">Manage Games </a> |
    <?php endif; ?>
    <?php if (!isset($_SESSION['user_id'])): ?>
        <a href="login_mongo.php">Login</a> | <a href="register_mongo.php">Register</a>
    <?php else: ?>
        Welcome <?= htmlspecialchars($_SESSION['user']) ?>! <a href="logout_mongo.php">Logout</a>
    <?php endif; ?>
    </div>
</header>

<!-- Filter form -->
<form method="GET" class="filter-form">
    <label>Genre:</label>
    <select name="genre">
        <option value="0">All</option>
        <?php foreach ($genres as $g): ?>
            <option value="<?= $g['genre_id'] ?>" <?= $genre_filter == $g['genre_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($g['genre_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Sort by:</label>
    <select name="sort">
        <option value="">Default</option>
        <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>>Price ASC</option>
        <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>Price DESC</option>
        <option value="release_date" <?= $sort == 'release_date' ? 'selected' : '' ?>>Release Date</option>
    </select>

    <button type="submit">Apply</button>
</form>

<!-- Games Showcase -->
<div class="games-section">
    <?php foreach ($uniqueGames as $game): ?>
        <a href="game_details_mongo.php?id=<?= htmlspecialchars($game['_id']) ?>">
            <button>
                <div class="game-box">
                    <img src="<?= htmlspecialchars($game['image_url'] ?? '') ?>" alt="<?= htmlspecialchars($game['title'] ?? '') ?>">
                    <div><strong><?= htmlspecialchars($game['title'] ?? '') ?></strong></div>
                    <div><?= $game['price'] ?? '' ?> EUR</div>
                    <div></div>
                    <div><?= $game['release_date'] ?? '' ?></div>
                </div>
            </button>
        </a>
    <?php endforeach; ?>
</div>

</body>
</html>
