<?php
require 'vendor/autoload.php';
include 'db_mongo.php'; // Your MongoDB connection

use MongoDB\BSON\ObjectId;

// Find duplicates by user_id + game_id
$duplicates = $db->orders->aggregate([
    [
        '$group' => [
            '_id' => ['user_id' => '$user_id', 'game_id' => '$game_id'],
            'count' => ['$sum' => 1],
            'ids' => ['$push' => '$_id']
        ]
    ],
    [
        '$match' => [
            'count' => ['$gt' => 1]
        ]
    ]
]);

$deletedCount = 0;
foreach ($duplicates as $dup) {
    $ids = iterator_to_array($dup['ids']); // 👈 Fix: convert BSONArray to PHP array
    array_shift($ids); // Keep the first, delete the rest

    $deleteResult = $db->orders->deleteMany(['_id' => ['$in' => $ids]]);
    $deletedCount += $deleteResult->getDeletedCount();
}

echo "✅ Deleted $deletedCount duplicate order(s).\n";
?>
