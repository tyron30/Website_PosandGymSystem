<?php
/**
 * admin/website_settings.php
 * Shared control panel for managing the promotional website content.
 * Accessible by BOTH admin and cashier accounts (see role check below).
 * Physically lives in /admin/ but is linked from both the admin and
 * cashier sidebars — this is intentional, not a bug.
 */
include "../config/db.php";
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'cashier'])) {
    header('Location: ../index.php'); exit;
}
$user = $_SESSION['user'];
$back_dashboard = $user['role'] === 'admin' ? 'dashboard.php' : '../cashier/dashboard.php';

// Ensure website tables exist
$conn->query("CREATE TABLE IF NOT EXISTS website_promos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(120) NOT NULL, description TEXT, discount VARCHAR(40),
    expiry_date DATE NULL, is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$conn->query("CREATE TABLE IF NOT EXISTS website_inquiries (
    id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120), phone VARCHAR(30),
    email VARCHAR(120), subject VARCHAR(120), message TEXT,
    is_read TINYINT(1) DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$conn->query("CREATE TABLE IF NOT EXISTS website_bookings (
    id INT AUTO_INCREMENT PRIMARY KEY, full_name VARCHAR(120), phone VARCHAR(30),
    email VARCHAR(120), plan_type VARCHAR(20), amount DECIMAL(10,2),
    gcash_ref VARCHAR(80), screenshot VARCHAR(255), student_id VARCHAR(80),
    status ENUM('pending','verified','rejected') DEFAULT 'pending',
    admin_notes TEXT NULL, is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Add website columns to gym_settings if missing
foreach ([
    "ALTER TABLE gym_settings ADD COLUMN IF NOT EXISTS address VARCHAR(255) DEFAULT ''",
    "ALTER TABLE gym_settings ADD COLUMN IF NOT EXISTS phone VARCHAR(30) DEFAULT ''",
    "ALTER TABLE gym_settings ADD COLUMN IF NOT EXISTS email VARCHAR(120) DEFAULT ''",
    "ALTER TABLE gym_settings ADD COLUMN IF NOT EXISTS gcash_number VARCHAR(30) DEFAULT ''",
    "ALTER TABLE gym_settings ADD COLUMN IF NOT EXISTS gcash_name VARCHAR(100) DEFAULT ''",
    "ALTER TABLE gym_settings ADD COLUMN IF NOT EXISTS facebook_url VARCHAR(255) DEFAULT ''",
    "ALTER TABLE gym_settings ADD COLUMN IF NOT EXISTS instagram_url VARCHAR(255) DEFAULT ''",
    "ALTER TABLE gym_settings ADD COLUMN IF NOT EXISTS hours VARCHAR(255) DEFAULT 'Monday - Sunday: 5:00 AM - 10:00 PM'",
    "ALTER TABLE gym_settings ADD COLUMN IF NOT EXISTS about_text TEXT",
    "ALTER TABLE gym_settings ADD COLUMN IF NOT EXISTS map_embed TEXT",
] as $sql) { $conn->query($sql); }

$settings = $conn->query("SELECT * FROM gym_settings WHERE id = 1")->fetch_assoc();

// Migration: ensure new columns exist
$conn->query("ALTER TABLE website_bookings ADD COLUMN IF NOT EXISTS preferred_start_date DATE NULL");
$conn->query("ALTER TABLE website_bookings ADD COLUMN IF NOT EXISTS verified_by INT NULL");
$conn->query("ALTER TABLE website_bookings ADD COLUMN IF NOT EXISTS confirmation_token VARCHAR(64) NULL");
$conn->query("ALTER TABLE website_bookings ADD COLUMN IF NOT EXISTS created_member_id INT NULL");
$message  = '';
$msg_type = 'success';
$tab      = $_GET['tab'] ?? 'bookings';

// ── Handle POST actions ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Save website info
    if (isset($_POST['save_website_info'])) {
        $fields = ['address','phone','email','gcash_number','gcash_name','facebook_url','instagram_url','hours','about_text','map_embed'];
        $sets = []; $params = []; $types = '';
        foreach ($fields as $f) {
            $sets[]   = "$f = ?";
            $params[] = trim($_POST[$f] ?? '');
            $types   .= 's';
        }
        $params[] = 1; $types .= 'i';
        $stmt = $conn->prepare("UPDATE gym_settings SET " . implode(', ', $sets) . " WHERE id = ?");
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $settings = $conn->query("SELECT * FROM gym_settings WHERE id = 1")->fetch_assoc();
        $message = 'Website info updated successfully!';
        $tab = 'info';
    }

    // Add promo
    if (isset($_POST['add_promo'])) {
        $title  = trim($_POST['promo_title'] ?? '');
        $desc   = trim($_POST['promo_desc']  ?? '');
        $disc   = trim($_POST['promo_disc']  ?? '');
        $expiry = $_POST['promo_expiry'] ?: null;
        if ($title) {
            $stmt = $conn->prepare("INSERT INTO website_promos (title, description, discount, expiry_date) VALUES (?,?,?,?)");
            $stmt->bind_param('ssss', $title, $desc, $disc, $expiry);
            $stmt->execute();
            $message = 'Promo added!';
        }
        $tab = 'promos';
    }

    // Toggle promo
    if (isset($_POST['toggle_promo'])) {
        $id  = (int)$_POST['promo_id'];
        $cur = (int)$_POST['current_active'];
        $conn->query("UPDATE website_promos SET is_active = " . ($cur ? 0 : 1) . " WHERE id = $id");
        $tab = 'promos';
    }

    // Delete promo
    if (isset($_POST['delete_promo'])) {
        $conn->query("DELETE FROM website_promos WHERE id = " . (int)$_POST['promo_id']);
        $message = 'Promo deleted.';
        $tab = 'promos';
    }

    // ── Undo: restore any booking back to 'pending' for re-review ──────────────
    if (isset($_POST['undo_booking'])) {
        $id = (int)$_POST['booking_id'];
        $conn->query("UPDATE website_bookings SET status='pending', is_read=0 WHERE id=$id");
        $message  = 'Booking #' . $id . ' has been moved back to Pending for re-review.';
        $msg_type = 'warning';
        $tab      = 'bookings';
    }

    // Update booking status — auto-creates member on verify
    if (isset($_POST['update_booking'])) {
        $id     = (int)$_POST['booking_id'];
        $status = in_array($_POST['status'], ['pending','verified','rejected']) ? $_POST['status'] : 'pending';
        $notes  = trim($_POST['admin_notes'] ?? '');

        // Fetch current booking
        $bk = $conn->prepare("SELECT * FROM website_bookings WHERE id = ?");
        $bk->bind_param('i', $id);
        $bk->execute();
        $booking = $bk->get_result()->fetch_assoc();

        // Only auto-create member when status changes TO verified and not already done
        $was_verified   = ($booking['status'] === 'verified');
        $member_created = false;
        $force_approve  = (int)($_POST['force_approve'] ?? 0);
        $block_reason   = '';   // set if we refuse to create due to existing active member

        if ($status === 'verified' && !$was_verified && $booking) {

            // ── Guard 1: never re-create for the same booking ───────────────
            $already = $conn->query(
                "SELECT created_member_id FROM website_bookings WHERE id = $id"
            )->fetch_assoc();

            // ── Guard 2: check for existing ACTIVE member with same phone ───
            $existing_member = null;
            if (empty($already['created_member_id'])) {
                $phone_esc = $conn->real_escape_string($booking['phone']);
                $name_esc  = $conn->real_escape_string($booking['full_name']);
                $existing_member = $conn->query("
                    SELECT id, fullname, phone, plan,
                           DATEDIFF(end_date, CURDATE()) AS days_left,
                           end_date
                    FROM members
                    WHERE (phone = '$phone_esc' OR fullname = '$name_esc')
                      AND status = 'ACTIVE'
                    ORDER BY id DESC LIMIT 1
                ")->fetch_assoc();
            }

            // If existing active member found and admin did NOT force the approval → block
            if ($existing_member && !$force_approve) {
                $block_reason = "DUPLICATE_MEMBER|"
                    . $existing_member['id']           . "|"
                    . $existing_member['fullname']     . "|"
                    . $existing_member['plan']         . "|"
                    . $existing_member['days_left']    . "|"
                    . $existing_member['end_date'];
                $status = 'pending'; // revert — do not approve
            }

            if (empty($already['created_member_id']) && !$block_reason) {

                // ── Plan mapping ─────────────────────────────────────────────
                // website stores: 'session' | 'monthly' | 'student'
                // members.plan stores the EXACT label used in gym_settings fees
                // (Per Session, Half Month, 1 Month, 2 Months … 1 Year, etc.)
                // For the website we map: session→Per Session, monthly/student→1 Month
                $is_student = ($booking['plan_type'] === 'student') ? 1 : 0;
                $plan_map   = [
                    'session' => 'Per Session',
                    'monthly' => '1 Month',
                    'student' => '1 Month',
                ];
                $plan = $plan_map[$booking['plan_type']] ?? '1 Month';

                // ── Dates ────────────────────────────────────────────────────
                $start_date = (!empty($booking['preferred_start_date']))
                    ? $booking['preferred_start_date']
                    : date('Y-m-d');

                $end_date = ($booking['plan_type'] === 'session')
                    ? $start_date          // per-session expires same day
                    : date('Y-m-d', strtotime($start_date . ' +30 days'));

                $student_id = $booking['student_id'] ?? null;
                $email      = $booking['email']      ?? null;
                $phone      = $booking['phone']      ?? null;

                // ── Unique member code ───────────────────────────────────────
                $last_id     = (int)($conn->query("SELECT MAX(id) m FROM members")->fetch_assoc()['m'] ?? 0);
                $member_code = 'GYM-' . str_pad($last_id + 1, 5, '0', STR_PAD_LEFT);

                // ── Unique QR token ──────────────────────────────────────────
                do {
                    $qr_token = bin2hex(random_bytes(32));
                    $chk = $conn->prepare("SELECT id FROM members WHERE qr_token = ?");
                    $chk->bind_param('s', $qr_token);
                    $chk->execute(); $chk->store_result();
                } while ($chk->num_rows > 0);

                // ── QR image generation ──────────────────────────────────────
                $qr_code_path = null;
                $qrlib = __DIR__ . '/../phpqrcode/qrlib.php';
                if (file_exists($qrlib)) {
                    require_once $qrlib;
                    $dir = __DIR__ . '/../qr_codes';
                    if (!is_dir($dir)) mkdir($dir, 0755, true);

                    $tmp_file = $dir . '/tmp_' . $qr_token . '.png';
                    $filename = $qr_token . '.png';
                    $filepath = $dir . '/' . $filename;

                    $gym_s  = $conn->query("SELECT gym_name, logo_path FROM gym_settings WHERE id = 1")->fetch_assoc();
                    $g_name = $gym_s['gym_name'] ?? 'Gym';
                    $g_logo = !empty($gym_s['logo_path']) ? __DIR__ . '/../' . $gym_s['logo_path'] : '';

                    QRcode::png($qr_token, $tmp_file, QR_ECLEVEL_H, 10, 4);
                    if (file_exists($tmp_file)) {
                        $qr_img   = imagecreatefrompng($tmp_file);
                        $qr_w     = imagesx($qr_img); $qr_h = imagesy($qr_img);
                        $font     = 5;
                        $banner_h = 48; $footer_h = 36; $padding = 16;
                        $total_w  = $qr_w + $padding * 2;
                        $total_h  = $qr_h + $banner_h + $footer_h + $padding * 2;
                        $canvas   = imagecreatetruecolor($total_w, $total_h);
                        $teal     = imagecolorallocate($canvas, 0, 80, 80);
                        $white    = imagecolorallocate($canvas, 255, 255, 255);
                        $dark     = imagecolorallocate($canvas, 30, 30, 30);
                        imagefill($canvas, 0, 0, $white);
                        imagefilledrectangle($canvas, 0, 0, $total_w, $banner_h, $teal);
                        $max_c = (int)floor($total_w / imagefontwidth($font)) - 2;
                        $lbl   = strlen($g_name) > $max_c ? substr($g_name, 0, $max_c - 1) . '.' : $g_name;
                        $tw    = strlen($lbl) * imagefontwidth($font);
                        imagestring($canvas, $font, (int)(($total_w - $tw) / 2), (int)(($banner_h - imagefontheight($font)) / 2), $lbl, $white);
                        imagecopy($canvas, $qr_img, $padding, $banner_h + $padding, 0, 0, $qr_w, $qr_h);
                        $logo_size = (int)($qr_w * 0.22);
                        if (!empty($g_logo) && file_exists($g_logo)) {
                            $ext2 = strtolower(pathinfo($g_logo, PATHINFO_EXTENSION));
                            $ls = null;
                            if ($ext2==='png') $ls=imagecreatefrompng($g_logo);
                            elseif (in_array($ext2,['jpg','jpeg'])) $ls=imagecreatefromjpeg($g_logo);
                            elseif ($ext2==='gif') $ls=imagecreatefromgif($g_logo);
                            if ($ls) {
                                $lr = imagecreatetruecolor($logo_size, $logo_size);
                                imagealphablending($lr, false); imagesavealpha($lr, true);
                                imagefill($lr, 0, 0, imagecolorallocatealpha($lr, 0, 0, 0, 127));
                                imagecopyresampled($lr, $ls, 0, 0, 0, 0, $logo_size, $logo_size, imagesx($ls), imagesy($ls));
                                $lx = $padding + (int)(($qr_w - $logo_size) / 2);
                                $ly = $banner_h + $padding + (int)(($qr_h - $logo_size) / 2);
                                imagefilledellipse($canvas, $lx+(int)($logo_size/2), $ly+(int)($logo_size/2), $logo_size+16, $logo_size+16, $white);
                                imagecopy($canvas, $lr, $lx, $ly, 0, 0, $logo_size, $logo_size);
                                imagedestroy($lr); imagedestroy($ls);
                            }
                        }
                        $mbr_lbl = strlen($booking['full_name']) > $max_c ? substr($booking['full_name'], 0, $max_c - 1) . '.' : $booking['full_name'];
                        $nw = strlen($mbr_lbl) * imagefontwidth($font);
                        $fy = $banner_h + $padding + $qr_h + $padding;
                        imagestring($canvas, $font, (int)(($total_w - $nw)/2), (int)($fy + ($footer_h - imagefontheight($font))/2), $mbr_lbl, $dark);
                        imagepng($canvas, $filepath);
                        imagedestroy($canvas); imagedestroy($qr_img);
                        @unlink($tmp_file);
                        if (file_exists($filepath)) $qr_code_path = 'qr_codes/' . $filename;
                    }
                }

                // ── Insert member (ACTIVE, created_by this admin/cashier) ────
                $stmt2 = $conn->prepare("
                    INSERT INTO members
                        (member_code, fullname, email, phone, plan,
                         start_date, end_date, is_student, student_id,
                         qr_code, qr_token, status, created_by)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,'ACTIVE',?)
                ");
                $stmt2->bind_param(
                    'sssssssisssi',
                    $member_code, $booking['full_name'], $email, $phone, $plan,
                    $start_date, $end_date, $is_student, $student_id,
                    $qr_code_path, $qr_token, $user['id']
                );
                $stmt2->execute();
                $new_member_id = $conn->insert_id;

                if ($new_member_id) {
                    // ── Payment record: marked Paid via GCash ────────────────
                    // payment_method must match what the members page uses
                    // (it just checks EXISTS, so any non-null value works, but
                    //  use 'GCash' to match the cashier's convention)
                    $ref_no     = 'GCASH-' . strtoupper(substr($booking['gcash_ref'], 0, 12));
                    $receipt_no = 'R' . date('Ymd') . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
                    $pay_amt    = (float)$booking['amount'];
                    $pay_stmt   = $conn->prepare("
                        INSERT INTO payments
                            (member_id, amount, receipt_no, payment_method,
                             reference_no, payment_date, created_by)
                        VALUES (?,?,?,'GCash',?,NOW(),?)
                    ");
                    $pay_stmt->bind_param('idssi', $new_member_id, $pay_amt, $receipt_no, $ref_no, $user['id']);
                    $pay_stmt->execute();

                    // ── Link booking → member (prevents re-creation) ─────────
                    $link_stmt = $conn->prepare(
                        "UPDATE website_bookings SET created_member_id = ? WHERE id = ?"
                    );
                    $link_stmt->bind_param('ii', $new_member_id, $id);
                    $link_stmt->execute();

                    $member_created = true;
                    $notes = ($notes ? $notes . "\n" : '')
                           . "Member account created automatically. "
                           . "Member Code: $member_code. Start: $start_date. "
                           . "Payment recorded (GCash ref: {$booking['gcash_ref']}).";
                }
            }
        }

        if ($status === 'verified') {
            $verified_by = $user['id'];
            $stmt = $conn->prepare("UPDATE website_bookings SET status = ?, admin_notes = ?, is_read = 1, verified_by = ? WHERE id = ?");
            $stmt->bind_param('ssii', $status, $notes, $verified_by, $id);
        } else {
            $stmt = $conn->prepare("UPDATE website_bookings SET status = ?, admin_notes = ?, is_read = 1 WHERE id = ?");
            $stmt->bind_param('ssi', $status, $notes, $id);
        }
        $stmt->execute();

        if ($block_reason) {
            // Pass the structured reason to JS via a data attribute on the page
            $message  = '__DUPLICATE__' . $block_reason . '__BID__' . $id;
            $msg_type = 'warning';
        } else {
            $message  = 'Booking #' . $id . ' updated to ' . strtoupper($status) . '.';
            if ($member_created) $message .= ' ✅ Member account and QR code created automatically.';
            $msg_type = $member_created ? 'success' : 'info';
        }
        $tab = 'bookings';
    }

    // Mark inquiry read
    if (isset($_POST['mark_read'])) {
        $conn->query("UPDATE website_inquiries SET is_read = 1 WHERE id = " . (int)$_POST['inquiry_id']);
        $tab = 'inquiries';
    }
}

// ── Fetch data ───────────────────────────────────────────────────────────────
$promos    = $conn->query("SELECT * FROM website_promos ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC);
$inquiries = $conn->query("SELECT * FROM website_inquiries ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Pending bookings = active queue (the "inbox")
$pending_bookings = $conn->query(
    "SELECT * FROM website_bookings WHERE status = 'pending' ORDER BY created_at DESC"
)->fetch_all(MYSQLI_ASSOC);

// History = verified or rejected (already actioned)
$history_bookings = $conn->query(
    "SELECT wb.*, m.member_code, m.plan AS member_plan, m.end_date AS member_end
     FROM website_bookings wb
     LEFT JOIN members m ON m.id = wb.created_member_id
     WHERE wb.status IN ('verified','rejected')
     ORDER BY wb.created_at DESC"
)->fetch_all(MYSQLI_ASSOC);

$unread_inquiries = array_filter($inquiries, fn($i) => !$i['is_read']);

$settings_page = $conn->query("SELECT * FROM gym_settings WHERE id = 1")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Website Management — Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
:root { --gold: #F5A623; }
.page-header { background: linear-gradient(135deg, #0d0d0d 0%, #1a1a2e 100%); color: #fff; padding: 28px 0; margin-bottom: 28px; border-radius: 0 0 16px 16px; }
.page-header h1 { font-size: 1.5rem; font-weight: 800; margin: 0; }
.page-header p  { font-size: .85rem; color: rgba(255,255,255,.55); margin: 4px 0 0; }
.nav-tabs .nav-link { font-weight: 600; font-size: .85rem; color: #6b7280; }
.nav-tabs .nav-link.active { color: var(--gold); border-bottom-color: var(--gold); }
.badge-count { background: #ef4444; color: #fff; font-size: .65rem; border-radius: 10px; padding: 2px 6px; margin-left: 6px; }
.stat-card { border-radius: 12px; padding: 20px; border: 1px solid #e5e7eb; }
.stat-card .num { font-size: 1.8rem; font-weight: 800; }
.status-pending  { background: #fef3c7; color: #92400e; }
.status-verified { background: #d1fae5; color: #065f46; }
.status-rejected { background: #fee2e2; color: #991b1b; }
.booking-card { border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 16px; }
.booking-card.unread { border-left: 4px solid var(--gold); }
.promo-row { border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px 16px; margin-bottom: 10px; display: flex; align-items: center; gap: 14px; }
.preview-btn { background: #0d0d0d; color: #fff; text-decoration: none; padding: 8px 18px; border-radius: 8px; font-size: .82rem; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; }
.preview-btn:hover { background: #333; color: #fff; }
</style>
</head>
<body class="bg-light">

<!-- Header -->
<div class="page-header">
  <div class="container-fluid px-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
      <div>
        <div class="d-flex align-items-center gap-3 mb-1">
          <a href="<?= htmlspecialchars($back_dashboard) ?>" class="text-white-50 text-decoration-none" style="font-size:.82rem"><i class="fas fa-arrow-left me-1"></i> Back to Dashboard</a>
        </div>
        <h1><i class="fas fa-globe me-2" style="color:var(--gold)"></i> Website Management</h1>
        <p>Manage your promotional website content, bookings, and inquiries — <?= htmlspecialchars($user['fullname']) ?> (<?= ucfirst($user['role']) ?>)</p>
      </div>
      <div class="d-flex gap-2 align-items-center">
        <a href="../website/index.php" target="_blank" class="preview-btn">
          <i class="fas fa-external-link-alt"></i> Preview Website
        </a>
      </div>
    </div>
  </div>
</div>

<div class="container-fluid px-4 pb-5">

  <!-- Stats row -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="stat-card bg-white">
        <div class="text-muted small mb-1"><i class="fas fa-clock me-1 text-warning"></i> Pending Bookings</div>
        <div class="num text-warning"><?= count($pending_bookings) ?></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card bg-white">
        <div class="text-muted small mb-1"><i class="fas fa-envelope me-1 text-danger"></i> Unread Inquiries</div>
        <div class="num text-danger"><?= count($unread_inquiries) ?></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card bg-white">
        <div class="text-muted small mb-1"><i class="fas fa-check-circle me-1 text-success"></i> Verified Today</div>
        <?php
        $verified_today = $conn->query(
            "SELECT COUNT(*) c FROM website_bookings
             WHERE status = 'verified'
               AND DATE(updated_at) = CURDATE()"
        )->fetch_assoc()['c'] ?? 0;
        ?><div class="num text-success"><?= $verified_today ?></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card bg-white">
        <div class="text-muted small mb-1"><i class="fas fa-fire me-1" style="color:var(--gold)"></i> Active Promos</div>
        <div class="num" style="color:var(--gold)"><?= count(array_filter($promos, fn($p) => $p['is_active'])) ?></div>
      </div>
    </div>
  </div>

  <!-- Alert -->
  <?php if ($message): ?>
  <div class="alert alert-<?= $msg_type === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show">
    <i class="fas fa-<?= $msg_type === 'error' ? 'exclamation-triangle' : 'check-circle' ?> me-2"></i>
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>

  <!-- Tabs -->
  <ul class="nav nav-tabs mb-4" id="wsTabs">
    <li class="nav-item">
      <a class="nav-link <?= $tab==='bookings'?'active':'' ?>" href="?tab=bookings">
        <i class="fas fa-inbox me-1"></i> Pending Bookings
        <?php if (count($pending_bookings) > 0): ?><span class="badge-count"><?= count($pending_bookings) ?></span><?php endif; ?>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $tab==='history'?'active':'' ?>" href="?tab=history">
        <i class="fas fa-history me-1"></i> Booking History
        <?php if (count($history_bookings) > 0): ?><span class="badge-count" style="background:#6c757d;"><?= count($history_bookings) ?></span><?php endif; ?>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $tab==='inquiries'?'active':'' ?>" href="?tab=inquiries">
        <i class="fas fa-envelope me-1"></i> Inquiries
        <?php if (count($unread_inquiries) > 0): ?><span class="badge-count"><?= count($unread_inquiries) ?></span><?php endif; ?>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $tab==='promos'?'active':'' ?>" href="?tab=promos">
        <i class="fas fa-fire me-1"></i> Promos & Discounts
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $tab==='info'?'active':'' ?>" href="?tab=info">
        <i class="fas fa-cog me-1"></i> Website Info
      </a>
    </li>
  </ul>

  <!-- ══════════ PENDING BOOKINGS TAB ══════════ -->
  <?php if ($tab === 'bookings'): ?>
  <div>
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
      <div>
        <h5 class="fw-bold mb-0">Pending Bookings</h5>
        <p class="text-muted mb-0" style="font-size:.85rem;">New online membership requests awaiting review. Approved/rejected bookings move to <a href="?tab=history">Booking History</a>.</p>
      </div>
      <a href="?tab=history" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-history me-1"></i> View History (<?= count($history_bookings) ?>)
      </a>
    </div>
    <?php if (empty($pending_bookings)): ?>
      <div class="text-center py-5" style="background:#f8fafc;border-radius:16px;border:2px dashed #e2e8f0;">
        <i class="fas fa-check-circle fa-3x mb-3 d-block" style="color:#22c55e;"></i>
        <h6 class="fw-bold" style="color:#166534;">All caught up!</h6>
        <p class="text-muted mb-2">No pending bookings at this time.</p>
        <a href="?tab=history" class="btn btn-sm btn-outline-secondary">View Booking History</a>
      </div>
    <?php else: foreach ($pending_bookings as $b): ?>
      <div class="booking-card bg-white <?= !$b['is_read'] ? 'unread' : '' ?>">
        <div class="row align-items-start g-3">
          <div class="col-md-5">
            <div class="d-flex align-items-center gap-2 mb-2">
              <span class="badge <?= $b['status']==='verified'?'bg-success':($b['status']==='rejected'?'bg-danger':'bg-warning text-dark') ?>">
                <?= strtoupper($b['status']) ?>
              </span>
              <?php if (!$b['is_read'] && $b['status']==='pending'): ?>
                <span class="badge bg-danger">NEW</span>
              <?php endif; ?>
              <span class="text-muted small">#<?= $b['id'] ?></span>
            </div>
            <div class="fw-bold"><?= htmlspecialchars($b['full_name']) ?></div>
            <div class="small text-muted">
              <i class="fas fa-phone me-1"></i><?= htmlspecialchars($b['phone']) ?>
              &nbsp;·&nbsp;
              <i class="fas fa-envelope me-1"></i><?= htmlspecialchars($b['email']) ?>
            </div>
            <div class="mt-2">
              <?php
              // Human-readable plan label
              $plan_labels = [
                  'session' => 'Per Session',
                  'monthly' => '1 Month',
                  'student' => '1 Month (Student)',
              ];
              $plan_label = $plan_labels[$b['plan_type']] ?? strtoupper($b['plan_type']) . ' PLAN';
              ?>
              <span class="badge bg-light text-dark border"><?= htmlspecialchars($plan_label) ?></span>
              <strong class="ms-2 text-success">₱<?= number_format($b['amount'], 2) ?></strong>
              <?php if ($b['status'] === 'verified'): ?>
                <span class="badge bg-success ms-1"><i class="fas fa-check-circle me-1"></i>PAID</span>
              <?php endif; ?>
            </div>
            <?php if ($b['student_id']): ?>
              <div class="small text-muted mt-1"><i class="fas fa-id-card me-1"></i> Student ID: <?= htmlspecialchars($b['student_id']) ?></div>
            <?php endif; ?>
            <?php if (!empty($b['preferred_start_date'])): ?>
              <div class="small mt-1" style="color:#059669;font-weight:600;"><i class="fas fa-calendar-check me-1"></i> Start: <?= date('M d, Y', strtotime($b['preferred_start_date'])) ?></div>
            <?php endif; ?>
            <?php if (!empty($b['created_member_id'])): ?>
              <div class="small mt-1 text-success fw-bold">
                <i class="fas fa-user-check me-1"></i>
                Member #<?= $b['created_member_id'] ?> created &amp; <span class="badge bg-success">Paid</span>
              </div>
            <?php endif; ?>
            <div class="small text-muted mt-1"><i class="fas fa-clock me-1"></i> <?= date('M d, Y g:i A', strtotime($b['created_at'])) ?></div>
            <?php if (!empty($b['confirmation_token'])): ?>
              <a href="../booking_confirmation.php?token=<?= urlencode($b['confirmation_token']) ?>" target="_blank" class="btn btn-sm btn-outline-dark mt-2" style="font-size:.75rem;">
                <i class="fas fa-file-alt me-1"></i> View Confirmation Slip
              </a>
            <?php endif; ?>
          </div>
          <div class="col-md-3">
            <div class="small text-muted mb-1">GCash Reference</div>
            <div class="fw-bold font-monospace"><?= htmlspecialchars($b['gcash_ref']) ?></div>
            <?php if ($b['screenshot']): ?>
              <a href="../<?= htmlspecialchars($b['screenshot']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary mt-2">
                <i class="fas fa-image me-1"></i> View Receipt
              </a>
            <?php endif; ?>
          </div>
          <div class="col-md-4">
            <form method="POST" id="bookingForm-<?= $b['id'] ?>">
              <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
              <input type="hidden" name="status"     value="<?= htmlspecialchars($b['status']) ?>">
              <input type="hidden" name="force_approve" value="0" id="force_approve_<?= $b['id'] ?>">
              <div class="mb-2">
                <textarea name="admin_notes" class="form-control form-control-sm" rows="2" placeholder="Admin notes..."><?= htmlspecialchars($b['admin_notes'] ?? '') ?></textarea>
              </div>
              <div class="d-flex gap-2 mb-2">
                <?php if (!empty($b['created_member_id'])): ?>
                  <button type="button" class="btn btn-sm btn-success flex-grow-1" disabled>
                    <i class="fas fa-user-check me-1"></i> Approved &amp; Paid
                  </button>
                <?php elseif ($b['status'] !== 'verified'): ?>
                  <button type="button"
                    class="btn btn-sm btn-success flex-grow-1"
                    onclick="checkAndApprove(<?= $b['id'] ?>, '<?= addslashes(htmlspecialchars($b['full_name'])) ?>', '<?= addslashes(htmlspecialchars($b['phone'])) ?>')">
                    <i class="fas fa-check me-1"></i> Approve &amp; Add Member
                  </button>
                <?php endif; ?>
                <?php if ($b['status'] !== 'rejected'): ?>
                <button type="button"
                  class="btn btn-sm btn-danger <?= !empty($b['created_member_id']) ? '' : 'flex-grow-1' ?>"
                  onclick="rejectBooking(<?= $b['id'] ?>)">
                  <i class="fas fa-times me-1"></i> Reject
                </button>
                <?php endif; ?>
              </div>
              <button type="submit" name="update_booking" class="btn btn-sm btn-dark w-100">
                <i class="fas fa-save me-1"></i> Save Notes / Status
              </button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <!-- ══════════ HISTORY TAB ══════════ -->
  <?php elseif ($tab === 'history'): ?>
  <div>
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
      <div>
        <h5 class="fw-bold mb-0">Booking History</h5>
        <p class="text-muted mb-0" style="font-size:.85rem;">All approved and rejected bookings. You can undo, re-approve, or re-reject any entry.</p>
      </div>
      <a href="?tab=bookings" class="btn btn-sm btn-outline-primary">
        <i class="fas fa-inbox me-1"></i> Back to Pending
      </a>
    </div>

    <?php if (empty($history_bookings)): ?>
      <div class="text-center py-5" style="background:#f8fafc;border-radius:16px;border:2px dashed #e2e8f0;">
        <i class="fas fa-history fa-3x mb-3 d-block" style="color:#94a3b8;"></i>
        <p class="text-muted mb-0">No booking history yet.</p>
      </div>
    <?php else: foreach ($history_bookings as $b):
      $isVerified = $b['status'] === 'verified';
      $plan_labels = ['session'=>'Per Session','monthly'=>'1 Month','student'=>'1 Month (Student)'];
      $plan_label  = $plan_labels[$b['plan_type']] ?? strtoupper($b['plan_type']);
    ?>
    <div class="booking-card bg-white mb-3"
         style="border-left:4px solid <?= $isVerified ? '#22c55e' : '#ef4444' ?>;opacity:<?= $isVerified ? '1' : '.88' ?>;">
      <div class="row align-items-start g-3">

        <!-- Left: person info -->
        <div class="col-md-4">
          <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
            <span class="badge <?= $isVerified ? 'bg-success' : 'bg-danger' ?>" style="font-size:.8rem;">
              <?= $isVerified ? '✅ APPROVED' : '❌ REJECTED' ?>
            </span>
            <span class="text-muted" style="font-size:.8rem;">#<?= $b['id'] ?></span>
          </div>
          <div class="fw-bold" style="font-size:1.05rem;"><?= htmlspecialchars($b['full_name']) ?></div>
          <div class="small text-muted mt-1">
            <i class="fas fa-phone me-1"></i><?= htmlspecialchars($b['phone']) ?>
            <?php if ($b['email']): ?>&nbsp;·&nbsp;<i class="fas fa-envelope me-1"></i><?= htmlspecialchars($b['email']) ?><?php endif; ?>
          </div>
          <div class="mt-2 d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-light text-dark border"><?= htmlspecialchars($plan_label) ?></span>
            <strong class="text-success">₱<?= number_format($b['amount'],2) ?></strong>
            <?php if ($isVerified): ?><span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>PAID</span><?php endif; ?>
          </div>
          <?php if ($b['student_id']): ?><div class="small text-muted mt-1"><i class="fas fa-id-card me-1"></i><?= htmlspecialchars($b['student_id']) ?></div><?php endif; ?>
          <?php if ($isVerified && $b['member_code']): ?>
          <div class="small mt-1 fw-semibold" style="color:#0369a1;">
            <i class="fas fa-user-check me-1"></i>
            Member: <?= htmlspecialchars($b['member_code']) ?>
            <?php if ($b['member_end']): ?>
              · Expires <?= date('M d, Y', strtotime($b['member_end'])) ?>
            <?php endif; ?>
          </div>
          <?php endif; ?>
          <div class="small text-muted mt-1"><i class="fas fa-clock me-1"></i><?= date('M d, Y g:i A', strtotime($b['created_at'])) ?></div>
        </div>

        <!-- Middle: GCash info -->
        <div class="col-md-3">
          <div class="small text-muted mb-1">GCash Reference</div>
          <div class="fw-bold fs-5"><?= htmlspecialchars($b['gcash_ref']) ?></div>
          <?php if ($b['screenshot']): ?>
          <button type="button" class="btn btn-sm btn-outline-secondary mt-2"
            onclick="showReceipt('<?= htmlspecialchars($b['screenshot']) ?>')">
            <i class="fas fa-image me-1"></i> View Receipt
          </button>
          <?php endif; ?>
        </div>

        <!-- Right: admin notes + action buttons -->
        <div class="col-md-5">
          <form method="POST" id="historyForm-<?= $b['id'] ?>">
            <input type="hidden" name="booking_id"    value="<?= $b['id'] ?>">
            <input type="hidden" name="status"        value="<?= htmlspecialchars($b['status']) ?>">
            <input type="hidden" name="force_approve" value="0" id="force_approve_<?= $b['id'] ?>">
            <div class="mb-2">
              <label class="form-label small fw-semibold text-muted">Admin Notes</label>
              <textarea name="admin_notes" class="form-control form-control-sm" rows="2"
                placeholder="Admin notes..."><?= htmlspecialchars($b['admin_notes'] ?? '') ?></textarea>
            </div>
            <div class="d-flex flex-column gap-2">

              <!-- Undo → restore to pending -->
              <button type="button"
                class="btn btn-sm btn-outline-warning fw-semibold"
                onclick="undoBooking(<?= $b['id'] ?>)"
                title="Move back to pending — allows re-review">
                <i class="fas fa-undo me-1"></i> Undo — Move Back to Pending
              </button>

              <?php if (!$isVerified): ?>
              <!-- Re-approve (only shown for rejected) -->
              <button type="button"
                class="btn btn-sm btn-success fw-semibold"
                onclick="checkAndApprove(<?= $b['id'] ?>, '<?= addslashes(htmlspecialchars($b['full_name'])) ?>', '<?= addslashes(htmlspecialchars($b['phone'])) ?>')"
                <?= !empty($b['created_member_id']) ? 'disabled title="Member already created"' : '' ?>>
                <i class="fas fa-check me-1"></i> Approve &amp; Add Member
              </button>
              <?php else: ?>
              <!-- Re-reject (only shown for verified) -->
              <button type="button"
                class="btn btn-sm btn-outline-danger fw-semibold"
                onclick="rejectBooking(<?= $b['id'] ?>)">
                <i class="fas fa-times me-1"></i> Re-Reject This Booking
              </button>
              <?php endif; ?>

              <button type="submit" name="update_booking" class="btn btn-sm btn-dark">
                <i class="fas fa-save me-1"></i> Save Notes
              </button>
            </div>
          </form>
        </div>

      </div>
    </div>
    <?php endforeach; endif; ?>
  </div>

  <!-- ══════════ INQUIRIES TAB ══════════ -->
  <?php elseif ($tab === 'inquiries'): ?>
  <div>
    <h5 class="fw-bold mb-3">Contact Inquiries from Website</h5>
    <?php if (empty($inquiries)): ?>
      <div class="text-center py-5 text-muted"><i class="fas fa-inbox fa-3x mb-3 d-block"></i> No inquiries yet</div>
    <?php else: foreach ($inquiries as $inq): ?>
      <div class="booking-card bg-white <?= !$inq['is_read'] ? 'unread' : '' ?>">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
          <div>
            <?php if (!$inq['is_read']): ?><span class="badge bg-danger me-2">NEW</span><?php endif; ?>
            <strong><?= htmlspecialchars($inq['name']) ?></strong>
            <span class="text-muted small ms-2"><?= date('M d, Y g:i A', strtotime($inq['created_at'])) ?></span>
          </div>
          <?php if (!$inq['is_read']): ?>
          <form method="POST" class="d-inline">
            <input type="hidden" name="inquiry_id" value="<?= $inq['id'] ?>">
            <button type="submit" name="mark_read" class="btn btn-sm btn-outline-secondary">
              <i class="fas fa-check me-1"></i> Mark Read
            </button>
          </form>
          <?php endif; ?>
        </div>
        <div class="small text-muted mt-2">
          <i class="fas fa-phone me-1"></i><?= htmlspecialchars($inq['phone']) ?>
          <?php if ($inq['email']): ?>
            &nbsp;·&nbsp;<i class="fas fa-envelope me-1"></i><?= htmlspecialchars($inq['email']) ?>
          <?php endif; ?>
          &nbsp;·&nbsp;<strong>Re: <?= htmlspecialchars($inq['subject']) ?></strong>
        </div>
        <div class="mt-2 p-3 bg-light rounded" style="font-size:.88rem">
          <?= nl2br(htmlspecialchars($inq['message'])) ?>
        </div>
        <div class="mt-2">
          <a href="tel:<?= htmlspecialchars($inq['phone']) ?>" class="btn btn-sm btn-outline-success">
            <i class="fas fa-phone me-1"></i> Call Back
          </a>
          <?php if ($inq['email']): ?>
          <a href="mailto:<?= htmlspecialchars($inq['email']) ?>" class="btn btn-sm btn-outline-primary ms-2">
            <i class="fas fa-envelope me-1"></i> Reply by Email
          </a>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <!-- ══════════ PROMOS TAB ══════════ -->
  <?php elseif ($tab === 'promos'): ?>
  <div class="row g-4">
    <div class="col-md-5">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <h6 class="fw-bold mb-3"><i class="fas fa-plus-circle me-2 text-warning"></i>Add New Promo</h6>
          <form method="POST">
            <div class="mb-3">
              <label class="form-label small fw-semibold">Promo Title *</label>
              <input type="text" name="promo_title" class="form-control" placeholder="e.g. Summer Special" required>
            </div>
            <div class="mb-3">
              <label class="form-label small fw-semibold">Description</label>
              <textarea name="promo_desc" class="form-control" rows="3" placeholder="What's included in this promo?"></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label small fw-semibold">Discount Label</label>
              <input type="text" name="promo_disc" class="form-control" placeholder="e.g. 30% OFF or FREE or BUNDLE">
            </div>
            <div class="mb-3">
              <label class="form-label small fw-semibold">Expiry Date (optional)</label>
              <input type="date" name="promo_expiry" class="form-control" min="<?= date('Y-m-d') ?>">
            </div>
            <button type="submit" name="add_promo" class="btn btn-warning w-100 fw-bold">
              <i class="fas fa-plus me-1"></i> Add Promo
            </button>
          </form>
        </div>
      </div>
    </div>
    <div class="col-md-7">
      <h6 class="fw-bold mb-3">Current Promos <span class="text-muted small fw-normal">(shown on website)</span></h6>
      <?php if (empty($promos)): ?>
        <div class="text-center py-4 text-muted">No promos yet</div>
      <?php else: foreach ($promos as $p): ?>
        <div class="promo-row bg-white <?= !$p['is_active'] ? 'opacity-50' : '' ?>">
          <div class="flex-grow-1">
            <?php if ($p['discount']): ?>
              <span class="badge bg-warning text-dark me-2"><?= htmlspecialchars($p['discount']) ?></span>
            <?php endif; ?>
            <strong><?= htmlspecialchars($p['title']) ?></strong>
            <?php if (!$p['is_active']): ?><span class="badge bg-secondary ms-2">Hidden</span><?php endif; ?>
            <div class="small text-muted mt-1"><?= htmlspecialchars(substr($p['description'] ?? '', 0, 80)) ?><?= strlen($p['description'] ?? '') > 80 ? '…' : '' ?></div>
            <?php if ($p['expiry_date']): ?>
              <div class="small text-danger mt-1"><i class="fas fa-calendar me-1"></i> Expires: <?= date('M d, Y', strtotime($p['expiry_date'])) ?></div>
            <?php endif; ?>
          </div>
          <div class="d-flex gap-2 flex-shrink-0">
            <form method="POST">
              <input type="hidden" name="promo_id" value="<?= $p['id'] ?>">
              <input type="hidden" name="current_active" value="<?= $p['is_active'] ?>">
              <button type="submit" name="toggle_promo" class="btn btn-sm <?= $p['is_active'] ? 'btn-outline-secondary' : 'btn-outline-success' ?>">
                <i class="fas fa-<?= $p['is_active'] ? 'eye-slash' : 'eye' ?>"></i>
              </button>
            </form>
            <form method="POST" onsubmit="return confirm('Delete this promo?')">
              <input type="hidden" name="promo_id" value="<?= $p['id'] ?>">
              <button type="submit" name="delete_promo" class="btn btn-sm btn-outline-danger">
                <i class="fas fa-trash"></i>
              </button>
            </form>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <!-- ══════════ WEBSITE INFO TAB ══════════ -->
  <?php elseif ($tab === 'info'): ?>
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
          <h5 class="fw-bold mb-4"><i class="fas fa-globe me-2 text-warning"></i>Website Content Settings</h5>
          <form method="POST">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label fw-semibold">Gym Address</label>
                <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($settings_page['address'] ?? '') ?>" placeholder="Full gym address">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Phone Number</label>
                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($settings_page['phone'] ?? '') ?>" placeholder="09XX XXX XXXX">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Email Address</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($settings_page['email'] ?? '') ?>" placeholder="gym@email.com">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold"><i class="fas fa-mobile-alt me-1 text-primary"></i> GCash Number</label>
                <input type="text" name="gcash_number" class="form-control" value="<?= htmlspecialchars($settings_page['gcash_number'] ?? '') ?>" placeholder="09XX XXX XXXX">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">GCash Account Name</label>
                <input type="text" name="gcash_name" class="form-control" value="<?= htmlspecialchars($settings_page['gcash_name'] ?? '') ?>" placeholder="Name on GCash">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold"><i class="fab fa-facebook me-1 text-primary"></i> Facebook URL</label>
                <input type="url" name="facebook_url" class="form-control" value="<?= htmlspecialchars($settings_page['facebook_url'] ?? '') ?>" placeholder="https://facebook.com/...">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold"><i class="fab fa-instagram me-1 text-danger"></i> Instagram URL</label>
                <input type="url" name="instagram_url" class="form-control" value="<?= htmlspecialchars($settings_page['instagram_url'] ?? '') ?>" placeholder="https://instagram.com/...">
              </div>
              <div class="col-12">
                <label class="form-label fw-semibold">Operating Hours</label>
                <input type="text" name="hours" class="form-control" value="<?= htmlspecialchars($settings_page['hours'] ?? '') ?>" placeholder="Monday – Sunday: 5:00 AM – 10:00 PM">
              </div>
              <div class="col-12">
                <label class="form-label fw-semibold">About Us Text</label>
                <textarea name="about_text" class="form-control" rows="4" placeholder="Describe your gym for website visitors..."><?= htmlspecialchars($settings_page['about_text'] ?? '') ?></textarea>
              </div>
              <div class="col-12">
                <label class="form-label fw-semibold">Google Maps Embed URL</label>
                <input type="text" name="map_embed" class="form-control" value="<?= htmlspecialchars($settings_page['map_embed'] ?? '') ?>" placeholder="Paste Google Maps embed src URL here">
                <div class="form-text">Go to Google Maps → Share → Embed a map → Copy the src="..." URL only.</div>
              </div>
              <div class="col-12">
                <button type="submit" name="save_website_info" class="btn btn-dark px-4">
                  <i class="fas fa-save me-2"></i> Save Website Info
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/sidebar.js"></script>

<!-- ============================================================
     DUPLICATE MEMBER CONFLICT MODAL
     Shown when Approve is clicked but an active member with the
     same phone/name already exists in the members table.
============================================================ -->
<div class="modal fade" id="duplicateMemberModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered" style="max-width:520px;">
    <div class="modal-content border-0 shadow-lg" style="border-radius:18px;overflow:hidden;">

      <!-- Header -->
      <div class="modal-header border-0 pb-0" style="background:linear-gradient(135deg,#fff3cd,#ffeaa7);padding:1.5rem 1.8rem .8rem;">
        <div class="d-flex align-items-center gap-3">
          <div style="width:52px;height:52px;border-radius:14px;background:#f59e0b;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fas fa-exclamation-triangle text-white" style="font-size:1.4rem;"></i>
          </div>
          <div>
            <h5 class="modal-title mb-0 fw-bold" style="color:#92400e;font-size:1.15rem;">Active Membership Already Exists</h5>
            <p class="mb-0" style="color:#b45309;font-size:.83rem;">This person is already registered as an active member</p>
          </div>
        </div>
      </div>

      <!-- Body -->
      <div class="modal-body" style="padding:1.4rem 1.8rem;">
        <div class="rounded-3 p-3 mb-3" style="background:#f8fafc;border:1.5px solid #e2e8f0;">
          <div class="d-flex align-items-center gap-2 mb-2">
            <i class="fas fa-user-circle text-primary" style="font-size:1.3rem;"></i>
            <span class="fw-bold" id="dup_name" style="font-size:1.05rem;"></span>
          </div>
          <div class="d-flex gap-4 flex-wrap">
            <div>
              <div class="text-muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;">Plan</div>
              <div class="fw-semibold" id="dup_plan"></div>
            </div>
            <div>
              <div class="text-muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;">Member ID</div>
              <div class="fw-semibold" id="dup_member_id"></div>
            </div>
            <div>
              <div class="text-muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;">Expires</div>
              <div class="fw-semibold" id="dup_expiry"></div>
            </div>
            <div>
              <div class="text-muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;">Days Left</div>
              <div class="fw-semibold text-success" id="dup_days"></div>
            </div>
          </div>
        </div>

        <p style="color:#475569;font-size:.93rem;line-height:1.6;">
          Creating a new member account for this booking will result in a <strong>duplicate entry</strong>.
          This usually happens when someone submits multiple online bookings.
        </p>
        <p style="color:#475569;font-size:.93rem;line-height:1.6;margin-bottom:0;">
          <strong>What would you like to do?</strong>
        </p>
      </div>

      <!-- Footer with 3 clear actions -->
      <div class="modal-footer border-0 d-flex flex-column gap-2" style="padding:0 1.8rem 1.8rem;">

        <!-- Option A: Reject the duplicate booking (recommended) -->
        <button type="button" id="dup_btn_reject"
          class="btn w-100 fw-bold"
          style="background:#dc2626;color:#fff;border-radius:10px;padding:.75rem;font-size:.97rem;">
          <i class="fas fa-ban me-2"></i>Reject This Booking (Recommended)
          <div style="font-size:.75rem;font-weight:400;opacity:.88;margin-top:.15rem;">The person already has an active membership — reject this extra booking</div>
        </button>

        <!-- Option B: Approve anyway (add new member — rare, e.g. renewal) -->
        <button type="button" id="dup_btn_force"
          class="btn w-100 fw-bold"
          style="background:#1e40af;color:#fff;border-radius:10px;padding:.75rem;font-size:.97rem;">
          <i class="fas fa-user-plus me-2"></i>Approve Anyway — Add New Member
          <div style="font-size:.75rem;font-weight:400;opacity:.88;margin-top:.15rem;">Only use this if it's a renewal or the existing membership is for a different person</div>
        </button>

        <!-- Option C: Cancel — go back and review -->
        <button type="button" class="btn w-100 fw-semibold"
          style="background:#f1f5f9;color:#475569;border-radius:10px;padding:.7rem;font-size:.93rem;"
          data-bs-dismiss="modal">
          <i class="fas fa-arrow-left me-2"></i>Go Back &amp; Review
        </button>

      </div>
    </div>
  </div>
</div>

<!-- ============================================================
     AJAX: check-member-duplicate.php  (inline for simplicity)
     Called before approving to detect existing active member.
============================================================ -->
<script>
/* ── State ────────────────────────────────────────────────── */
let _pendingBookingId   = null;
let _pendingBookingName = null;
let _dupModal           = null;

document.addEventListener('DOMContentLoaded', () => {
  _dupModal = new bootstrap.Modal(document.getElementById('duplicateMemberModal'));

  /* ── Reject (recommended) ─────────────────────────────── */
  document.getElementById('dup_btn_reject').addEventListener('click', () => {
    if (!_pendingBookingId) return;
    const form = document.getElementById('bookingForm-' + _pendingBookingId);
    form.querySelector('[name=status]').value       = 'rejected';
    form.querySelector('[name=force_approve]').value = '0';
    const notesEl = form.querySelector('[name=admin_notes]');
    if (!notesEl.value.trim()) {
      notesEl.value = 'Rejected: this person already has an active membership. Duplicate booking.';
    }
    form.querySelector('[name=update_booking]') 
      ? form.querySelector('[name=update_booking]').click()
      : addHiddenAndSubmit(form, 'update_booking', '1');
    _dupModal.hide();
  });

  /* ── Force approve ────────────────────────────────────── */
  document.getElementById('dup_btn_force').addEventListener('click', () => {
    if (!_pendingBookingId) return;
    const form = document.getElementById('bookingForm-' + _pendingBookingId);
    form.querySelector('[name=status]').value        = 'verified';
    form.querySelector('[name=force_approve]').value = '1';
    addHiddenAndSubmit(form, 'update_booking', '1');
    _dupModal.hide();
  });

  /* ── Auto-open modal if PHP detected a duplicate ─────── */
  <?php if (!empty($message) && str_starts_with($message, '__DUPLICATE__')): ?>
  (() => {
    const raw = <?= json_encode($message) ?>;
    // format: __DUPLICATE__DUPLICATE_MEMBER|id|name|plan|days|end_date__BID__bookingId
    const m = raw.match(/^__DUPLICATE__DUPLICATE_MEMBER\|(\d+)\|([^|]+)\|([^|]+)\|(-?\d+)\|([^_]+)__BID__(\d+)$/);
    if (m) {
      showDuplicateModal(m[6], m[2], m[1], m[3], m[4], m[5]);
    }
  })();
  <?php endif; ?>
});

/* ── Click handler on Approve button ─────────────────────── */
function checkAndApprove(bookingId, fullName, phone) {
  // Optimistic: show spinner on button
  const btn = event.currentTarget;
  const origHTML = btn.innerHTML;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Checking…';
  btn.disabled  = true;

  fetch('check_member_duplicate.php?phone=' + encodeURIComponent(phone) + '&name=' + encodeURIComponent(fullName))
    .then(r => r.json())
    .then(data => {
      btn.innerHTML = origHTML;
      btn.disabled  = false;

      if (data.exists) {
        // Conflict found — show modal instead of submitting
        showDuplicateModal(
          bookingId,
          data.member.fullname,
          data.member.id,
          data.member.plan,
          data.member.days_left,
          data.member.end_date
        );
      } else {
        // No conflict — approve directly
        const form = document.getElementById('bookingForm-' + bookingId);
        form.querySelector('[name=status]').value        = 'verified';
        form.querySelector('[name=force_approve]').value = '0';
        addHiddenAndSubmit(form, 'update_booking', '1');
      }
    })
    .catch(() => {
      btn.innerHTML = origHTML;
      btn.disabled  = false;
      // On network error, fall back to direct approve
      const form = document.getElementById('bookingForm-' + bookingId);
      form.querySelector('[name=status]').value        = 'verified';
      form.querySelector('[name=force_approve]').value = '0';
      addHiddenAndSubmit(form, 'update_booking', '1');
    });
}

/* ── Undo: move booking back to pending ─────────────────── */
function undoBooking(bookingId) {
  if (!confirm('Move Booking #' + bookingId + ' back to Pending?\n\nThis lets you re-review and re-approve or re-reject it.\n\nNote: if a member was already created from this booking, it will NOT be removed — you will need to delete it manually from Member Management.')) return;
  const form = document.getElementById('historyForm-' + bookingId)
             || document.getElementById('bookingForm-' + bookingId);
  addHiddenAndSubmit(form, 'undo_booking', '1');
}
  if (!confirm('Are you sure you want to reject booking #' + bookingId + '?')) return;
  const form = document.getElementById('bookingForm-' + bookingId);
  form.querySelector('[name=status]').value        = 'rejected';
  form.querySelector('[name=force_approve]').value = '0';
  addHiddenAndSubmit(form, 'update_booking', '1');
}

/* ── Populate & show the conflict modal ─────────────────── */
function showDuplicateModal(bookingId, memberName, memberId, plan, daysLeft, endDate) {
  _pendingBookingId   = bookingId;
  _pendingBookingName = memberName;

  document.getElementById('dup_name').textContent      = memberName;
  document.getElementById('dup_plan').textContent      = plan;
  document.getElementById('dup_member_id').textContent = '#' + memberId;

  const d = new Date(endDate);
  document.getElementById('dup_expiry').textContent =
    isNaN(d) ? endDate : d.toLocaleDateString('en-PH', {month:'short',day:'numeric',year:'numeric'});

  const days = parseInt(daysLeft);
  const daysEl = document.getElementById('dup_days');
  daysEl.textContent  = days > 0 ? days + ' days' : (days === 0 ? 'Expires today' : 'Expired');
  daysEl.style.color  = days > 7 ? '#16a34a' : (days >= 0 ? '#d97706' : '#dc2626');

  _dupModal.show();
}

/* ── Helper: submit a form with an extra hidden field ───── */
function addHiddenAndSubmit(form, name, value) {
  let el = form.querySelector('[name=' + name + ']');
  if (!el) {
    el = document.createElement('input');
    el.type = 'hidden'; el.name = name;
    form.appendChild(el);
  }
  el.value = value;
  form.submit();
}
</script>
</body>
</html>
