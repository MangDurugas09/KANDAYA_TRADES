<?php
// api_notifications.php - Purchase requests and approvals API

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

// GET: List notifications for current user
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    checkAuth();

    $userId = $_SESSION['user_id'];
    $type = $_GET['type'] ?? null; // 'purchase_request' or 'approval'

    $query = "SELECT * FROM notifications WHERE recipient_id = ?";
    $params = [$userId];
    $types = "i";

    if ($type) {
        $query .= " AND type = ?";
        $params[] = $type;
        $types .= "s";
    }

    $query .= " ORDER BY created_at DESC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = [];

    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }

    echo json_encode(['success' => true, 'notifications' => $notifications]);
    exit;
}

// POST: Send purchase request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'purchase-request') {
    checkAuth();

    $productId = $_POST['productId'] ?? null;

    if (!$productId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Product ID required']);
        exit;
    }

    $buyerId = $_SESSION['user_id'];

    // Get product and seller info
    $productQuery = "SELECT id, name, owner_id, price FROM products WHERE id = ?";
    $productStmt = $conn->prepare($productQuery);
    $productStmt->bind_param("i", $productId);
    $productStmt->execute();
    $productResult = $productStmt->get_result();

    if ($productResult->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }

    $product = $productResult->fetch_assoc();

    if ($product['owner_id'] === $buyerId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cannot buy your own product']);
        exit;
    }

    // Create notification
    $notifQuery = "INSERT INTO notifications (sender_id, recipient_id, type, product_id, product_name, message, created_at) 
                   VALUES (?, ?, 'purchase_request', ?, ?, ?, NOW())";
    $notifStmt = $conn->prepare($notifQuery);
    $message = "Wants to purchase your product";
    $notifStmt->bind_param("iiis", $buyerId, $product['owner_id'], $productId, $product['name'], $message);

    if ($notifStmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Purchase request sent']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error sending request']);
    }
    exit;
}

// POST: Approve purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'approve') {
    checkAuth();

    $notificationId = $_POST['notificationId'] ?? null;

    if (!$notificationId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Notification ID required']);
        exit;
    }

    $sellerId = $_SESSION['user_id'];

    // Get notification
    $notifQuery = "SELECT * FROM notifications WHERE id = ? AND recipient_id = ? AND type = 'purchase_request'";
    $notifStmt = $conn->prepare($notifQuery);
    $notifStmt->bind_param("ii", $notificationId, $sellerId);
    $notifStmt->execute();
    $notifResult = $notifStmt->get_result();

    if ($notifResult->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Notification not found']);
        exit;
    }

    $notification = $notifResult->fetch_assoc();

    // Delete purchase request
    $deleteQuery = "DELETE FROM notifications WHERE id = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param("i", $notificationId);
    $deleteStmt->execute();

    // Send approval notification to buyer
    $approvalQuery = "INSERT INTO notifications (sender_id, recipient_id, type, product_id, product_name, message, created_at) 
                      VALUES (?, ?, 'approval', ?, ?, ?, NOW())";
    $approvalStmt = $conn->prepare($approvalQuery);
    $approvalMessage = "Approved your purchase request";
    $approvalStmt->bind_param("iiis", $sellerId, $notification['sender_id'], $notification['product_id'], $notification['product_name'], $approvalMessage);

    if ($approvalStmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Purchase approved']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error approving purchase']);
    }
    exit;
}

// DELETE: Clear notification
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $action === 'clear') {
    checkAuth();

    $notificationId = $_GET['id'] ?? null;

    if (!$notificationId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Notification ID required']);
        exit;
    }

    $userId = $_SESSION['user_id'];

    $query = "DELETE FROM notifications WHERE id = ? AND recipient_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $notificationId, $userId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Notification cleared']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error clearing notification']);
    }
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>
