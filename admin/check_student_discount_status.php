<?php
include "../config/db.php";

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}

// Fetch gym settings
$settings = $conn->query("SELECT * FROM gym_settings WHERE id = 1")->fetch_assoc();
$student_discount_enabled = isset($settings['student_discount_enabled']) ? $settings['student_discount_enabled'] : true;

// Fetch members data for real-time updates
$query = "SELECT id, is_student FROM members ORDER BY created_at DESC";
$members_result = $conn->query($query);
$members = [];
while ($member = $members_result->fetch_assoc()) {
    $members[] = $member;
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'enabled' => $student_discount_enabled,
    'members' => $members
]);
?>
