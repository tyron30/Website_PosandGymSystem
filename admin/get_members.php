<?php
include "../config/db.php";

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'cashier'])) {
    http_response_code(403);
    exit();
}

header('Content-Type: application/json');

// Fetch active members
$result = $conn->query("SELECT id, member_code, fullname FROM members WHERE status = 'active' ORDER BY fullname");

$members = [];
while ($row = $result->fetch_assoc()) {
    $members[] = $row;
}

echo json_encode($members);
?>
