<?php
// account.php - User account management and profile
// require_once '../db_connect.php';
// session_start();

// TODO: Verify user is logged in
// TODO: Fetch user profile from database
// TODO: Fetch user favorites from database
// Example: $user = $mysqli->query("SELECT * FROM users WHERE id = {$_SESSION['user_id']}");
?>
<section id="account" class="section">
    <div id="account-info">
        <h2>My Account</h2>
        <div id="profile-view">
            <h3>Profile Info</h3>
            <p><strong>Username:</strong> <span id="profile-username"></span></p>
            <p><strong>Display Name:</strong> <span id="profile-displayname"></span></p>
            <p><strong>Email:</strong> <span id="profile-email"></span></p>
            <p><strong>Bio:</strong> <span id="profile-bio"></span></p>
            <img id="profile-avatar" style="max-width: 100px; border-radius: 50%; margin-top: 10px; display: none;">
            <div style="margin-top: 10px;">
                <button type="button" id="show-edit-profile-btn" onclick="showEditProfile()">Edit Profile</button>
                <button type="button" id="account-logout" onclick="logout()" style="margin-left: 8px;">Logout</button>
            </div>
        </div>
        <div id="profile-edit" style="display: none;">
            <h3>Edit Profile</h3>
            <label for="profile-avatar-input">Profile Picture:</label>
            <input type="file" id="profile-avatar-input" accept="image/*">
            <img id="profile-avatar-preview" style="max-width: 100px; border-radius: 50%; margin-top: 8px; display: none;">
            <input type="text" id="profile-displayname-input" placeholder="Display Name">
            <input type="email" id="profile-email-input" placeholder="Email">
            <textarea id="profile-bio-input" placeholder="Bio"></textarea>
            <div style="margin-top: 10px;">
                <button type="button" id="save-profile-btn" onclick="saveProfile()">Save</button>
                <button type="button" id="cancel-profile-btn" onclick="cancelEditProfile()">Cancel</button>
            </div>
        </div>
    </div>
    <div id="account-notifications" style="display: none; margin-top: 20px; background: #fffaf4; padding: 12px; border-radius: 8px;">
        <h3>Notifications</h3>
        <ul id="account-notifs-list" style="list-style:none; padding:0; margin:6px 0;"></ul>
        <div style="margin-top:8px;"><button type="button" id="clear-account-notifs-btn">Clear Notifications</button></div>
    </div>
    <div id="be-seller-box" style="display:none; margin-top:16px;">
        <h3>Become a Seller</h3>
        <p>Apply to become a registered seller. Fill up the form below for verification.</p>
        <form id="be-seller-form">
            <input type="text" id="seller-business" placeholder="Business / Display Name" required>
            <input type="text" id="seller-id" placeholder="Government ID / Tax ID" required>
            <input type="text" id="seller-contact" placeholder="Contact Number or Email" required>
            <textarea id="seller-desc" placeholder="Short description / proof" required></textarea>
            <div style="margin-top:8px;"><button type="submit">Request Seller Status</button></div>
        </form>
        <p id="be-seller-msg" style="display:none; color:green; margin-top:8px;"></p>
    </div>
    <h3>Your Favorites</h3>
    <ul id="favorites-list"></ul>
</section>
