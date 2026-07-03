<?php
/**
 * website/api/contact.php
 * POST → saves a contact/inquiry and creates admin notification.
 */
require_once __DIR__ . '/config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);

// Create table if needed
$conn->query("
    CREATE TABLE IF NOT EXISTS website_inquiries (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        name       VARCHAR(120) NOT NULL,
        phone      VARCHAR(30),
        email      VARCHAR(120),
        subject    VARCHAR(120),
        message    TEXT,
        is_read    TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$name    = clean($_POST['name']    ?? '');
$phone   = clean($_POST['phone']   ?? '');
$email   = clean($_POST['email']   ?? '');
$subject = clean($_POST['subject'] ?? '');
$message = clean($_POST['message'] ?? '');

if (!$name || !$phone || !$subject || !$message) fail('All required fields must be filled.');
if (strlen($message) < 5) fail('Message is too short.');

$stmt = $conn->prepare("
    INSERT INTO website_inquiries (name, phone, email, subject, message)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->bind_param('sssss', $name, $phone, $email, $subject, $message);
if (!$stmt->execute()) fail('Could not save inquiry. Please try again.');

ok(['message' => 'Thank you! We will get back to you within 24 hours.']);
