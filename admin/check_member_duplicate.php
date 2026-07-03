<?php
/**
 * admin/check_member_duplicate.php
 * Lightweight AJAX endpoint called before approving a website booking.
 * Returns JSON { exists: bool, member?: { id, fullname, plan, days_left, end_date } }
 */
include "../config/db.php";

// Must be a logged-in admin or cashier
if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo json_encode(['exists' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$phone = trim($_GET['phone'] ?? '');
$name  = trim($_GET['name']  ?? '');

if (empty($phone) && empty($name)) {
    echo json_encode(['exists' => false]);
    exit;
}

$phone_esc = $conn->real_escape_string($phone);
$name_esc  = $conn->real_escape_string($name);

$conditions = [];
if ($phone_esc) $conditions[] = "phone = '$phone_esc'";
if ($name_esc)  $conditions[] = "LOWER(TRIM(fullname)) = LOWER(TRIM('$name_esc'))";
$where = implode(' OR ', $conditions);

$res = $conn->query("
    SELECT  id,
            fullname,
            phone,
            plan,
            end_date,
            DATEDIFF(end_date, CURDATE()) AS days_left
    FROM    members
    WHERE   ($where)
      AND   status = 'ACTIVE'
    ORDER BY id DESC
    LIMIT 1
");

if (!$res || $res->num_rows === 0) {
    echo json_encode(['exists' => false]);
    exit;
}

$member = $res->fetch_assoc();
echo json_encode([
    'exists' => true,
    'member' => [
        'id'        => (int)$member['id'],
        'fullname'  => $member['fullname'],
        'phone'     => $member['phone'],
        'plan'      => $member['plan'],
        'end_date'  => $member['end_date'],
        'days_left' => (int)$member['days_left'],
    ]
]);
