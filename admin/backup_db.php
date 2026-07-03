<?php
include "../config/db.php";

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}

$user = $_SESSION['user'];
if ($user['role'] !== 'admin' && $user['role'] !== 'cashier') {
    header("Location: ../index.php");
    exit();
}

// Database credentials (consistent with MySQL Workbench)
$host = 'localhost';
$user_db = 'root';
$pass = '';
$db_name = 'gym_db';

// Generate filename with timestamp
$filename = 'gym_db_backup_' . date('Y-m-d_H-i-s') . '.sql';

// Set headers for download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Execute mysqldump command with full path
$command = '"C:\\xampp\\mysql\\bin\\mysqldump.exe" --host=' . $host . ' --user=' . $user_db . ' --password=' . $pass . ' ' . $db_name;
exec($command, $output, $return_var);
if ($return_var !== 0) {
    echo "Error: Unable to backup database.";
} else {
    echo implode("\n", $output);
}

exit();
?>
