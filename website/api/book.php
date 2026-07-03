<?php
/**
 * website/api/book.php
 * POST → saves an online membership booking + GCash payment info.
 * Creates a notification in the admin system (website_bookings table).
 * Admin verifies GCash reference then manually creates the member account.
 */
require_once __DIR__ . '/config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);

// Create bookings table if needed
$conn->query("
    CREATE TABLE IF NOT EXISTS website_bookings (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        full_name     VARCHAR(120) NOT NULL,
        phone         VARCHAR(30)  NOT NULL,
        email         VARCHAR(120) NOT NULL,
        plan_type     ENUM('session','monthly','student') NOT NULL,
        amount        DECIMAL(10,2) NOT NULL,
        gcash_ref     VARCHAR(80)  NOT NULL,
        screenshot    VARCHAR(255) NULL,
        student_id    VARCHAR(80)  NULL,
        status        ENUM('pending','verified','rejected') DEFAULT 'pending',
        admin_notes   TEXT NULL,
        is_read       TINYINT(1) DEFAULT 0,
        created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        preferred_start_date DATE NULL,
        confirmation_token   VARCHAR(64) NULL,
        updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");

$name       = clean($_POST['name']       ?? '');
$phone      = clean($_POST['phone']      ?? '');
$email      = clean($_POST['email']      ?? '');
$plan       = clean($_POST['plan']       ?? '');
$amount     = (float)($_POST['amount']   ?? 0);
$gcash_ref  = clean($_POST['gcash_ref']  ?? '');
$student_id         = clean($_POST['student_id']          ?? '');
$preferred_start    = clean($_POST['preferred_start_date'] ?? '');
// Validate preferred start date
if (!empty($preferred_start) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $preferred_start)) {
    $preferred_start = '';
}
if (empty($preferred_start)) {
    $preferred_start = date('Y-m-d'); // default to today if not provided
}

// Validate
if (!$name || !$phone || !$email || !$plan || !$gcash_ref) fail('All required fields must be filled.');
if (!in_array($plan, ['session','monthly','student'])) fail('Invalid plan selected.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Invalid email address.');
if (!preg_match('/^09\d{9}$/', preg_replace('/\s/', '', $phone))) fail('Invalid Philippine phone number (must start with 09).');
if ($amount <= 0) fail('Invalid amount.');
if ($plan === 'student' && !$student_id) fail('Student ID is required for the student plan.');
if (!isset($_FILES['screenshot']) || $_FILES['screenshot']['error'] !== UPLOAD_ERR_OK) {
    fail('A GCash payment screenshot is required as proof of payment.');
}

// Handle screenshot upload
$screenshot_path = null;
if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    if (!in_array($_FILES['screenshot']['type'], $allowed)) fail('Screenshot must be an image file.');
    if ($_FILES['screenshot']['size'] > 5 * 1024 * 1024) fail('Screenshot too large (max 5MB).');

    $dir = __DIR__ . '/../../uploads/gcash_receipts/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $ext  = pathinfo($_FILES['screenshot']['name'], PATHINFO_EXTENSION);
    $fname = 'gcash_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (move_uploaded_file($_FILES['screenshot']['tmp_name'], $dir . $fname)) {
        $screenshot_path = 'uploads/gcash_receipts/' . $fname;
    }
}

// ── Duplicate GCash reference ────────────────────────────────────────────────
$dup = $conn->prepare("SELECT id FROM website_bookings WHERE gcash_ref = ? AND status != 'rejected'");
$dup->bind_param('s', $gcash_ref);
$dup->execute();
if ($dup->get_result()->num_rows > 0) {
    fail('This GCash reference number has already been submitted. Please contact us if this is an error.');
}

// ── Duplicate pending/verified booking from same phone ───────────────────────
// Prevents multiple submissions: one active booking per phone at a time.
$dupPhone = $conn->prepare(
    "SELECT id FROM website_bookings WHERE phone = ? AND status IN ('pending','verified') LIMIT 1"
);
$dupPhone->bind_param('s', $phone);
$dupPhone->execute();
$dupPhoneRow = $dupPhone->get_result()->fetch_assoc();
if ($dupPhoneRow) {
    fail('You already have a pending or verified booking (#' . $dupPhoneRow['id'] . '). '
       . 'Please wait for the gym to process it. Contact us if you need assistance.');
}

// ── Active membership check ──────────────────────────────────────────────────
// Block re-booking if this phone number already has an ACTIVE membership
// that does NOT expire within the next 14 days (renewal window).
// If membership expires within 14 days or has already expired → allow renewal.
$phone_esc = $conn->real_escape_string($phone);
$name_esc  = $conn->real_escape_string($name);
$existing  = $conn->query("
    SELECT id, fullname, plan, end_date,
           DATEDIFF(end_date, CURDATE()) AS days_left
    FROM   members
    WHERE  (phone = '$phone_esc' OR LOWER(TRIM(fullname)) = LOWER(TRIM('$name_esc')))
      AND  status = 'ACTIVE'
    ORDER BY end_date DESC
    LIMIT 1
")->fetch_assoc();

if ($existing) {
    $daysLeft = (int)$existing['days_left'];
    if ($daysLeft > 14) {
        // Still has more than 14 days — not eligible to book a new plan online yet
        fail(
            'You already have an active ' . $existing['plan'] . ' membership '
            . 'that expires in ' . $daysLeft . ' days (' . date('F j, Y', strtotime($existing['end_date'])) . '). '
            . 'Online booking is available as a renewal within 14 days of your expiry. '
            . 'Please visit the gym or contact us if you need assistance.'
        );
    }
    // Within 14-day renewal window (or expired) → fall through and allow booking
}

// Save booking
$confirmation_token = bin2hex(random_bytes(32));
$stmt = $conn->prepare("
    INSERT INTO website_bookings (full_name, phone, email, plan_type, amount, gcash_ref, screenshot, student_id, preferred_start_date, confirmation_token)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param('ssssdsssss', $name, $phone, $email, $plan, $amount, $gcash_ref, $screenshot_path, $student_id, $preferred_start, $confirmation_token);
if (!$stmt->execute()) fail('Could not save booking. Please try again.');

$booking_id = $conn->insert_id;

ok([
    'booking_id'          => $booking_id,
    'confirmation_token'  => $confirmation_token,
    'full_name'           => $name,
    'plan_type'           => $plan,
    'amount'              => number_format($amount, 2),
    'gcash_ref'           => $gcash_ref,
    'preferred_start_date'=> $preferred_start,
    'submitted_at'        => date('F j, Y g:i A'),
    'message'             => 'Booking received! We will verify your GCash payment and confirm your membership within 1-2 hours.',
]);
