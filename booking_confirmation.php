<?php
/**
 * booking_confirmation.php
 * Public printable confirmation slip — accessible by the member via their unique token.
 * No login required. Token is 64-char hex (256-bit entropy).
 */
include "config/db.php";

$token = trim($_GET['token'] ?? '');
if (strlen($token) !== 64 || !ctype_xdigit($token)) {
    http_response_code(404);
    die('<h2 style="font-family:sans-serif;text-align:center;margin-top:4rem;color:#dc2626;">Invalid or expired confirmation link.</h2>');
}

// Ensure columns exist (migration safety)
$conn->query("ALTER TABLE website_bookings ADD COLUMN IF NOT EXISTS preferred_start_date DATE NULL");
$conn->query("ALTER TABLE website_bookings ADD COLUMN IF NOT EXISTS confirmation_token VARCHAR(64) NULL");

$stmt = $conn->prepare("SELECT * FROM website_bookings WHERE confirmation_token = ? LIMIT 1");
$stmt->bind_param('s', $token);
$stmt->execute();
$b = $stmt->get_result()->fetch_assoc();

if (!$b) {
    http_response_code(404);
    die('<h2 style="font-family:sans-serif;text-align:center;margin-top:4rem;color:#dc2626;">Booking not found. The confirmation link may be invalid.</h2>');
}

$settings = $conn->query("SELECT * FROM gym_settings WHERE id = 1")->fetch_assoc();
$gym_name = $settings['gym_name'] ?? 'Gym Management System';
$gym_logo = $settings['logo_path'] ?? '';

$status_map = [
    'pending'  => ['label'=>'PENDING VERIFICATION', 'color'=>'#92400e','bg'=>'#fef3c7','icon'=>'⏳'],
    'verified' => ['label'=>'APPROVED & ACTIVE',    'color'=>'#065f46','bg'=>'#d1fae5','icon'=>'✅'],
    'rejected' => ['label'=>'REJECTED',              'color'=>'#991b1b','bg'=>'#fee2e2','icon'=>'❌'],
];
$st = $status_map[$b['status']] ?? $status_map['pending'];

$plan_labels = ['session'=>'Per Session','monthly'=>'Monthly','student'=>'Student'];
$plan_label  = $plan_labels[$b['plan_type']] ?? ucfirst($b['plan_type']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Booking Confirmation — <?php echo htmlspecialchars($gym_name); ?></title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', Arial, sans-serif; background: #f3f4f6; color: #111827; }

  .page { max-width: 520px; margin: 2rem auto; padding: 1rem; }

  .slip { background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,.10); overflow: hidden; }

  .slip-header { background: linear-gradient(135deg, #0d0d0d 0%, #1a1a2e 100%); color: #fff; padding: 28px 28px 22px; text-align: center; }
  .slip-header img { width: 72px; height: 72px; border-radius: 50%; object-fit: cover; border: 3px solid rgba(255,255,255,.2); margin-bottom: 12px; }
  .slip-header h1 { font-size: 1.1rem; font-weight: 800; letter-spacing: .03em; }
  .slip-header p  { font-size: .78rem; color: rgba(255,255,255,.5); margin-top: 4px; }

  .status-bar { text-align: center; padding: 12px 28px; background: <?php echo $st['bg']; ?>; border-bottom: 1px solid rgba(0,0,0,.06); }
  .status-bar .st-icon { font-size: 1.4rem; }
  .status-bar .st-label { font-size: .75rem; font-weight: 800; letter-spacing: .08em; color: <?php echo $st['color']; ?>; display: block; margin-top: 4px; }

  .slip-body { padding: 24px 28px; }

  .slip-title { font-size: .68rem; text-transform: uppercase; letter-spacing: .08em; color: #9ca3af; text-align: center; margin-bottom: 20px; }

  .row { display: flex; justify-content: space-between; align-items: flex-start; padding: 10px 0; border-bottom: 1px dashed #e5e7eb; gap: 12px; }
  .row:last-child { border-bottom: none; }
  .row .lbl { font-size: .78rem; color: #6b7280; flex-shrink: 0; }
  .row .val { font-size: .85rem; font-weight: 600; text-align: right; word-break: break-word; }

  .amount { color: #059669; font-size: 1.1rem !important; }
  .ref    { font-family: monospace; font-size: .82rem !important; }
  .booking-id { color: #f59e0b; }

  .token-box { background: #f9fafb; border: 1px dashed #d1d5db; border-radius: 8px; padding: 12px; margin-top: 18px; text-align: center; }
  .token-box .tb-label { font-size: .65rem; text-transform: uppercase; letter-spacing: .07em; color: #9ca3af; margin-bottom: 6px; }
  .token-box .tb-val { font-family: monospace; font-size: .68rem; color: #6b7280; word-break: break-all; line-height: 1.6; }

  .notes-box { background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 12px 14px; margin-top: 16px; font-size: .8rem; color: #78350f; line-height: 1.6; }
  .notes-box strong { display: block; margin-bottom: 4px; }

  .slip-footer { background: #f9fafb; border-top: 1px solid #e5e7eb; padding: 16px 28px; text-align: center; }
  .slip-footer p { font-size: .72rem; color: #9ca3af; line-height: 1.7; }

  .print-bar { display: flex; gap: 10px; margin-top: 20px; justify-content: center; }
  .btn-print { display: inline-flex; align-items: center; gap: 8px; padding: 10px 24px; border: none; border-radius: 8px; font-size: .85rem; font-weight: 700; cursor: pointer; }
  .btn-print.primary { background: #111827; color: #fff; }
  .btn-print.secondary { background: #fff; color: #374151; border: 1.5px solid #d1d5db; }

  .watermark { text-align: center; margin-top: 20px; font-size: .7rem; color: #d1d5db; }

  @media print {
    body { background: #fff; }
    .page { margin: 0; padding: 0; }
    .slip { box-shadow: none; border-radius: 0; }
    .print-bar, .watermark { display: none; }
    .slip-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .status-bar { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  }
</style>
</head>
<body>
<div class="page">
  <div class="slip">

    <!-- Header -->
    <div class="slip-header">
      <?php if ($gym_logo && file_exists(__DIR__ . '/' . $gym_logo)): ?>
      <img src="<?php echo htmlspecialchars($gym_logo); ?>" alt="Gym Logo">
      <?php endif; ?>
      <h1><?php echo htmlspecialchars($gym_name); ?></h1>
      <p>Online Membership Booking Confirmation</p>
    </div>

    <!-- Status -->
    <div class="status-bar">
      <span class="st-icon"><?php echo $st['icon']; ?></span>
      <span class="st-label"><?php echo $st['label']; ?></span>
    </div>

    <!-- Body -->
    <div class="slip-body">
      <div class="slip-title">Official Booking Receipt</div>

      <div class="row">
        <span class="lbl">Booking ID</span>
        <span class="val booking-id">#<?php echo $b['id']; ?></span>
      </div>
      <div class="row">
        <span class="lbl">Member Name</span>
        <span class="val"><?php echo htmlspecialchars($b['full_name']); ?></span>
      </div>
      <div class="row">
        <span class="lbl">Phone</span>
        <span class="val"><?php echo htmlspecialchars($b['phone']); ?></span>
      </div>
      <div class="row">
        <span class="lbl">Email</span>
        <span class="val"><?php echo htmlspecialchars($b['email']); ?></span>
      </div>
      <div class="row">
        <span class="lbl">Membership Plan</span>
        <span class="val"><?php echo htmlspecialchars($plan_label); ?></span>
      </div>
      <?php if ($b['student_id']): ?>
      <div class="row">
        <span class="lbl">Student ID</span>
        <span class="val"><?php echo htmlspecialchars($b['student_id']); ?></span>
      </div>
      <?php endif; ?>
      <div class="row">
        <span class="lbl">Amount Paid</span>
        <span class="val amount">&#8369;<?php echo number_format($b['amount'], 2); ?></span>
      </div>
      <div class="row">
        <span class="lbl">Payment Method</span>
        <span class="val">GCash</span>
      </div>
      <div class="row">
        <span class="lbl">GCash Reference #</span>
        <span class="val ref"><?php echo htmlspecialchars($b['gcash_ref']); ?></span>
      </div>
      <div class="row">
        <span class="lbl">Preferred Start Date</span>
        <span class="val"><?php echo $b['preferred_start_date'] ? date('F j, Y', strtotime($b['preferred_start_date'])) : '—'; ?></span>
      </div>
      <div class="row">
        <span class="lbl">Submitted On</span>
        <span class="val"><?php echo date('F j, Y g:i A', strtotime($b['created_at'])); ?></span>
      </div>

      <?php if ($b['status'] === 'verified'): ?>
      <div class="row">
        <span class="lbl">Approved On</span>
        <span class="val" style="color:#059669;"><?php echo date('F j, Y g:i A', strtotime($b['updated_at'])); ?></span>
      </div>
      <?php endif; ?>

      <?php if (!empty($b['admin_notes']) && $b['status'] !== 'pending'): ?>
      <div class="notes-box">
        <strong><?php echo $b['status']==='verified' ? '✅ Admin Note:' : '❌ Rejection Reason:'; ?></strong>
        <?php echo htmlspecialchars($b['admin_notes']); ?>
      </div>
      <?php endif; ?>

      <?php if ($b['screenshot']): ?>
      <div style="margin-top:14px;text-align:center;">
        <a href="../<?php echo htmlspecialchars($b['screenshot']); ?>" target="_blank" style="font-size:.78rem;color:#2563eb;">
          📷 View GCash Screenshot
        </a>
      </div>
      <?php endif; ?>

      <!-- Confirmation token -->
      <div class="token-box">
        <div class="tb-label">Confirmation Token (for verification)</div>
        <div class="tb-val"><?php echo htmlspecialchars($b['confirmation_token']); ?></div>
      </div>

      <?php if ($b['status'] === 'pending'): ?>
      <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:12px 14px;margin-top:16px;font-size:.78rem;color:#1e40af;line-height:1.7;">
        <strong>⏳ What happens next?</strong><br>
        Our staff will verify your GCash payment and create your membership account.<br>
        This usually takes <strong>1–2 hours</strong> during business hours.<br><br>
        <strong>Present this confirmation</strong> at the gym front desk as proof of your online booking.
      </div>
      <?php elseif ($b['status'] === 'verified'): ?>
      <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:12px 14px;margin-top:16px;font-size:.78rem;color:#14532d;line-height:1.7;">
        <strong>✅ Membership Approved!</strong><br>
        Your membership is active. Present this confirmation at the gym front desk.<br>
        Your QR code will be provided to you upon your first visit.
      </div>
      <?php endif; ?>
    </div>

    <!-- Footer -->
    <div class="slip-footer">
      <p>
        This is an official confirmation from <?php echo htmlspecialchars($gym_name); ?>.<br>
        Keep this page as proof of your online booking and GCash payment.<br>
        Generated <?php echo date('F j, Y g:i A'); ?>
      </p>
    </div>

  </div>

  <!-- Print buttons (hidden on print) -->
  <div class="print-bar">
    <button class="btn-print primary" onclick="window.print()">🖨️ Print This Page</button>
    <button class="btn-print secondary" onclick="window.history.back()">← Back</button>
  </div>

  <div class="watermark"><?php echo htmlspecialchars($gym_name); ?> · Online Booking System</div>
</div>
</body>
</html>
