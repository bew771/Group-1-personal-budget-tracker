<?php
// Simple DB connection test for budget-tracker
require 'db.php';
try {
    // Basic query to validate the connection
    $stmt = $pdo->query('SELECT COUNT(*) as users_count FROM users');
    $row = $stmt->fetch();
    $count = $row ? $row['users_count'] : 0;
    echo "DB connection OK. Users: " . intval($count);
} catch (Exception $e) {
    echo "DB connection error: ", $e->getMessage();
}
?>