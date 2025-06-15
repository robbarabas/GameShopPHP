<?php
session_start();
include 'db.php';



$errors = [];
$success = "";

// Handle Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $title = trim($_POST['title']);
    $price = floatval($_POST['price']);
    $release_date = $_POST['release_date'];
    $image_url = trim($_POST['image_url']);

    if ($title && $price >= 0 && $release_date) {
        $stmt = $conn->prepare("INSERT INTO games (title, price, release_date, image_url) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdss", $title, $price, $release_date, $image_url);
        $stmt->execute();
        $success = "Game created successfully!";
    } else {
        $errors[] = "All fields are required and price must be positive.";
    }
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $game_id = intval($_POST['game_id']);
    $title = trim($_POST['title']);
    $price = floatval($_POST['price']);
    $release_date = $_POST['release_date'];
    $image_url = trim($_POST['image_url']);

    if ($title && $price >= 0 && $release_date) {
        $stmt = $conn->prepare("UPDATE games SET title=?, price=?, release_date=?, image_url=? WHERE game_id=?");
        $stmt->bind_param("sdssi", $title, $price, $release_date, $image_url, $game_id);
        $stmt->execute();
        $success = "Game updated successfully!";
    } else {
        $errors[] = "Invalid input for update.";
    }
}

// Handle Delete
if (isset($_GET['delete_id'])) {
    $game_id = intval($_GET['delete_id']);
    try {
        $stmt = $conn->prepare("DELETE FROM games WHERE game_id = ?");
        $stmt->bind_param("i", $game_id);
        $stmt->execute();


        echo "<div class='message success'>✅ Game deleted successfully.</div>";
   
    } catch (mysqli_sql_exception $e) {
        // Custom handling for the trigger exception
        if (str_contains($e->getMessage(), 'Cannot delete a game that has been purchased')) {
            echo "<div class='message error'>❌ Cannot delete game: It has already been purchased by a user.</div>";
        } else {
            echo "<div class='message error'>❌ An error occurred: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
   
}




// Fetch games
$games = $conn->query("SELECT * FROM games ORDER BY release_date DESC");
?>

<!DOCTYPE html>
<html>
    
<link rel="stylesheet" href="style.css">
<head>
    <title>Admin Game Panel</title>
  
</head>
<body>
<div class="login-box" style="margin-right: 20px;  margin-left: auto; margin-top:20px;">


 
        <a href="Index.php"><button> Home </button></a>
        <h1>🎮 Admin Game Panel</h1>
        </div>
<div class="tabs">
    <div class="tab active" onclick="showTab('view')">View Games</div>
    <div class="tab" onclick="showTab('create')">Create Game</div>
</div>

<!-- Success or error messages -->
<?php if ($success): ?>
    <p class="message"><?= htmlspecialchars($success) ?></p>
<?php endif; ?>
<?php foreach ($errors as $error): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
<?php endforeach; ?>

<!-- View Games -->
<div id="view" class="tab-content active">
    <table>
        <tr>
            <th>Title</th><th>Price</th><th>Release</th><th>Image</th><th>Actions</th>
        </tr>
        <?php while ($g = $games->fetch_assoc()): ?>
            <tr>
                <form method="POST">
                    <td><input name="title" value="<?= htmlspecialchars($g['title']) ?>"></td>
                    <td><input name="price" type="number" step="0.01" value="<?= $g['price'] ?>"></td>
                    <td><input name="release_date" type="date" value="<?= $g['release_date'] ?>"></td>
                    <td><input name="image_url" value="<?= htmlspecialchars($g['image_url']) ?>"></td>
                    <td>
                        <input type="hidden" name="game_id" value="<?= $g['game_id'] ?>">
                        <input type="hidden" name="action" value="update">
                        <button type="submit">💾 Save</button>
                        <a href="?delete_id=<?= $g['game_id'] ?>" onclick="return confirm('Delete this game?')">🗑 Delete</a>
                    </td>
                </form>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

<!-- Create Game -->
<div id="create" class="tab-content">
    <form method="POST">
        <input type="hidden" name="action" value="create">
        <label>Title:<br><input name="title" required></label><br>
        <label>Price:<br><input name="price" type="number" step="0.01" required></label><br>
        <label>Release Date:<br><input name="release_date" type="date" required></label><br>
        <label>Image URL:<br><input name="image_url"></label><br>
        <button type="submit">➕ Add Game</button>
    </form>
</div>

<script>
function showTab(id) {
    document.querySelectorAll(".tab").forEach(tab => tab.classList.remove("active"));
    document.querySelectorAll(".tab-content").forEach(tab => tab.classList.remove("active"));
    document.getElementById(id).classList.add("active");
    event.target.classList.add("active");
}
</script>

</body>
</html>
