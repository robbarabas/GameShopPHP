<?php
require 'vendor/autoload.php'; // Composer autoload for MongoDB

// MongoDB connection setup
$client = new MongoDB\Client("mongodb://localhost:27017");
$db = $client->your_mongo_db; // CHANGE this to your MongoDB database name
