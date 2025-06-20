<?php
include 'db_mongo.php';

$db->orders->createIndex(
    ['user_id' => 1, 'game_id' => 1],
    ['unique' => true]
);

echo "✅ Unique index created on user_id + game_id.";
?>