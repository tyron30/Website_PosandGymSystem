<?php
/**
 * website/api/config.php
 * Shared config for the website backend API.
 * Connects to the SAME gym_db used by the POS system.
 */

// ── CORS: allow the website to call these APIs ──────────────────────────────
$allowed_origins = [
    'http://localhost',
    'http://127.0.0.1',
    // Add your live domain here when deployed, e.g.:
    // 'https://olympicfitnessgym.com'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ── Database ─────────────────────────────────────────────────────────────────
$conn = new mysqli('localhost', 'root', '', 'gym_db');
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}
$conn->set_charset('utf8mb4');

// ── Helpers ───────────────────────────────────────────────────────────────────
function ok($data = [])  { echo json_encode(array_merge(['success' => true],  $data)); exit; }
function fail($msg, $code = 400) { http_response_code($code); echo json_encode(['success' => false, 'error' => $msg]); exit; }
function clean($s)       { return htmlspecialchars(strip_tags(trim($s)), ENT_QUOTES, 'UTF-8'); }
