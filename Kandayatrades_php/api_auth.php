<?php
// api_auth.php - Authentication API endpoints

session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // LOGIN
    if ($action === 'login') {
        $username = $data['username'] ?? null;
        $password = $data['password'] ?? null;

        if (!$username || !$password) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Username and password required']);
            exit;
        }

        $query = "SELECT id, username, password, role FROM users WHERE username = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
            exit;
        }

        $user = $result->fetch_assoc();

        // Verify password (using password_verify for hashed passwords, or direct comparison for demo)
        if (!password_verify($password, $user['password']) && $user['password'] !== $password) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
            exit;
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role']
            ]
        ]);
        exit;
    }

    // SIGNUP
    if ($action === 'signup') {
        $username = $data['username'] ?? null;
        $password = $data['password'] ?? null;
        $role = $data['role'] ?? 'customer';

        if (!$username || !$password) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Username and password required']);
            exit;
        }

        // Check if username exists
        $checkQuery = "SELECT id FROM users WHERE username = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("s", $username);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Username already exists']);
            exit;
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert user
        $insertQuery = "INSERT INTO users (username, password, role, created_at) VALUES (?, ?, ?, NOW())";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param("sss", $username, $hashedPassword, $role);

        if ($insertStmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Account created successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error creating account']);
        }
        exit;
    }

    // LOGOUT
    if ($action === 'logout') {
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
        exit;
    }
}

// GET: Check session status
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'check') {
        if (isset($_SESSION['user_id'])) {
            echo json_encode([
                'authenticated' => true,
                'user' => [
                    'id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username'],
                    'role' => $_SESSION['role']
                ]
            ]);
        } else {
            echo json_encode(['authenticated' => false]);
        }
        exit;
    }
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>
