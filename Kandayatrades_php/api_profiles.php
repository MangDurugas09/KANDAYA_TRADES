<?php
// api_profiles.php - User profile API endpoints

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

// GET: Load user profile
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'load') {
    checkAuth();

    $userId = $_SESSION['user_id'];

    $query = "SELECT id, username, displayName, email, bio, avatar, role, created_at FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    $profile = $result->fetch_assoc();
    $stmt->close();
    
    // Base64 encode avatar for JSON transmission
    if (!empty($profile['avatar'])) {
        $profile['avatar'] = base64_encode($profile['avatar']);
    } else {
        $profile['avatar'] = null;
    }
    
    // Ensure all values are properly encoded
    $response = ['success' => true, 'profile' => $profile];
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// GET: Load profile by username (public)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get') {
    $username = $_GET['username'] ?? null;
    $userId = $_GET['user_id'] ?? null;

    if (!$username && !$userId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Username or user_id required']);
        exit;
    }

    if ($userId) {
        $query = "SELECT id, username, displayName, email, bio, avatar, role FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $userId);
    } else {
        $query = "SELECT id, username, displayName, email, bio, avatar, role FROM users WHERE username = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $username);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    $profile = $result->fetch_assoc();
    $stmt->close();
    
    // Base64 encode avatar for JSON transmission
    if (!empty($profile['avatar'])) {
        $profile['avatar'] = base64_encode($profile['avatar']);
    } else {
        $profile['avatar'] = null;
    }
    
    $response = ['success' => true, 'profile' => $profile];
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// POST: Update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update') {
    checkAuth();

    $userId = $_SESSION['user_id'];
    $displayName = $_POST['displayName'] ?? null;
    $email = $_POST['email'] ?? null;
    $bio = $_POST['bio'] ?? null;

    // Handle avatar upload
    $avatarData = null;
    if (isset($_FILES['avatar'])) {
        $file = $_FILES['avatar'];
        $fileType = $file['type'];
        
        $allowed = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($fileType, $allowed)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid image format']);
            exit;
        }

        if ($file['size'] > 2 * 1024 * 1024) { // 2MB limit
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Avatar too large']);
            exit;
        }

        $avatarData = file_get_contents($file['tmp_name']);
    }

    $query = "UPDATE users SET displayName = ?, email = ?, bio = ?";
    $params = [$displayName, $email, $bio];
    $types = "sss";

    if ($avatarData) {
        $query .= ", avatar = ?";
        $params[] = $avatarData;
        $types .= "s";
    }

    $query .= " WHERE id = ?";
    $params[] = $userId;
    $types .= "i";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Profile updated']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error updating profile']);
    }
    exit;
}

// POST: Upgrade to seller
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'become-seller') {
    checkAuth();

    $userId = $_SESSION['user_id'];
    $businessName = $_POST['businessName'] ?? null;
    $businessId = $_POST['businessId'] ?? null;
    $contact = $_POST['contact'] ?? null;
    $description = $_POST['description'] ?? null;

    if (!$businessName || !$businessId || !$contact) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Required fields missing']);
        exit;
    }

    $role = 'seller';
    $query = "UPDATE users SET displayName = ?, role = ?, email = ?, bio = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssi", $businessName, $role, $contact, $description, $userId);

    if ($stmt->execute()) {
        $_SESSION['role'] = 'seller';
        echo json_encode(['success' => true, 'message' => 'Upgraded to seller']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error upgrading account']);
    }
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>
