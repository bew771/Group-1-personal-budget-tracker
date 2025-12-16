<?php
// setup.php - Create database and import schema.sql automatically
// Usage: open in browser (http://localhost/swe%20group1/budget-tracker/setup.php)

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$dbName = 'budget_tracker';
$schemaFile = __DIR__ . '/schema.sql';

try {
    $dsn = "mysql:host=$host;charset=$charset"; // no db specified
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die('Connection to MySQL server failed: ' . htmlspecialchars($e->getMessage()));
}

echo "Connected to MySQL server.<br>";

// Check if database exists
$check = $pdo->prepare('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?');
$check->execute([$dbName]);
if ($check->fetch()) {
    echo "Database '$dbName' already exists.<br>";
} else {
    // Create database
    try {
        $pdo->exec("CREATE DATABASE `$dbName` CHARACTER SET $charset COLLATE ${charset}_general_ci");
        echo "Database '$dbName' created.<br>";
    } catch (PDOException $e) {
        die('Failed to create database: ' . htmlspecialchars($e->getMessage()));
    }
}

// Import schema file (if present)
if (!file_exists($schemaFile)) {
    echo "schema.sql not found, skipping import.<br>";
} else {
    // Connect once more using the new database
    try {
        $dsnDb = "mysql:host=$host;dbname=$dbName;charset=$charset";
        $pdoDb = new PDO($dsnDb, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    } catch (PDOException $e) {
        die('Connection to database failed after creation: ' . htmlspecialchars($e->getMessage()));
    }

    $sqlContent = file_get_contents($schemaFile);
    // Defensive cleanup: remove PHP tags and DELIMITER lines that might be present
    $sqlContent = preg_replace('/<\?(?:php)?/i', '', $sqlContent);
    $sqlContent = preg_replace('/\?>/i', '', $sqlContent);
    $sqlContent = preg_replace('/^DELIMITER.*$/mi', '', $sqlContent);
    $sqlContent = preg_replace('/(^|\r?\n)\s*(--|#).*?(\r?\n)/', "\1", $sqlContent);
    // Split by semicolon followed by newline(s) to get individual statements
    $statements = array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $sqlContent)));
    $executed = 0;
    foreach ($statements as $stmt) {
        if (empty($stmt)) continue;
        try {
            $pdoDb->exec($stmt);
            $executed++;
        } catch (PDOException $e) {
            // If it's a CREATE DATABASE that already exists, ignore; otherwise show error
            echo "<b>Warning:</b> failed executing statement: " . htmlspecialchars($e->getMessage()) . "<br>";
        }
    }
    echo "Imported schema (approx statements executed): $executed.<br>";
}

// Connect once more using the new database
try {
    $dsnDb = "mysql:host=$host;dbname=$dbName;charset=$charset";
    $pdoDb = new PDO($dsnDb, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die('Connection to database failed after creation: ' . htmlspecialchars($e->getMessage()));
}

$sqlContent = file_get_contents($schemaFile);
// Defensive cleanup: remove PHP tags and DELIMITER lines that might be present
$sqlContent = preg_replace('/<\?(?:php)?/i', '', $sqlContent);
$sqlContent = preg_replace('/\?>/i', '', $sqlContent);
$sqlContent = preg_replace('/^DELIMITER.*$/mi', '', $sqlContent);
$sqlContent = preg_replace('/(^|\r?\n)\s*(--|#).*?(\r?\n)/', "\1", $sqlContent);
// Split by semicolon followed by newline(s) to get individual statements
$statements = array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $sqlContent)));
$executed = 0;
foreach ($statements as $stmt) {
    if (empty($stmt)) continue;
    try {
        $pdoDb->exec($stmt);
        $executed++;
    } catch (PDOException $e) {
        // If it's a CREATE DATABASE that already exists, ignore; otherwise show error
        echo "<b>Warning:</b> failed executing statement: " . htmlspecialchars($e->getMessage()) . "<br>";
    }
}

echo "Done. You can now remove setup.php for security reasons and retry db_test.php or load the app.<br>";

?>