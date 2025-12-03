<?php
require_once 'db_connect.php';

echo "Testing database connection...\n";

$result = $conn->query('SELECT id, username, email FROM users LIMIT 1');
if ($result) {
    $row = $result->fetch_assoc();
    if ($row) {
        echo "Found user: " . $row['username'] . "\n";
    } else {
        echo "No users found in database\n";
    }
} else {
    echo "Query failed: " . $conn->error . "\n";
}

echo "\nTesting avatar base64 encoding...\n";
$result = $conn->query('SELECT id, username, avatar FROM users WHERE avatar IS NOT NULL LIMIT 1');
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $avatarLength = strlen($row['avatar']);
    $avatarBase64 = base64_encode($row['avatar']);
    echo "User: " . $row['username'] . "\n";
    echo "Avatar size: " . $avatarLength . " bytes\n";
    echo "Base64 size: " . strlen($avatarBase64) . " bytes\n";
    echo "Base64 preview: " . substr($avatarBase64, 0, 50) . "...\n";
} else {
    echo "No users with avatars found\n";
}

echo "\nDone!\n";
?>
