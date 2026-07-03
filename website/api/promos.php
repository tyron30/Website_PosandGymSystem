<?php
/**
 * website/api/promos.php
 * GET → returns active promos managed by admin in the website_promos table.
 * Admin can add/edit/delete promos from admin/website_settings.php
 */
require_once __DIR__ . '/config.php';

// Create table if it doesn't exist yet
$conn->query("
    CREATE TABLE IF NOT EXISTS website_promos (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        title       VARCHAR(120) NOT NULL,
        description TEXT,
        discount    VARCHAR(40),
        expiry_date DATE NULL,
        is_active   TINYINT(1) DEFAULT 1,
        sort_order  INT DEFAULT 0,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Insert default promos on first run
$count = $conn->query("SELECT COUNT(*) as c FROM website_promos")->fetch_assoc()['c'];
if ($count == 0) {
    $conn->query("INSERT INTO website_promos (title, description, discount, expiry_date, is_active) VALUES
        ('Student Discount', 'Present a valid school ID and get 20% off on your monthly membership. Available all year round.', '20% OFF', NULL, 1),
        ('First Day Free Trial', 'First-timers get a free day pass to try our facilities. No strings attached!', 'FREE', NULL, 1),
        ('Refer a Friend', 'Refer a friend who signs up monthly and both get 1 week free added to your membership.', 'BUNDLE', NULL, 1)
    ");
}

$result = $conn->query("
    SELECT id, title, description, discount, expiry_date
    FROM website_promos
    WHERE is_active = 1
    ORDER BY sort_order ASC, id ASC
");

$promos = [];
while ($row = $result->fetch_assoc()) {
    $promos[] = [
        'id'          => (int)$row['id'],
        'title'       => $row['title'],
        'description' => $row['description'],
        'discount'    => $row['discount'],
        'expiry_date' => $row['expiry_date'],
    ];
}

ok(['promos' => $promos]);
