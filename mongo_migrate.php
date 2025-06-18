<?php

require 'vendor/autoload.php'; // Composer autoload for MongoDB

// SQL connection
$sql = new PDO("mysql:host=localhost;dbname=gameshop;charset=utf8mb4", "root", "");

// MongoDB connection
$mongo = (new MongoDB\Client)->your_mongo_db;

// ----------- USERS ------------
$users = $mongo->users;
foreach ($sql->query("SELECT * FROM Users") as $row) {
    $users->insertOne($row);
}

// ----------- GENRES ------------
$genresMap = [];
$genres = $mongo->genres;
foreach ($sql->query("SELECT * FROM Genres") as $row) {
    $genresMap[$row['genre_id']] = $row['genre_name'];
    $genres->insertOne($row);
}

// ----------- GAMES ------------
$games = $mongo->games;
foreach ($sql->query("SELECT * FROM Games") as $row) {
    // Fetch genres for this game
    $stmt = $sql->prepare("SELECT genre_id FROM GameGenres WHERE game_id = ?");
    $stmt->execute([$row['game_id']]);
    $genre_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $row['genres'] = array_map(fn($id) => $genresMap[$id] ?? null, $genre_ids);
    $games->insertOne($row);
}

// ----------- ORDERS & ORDER DETAILS ------------
$orders = $mongo->orders;
foreach ($sql->query("SELECT * FROM Orders") as $order) {
    // Fetch order details
  
    $stmt->execute([$order['order_id']]);

    $orders->insertOne($order);
}

// ----------- REVIEWS ------------
$reviews = $mongo->reviews;
foreach ($sql->query("SELECT * FROM Reviews") as $row) {
    $reviews->insertOne($row);
}

// ----------- REVIEW LOG ------------
$reviewLog = $mongo->review_log;
foreach ($sql->query("SELECT * FROM review_log") as $row) {
    $reviewLog->insertOne($row);
}

echo "✅ All data migrated successfully to MongoDB.\n";
