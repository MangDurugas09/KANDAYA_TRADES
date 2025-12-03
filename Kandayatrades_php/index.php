<?php
// index.php - Main entry point for Kandaya Trades marketplace
session_start();
require_once 'db_connect.php';

// Initialize authentication status and user data
$is_authenticated = false;
$user_profile = null;
$user_role = null;

// Verify user session and load profile if logged in
if (isset($_SESSION['user_id'])) {
    // User has an active session
    $user_id = $_SESSION['user_id'];
    
    // Load user profile and role from database
    $query = "SELECT id, username, role, email, created_at FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user_profile = $result->fetch_assoc();
            $user_role = $user_profile['role'];
            $is_authenticated = true;
        } else {
            // User ID in session but no matching user in database - invalid session
            session_destroy();
        }
        
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kandaya Trades</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Login Popup Modal -->
    <div id="login-modal" class="modal">
        <div class="modal-content">
            <h2>Login to Kandaya Trades</h2>
            <form id="login-form">
                <input type="text" id="username" placeholder="Username" required>
                <input type="password" id="password" placeholder="Password" required>
                <button type="submit">Login</button>
            </form>
            <p id="login-error" style="color: red; display: none;">Invalid credentials!</p>
                <p style="margin-top:8px;">Don't have an account? <a href="#" id="show-signup-link" onclick="showSignup()">Sign up</a></p>

                <!-- Signup Form (hidden by default) -->
                <div id="signup-box" style="display: none; margin-top:10px;">
                    <h2>Create an Account</h2>
                    <form id="signup-form">
                        <input type="text" id="signup-username" placeholder="Choose a username" required>
                        <label for="signup-role">I am a</label>
                        <select id="signup-role" required>
                            <option value="customer">Customer</option>
                            <option value="seller">Seller</option>
                        </select>
                        <input type="password" id="signup-password" placeholder="Password" required>
                        <input type="password" id="signup-password-confirm" placeholder="Confirm password" required>
                        <button type="submit">Sign up</button>
                    </form>
                    <p id="signup-error" style="color: red; display: none;"></p>
                    <p id="signup-success" style="color: green; display: none;"></p>
                    <p style="margin-top:8px;"><a href="#" id="back-to-login" onclick="showLogin()">Back to Login</a></p>
                </div>
        </div>
    </div>

    <!-- Main Page (Hidden Initially) -->
    <div id="main-page" style="display: none;">
        <nav class="navbar">
            <div class="nav-container">
                <h1 class="logo">Kandaya Trades</h1>
                <ul class="nav-menu">
                    <li><a href="#" onclick="showSection('home')">Home</a></li>
                    <li class="dropdown">
                        <a href="#" class="dropbtn">Category</a>
                        <div class="dropdown-content">
                            <a href="#" onclick="showSection('foods')">Foods</a>
                            <a href="#" onclick="showSection('clothes')">Clothes</a>
                            <a href="#" onclick="showSection('vehicles')">Vehicles</a>
                            <a href="#" onclick="showSection('property')">Property</a>
                            <a href="#" onclick="showSection('others')">Others</a>
                        </div>
                    </li>
                    <li><a href="#" onclick="showSection('about')">About</a></li>
                    <li id="nav-sellers"><a href="#" onclick="showSection('sellers')">Sellers</a></li>
                    <li id="nav-account"><a href="#" onclick="showSection('account')"><b>Account</b></a></li>
                </ul>
            </div>
        </nav>

        <div id="content">
            <!-- Pages will be loaded dynamically here -->
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
