<?php
// setup_database.php - Initialize database tables
// Visit: http://localhost/Kandayatrades_php/setup_database.php

require_once 'db_connect.php';

$setupSQL = "
-- 1. Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    bio TEXT,
    avatar LONGBLOB,
    role ENUM('customer', 'seller') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Products Table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    type ENUM('for_sale', 'for_rent', 'wanted') DEFAULT 'for_sale',
    image LONGBLOB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_owner_id (owner_id),
    INDEX idx_category (category),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Favorites Table
CREATE TABLE IF NOT EXISTS favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (user_id, product_id),
    INDEX idx_user_id (user_id),
    INDEX idx_product_id (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    recipient_id INT NOT NULL,
    type ENUM('purchase_request', 'approval') NOT NULL,
    product_id INT,
    product_name VARCHAR(255),
    message TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    INDEX idx_recipient_id (recipient_id),
    INDEX idx_sender_id (sender_id),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Purchase History Table
CREATE TABLE IF NOT EXISTS purchase_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT NOT NULL,
    seller_id INT NOT NULL,
    product_id INT,
    product_name VARCHAR(255),
    price DECIMAL(10, 2),
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    INDEX idx_buyer_id (buyer_id),
    INDEX idx_seller_id (seller_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Categories Table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    icon VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default categories
INSERT IGNORE INTO categories (name, description) VALUES
('foods', 'Food and beverages'),
('clothes', 'Clothing and fashion'),
('vehicles', 'Vehicles and auto parts'),
('property', 'Real estate and properties'),
('others', 'Miscellaneous items');
";

// Split multiple statements and execute
$statements = array_filter(array_map('trim', explode(';', $setupSQL)));

$successCount = 0;
$errors = [];

foreach ($statements as $statement) {
    if (!empty($statement)) {
        if ($conn->query($statement) === TRUE) {
            $successCount++;
        } else {
            $errors[] = $conn->error;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kandaya Trades - Database Setup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .success { color: #27ae60; background: #d5f4e6; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .error { color: #c0392b; background: #fadbd8; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .info { color: #2980b9; background: #d6eaf8; padding: 15px; border-radius: 5px; margin: 15px 0; }
        ul { line-height: 1.8; }
        code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
        a { color: #2980b9; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛠️ Kandaya Trades Database Setup</h1>
        
        <?php if (count($errors) === 0 && $successCount > 0): ?>
            <div class="success">
                <strong>✓ Database Setup Successful!</strong>
                <p>All tables created successfully. Executed <?php echo $successCount; ?> statements.</p>
            </div>
            
            <div class="info">
                <strong>✓ What was created:</strong>
                <ul>
                    <li><strong>users</strong> - User accounts and profiles</li>
                    <li><strong>products</strong> - Marketplace listings</li>
                    <li><strong>favorites</strong> - User favorite products</li>
                    <li><strong>notifications</strong> - Purchase requests and approvals</li>
                    <li><strong>purchase_history</strong> - Transaction history</li>
                    <li><strong>categories</strong> - Product categories</li>
                </ul>
            </div>

            <div class="info">
                <strong>📝 Next Steps:</strong>
                <ol>
                    <li>Visit <a href="index.php">http://localhost/Kandayatrades_php/</a> to access the application</li>
                    <li>Sign up for a new account or test the platform</li>
                    <li>All data will now be stored in the MySQL database</li>
                </ol>
            </div>
        <?php else: ?>
            <div class="error">
                <strong>✗ Database Setup Failed</strong>
                <p>Errors encountered during setup:</p>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="info">
                <strong>Troubleshooting:</strong>
                <ul>
                    <li>Make sure MySQL server is running</li>
                    <li>Check database credentials in <code>db_connect.php</code></li>
                    <li>Verify database <code>kandayatrades_database</code> exists</li>
                    <li>Run <code>database_schema.sql</code> manually via phpMyAdmin</li>
                </ul>
            </div>
        <?php endif; ?>

        <div class="info" style="margin-top: 30px;">
            <strong>Database Information:</strong>
            <ul>
                <li><strong>Database:</strong> <?php echo DB_NAME; ?></li>
                <li><strong>Host:</strong> <?php echo DB_HOST; ?></li>
                <li><strong>User:</strong> <?php echo DB_USER; ?></li>
                <li><strong>Status:</strong> <span style="color: #27ae60;">Connected ✓</span></li>
            </ul>
        </div>

        <hr style="margin: 30px 0;">
        
        <p style="font-size: 12px; color: #666;">
            For manual setup, see <a href="DATABASE_SETUP.md">DATABASE_SETUP.md</a><br>
            <strong>Note:</strong> This setup page can be deleted after initialization for security.
        </p>
    </div>
</body>
</html>
