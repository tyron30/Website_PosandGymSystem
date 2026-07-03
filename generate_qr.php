<?php
// Generate QR code image for attendance system
// Returns a PNG image directly (not JSON)

$text = isset($_GET['text']) ? trim($_GET['text']) : '';

if (empty($text)) {
    $text = 'INVALID';
}

$qrlib = __DIR__ . '/phpqrcode/qrlib.php';
if (!file_exists($qrlib)) {
    http_response_code(500);
    exit('QR library not found.');
}
include_once $qrlib;

header('Content-Type: image/png');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

QRcode::png($text, false, QR_ECLEVEL_L, 4, 4);
exit;
?>