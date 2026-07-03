<?php
// This standalone page has been superseded by the Change Password section
// now built into each role's Settings page (admin/settings.php and
// cashier/settings.php). Redirect old links/bookmarks there instead of
// keeping a duplicate, disconnected sidebar around.
include "config/db.php";

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'cashier'])) {
    header("Location: index.php");
    exit();
}

$role = $_SESSION['user']['role'];
header("Location: " . $role . "/settings.php");
exit();

