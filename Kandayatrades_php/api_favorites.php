<?php
// api_favorites.php - Favorites management API

session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? null;

function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
}

// GET: List user favorites
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    checkAuth();

    $userId = $_SESSION['user_id'];

    $query = "SELECT p.* FROM favorites f 
              JOIN products p ON f.product_id = p.id 
              WHERE f.user_id = ? 
              ORDER BY f.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $favorites = [];

    while ($row = $result->fetch_assoc()) {
        $favorites[] = $row;
    }

    echo json_encode(['success' => true, 'favorites' => $favorites]);
    exit;
}

// POST: Add to favorites
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add') {
    checkAuth();

    $productId = $_POST['productId'] ?? null;

    if (!$productId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Product ID required']);
        exit;
    }

    $userId = $_SESSION['user_id'];

    // Check if already favorited
    $checkQuery = "SELECT id FROM favorites WHERE user_id = ? AND product_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("ii", $userId, $productId);
    $checkStmt->execute();

    if ($checkStmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Already in favorites']);
        exit;
    }

    $insertQuery = "INSERT INTO favorites (user_id, product_id, created_at) VALUES (?, ?, NOW())";
    $insertStmt = $conn->prepare($insertQuery);
    $insertStmt->bind_param("ii", $userId, $productId);

    if ($insertStmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Added to favorites']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error adding to favorites']);
    }
    exit;
}

// DELETE: Remove from favorites
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $action === 'remove') {
    checkAuth();

    $productId = $_GET['productId'] ?? null;

    if (!$productId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Product ID required']);
        exit;
    }

    $userId = $_SESSION['user_id'];

    $query = "DELETE FROM favorites WHERE user_id = ? AND product_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $userId, $productId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Removed from favorites']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error removing from favorites']);
    }
    exit;
}

// GET: Check if product is favorited
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'check') {
    checkAuth();

    $productId = $_GET['productId'] ?? null;

    if (!$productId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Product ID required']);
        exit;
    }

    $userId = $_SESSION['user_id'];

    $query = "SELECT id FROM favorites WHERE user_id = ? AND product_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $userId, $productId);
    $stmt->execute();

    $isFavorited = $stmt->get_result()->num_rows > 0;
    echo json_encode(['success' => true, 'favorited' => $isFavorited]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>
