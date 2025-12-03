<?php
// api_products.php - Product management API endpoints

session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? null;

// Helper: Check authentication
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
}

// GET: Fetch all products or products by category
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') {
        $category = $_GET['category'] ?? null;

        $query = "SELECT p.*, u.username as owner_name FROM products p 
                  LEFT JOIN users u ON p.owner_id = u.id";
        $params = [];
        $types = "";

        if ($category) {
            $query .= " WHERE p.category = ?";
            $params[] = $category;
            $types = "s";
        }

        $query .= " ORDER BY p.created_at DESC";
        $stmt = $conn->prepare($query);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $products = [];

        while ($row = $result->fetch_assoc()) {
            // Base64 encode product image for JSON transmission
            if (!empty($row['image'])) {
                $row['image'] = base64_encode($row['image']);
            } else {
                $row['image'] = null;
            }
            $products[] = $row;
        }
        
        $stmt->close();

        echo json_encode(['success' => true, 'products' => $products], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // GET: Fetch single product
    if ($action === 'get') {
        $productId = $_GET['id'] ?? null;

        if (!$productId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Product ID required']);
            exit;
        }

        $query = "SELECT p.*, u.username as owner_name FROM products p 
                  LEFT JOIN users u ON p.owner_id = u.id WHERE p.id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit;
        }

        $product = $result->fetch_assoc();
        if (!empty($product['image'])) {
            $product['image'] = base64_encode($product['image']);
        } else {
            $product['image'] = null;
        }
        echo json_encode(['success' => true, 'product' => $product], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

// POST: Create product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    checkAuth();

    $name = $_POST['name'] ?? null;
    $desc = $_POST['desc'] ?? null;
    $category = $_POST['category'] ?? 'others';
    $price = $_POST['price'] ?? null;
    $type = $_POST['type'] ?? 'for_sale';

    if (!$name || !$price) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Name and price required']);
        exit;
    }

    // Handle image upload
    $imageData = null;
    if (isset($_FILES['image'])) {
        $file = $_FILES['image'];
        $fileType = $file['type'];
        
        // Validate image
        $allowed = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($fileType, $allowed)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid image format']);
            exit;
        }

        if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Image too large']);
            exit;
        }

        $imageData = file_get_contents($file['tmp_name']);
    }

    $userId = $_SESSION['user_id'];

    $query = "INSERT INTO products (owner_id, name, description, category, price, type, image, created_at) 
              VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isssdss", $userId, $name, $desc, $category, $price, $type, $imageData);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Product created', 'productId' => $conn->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error creating product']);
    }
    exit;
}

// DELETE: Delete product
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $action === 'delete') {
    checkAuth();

    $productId = $_GET['id'] ?? null;

    if (!$productId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Product ID required']);
        exit;
    }

    // Verify ownership
    $query = "SELECT owner_id FROM products WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }

    $product = $result->fetch_assoc();
    if ($product['owner_id'] !== $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Not authorized to delete this product']);
        exit;
    }

    $deleteQuery = "DELETE FROM products WHERE id = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param("i", $productId);

    if ($deleteStmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Product deleted']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error deleting product']);
    }
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>
