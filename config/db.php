<?php
$conn = new mysqli("localhost", "root", "", "gym_db");
if ($conn->connect_error) {
    die("Database connection failed");
}
include "auto_expire.php";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
