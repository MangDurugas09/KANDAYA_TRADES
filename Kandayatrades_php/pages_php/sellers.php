<?php
// sellers.php - Seller dashboard with product management
// require_once '../db_connect.php';
// session_start();

// TODO: Verify seller is logged in
// TODO: Fetch products from database for current seller
// Example: $products = $mysqli->query("SELECT * FROM products WHERE seller_id = {$_SESSION['user_id']}");
?>
<section id="sellers" class="section">
    <h2>My Products</h2>
    <button id="post-product-btn" onclick="showPostForm()">Post a Product</button>
    <div id="post-form" style="display: none; margin-top: 20px;">
        <h3>Post a New Product</h3>
        <form id="product-form">
            <input type="text" id="product-name" placeholder="Product Name" required>
            <textarea id="product-desc" placeholder="Product Description" required></textarea>
            <select id="sale-type" required>
                <option value="">Select Sale Type</option>
                <option value="Fixed Price">Fixed Price</option>
                <option value="Auction">Auction</option>
            </select>
            <select id="product-category" required>
                <option value="foods">Foods</option>
                <option value="clothes">Clothes</option>
                <option value="vehicles">Vehicles</option>
                <option value="property">Property</option>
                <option value="others">Others</option>
            </select>
            <input type="number" id="price" placeholder="Price (₱)" step="0.01" min="0" required>
            <input type="file" id="product-image" accept="image/*" required>
            <img id="image-preview" style="display: none; max-width: 150px; margin-top: 10px;">
            <button type="submit">Post Product</button>
        </form>
    </div>
    <div id="seller-notifications" style="display: none; margin-top: 20px; background: #fffaf4; padding: 12px; border-radius: 8px;">
        <h3>Purchase Requests</h3>
        <ul id="notifications-list" style="list-style: none; padding: 0; margin: 6px 0;"></ul>
        <div style="margin-top: 8px;"><button type="button" id="clear-notifications-btn">Clear Notifications</button></div>
    </div>
    <h3>Your Posted Products</h3>
    <ul id="product-list"></ul>
</section>
