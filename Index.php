
<?php
session_start();
include 'db.php';

// Get genres for dropdown
$genres = $conn->query("SELECT * FROM Genres");

// Handle filters
$genre_filter = isset($_GET['genre']) ? intval($_GET['genre']) : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : '';

// Build dynamic query
$sql = "
    SELECT g.*
    FROM Games g
    LEFT JOIN GameGenres gg ON g.game_id = gg.game_id
    WHERE 1
";
$params = [];
if ($genre_filter > 0) {
    $sql .= " AND gg.genre_id = ?";
    $params[] = $genre_filter;
}

switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY g.price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY g.price DESC";
        break;
    case 'release_date':
        $sql .= " ORDER BY g.release_date DESC";
        break;
    default:
        $sql .= " ORDER BY g.title ASC";
}

$stmt = $conn->prepare($sql);

if ($genre_filter > 0) {
    $stmt->bind_param("i", $params[0]);
}

$stmt->execute();
$games = $stmt->get_result();
?>


<!DOCTYPE html>
<html>
<head>
    <title>GameStore</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header>
    <h1>GameStore</h1>
    <div class="login-box">
        <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="login.php">Login</a> | <a href="register.php">Register</a>
        <?php else: ?>
            Welcome! <a href="logout.php">Logout</a>
        <?php endif; ?>
    </div>
</header>

<!-- Filter form -->
<form method="GET" class="filter-form">
    <label>Genre:</label>
    <select name="genre">
        <option value="0">All</option>
        <?php while ($g = $genres->fetch_assoc()): ?>
            <option value="<?= $g['genre_id'] ?>" <?= $genre_filter == $g['genre_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($g['genre_name']) ?>
            </option>
        <?php endwhile; ?>
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
    <?php while ($game = $games->fetch_assoc()): ?>
        <div class="game-box">
            <img src="<?= htmlspecialchars($game['image_url']) ?>" alt="<?= htmlspecialchars($game['title']) ?>">
            <div><strong><?= htmlspecialchars($game['title']) ?></strong></div>
            <div><?= $game['price'] ?> EUR</div>
            <div><?= $game['release_date'] ?></div>
        </div>
    <?php endwhile; ?>
</div>

</body>
</html>

