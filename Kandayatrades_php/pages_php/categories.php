<?php
// categories.php - Categories page with dynamic product loading from database
// require_once '../db_connect.php';

// TODO: Replace localStorage category fetching with database queries
// Example: $foods = $mysqli->query("SELECT * FROM products WHERE category = 'foods'");
?>
<section id="foods" class="section">
    <h2>Foods</h2>
    <ul class="category-product-list"></ul>
</section>

<section id="clothes" class="section">
    <h2>Clothes</h2>
    <ul class="category-product-list"></ul>
</section>

<section id="vehicles" class="section">
    <h2>Vehicles</h2>
    <ul class="category-product-list"></ul>
</section>

<section id="property" class="section">
    <h2>Property</h2>
    <ul class="category-product-list"></ul>
</section>

<section id="others" class="section">
    <h2>Others</h2>
    <ul class="category-product-list"></ul>
</section>
