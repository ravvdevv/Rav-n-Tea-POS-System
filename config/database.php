<?php
// Check for local environment file first, then fall back to production
$envFile = file_exists(__DIR__ . '/../.env.local') 
    ? __DIR__ . '/../.env.local' 
    : __DIR__ . '/../.env';

// Load environment variables
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, "'\" \t\n\r\0\x0B");
            if (!array_key_exists($name, $_ENV)) {
                $_ENV[$name] = $value;
                putenv("$name=$value");
            }
        }
    }
}

// Database configuration - all values must be set in .env file
$host = getenv('DB_HOST');
$dbname = getenv('DB_NAME');
$username = getenv('DB_USER');
$password = getenv('DB_PASS');

// Validate that all required environment variables are set
$requiredVars = [
    'DB_HOST' => $host,
    'DB_NAME' => $dbname,
    'DB_USER' => $username,
    'DB_PASS' => $password
];

$missingVars = [];
foreach ($requiredVars as $varName => $value) {
    if (empty($value)) {
        $missingVars[] = $varName;
    }
}

if (!empty($missingVars)) {
    die('Error: The following required environment variables are not set: ' . implode(', ', $missingVars));
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    // If database doesn't exist, try to create it
    if ($e->getCode() === 1049) {
        try {
            $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            $pdo->exec("USE `$dbname`;");
            
            // Import the SQL schema
            $sql = file_get_contents(__DIR__ . '/../sql-setup.sql');
            $pdo->exec($sql);
            
            // Redirect to refresh the page
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        } catch (PDOException $e) {
            die("Error creating database: " . $e->getMessage());
        }
    } else {
        die("Connection failed: " . $e->getMessage());
    }
}
