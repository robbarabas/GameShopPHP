<?php
// DB connection (adjust credentials as needed)
include 'db.php';

// Call PrintAllGameTitles()
echo "<h2>All Game Titles:</h2>";
if ($result = $conn->query("CALL PrintAllGameTitles()")) {
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>" . htmlspecialchars($row['Title']) . "</li>";
    }
    echo "</ul>";
    $result->close();
    // Consume next result (for multi-query cleanup)
    while ($conn->more_results() && $conn->next_result()) {;}
} else {
    echo "Error calling PrintAllGameTitles: " . $conn->error;
}

// Call IncreaseOrderPrices(10)
$percent = 10;
if ($conn->query("CALL IncreaseOrderPrices($percent)")) {
    echo "<p>Order prices increased by $percent% successfully.</p>";
} else {
    echo "Error calling IncreaseOrderPrices: " . $conn->error;
}
// Clear results
while ($conn->more_results() && $conn->next_result()) {;}

// Call InsertFullLibrary(5, 1)
$count = 5;
$user_id = 1;
if ($conn->query("CALL InsertFullLibrary($count, $user_id)")) {
    echo "<p>Inserted $count games into orders for user ID $user_id.</p>";
} else {
    echo "Error calling InsertFullLibrary: " . $conn->error;
}
// Clear results
while ($conn->more_results() && $conn->next_result()) {;}

$conn->close();
?>
