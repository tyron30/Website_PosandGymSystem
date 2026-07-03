<?php
include "../config/db.php";
include "../get_membership_fees.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'cashier') {
    header("Location: ../index.php");
    exit();
}

$user = $_SESSION['user'];

// Fetch gym settings
$settings = $conn->query("SELECT * FROM gym_settings WHERE id = 1")->fetch_assoc();
if (!$settings) {
    // Insert default settings if not exists
    $conn->query("INSERT INTO gym_settings (gym_name, logo_path, background_path) VALUES ('Gym Management System', 'gym logo.jpg', 'gym background.jpg')");
    $settings = $conn->query("SELECT * FROM gym_settings WHERE id = 1")->fetch_assoc();
}

// Check if student discount feature is enabled
$student_discount_enabled = isset($settings['student_discount_enabled']) ? $settings['student_discount_enabled'] : true;

// Get membership fees
$membership_fees = getMembershipFees();

// Function to generate unique member code
function generateMemberCode() {
    return 'MEM' . date('Y') . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
}

// Function to generate unique receipt number
function generateReceiptNo() {
    return 'R' . date('Ymd') . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
}

// Function to generate QR code
// Layout is uniform across the whole system: [TOP: gym name] [QR + logo] [BOTTOM: member name]
function generateQRCode($qr_token, $member_name = '') {
    require_once __DIR__ . '/../phpqrcode/qrlib.php';

    // Load gym settings for branding
    global $conn;
    $gym_name  = 'Gym Management System';
    $logo_path = '';
    $gs = $conn->query("SELECT gym_name, logo_path FROM gym_settings WHERE id = 1");
    if ($gs && $gs->num_rows > 0) {
        $gsr = $gs->fetch_assoc();
        $gym_name  = $gsr['gym_name'];
        $logo_path = __DIR__ . '/../' . $gsr['logo_path'];
    }

    $dir      = __DIR__ . '/../qr_codes';
    $filename = $qr_token . '.png';
    $tmp_file = $dir . '/tmp_' . $qr_token . '.png';
    $filepath = $dir . '/' . $filename;

    if (!is_dir($dir)) mkdir($dir, 0755, true);

    // High error correction so logo overlay is safe
    QRcode::png($qr_token, $tmp_file, QR_ECLEVEL_H, 10, 4);

    if (!file_exists($tmp_file)) {
        QRcode::png($qr_token, $filepath, QR_ECLEVEL_L, 10, 4);
        return 'qr_codes/' . $filename;
    }

    // Build branded QR with GD
    $qr_img   = imagecreatefrompng($tmp_file);
    $qr_w     = imagesx($qr_img);
    $qr_h     = imagesy($qr_img);
    $banner_h = 48;
    $footer_h = !empty($member_name) ? 40 : 0;
    $padding  = 16;
    $total_w  = $qr_w + $padding * 2;
    $total_h  = $qr_h + $banner_h + $footer_h + $padding * 2;

    $canvas    = imagecreatetruecolor($total_w, $total_h);
    $dark_teal = imagecolorallocate($canvas, 0, 80, 80);
    $white     = imagecolorallocate($canvas, 255, 255, 255);
    $dark_text = imagecolorallocate($canvas, 20, 20, 20);
    imagefill($canvas, 0, 0, $white);
    imagefilledrectangle($canvas, 0, 0, $total_w, $banner_h, $dark_teal);

    $font      = 5;
    $max_chars = (int)floor($total_w / imagefontwidth($font)) - 2;
    $label     = strlen($gym_name) > $max_chars ? substr($gym_name, 0, $max_chars - 1) . '.' : $gym_name;
    $text_w    = strlen($label) * imagefontwidth($font);
    $text_x    = (int)(($total_w - $text_w) / 2);
    $text_y    = (int)(($banner_h - imagefontheight($font)) / 2);
    imagestring($canvas, $font, $text_x, $text_y, $label, $white);

    imagecopy($canvas, $qr_img, $padding, $banner_h + $padding, 0, 0, $qr_w, $qr_h);

    $logo_size = (int)($qr_w * 0.22);
    if (!empty($logo_path) && file_exists($logo_path)) {
        $ext = strtolower(pathinfo($logo_path, PATHINFO_EXTENSION));
        $logo_src = null;
        if ($ext === 'png')                     $logo_src = imagecreatefrompng($logo_path);
        elseif (in_array($ext, ['jpg','jpeg'])) $logo_src = imagecreatefromjpeg($logo_path);
        elseif ($ext === 'gif')                 $logo_src = imagecreatefromgif($logo_path);
        if ($logo_src) {
            $lr = imagecreatetruecolor($logo_size, $logo_size);
            imagealphablending($lr, false); imagesavealpha($lr, true);
            $tr = imagecolorallocatealpha($lr, 0, 0, 0, 127);
            imagefill($lr, 0, 0, $tr);
            imagecopyresampled($lr, $logo_src, 0, 0, 0, 0, $logo_size, $logo_size, imagesx($logo_src), imagesy($logo_src));
            $lx = $padding + (int)(($qr_w - $logo_size) / 2);
            $ly = $banner_h + $padding + (int)(($qr_h - $logo_size) / 2);
            imagefilledellipse($canvas, $lx+(int)($logo_size/2), $ly+(int)($logo_size/2), $logo_size+16, $logo_size+16, $white);
            imagecopy($canvas, $lr, $lx, $ly, 0, 0, $logo_size, $logo_size);
            imagedestroy($lr); imagedestroy($logo_src);
        }
    }

    // Bottom footer: member name, centered (drawn twice with a 1px offset so it reads bolder/clearer)
    if (!empty($member_name)) {
        $footer_y   = $banner_h + $padding + $qr_h + $padding;
        $name_label = strlen($member_name) > $max_chars ? substr($member_name, 0, $max_chars - 1) . '.' : $member_name;
        $name_w     = strlen($name_label) * imagefontwidth($font);
        $name_x     = (int)(($total_w - $name_w) / 2);
        $name_y     = (int)($footer_y + ($footer_h - imagefontheight($font)) / 2);
        imagestring($canvas, $font, $name_x + 1, $name_y, $name_label, $dark_text);
        imagestring($canvas, $font, $name_x, $name_y, $name_label, $dark_text);
    }

    imagepng($canvas, $filepath);
    imagedestroy($canvas); imagedestroy($qr_img);
    @unlink($tmp_file);

    return 'qr_codes/' . $filename;
}

// Function to generate unique QR token
function generateQRToken() {
    do {
        $token = bin2hex(random_bytes(32)); // 64 character hex string
        $stmt = $GLOBALS['conn']->prepare("SELECT id FROM members WHERE qr_token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
    } while ($exists || $token === '0' || empty($token));

    return $token;
}

// Function to calculate end date based on plan and start date
function calculateEndDate($plan, $start_date) {
    $start = strtotime($start_date);
    $end = $start;

    switch ($plan) {
        case 'Half Month':
            $end = strtotime('+15 days', $start);
            break;
        case '1 Month':
            $end = strtotime('+1 month', $start);
            break;
        case '2 Months':
            $end = strtotime('+2 months', $start);
            break;
        case '3 Months':
            $end = strtotime('+3 months', $start);
            break;
        case '4 Months':
            $end = strtotime('+4 months', $start);
            break;
        case '5 Months':
            $end = strtotime('+5 months', $start);
            break;
        case '6 Months':
            $end = strtotime('+6 months', $start);
            break;
        case '7 Months':
            $end = strtotime('+7 months', $start);
            break;
        case '8 Months':
            $end = strtotime('+8 months', $start);
            break;
        case '9 Months':
            $end = strtotime('+9 months', $start);
            break;
        case '10 Months':
            $end = strtotime('+10 months', $start);
            break;
        case '11 Months':
            $end = strtotime('+11 months', $start);
            break;
        case '1 Year':
            $end = strtotime('+1 year', $start);
            break;
        case '2 Years':
            $end = strtotime('+2 years', $start);
            break;
        case '3 Years':
            $end = strtotime('+3 years', $start);
            break;
        case 'Per Session':
        case 'Manual':
        default:
            $end = $start;
            break;
    }

    return date('Y-m-d', $end);
}

// Handle member addition
if (isset($_POST['add_member'])) {
    // Check if QR code was generated
    if (!isset($_POST['qr_generated']) || $_POST['qr_generated'] !== '1') {
        header("Location: members.php?error=qr_not_generated");
        exit();
    }

    // Use generated values (QR is required on cashier side)
    $member_code = isset($_POST['generated_member_code']) && !empty($_POST['generated_member_code']) ? $_POST['generated_member_code'] : generateMemberCode();
    $qr_code_path = isset($_POST['generated_qr_path']) ? $_POST['generated_qr_path'] : '';

    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $plan = $_POST['plan'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $is_student = isset($_POST['is_student']) ? 1 : 0;
    $student_id = $is_student ? $_POST['student_id'] : null;

    // Check if member with same fullname already exists
    $check_stmt = $conn->prepare("SELECT id FROM members WHERE fullname = ?");
    $check_stmt->bind_param("s", $fullname);
    $check_stmt->execute();
    $check_stmt->store_result();
    if ($check_stmt->num_rows > 0) {
        $check_stmt->close();
        header("Location: members.php?duplicate_error=1");
        exit();
    }
    $check_stmt->close();

    // Always use the QR token generated by generate_member_qr.php
    // This ensures the token encoded in the QR image matches what we store in the database
    $qr_token = isset($_POST['qr_token']) ? $_POST['qr_token'] : '';

    // Debug logging
    error_log("DEBUG: qr_token from POST: '" . $qr_token . "'");
    error_log("DEBUG: qr_code_path from POST: '" . $qr_code_path . "'");

    // Fallback safety: if for some reason no token/path came from the frontend, generate a fresh one
    if (empty($qr_token)) {
        error_log("DEBUG: No qr_token from POST, generating fallback token");
        $qr_token = generateQRToken();
        $qr_code_path = generateQRCode($qr_token, $fullname);
    } elseif (empty($qr_code_path)) {
        // If we have a token but no path, generate the QR image on the server
        error_log("DEBUG: Have qr_token but no qr_code_path, generating QR code");
        $qr_code_path = generateQRCode($qr_token, $fullname);
    } else {
        error_log("DEBUG: Using qr_token and qr_code_path from POST");
    }

    error_log("DEBUG: Final qr_token: '" . $qr_token . "', qr_code_path: '" . $qr_code_path . "'");

    // Handle NULL values properly for prepared statement
    $student_id_value = $student_id ?: null;

    $stmt = $conn->prepare("INSERT INTO members (member_code, fullname, email, phone, address, plan, start_date, end_date, is_student, student_id, qr_code, qr_token, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // Use individual bind_param calls to handle NULL values properly
    $stmt->bind_param("ssssssssisssi", $member_code, $fullname, $email, $phone, $address, $plan, $start_date, $end_date, $is_student, $student_id_value, $qr_code_path, $qr_token, $user['id']);

    error_log("DEBUG: About to execute INSERT with qr_token: '$qr_token', student_id: '" . ($student_id_value ?? 'NULL') . "'");
    if ($stmt->execute()) {
        $member_id = $stmt->insert_id;
        error_log("DEBUG: INSERT successful, member_id: $member_id");
        $stmt->close();
    } else {
        // Log the error for debugging
        error_log("Failed to insert member: " . $stmt->error);
        header("Location: members.php?error=1");
        exit();
    }

    // Handle payment if provided
    if (isset($_POST['payment_method']) && $_POST['payment_method'] !== '' && isset($_POST['amount']) && $_POST['amount'] !== '' && is_numeric($_POST['amount']) && $_POST['amount'] > 0) {
        $amount = $_POST['amount'];
        $payment_method = $_POST['payment_method'];
        $discount_amount = ($student_discount_enabled && $is_student) ? ($amount * 0.20) : 0.00; // 20% discount for students
        $is_student_discount = ($student_discount_enabled && $is_student) ? 1 : 0;
        $receipt_no = generateReceiptNo();
        $reference_no = isset($_POST['reference_no']) ? $_POST['reference_no'] : null;

        $stmt = $conn->prepare("INSERT INTO payments (member_id, amount, receipt_no, payment_date, payment_method, is_student_discount, student_id, discount_amount, reference_no) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("idsssssds", $member_id, $amount, $receipt_no, date('Y-m-d'), $payment_method, $is_student_discount, $student_id, $discount_amount, $reference_no);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: members.php?add_success=1");
    exit();
}

// Handle quick add per session member
if (isset($_POST['quick_add_member'])) {
    $member_code = generateMemberCode();
    $fullname = $_POST['fullname'];
    $phone = $_POST['phone'];
    $today = date('Y-m-d');
    $is_student = isset($_POST['is_student']) ? 1 : 0;
    $student_id = $is_student ? $_POST['student_id'] : '';

    // Check if member with same fullname already exists
    $check_stmt = $conn->prepare("SELECT id FROM members WHERE fullname = ?");
    $check_stmt->bind_param("s", $fullname);
    $check_stmt->execute();
    $check_stmt->store_result();
    if ($check_stmt->num_rows > 0) {
        $check_stmt->close();
        header("Location: members.php?duplicate_error=1");
        exit();
    }
    $check_stmt->close();

    // Use QR token pre-generated by JS (autoGenerateQRAndSubmit), or generate server-side as fallback
    $qr_token     = isset($_POST['qr_token']) && !empty($_POST['qr_token']) ? $_POST['qr_token'] : '';
    $qr_code_path = isset($_POST['generated_qr_path']) && !empty($_POST['generated_qr_path']) ? $_POST['generated_qr_path'] : '';
    if (empty($qr_token))     $qr_token     = generateQRToken();
    if (empty($qr_code_path)) $qr_code_path = generateQRCode($qr_token, $fullname);

    $stmt = $conn->prepare("INSERT INTO members (member_code, fullname, phone, plan, start_date, end_date, is_student, student_id, qr_code, qr_token, created_by) VALUES (?, ?, ?, 'Per Session', ?, ?, ?, ?, ?, ?, ?)");
    // Types: member_code (s), fullname (s), phone (s), start_date (s), end_date (s), is_student (i), student_id (s), qr_code (s), qr_token (s), created_by (i)
    $stmt->bind_param("sssssisssi", $member_code, $fullname, $phone, $today, $today, $is_student, $student_id, $qr_code_path, $qr_token, $user['id']);
    if ($stmt->execute()) {
        $member_id = $stmt->insert_id;
        $stmt->close();

        // Add payment record with student discount if applicable
        $amount = $_POST['amount']; // Use the posted amount
        $discount_amount = $is_student ? ($amount * 0.20) : 0.00; // 20% discount for students
        $is_student_discount = $is_student ? 1 : 0;
        $receipt_no = generateReceiptNo();

        $stmt = $conn->prepare("INSERT INTO payments (member_id, amount, receipt_no, payment_date, is_student_discount, student_id, discount_amount) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("idssisd", $member_id, $amount, $receipt_no, $today, $is_student_discount, $student_id, $discount_amount);
        if ($stmt->execute()) {
            $stmt->close();

            header("Location: members.php?quick_add_success=1");
        } else {
            header("Location: members.php?quick_add_error=1");
        }
    } else {
        header("Location: members.php?quick_add_error=1");
    }
}

// Handle QR code regeneration
if (isset($_POST['regenerate_qr'])) {
    $member_id = intval($_POST['regen_member_id']);
    $gs = $conn->query("SELECT gym_name, logo_path FROM gym_settings WHERE id = 1");
    $gym_name = 'Gym Management System'; $logo_path = '';
    if ($gs && $gs->num_rows > 0) {
        $gsr = $gs->fetch_assoc();
        $gym_name  = $gsr['gym_name'];
        $logo_path = __DIR__ . '/../' . $gsr['logo_path'];
    }
    $member_name = '';
    $mn = $conn->prepare("SELECT fullname FROM members WHERE id = ?");
    $mn->bind_param("i", $member_id);
    $mn->execute();
    $mn_res = $mn->get_result();
    if ($mn_row = $mn_res->fetch_assoc()) { $member_name = $mn_row['fullname']; }
    $mn->close();
    require_once __DIR__ . '/../phpqrcode/qrlib.php';
    do {
        $new_token = bin2hex(random_bytes(32));
        $chk = $conn->prepare("SELECT id FROM members WHERE qr_token = ?");
        $chk->bind_param("s", $new_token); $chk->execute(); $chk->store_result();
        $exists = $chk->num_rows > 0; $chk->close();
    } while ($exists);
    $dir = __DIR__ . '/../qr_codes';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $filename = $new_token . '.png'; $tmp_file = $dir . '/tmp_' . $new_token . '.png';
    $filepath = $dir . '/' . $filename; $qr_path = 'qr_codes/' . $filename;
    QRcode::png($new_token, $tmp_file, QR_ECLEVEL_H, 10, 4);
    if (file_exists($tmp_file)) {
        $qr_img = imagecreatefrompng($tmp_file);
        $qr_w = imagesx($qr_img); $qr_h = imagesy($qr_img);
        $banner_h = 48; $footer_h = !empty($member_name) ? 40 : 0; $padding = 20;
        $total_w = $qr_w + $padding * 2; $total_h = $qr_h + $banner_h + $footer_h + $padding * 2;
        $canvas = imagecreatetruecolor($total_w, $total_h);
        $dark_teal = imagecolorallocate($canvas, 0, 80, 80);
        $white     = imagecolorallocate($canvas, 255, 255, 255);
        $dark_text = imagecolorallocate($canvas, 20, 20, 20);
        imagefill($canvas, 0, 0, $white);
        imagefilledrectangle($canvas, 0, 0, $total_w, $banner_h, $dark_teal);
        $font_size  = 5;
        $max_chars  = (int)floor($total_w / imagefontwidth($font_size)) - 2;
        $label_text = strlen($gym_name) > $max_chars ? substr($gym_name, 0, $max_chars - 1) . '.' : $gym_name;
        $text_w     = strlen($label_text) * imagefontwidth($font_size);
        $text_x     = (int)(($total_w - $text_w) / 2);
        $text_y     = (int)(($banner_h - imagefontheight($font_size)) / 2);
        imagestring($canvas, $font_size, $text_x, $text_y, $label_text, $white);
        imagecopy($canvas, $qr_img, $padding, $banner_h + $padding, 0, 0, $qr_w, $qr_h);
        $logo_size = (int)($qr_w * 0.22);
        if (!empty($logo_path) && file_exists($logo_path)) {
            $ext = strtolower(pathinfo($logo_path, PATHINFO_EXTENSION));
            $logo_src = null;
            if ($ext === 'png') $logo_src = imagecreatefrompng($logo_path);
            elseif (in_array($ext, ['jpg','jpeg'])) $logo_src = imagecreatefromjpeg($logo_path);
            elseif ($ext === 'gif') $logo_src = imagecreatefromgif($logo_path);
            if ($logo_src) {
                $lr = imagecreatetruecolor($logo_size, $logo_size);
                imagealphablending($lr, false); imagesavealpha($lr, true);
                $tr = imagecolorallocatealpha($lr, 0, 0, 0, 127);
                imagefill($lr, 0, 0, $tr);
                imagecopyresampled($lr, $logo_src, 0, 0, 0, 0, $logo_size, $logo_size, imagesx($logo_src), imagesy($logo_src));
                $lx = $padding + (int)(($qr_w - $logo_size) / 2);
                $ly = $banner_h + $padding + (int)(($qr_h - $logo_size) / 2);
                imagefilledellipse($canvas, $lx + (int)($logo_size/2), $ly + (int)($logo_size/2), $logo_size + 12, $logo_size + 12, $white);
                imagecopy($canvas, $lr, $lx, $ly, 0, 0, $logo_size, $logo_size);
                imagedestroy($lr); imagedestroy($logo_src);
            }
        }
        // Bottom footer: member name (uniform with the rest of the system)
        if (!empty($member_name)) {
            $footer_y   = $banner_h + $padding + $qr_h + $padding;
            $name_label = strlen($member_name) > $max_chars ? substr($member_name, 0, $max_chars - 1) . '.' : $member_name;
            $name_w     = strlen($name_label) * imagefontwidth($font_size);
            $name_x     = (int)(($total_w - $name_w) / 2);
            $name_y     = (int)($footer_y + ($footer_h - imagefontheight($font_size)) / 2);
            imagestring($canvas, $font_size, $name_x + 1, $name_y, $name_label, $dark_text);
            imagestring($canvas, $font_size, $name_x, $name_y, $name_label, $dark_text);
        }
        imagepng($canvas, $filepath);
        imagedestroy($canvas); imagedestroy($qr_img);
        @unlink($tmp_file);
    }
    $upd = $conn->prepare("UPDATE members SET qr_token=?, qr_code=? WHERE id=?");
    $upd->bind_param("ssi", $new_token, $qr_path, $member_id);
    if ($upd->execute()) { header("Location: members.php?regen_success=1"); }
    else { header("Location: members.php?error=1"); }
    $upd->close(); exit();
}

// Handle membership renewal
if (isset($_POST['renew_member'])) {
    $id = intval($_POST['renew_id']);
    $new_plan = $_POST['renew_plan'];
    $new_start = $_POST['renew_start_date'];
    $new_end = calculateEndDate($new_plan, $new_start);
    $fee = getMembershipFeeByPlan($new_plan);
    $receipt_no = generateReceiptNo();
    $payment_method = $_POST['renew_payment_method'];

    $stmt = $conn->prepare("UPDATE members SET plan=?, start_date=?, end_date=?, status='active' WHERE id=?");
    $stmt->bind_param("sssi", $new_plan, $new_start, $new_end, $id);
    if ($stmt->execute()) {
        $stmt2 = $conn->prepare("INSERT INTO payments (member_id, receipt_no, amount, payment_method, payment_date, created_by) VALUES (?, ?, ?, ?, NOW(), ?)");
        $stmt2->bind_param("isdsi", $id, $receipt_no, $fee, $payment_method, $user['id']);
        $stmt2->execute();
        $stmt2->close();
        header("Location: members.php?renew_success=1");
        exit();
    } else {
        header("Location: members.php?error=1");
        exit();
    }
    $stmt->close();
}

// Handle member update
if (isset($_POST['update_member'])) {
    $id = $_POST['id'];
    $member_code = $_POST['member_code'];
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $plan = $_POST['plan'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status = $_POST['status'];
    $is_student = isset($_POST['is_student']) ? 1 : 0;
    $student_id = $is_student ? $_POST['student_id'] : null;

    $stmt = $conn->prepare("UPDATE members SET member_code=?, fullname=?, email=?, phone=?, address=?, plan=?, start_date=?, end_date=?, status=?, is_student=?, student_id=? WHERE id=?");
    $stmt->bind_param("sssssssssisi", $member_code, $fullname, $email, $phone, $address, $plan, $start_date, $end_date, $status, $is_student, $student_id, $id);
    if ($stmt->execute()) {
        header("Location: members.php?success=1");
        exit();
    } else {
        header("Location: members.php?error=1");
        exit();
    }
    $stmt->close();
}

// Handle member deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM members WHERE id=$id");
    header("Location: members.php?delete_success=1");
    exit();
}

// Handle filters
$plan_filter = isset($_GET['plan_filter']) ? $_GET['plan_filter'] : 'all';
$payment_filter = isset($_GET['payment_filter']) ? $_GET['payment_filter'] : 'all';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Fetch members with payment status and creator info
$query = "SELECT m.*,
          CASE WHEN EXISTS (SELECT 1 FROM payments p WHERE p.member_id = m.id) THEN 1 ELSE 0 END as has_payment,
          (SELECT id FROM payments p WHERE p.member_id = m.id ORDER BY p.payment_date DESC LIMIT 1) as latest_payment_id,
          u.fullname as created_by_name,
          u.role as created_by_role
          FROM members m
          LEFT JOIN users u ON m.created_by = u.id";

$where_conditions = [];

if ($plan_filter !== 'all') {
    $where_conditions[] = "m.plan = '" . $conn->real_escape_string($plan_filter) . "'";
}

if ($payment_filter == 'paid') {
    $where_conditions[] = "EXISTS (SELECT 1 FROM payments p WHERE p.member_id = m.id)";
} elseif ($payment_filter == 'not_paid') {
    $where_conditions[] = "NOT EXISTS (SELECT 1 FROM payments p WHERE p.member_id = m.id)";
}

if (!empty($search_term)) {
    $where_conditions[] = "(m.fullname LIKE '%" . $conn->real_escape_string($search_term) . "%' OR m.email LIKE '%" . $conn->real_escape_string($search_term) . "%' OR m.phone LIKE '%" . $conn->real_escape_string($search_term) . "%')";
}

if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}

$query .= " ORDER BY m.created_at DESC";

$members = $conn->query($query);

// Handle edit member
$edit_member = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $edit_member = $conn->query("SELECT * FROM members WHERE id = $edit_id")->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Management - Gym Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/style.css?v=20260630f" rel="stylesheet">
    <script src="../assets/toast.js"></script>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav id="sidebar" class="bg-<?php echo htmlspecialchars($settings['sidebar_theme']); ?> <?php echo ($settings['sidebar_theme'] == 'light') ? 'text-dark' : 'text-white'; ?> vh-100" style="width: 250px;">
            <div class="p-3">
                <div class="text-center mb-4">
                    <img src="../<?php echo htmlspecialchars($settings['logo_path']); ?>" alt="Gym Logo" class="rounded-circle mb-2" style="width: 80px; height: 80px;">
                    <h5 class="fw-bold"><?php echo htmlspecialchars($settings['gym_name']); ?></h5>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item mb-2">
                        <a class="nav-link <?php echo ($settings['sidebar_theme'] == 'light') ? 'text-dark' : 'text-white'; ?>" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i><span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link <?php echo ($settings['sidebar_theme'] == 'light') ? 'text-dark' : 'text-white'; ?> active" href="members.php">
                            <i class="fas fa-users me-2"></i><span>Members</span>
                        </a>
                    </li>
                     <li class="nav-item mb-2">
                        <a class="nav-link <?php echo ($settings['sidebar_theme'] == 'light') ? 'text-dark' : 'text-white'; ?>" href="pos.php">
                            <i class="fas fa-cash-register me-2"></i><span>Point of Sale</span>
                        </a>
                    </li>

                    <li class="nav-item mb-2">
                        <a class="nav-link <?php echo ($settings['sidebar_theme'] == 'light') ? 'text-dark' : 'text-white'; ?>" href="attendance.php">
                            <i class="fas fa-calendar-check me-2"></i><span>Attendance</span>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link <?php echo ($settings['sidebar_theme'] == 'light') ? 'text-dark' : 'text-white'; ?>" href="../admin/website_settings.php">
                            <i class="fas fa-globe me-2"></i><span>Website</span>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link <?php echo ($settings['sidebar_theme'] == 'light') ? 'text-dark' : 'text-white'; ?>" href="settings.php">
                            <i class="fas fa-cog me-2"></i><span>Settings</span>
                        </a>
                    </li>
                    <li class="nav-item mt-4">
                        <a class="nav-link <?php echo ($settings['sidebar_theme'] == 'light') ? 'text-dark' : 'text-white'; ?>" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i><span>Logout</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="flex-grow-1">
            <!-- Top Bar -->
            <nav class="navbar navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-outline-secondary me-3" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <span class="navbar-brand mb-0 h1">Member Management - <?php echo htmlspecialchars($user['fullname']); ?> (Cashier)</span>
                </div>
            </nav>



            <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3">Member Management</h1>
                    <div>
                        <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#quickAddModal">
                            <i class="fas fa-plus me-1"></i>Quick Add Per Session
                        </button>
                        <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                            <i class="fas fa-plus me-1"></i>Add Member
                        </button>
                        <a href="payments.php" class="btn btn-info">
                            <i class="fas fa-credit-card me-1"></i>Payment Management
                        </a>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="searchInput" class="form-label">Search Members</label>
                        <input type="text" class="form-control" id="searchInput" placeholder="Search by name, email, or phone..." value="<?php echo htmlspecialchars($search_term); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="planFilterSelect" class="form-label">Filter by Plan</label>
                        <select class="form-select" id="planFilterSelect" onchange="applyFilters()">
                            <option value="all" <?php echo $plan_filter == 'all' ? 'selected' : ''; ?>>All Plans</option>
                            <option value="Per Session" <?php echo $plan_filter == 'Per Session' ? 'selected' : ''; ?>>Per Session</option>
                            <option value="Half Month" <?php echo $plan_filter == 'Half Month' ? 'selected' : ''; ?>>Half Month</option>
                            <option value="Monthly" <?php echo $plan_filter == 'Monthly' ? 'selected' : ''; ?>>Monthly</option>
                            <option value="Annual" <?php echo $plan_filter == 'Annual' ? 'selected' : ''; ?>>Annual</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="paymentFilterSelect" class="form-label">Filter by Payment Status</label>
                        <select class="form-select" id="paymentFilterSelect" onchange="applyFilters()">
                            <option value="all" <?php echo $payment_filter == 'all' ? 'selected' : ''; ?>>All Members</option>
                            <option value="paid" <?php echo $payment_filter == 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="not_paid" <?php echo $payment_filter == 'not_paid' ? 'selected' : ''; ?>>Not Paid</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-primary me-2" onclick="applyFilters()">Search</button>
                        <button class="btn btn-secondary" onclick="clearFilters()">Clear</button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Full Name</th>
                                                <th>Plan</th>
                                                <th>Paid</th>
                                                <?php if ($student_discount_enabled): ?>
                                                <th>Student</th>
                                                <?php endif; ?>
                                                <th>Expiry</th>
                                                <th>Added By</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                <tbody>
                                    <?php while ($member = $members->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($member['fullname']); ?></td>
                                        <td><?php echo htmlspecialchars($member['plan']); ?></td>
                                        <td><?php echo $member['has_payment'] ? '<span class="badge bg-success">Paid</span>' : '<span class="badge bg-warning">Not Paid</span>'; ?></td>
                                        <?php if ($student_discount_enabled): ?>
                                        <td><?php echo $member['is_student'] ? '<span class="badge bg-info">Student</span>' : '<span class="badge bg-secondary">Regular</span>'; ?></td>
                                        <?php endif; ?>
                                                <td>
                                                    <?php
                                                    $start_date = strtotime($member['start_date']);
                                                    $end_date = strtotime($member['end_date']);
                                                    $today = strtotime(date('Y-m-d'));
                                                    $current_hour = (int)date('H');
                                                    $closing_hour = 21; // 9pm
                                                    $is_expired = ($today > $end_date) || ($member['plan'] != 'Per Session' && $today == $end_date && $current_hour >= $closing_hour);

                                                    // Normalize dates to Y-m-d strings for clear comparisons
                                                    $start_str = date('Y-m-d', $start_date);
                                                    $end_str = date('Y-m-d', $end_date);
                                                    $today_str = date('Y-m-d', $today);

                                                    if (strtolower($member['plan']) == 'per session') {
                                                        // Per Session rules (same as admin):
                                                        // - If start/end are in the future  -> "Starts in X days"
                                                        // - If start/end are in the past    -> "Expired"
                                                        // - If start/end are today          -> "Until today"
                                                        if ($today_str < $start_str) {
                                                            $days_to_start = ceil(($start_date - $today) / (60 * 60 * 24));
                                                            echo '<span class="badge bg-info">Starts in ' . $days_to_start . ' days</span>';
                                                        } elseif ($today_str > $end_str) {
                                                            echo '<span class="badge bg-danger">Expired</span>';
                                                        } else {
                                                            // Today is within [start, end], and for per session this means "today"
                                                            echo '<span class="badge bg-info">Until today</span>';
                                                        }
                                                    } else {
                                                        if ($today < $start_date) {
                                                            $days_to_start = ceil(($start_date - $today) / (60 * 60 * 24));
                                                            echo '<span class="badge bg-info">Starts in ' . $days_to_start . ' days</span>';
                                                        } elseif ($is_expired) {
                                                            echo '<span class="badge bg-danger">Expired</span>';
                                                        } elseif ($end_date == $today) {
                                                            echo 'Until today';
                                                        } else {
                                                            $days_diff = ($end_date - $today) / (60 * 60 * 24);
                                                            if ($days_diff <= 7) {
                                                                echo '<span class="badge bg-warning">' . ceil($days_diff) . ' days left</span>';
                                                            } else {
                                                                echo '<span class="badge bg-success">' . ceil($days_diff) . ' days left</span>';
                                                            }
                                                        }
                                                    }
                                                    ?>
                                                </td>
                                                </td>
                                                <td>
                                                    <?php
                                                    $creator_name = $member['created_by_name'] ?? 'Unknown';
                                                    $role = $member['created_by_role'] ?? 'Unknown';
                                                    if ($role == 'admin') {
                                                        echo '<span class="badge bg-danger">' . htmlspecialchars($creator_name) . '</span>';
                                                    } elseif ($role == 'cashier') {
                                                        echo '<span class="badge bg-success">' . htmlspecialchars($creator_name) . '</span>';
                                                    } else {
                                                        echo '<span class="badge bg-secondary">' . htmlspecialchars($creator_name) . '</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary me-1" onclick="viewQR('<?php echo htmlspecialchars($member['fullname']); ?>', '<?php echo htmlspecialchars($member['qr_code']); ?>', <?php echo $member['id']; ?>)">
                                                        <i class="fas fa-qrcode"></i> View QR
                                                    </button>
                                                    <?php if ($is_expired): ?>
                                                    <button type="button" class="btn btn-sm btn-warning me-1" onclick="openRenewModal(<?php echo $member['id']; ?>, '<?php echo htmlspecialchars($member['fullname']); ?>', '<?php echo htmlspecialchars($member['plan']); ?>', '<?php echo htmlspecialchars($member['end_date']); ?>')">
                                                        <i class="fas fa-sync-alt"></i> Renew
                                                    </button>
                                                    <?php elseif (!$member['has_payment']): ?>
                                                    <a href="payments.php?member_id=<?php echo $member['id']; ?>" class="btn btn-sm btn-outline-success me-1">
                                                        <i class="fas fa-credit-card"></i> Pay
                                                    </a>
                                                    <?php endif; ?>
                                            <?php if ($member['has_payment']): ?>
                                            <a href="print_receipt.php?id=<?php echo $member['latest_payment_id']; ?>&from=members" class="btn btn-sm btn-outline-info me-1" target="_blank">
                                                <i class="fas fa-eye"></i> View Receipt
                                            </a>
                                            <?php endif; ?>
                                            <a href="?edit=<?php echo $member['id']; ?>" class="btn btn-sm btn-outline-primary me-1">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?delete=<?php echo $member['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this member?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-light text-center text-muted py-3 mt-5 border-top">
        <div class="container">
            <small>Developed by Tyron Del Valle</small>
        </div>
    </footer>

    <!-- Add Member Modal -->
    <div class="modal fade" id="addMemberModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="addMemberForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="fullname" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" name="phone">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Plan <span class="text-danger">*</span></label>
                                <select class="form-control" name="plan" id="plan" required>
                                    <option value="Half Month">Half Month</option>
                                    <option value="1 Month">1 Month</option>
                                    <option value="2 Months">2 Months</option>
                                    <option value="3 Months">3 Months</option>
                                    <option value="4 Months">4 Months</option>
                                    <option value="5 Months">5 Months</option>
                                    <option value="6 Months">6 Months</option>
                                    <option value="7 Months">7 Months</option>
                                    <option value="8 Months">8 Months</option>
                                    <option value="9 Months">9 Months</option>
                                    <option value="10 Months">10 Months</option>
                                    <option value="11 Months">11 Months</option>
                                    <option value="1 Year">1 Year</option>
                                    <option value="2 Years">2 Years</option>
                                    <option value="3 Years">3 Years</option>
                                    <option value="Manual">Manual</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="start_date" id="start_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">End Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="end_date" id="end_date" readonly required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="2"></textarea>
                            </div>
                            <?php if ($student_discount_enabled): ?>
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_student" name="is_student">
                                    <label class="form-check-label" for="is_student">
                                        Is this member a student?
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3 student-id-field" style="display: none;">
                                <label class="form-label">Student ID</label>
                                <input type="text" class="form-control" name="student_id" placeholder="Enter student ID for verification">
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- QR Code hidden fields — generated automatically on submit -->
                        <input type="hidden" id="qr_token" name="qr_token" value="">
                        <input type="hidden" id="generated_qr_path" name="generated_qr_path">

                        <!-- Payment Section -->
                        <hr>
                        <h6 class="mb-3">Payment Information</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Payment Method</label>
                                <select class="form-control" name="payment_method" id="payment_method">
                                    <option value="">No Payment</option>
                                    <option value="Cash">Cash</option>
                                    <option value="GCash">GCash</option>
                                    <option value="Maya">Maya</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3 payment-amount-field" style="display: none;">
                                <label class="form-label">Amount (₱)</label>
                                <input type="number" class="form-control" name="amount" id="payment_amount" min="0" step="0.01" placeholder="Enter amount">
                            </div>
                            <div class="col-md-6 mb-3 reference-field" style="display: none;">
                                <label class="form-label">Reference Number</label>
                                <input type="text" class="form-control" name="reference_no" id="reference_no" placeholder="Enter reference number" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_member" class="btn btn-primary">Add Member</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Member Modal -->
    <div class="modal fade" id="editMemberModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editForm">
                    <input type="hidden" name="id" id="editId">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Member Code</label>
                                <input type="text" class="form-control" name="member_code" id="editMemberCode" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="fullname" id="editFullname" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="editEmail">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" name="phone" id="editPhone">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Plan <span class="text-danger">*</span></label>
                                <select class="form-control" name="plan" id="editPlan" required>
                                    <option value="Per Session">Per Session</option>
                                    <option value="Half Month">Half Month</option>
                                    <option value="1 Month">1 Month</option>
                                    <option value="2 Months">2 Months</option>
                                    <option value="3 Months">3 Months</option>
                                    <option value="4 Months">4 Months</option>
                                    <option value="5 Months">5 Months</option>
                                    <option value="6 Months">6 Months</option>
                                    <option value="7 Months">7 Months</option>
                                    <option value="8 Months">8 Months</option>
                                    <option value="9 Months">9 Months</option>
                                    <option value="10 Months">10 Months</option>
                                    <option value="11 Months">11 Months</option>
                                    <option value="1 Year">1 Year</option>
                                    <option value="2 Years">2 Years</option>
                                    <option value="3 Years">3 Years</option>
                                    <option value="Manual">Manual</option>
                                </select>
                            </div>
                            <!-- Keep status value, but hide it from the edit form UI -->
                            <input type="hidden" name="status" id="editStatus">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="start_date" id="editStartDate" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">End Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="end_date" id="editEndDate" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" id="editAddress" rows="2"></textarea>
                            </div>
                            <?php if ($student_discount_enabled): ?>
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_is_student" name="is_student">
                                    <label class="form-check-label" for="edit_is_student">
                                        Is this member a student?
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3 edit-student-id-field" style="display: none;">
                                <label class="form-label">Student ID</label>
                                <input type="text" class="form-control" name="student_id" id="edit_student_id" placeholder="Enter student ID for verification">
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_member" class="btn btn-primary">Update Member</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Quick Add Per Session Modal -->
    <div class="modal fade" id="quickAddModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Quick Add Per Session Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="fullname" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Per Session Amount (₱) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="quick_amount" name="amount" value="<?php echo number_format($membership_fees['per_session_fee'], 2, '.', ''); ?>" min="0" step="0.01" required>
                        </div>
                        <?php if ($student_discount_enabled): ?>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="quick_is_student" name="is_student">
                                <label class="form-check-label" for="quick_is_student">
                                    Is this member a student?
                                </label>
                            </div>
                        </div>
                        <div class="mb-3 quick-student-id-field" style="display: none;">
                            <label class="form-label">Student ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="student_id" placeholder="Enter student ID for verification">
                        </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <div class="alert alert-info">
                                <strong>Total Amount: ₱<span id="total_amount">50.00</span></strong>
                                <small class="d-block mt-1">This will create a per session member with today's date as start/end date and automatically add a payment record.</small>
                                <small class="d-block mt-1"><strong>Expiry:</strong> Until Today</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="quick_add_member" class="btn btn-success">Add Member & Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Renew Membership Modal -->
    <div class="modal fade" id="renewModal" tabindex="-1" aria-labelledby="renewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title text-dark" id="renewModalLabel">
                        <i class="fas fa-sync-alt me-2"></i>Renew Membership
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="renewForm">
                    <div class="modal-body">
                        <input type="hidden" name="renew_member" value="1">
                        <input type="hidden" name="renew_id" id="renewMemberId">

                        <div class="alert alert-secondary py-2 mb-3">
                            <strong>Member:</strong> <span id="renewMemberName"></span><br>
                            <strong>Expired Plan:</strong> <span id="renewOldPlan"></span><br>
                            <strong>Expired On:</strong> <span id="renewOldEnd" class="text-danger"></span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Select New Plan <span class="text-danger">*</span></label>
                            <select class="form-select" name="renew_plan" id="renewPlan" required onchange="updateRenewFee()">
                                <option value="">-- Choose a Plan --</option>
                                <option value="Per Session">Per Session</option>
                                <option value="Half Month">Half Month</option>
                                <option value="1 Month">1 Month</option>
                                <option value="2 Months">2 Months</option>
                                <option value="3 Months">3 Months</option>
                                <option value="4 Months">4 Months</option>
                                <option value="5 Months">5 Months</option>
                                <option value="6 Months">6 Months</option>
                                <option value="7 Months">7 Months</option>
                                <option value="8 Months">8 Months</option>
                                <option value="9 Months">9 Months</option>
                                <option value="10 Months">10 Months</option>
                                <option value="11 Months">11 Months</option>
                                <option value="1 Year">1 Year</option>
                                <option value="2 Years">2 Years</option>
                                <option value="3 Years">3 Years</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">New Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="renew_start_date" id="renewStartDate" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Payment Method <span class="text-danger">*</span></label>
                            <select class="form-select" name="renew_payment_method" id="renewPaymentMethod" required>
                                <option value="Cash">Cash</option>
                                <option value="GCash">GCash</option>
                                <option value="Credit Card">Credit Card</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                            </select>
                        </div>

                        <div class="alert alert-info py-2 mb-0" id="renewFeeBox" style="display:none;">
                            <strong>Renewal Fee: ₱<span id="renewFeeAmount">0.00</span></strong>
                            <small class="d-block mt-1 text-muted">A payment record will be created for this renewal.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning text-dark fw-bold" id="renewSubmitBtn" disabled>
                            <i class="fas fa-sync-alt me-1"></i>Confirm Renewal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- QR Code Modal -->
    <div class="modal fade" id="qrModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">QR Code - <span id="qrMemberName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="qrCodeContainer" class="mb-3">
                        <!-- QR Code will be generated here -->
                    </div>
                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        <button type="button" class="btn btn-primary" onclick="printQR()">
                            <i class="fas fa-print me-2"></i>Print QR
                        </button>
                        <form method="POST" id="regenQrForm" class="d-inline">
                            <input type="hidden" name="regenerate_qr" value="1">
                            <input type="hidden" name="regen_member_id" id="regenMemberId" value="">
                            <button type="button" class="btn btn-info text-white" onclick="confirmRegenQR()">
                                <i class="fas fa-sync-alt me-2"></i>Regenerate QR
                            </button>
                        </form>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Regenerate QR Confirmation Modal -->
    <div class="modal fade" id="regenConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 pb-0" style="background: linear-gradient(135deg, #005f73, #0a9396); border-radius: 12px 12px 0 0;">
                    <div class="d-flex align-items-center gap-2 w-100">
                        <div style="background: rgba(255,255,255,0.2); border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-sync-alt text-white"></i>
                        </div>
                        <h5 class="modal-title text-white fw-bold mb-0">Regenerate QR Code</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 pt-4 pb-3">
                    <div class="text-center mb-3">
                        <div style="background: #fff3cd; border-radius: 50%; width: 64px; height: 64px; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;">
                            <i class="fas fa-exclamation-triangle text-warning" style="font-size: 28px;"></i>
                        </div>
                        <p class="text-muted mb-1" style="font-size: 13px;">You are about to regenerate the QR code for</p>
                        <h6 class="fw-bold text-dark mb-0" id="regenConfirmName" style="font-size: 16px;"></h6>
                    </div>
                    <div class="alert alert-warning border-0 py-2 px-3" style="background: #fff8e1; border-radius: 8px; font-size: 13px;">
                        <i class="fas fa-info-circle me-2 text-warning"></i>
                        The current QR code will be <strong>permanently replaced</strong>. Please ensure the member receives the new QR code before their next visit.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4 d-flex gap-2">
                    <button type="button" class="btn btn-light fw-semibold flex-fill" data-bs-dismiss="modal" style="border-radius: 8px;">
                        <i class="fas fa-times me-1"></i> Cancel
                    </button>
                    <button type="button" class="btn text-white fw-semibold flex-fill" onclick="doRegenQR()" style="background: linear-gradient(135deg, #005f73, #0a9396); border-radius: 8px;">
                        <i class="fas fa-sync-alt me-1"></i> Yes, Regenerate
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to check student discount feature status and update UI
        function checkStudentDiscountStatus() {
            fetch('check_student_discount_status.php')
                .then(response => response.json())
                .then(data => {
                    const studentDiscountEnabled = data.enabled;
                    const studentColumnHeader = document.querySelector('th:nth-child(4)');
                    const studentColumns = document.querySelectorAll('tbody tr td:nth-child(4)');

                    if (studentDiscountEnabled) {
                        // Show student column
                        if (studentColumnHeader && studentColumnHeader.textContent !== 'Student') {
                            const newHeader = document.createElement('th');
                            newHeader.textContent = 'Student';
                            studentColumnHeader.parentNode.insertBefore(newHeader, studentColumnHeader.nextSibling);
                        }

                        // Show student data cells
                        document.querySelectorAll('tbody tr').forEach((row, index) => {
                            const cells = row.querySelectorAll('td');
                            if (cells.length < 6) { // If student column is missing
                                const studentCell = document.createElement('td');
                                const memberData = data.members[index];
                                if (memberData) {
                                    studentCell.innerHTML = memberData.is_student ?
                                        '<span class="badge bg-info">Student</span>' :
                                        '<span class="badge bg-secondary">Regular</span>';
                                    row.insertBefore(studentCell, cells[3]);
                                }
                            }
                        });
                    } else {
                        // Hide student column
                        if (studentColumnHeader && studentColumnHeader.textContent === 'Student') {
                            studentColumnHeader.remove();
                        }

                        // Hide student data cells
                        document.querySelectorAll('tbody tr').forEach(row => {
                            const cells = row.querySelectorAll('td');
                            if (cells.length > 5) {
                                cells[3].remove(); // Remove student column
                            }
                        });
                    }
                })
                .catch(error => console.error('Error checking student discount status:', error));
        }

        // Check status every 30 seconds for real-time updates
        setInterval(checkStudentDiscountStatus, 30000);

        function editMember(id) {
            // Populate edit modal with member data
            <?php if ($edit_member): ?>
            document.getElementById('editId').value = '<?php echo $edit_member['id']; ?>';
            document.getElementById('editMemberCode').value = '<?php echo htmlspecialchars($edit_member['member_code']); ?>';
            document.getElementById('editFullname').value = '<?php echo htmlspecialchars($edit_member['fullname']); ?>';
            document.getElementById('editEmail').value = '<?php echo htmlspecialchars($edit_member['email']); ?>';
            document.getElementById('editPhone').value = '<?php echo htmlspecialchars($edit_member['phone']); ?>';
            document.getElementById('editPlan').value = '<?php echo htmlspecialchars($edit_member['plan']); ?>';
            document.getElementById('editStatus').value = '<?php echo htmlspecialchars($edit_member['status']); ?>';
            document.getElementById('editStartDate').value = '<?php echo htmlspecialchars($edit_member['start_date']); ?>';
            document.getElementById('editEndDate').value = '<?php echo htmlspecialchars($edit_member['end_date']); ?>';
            document.getElementById('editAddress').value = '<?php echo htmlspecialchars($edit_member['address']); ?>';
            <?php if ($student_discount_enabled): ?>
            document.getElementById('edit_is_student').checked = <?php echo $edit_member['is_student'] ? 'true' : 'false'; ?>;
            document.getElementById('edit_student_id').value = '<?php echo htmlspecialchars($edit_member['student_id'] ?? ''); ?>';
            // Show/hide student ID field based on checkbox
            const editStudentIdField = document.querySelector('.edit-student-id-field');
            if (<?php echo $edit_member['is_student'] ? 'true' : 'false'; ?>) {
                editStudentIdField.style.display = 'block';
            } else {
                editStudentIdField.style.display = 'none';
            }
            <?php endif; ?>
            var editModal = new bootstrap.Modal(document.getElementById('editMemberModal'));
            editModal.show();
            <?php endif; ?>
        }

        // Trigger edit modal if edit parameter is present
        <?php if (isset($_GET['edit'])): ?>
        window.onload = function() {
            editMember(<?php echo $_GET['edit']; ?>);
        };
        <?php endif; ?>

        // Handle student checkbox in members.php
        const studentCheckbox = document.getElementById('is_student');
        if (studentCheckbox) {
            studentCheckbox.addEventListener('change', function() {
                const studentIdField = document.querySelector('.student-id-field');
                if (this.checked) {
                    studentIdField.style.display = 'block';
                } else {
                    studentIdField.style.display = 'none';
                }
            });
        }

        // Handle edit student checkbox in members.php
        const editStudentCheckbox = document.getElementById('edit_is_student');
        if (editStudentCheckbox) {
            editStudentCheckbox.addEventListener('change', function() {
                const editStudentIdField = document.querySelector('.edit-student-id-field');
                const editStudentIdInput = editStudentIdField.querySelector('input[name="student_id"]');
                if (this.checked) {
                    editStudentIdField.style.display = 'block';
                    editStudentIdInput.setAttribute('required', 'required');
                } else {
                    editStudentIdField.style.display = 'none';
                    editStudentIdInput.removeAttribute('required');
                }
            });
        }

        // Handle quick add student checkbox
        const quickStudentCheckbox = document.getElementById('quick_is_student');
        if (quickStudentCheckbox) {
            quickStudentCheckbox.addEventListener('change', function() {
                const quickStudentIdField = document.querySelector('.quick-student-id-field');
                if (this.checked) {
                    quickStudentIdField.style.display = 'block';
                } else {
                    quickStudentIdField.style.display = 'none';
                }
            });
        }

        // Apply filters function
        function applyFilters() {
            const searchValue = document.getElementById('searchInput').value;
            const planFilterValue = document.getElementById('planFilterSelect').value;
            const paymentFilterValue = document.getElementById('paymentFilterSelect').value;
            const params = new URLSearchParams();
            if (searchValue) params.set('search', searchValue);
            params.set('plan_filter', planFilterValue);
            params.set('payment_filter', paymentFilterValue);
            window.location.href = 'members.php?' + params.toString();
        }

        // Clear filters function
        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('planFilterSelect').value = 'all';
            document.getElementById('paymentFilterSelect').value = 'all';
            window.location.href = 'members.php';
        }

        // Function to calculate and update total amount
        function updateTotalAmount() {
            const amountInput = document.getElementById('quick_amount');
            const studentCheckbox = document.getElementById('quick_is_student');
            const totalAmountSpan = document.getElementById('total_amount');

            const amount = parseFloat(amountInput.value) || 0;
            const isStudent = studentCheckbox ? studentCheckbox.checked : false;
            const discount = isStudent ? amount * 0.20 : 0;
            const total = amount - discount;

            totalAmountSpan.textContent = total.toFixed(2);
        }

        // Function to calculate end date based on plan and start date
        function calculateEndDate(plan, startDate) {
            if (!startDate) return '';
            const start = new Date(startDate);
            let end = new Date(start);

            switch (plan) {
                case 'Half Month':
                    end.setDate(end.getDate() + 15);
                    break;
                case '1 Month':
                    end.setMonth(end.getMonth() + 1);
                    break;
                case '2 Months':
                    end.setMonth(end.getMonth() + 2);
                    break;
                case '3 Months':
                    end.setMonth(end.getMonth() + 3);
                    break;
                case '4 Months':
                    end.setMonth(end.getMonth() + 4);
                    break;
                case '5 Months':
                    end.setMonth(end.getMonth() + 5);
                    break;
                case '6 Months':
                    end.setMonth(end.getMonth() + 6);
                    break;
                case '7 Months':
                    end.setMonth(end.getMonth() + 7);
                    break;
                case '8 Months':
                    end.setMonth(end.getMonth() + 8);
                    break;
                case '9 Months':
                    end.setMonth(end.getMonth() + 9);
                    break;
                case '10 Months':
                    end.setMonth(end.getMonth() + 10);
                    break;
                case '11 Months':
                    end.setMonth(end.getMonth() + 11);
                    break;
                case '1 Year':
                    end.setFullYear(end.getFullYear() + 1);
                    break;
                case '2 Years':
                    end.setFullYear(end.getFullYear() + 2);
                    break;
                case '3 Years':
                    end.setFullYear(end.getFullYear() + 3);
                    break;
                case 'Per Session':
                    // End date same as start date
                    break;
                case 'Manual':
                    // Leave as is
                    break;
                default:
                    break;
            }

            return end.toISOString().split('T')[0];
        }

        // Function to update end date in add member modal
        function updateEndDate() {
            const planSelect = document.getElementById('plan');
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');

            if (planSelect && startDateInput && endDateInput) {
                const plan = planSelect.value;
                const startDate = startDateInput.value;
                const calculatedEndDate = calculateEndDate(plan, startDate);
                if (calculatedEndDate && plan !== 'Manual') {
                    endDateInput.value = calculatedEndDate;
                }
            }
        }

        // Function to update end date in edit member modal
        function updateEditEndDate() {
            const planSelect = document.getElementById('editPlan');
            const startDateInput = document.getElementById('editStartDate');
            const endDateInput = document.getElementById('editEndDate');

            if (planSelect && startDateInput && endDateInput) {
                const plan = planSelect.value;
                const startDate = startDateInput.value;
                const calculatedEndDate = calculateEndDate(plan, startDate);
                if (calculatedEndDate && plan !== 'Manual') {
                    endDateInput.value = calculatedEndDate;
                }
            }
        }

        // Add event listeners for amount input and student checkbox
        document.addEventListener('DOMContentLoaded', function() {
            const amountInput = document.getElementById('quick_amount');
            const studentCheckbox = document.getElementById('quick_is_student');

            if (amountInput) {
                amountInput.addEventListener('input', updateTotalAmount);
            }
            if (studentCheckbox) {
                studentCheckbox.addEventListener('change', updateTotalAmount);
            }

            // Initial calculation
            updateTotalAmount();

            // Add event listeners for plan and start date changes in add member modal
            const planSelect = document.getElementById('plan');
            const startDateInput = document.getElementById('start_date');
            if (planSelect) {
                planSelect.addEventListener('change', updateEndDate);
                // Update amount in real-time when plan changes if payment method is selected
                planSelect.addEventListener('change', function() {
                    const paymentMethodSelect = document.getElementById('payment_method');
                    const paymentAmountInput = document.getElementById('payment_amount');
                    if (paymentMethodSelect && paymentMethodSelect.value !== '' && paymentAmountInput) {
                        const selectedPlan = this.value;
                        if (selectedPlan && selectedPlan !== 'Manual') {
                            fetch('../admin/get_membership_fee.php?plan=' + encodeURIComponent(selectedPlan))
                                .then(response => response.json())
                                .then(data => {
                                    if (data.fee !== undefined) {
                                        paymentAmountInput.value = data.fee;
                                    }
                                })
                                .catch(error => console.error('Error fetching membership fee:', error));
                        }
                    }
                });
            }
            if (startDateInput) {
                startDateInput.addEventListener('change', updateEndDate);
            }

            // Add event listeners for plan and start date changes in edit member modal
            const editPlanSelect = document.getElementById('editPlan');
            const editStartDateInput = document.getElementById('editStartDate');
            if (editPlanSelect) {
                editPlanSelect.addEventListener('change', updateEditEndDate);
            }
            if (editStartDateInput) {
                editStartDateInput.addEventListener('change', updateEditEndDate);
            }

            // Handle payment method selection
            const paymentMethodSelect = document.getElementById('payment_method');
            if (paymentMethodSelect) {
                paymentMethodSelect.addEventListener('change', function() {
                    const paymentAmountField = document.querySelector('.payment-amount-field');
                    const referenceField = document.querySelector('.reference-field');
                    const paymentAmountInput = document.getElementById('payment_amount');
                    const planSelect = document.getElementById('plan');

                    if (this.value === '') {
                        paymentAmountField.style.display = 'none';
                        referenceField.style.display = 'none';
                        referenceField.querySelector('input').removeAttribute('required');
                    } else {
                        paymentAmountField.style.display = 'block';
                        if (this.value !== 'Cash') {
                            referenceField.style.display = 'block';
                            referenceField.querySelector('label').innerHTML = 'Reference Number *';
                            referenceField.querySelector('input').setAttribute('required', 'required');
                        } else {
                            referenceField.style.display = 'none';
                            referenceField.querySelector('input').removeAttribute('required');
                        }

                        // Auto-populate amount based on selected plan when payment method is chosen
                        if (paymentAmountInput && planSelect) {
                            const selectedPlan = planSelect.value;
                            if (selectedPlan && selectedPlan !== 'Manual') {
                                // Fetch membership fee for the selected plan
                                fetch('../admin/get_membership_fee.php?plan=' + encodeURIComponent(selectedPlan))
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.fee !== undefined) {
                                            paymentAmountInput.value = data.fee;
                                        }
                                    })
                                    .catch(error => console.error('Error fetching membership fee:', error));
                            }
                        }
                    }
                });
            }

            // Function to validate required fields
            function validateRequiredFields() {
                const fullname = document.querySelector('input[name="fullname"]').value.trim();
                const plan = document.querySelector('select[name="plan"]').value;
                const startDate = document.querySelector('input[name="start_date"]').value;
                const endDate = document.querySelector('input[name="end_date"]').value;

                return fullname !== '' && plan !== '' && startDate !== '' && endDate !== '';
            }

            // Function to show validation notification
            function showValidationNotification() {
                // Remove existing notification if any
                const existingNotification = document.querySelector('.validation-notification');
                if (existingNotification) {
                    existingNotification.remove();
                }

                // Create notification element
                const notification = document.createElement('div');
                notification.className = 'alert alert-warning alert-dismissible fade show validation-notification mt-3';
                notification.innerHTML = `
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Please complete all required fields first:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Full Name</li>
                        <li>Plan</li>
                        <li>Start Date</li>
                        <li>End Date</li>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;

                // Insert notification before QR Code section
                const qrSections = document.querySelectorAll('h6');
                let qrSection = null;
                for (let h6 of qrSections) {
                    if (h6.textContent.includes('QR Code Generation')) {
                        qrSection = h6;
                        break;
                    }
                }

                if (qrSection) {
                    qrSection.parentNode.insertBefore(notification, qrSection);
                } else {
                    // Fallback: insert before the QR code section
                    const hrElements = document.querySelectorAll('hr');
                    if (hrElements.length >= 1) {
                        hrElements[0].parentNode.insertBefore(notification, hrElements[0]);
                    }
                }

                // Auto-dismiss after 5 seconds
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 5000);
            }

            // ── AUTO QR GENERATION ─────────────────────────────────────────────────
            // Shared helper: calls generate_member_qr.php with the member's name,
            // fills hidden fields, then submits the form.
            function autoGenerateQRAndSubmit(form, memberNameValue, endpoint) {
                const submitBtn = form.querySelector('[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating QR & Saving...';
                }

                const fd = new FormData();
                if (memberNameValue) fd.append('member_name', memberNameValue);

                fetch(endpoint, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(data => {
                        if (data.error) {
                            alert('QR generation failed: ' + data.error);
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = submitBtn.dataset.origLabel || 'Submit';
                            }
                            return;
                        }
                        const tokenField = form.querySelector('[name="qr_token"]');
                        const pathField  = form.querySelector('[name="generated_qr_path"]');
                        if (tokenField) tokenField.value = data.qr_token;
                        if (pathField)  pathField.value  = data.path;

                        // form.submit() skips the submit button so its name is never POSTed.
                        // Inject a hidden field carrying the action name so PHP can detect it.
                        const actionBtn = form.querySelector('[type="submit"][name]');
                        if (actionBtn && !form.querySelector('input[name="' + actionBtn.name + '"]')) {
                            const h = document.createElement('input');
                            h.type  = 'hidden';
                            h.name  = actionBtn.name;
                            h.value = actionBtn.value || '1';
                            form.appendChild(h);
                        }
                        form.submit();
                    })
                    .catch(err => {
                        console.error('QR generation error:', err);
                        alert('Could not generate QR code. Please try again.');
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = submitBtn.dataset.origLabel || 'Submit';
                        }
                    });
            }

            // ── Add Member form ─────────────────────────────────────────────────────
            const addMemberForm = document.getElementById('addMemberForm');
            if (addMemberForm) {
                const addSubmitBtn = addMemberForm.querySelector('[name="add_member"]');
                if (addSubmitBtn) addSubmitBtn.dataset.origLabel = addSubmitBtn.innerHTML;

                addMemberForm.addEventListener('submit', function(e) {
                    const tokenField = this.querySelector('[name="qr_token"]');
                    if (tokenField && tokenField.value.trim() !== '') return;

                    e.preventDefault();
                    if (!validateRequiredFields()) {
                        showValidationNotification();
                        return;
                    }
                    const memberName = (this.querySelector('[name="fullname"]') || {}).value || '';
                    autoGenerateQRAndSubmit(this, memberName, '../generate_member_qr.php');
                });
            }

            // ── Quick Add Per Session form ──────────────────────────────────────────
            const quickAddForm = document.querySelector('#quickAddModal form');
            if (quickAddForm) {
                if (!quickAddForm.querySelector('[name="qr_token"]')) {
                    const t = document.createElement('input');
                    t.type = 'hidden'; t.name = 'qr_token';
                    quickAddForm.appendChild(t);
                }
                if (!quickAddForm.querySelector('[name="generated_qr_path"]')) {
                    const p = document.createElement('input');
                    p.type = 'hidden'; p.name = 'generated_qr_path';
                    quickAddForm.appendChild(p);
                }

                const quickSubmitBtn = quickAddForm.querySelector('[name="quick_add_member"]');
                if (quickSubmitBtn) quickSubmitBtn.dataset.origLabel = quickSubmitBtn.innerHTML;

                quickAddForm.addEventListener('submit', function(e) {
                    const tokenField = this.querySelector('[name="qr_token"]');
                    if (tokenField && tokenField.value.trim() !== '') return;

                    e.preventDefault();
                    const memberName = (this.querySelector('[name="fullname"]') || {}).value || '';
                    autoGenerateQRAndSubmit(this, memberName, '../generate_member_qr.php');
                });
            }

            // Function to print QR code
            window.printQRCode = function(imagePath, memberCode, memberName) {
                const printWindow = window.open('', '_blank', 'width=800,height=600');
                printWindow.document.title = `QR Code - ${memberName}`;
                printWindow.document.write(`
                    <html>
                    <head>
                        <title>QR Code - ${memberName}</title>
                        <style>
                            @page {
                                size: A4;
                                margin: 0.5in;
                            }
                            body {
                                font-family: Arial, sans-serif;
                                margin: 0;
                                padding: 20px;
                                text-align: center;
                                height: 100vh;
                                display: flex;
                                flex-direction: column;
                                justify-content: center;
                                align-items: center;
                            }
                            .qr-container {
                                border: 2px solid #000;
                                padding: 20px;
                                display: inline-block;
                                background: white;
                                margin: 20px 0;
                            }
                            .qr-code {
                                width: 300px;
                                height: 300px;
                                max-width: 100%;
                            }
                            .instructions {
                                font-size: 14px;
                                color: #666;
                                margin-top: 20px;
                            }
                        </style>
                    </head>
                    <body>
                        <div class="qr-container">
                            <img src="${imagePath}" alt="QR Code" class="qr-code">
                        </div>

                        <div class="instructions">
                            Scan this QR code for attendance check-in at the gym entrance
                        </div>
                    </body>
                    </html>
                `);
                printWindow.document.close();
                printWindow.print();
            };

            // viewQR, confirmRegenQR and printQR defined as globals below

            window.printQR = function() {
                const qrContainer = document.getElementById('qrCodeContainer');
                const memberName = document.getElementById('qrMemberName').textContent;
                const imgSrc = qrContainer.querySelector('img') ? qrContainer.querySelector('img').src : '';
                const printWindow = window.open('', '_blank', 'width=600,height=750');
                printWindow.document.write(`
                    <!DOCTYPE html><html><head><title>QR Code - ${memberName}</title>
                    <style>
                        body { font-family: Arial, sans-serif; text-align: center; padding: 30px; background: #fff; }
                        .wrapper { display: inline-block; border: 2px solid #005050; border-radius: 12px; padding: 24px 32px; }
                        .scan-note { font-size: 10px; color: #888; margin-top: 10px; }
                        img { max-width: 260px; display: block; margin: 0 auto; }
                    </style></head>
                    <body>
                        <div class='wrapper'>
                            <img src='${imgSrc}'>
                            <div class='scan-note'>Scan to record attendance</div>
                        </div>
                    </body></html>
                `);
                printWindow.document.close();
                printWindow.print();
            };
        });
    </script>

    <script>
        // Global QR functions - outside DOMContentLoaded so onclick works
        function viewQR(fullname, qrCodePath, memberId) {
            document.getElementById('qrMemberName').textContent = fullname;
            document.getElementById('regenMemberId').value = memberId;
            var qrContainer = document.getElementById('qrCodeContainer');
            qrContainer.innerHTML = '<img src="../' + qrCodePath + '?t=' + Date.now() + '" class="img-fluid" style="max-width: 220px;">';
            var modal = new bootstrap.Modal(document.getElementById('qrModal'));
            modal.show();
        }

        function confirmRegenQR() {
            var name = document.getElementById('qrMemberName').textContent;
            document.getElementById('regenConfirmName').textContent = name;
            var modal = new bootstrap.Modal(document.getElementById('regenConfirmModal'));
            modal.show();
        }

        function doRegenQR() {
            bootstrap.Modal.getInstance(document.getElementById('regenConfirmModal')).hide();
            document.getElementById('regenQrForm').submit();
        }

        function printQR() {
            var qrContainer = document.getElementById('qrCodeContainer');
            var memberName = document.getElementById('qrMemberName').textContent;
            var imgEl = qrContainer.querySelector('img');
            var imgSrc = imgEl ? imgEl.src : '';
            var printWindow = window.open('', '_blank', 'width=600,height=750');
            printWindow.document.write('<!DOCTYPE html><html><head><title>QR - ' + memberName + '</title>'
                + '<style>body{font-family:Arial,sans-serif;text-align:center;padding:30px;background:#fff;}'
                + '.wrapper{display:inline-block;border:2px solid #005050;border-radius:12px;padding:24px 32px;}'
                + '.note{font-size:11px;color:#888;margin-top:10px;}'
                + 'img{max-width:260px;display:block;margin:0 auto;}</style></head>'
                + '<body><div class="wrapper">'
                + '<img src="' + imgSrc + '">'
                + '<div class="note">Scan to record attendance</div>'
                + '</div></body></html>');
            printWindow.document.close();
            printWindow.print();
        }
    </script>

    <script>

        // Professional delete confirmation function
        function confirmDelete(memberName, deleteUrl) {
            // Create confirmation modal
            const modalHtml = `
                <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-warning">
                                <h5 class="modal-title text-dark" id="deleteConfirmModalLabel">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Confirm Deletion
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="text-center">
                                    <div class="mb-3">
                                        <i class="fas fa-user-times fa-3x text-warning"></i>
                                    </div>
                                    <h6 class="fw-bold">Are you sure you want to delete this member?</h6>
                                    <p class="text-muted mb-0">Member: <strong>${memberName}</strong></p>
                                    <p class="text-danger small mt-2">
                                        <i class="fas fa-exclamation-circle me-1"></i>
                                        This action cannot be undone. All associated data will be permanently removed.
                                    </p>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-1"></i>Cancel
                                </button>
                                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                                    <i class="fas fa-trash me-1"></i>Delete Member
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Remove existing modal if present
            const existingModal = document.getElementById('deleteConfirmModal');
            if (existingModal) {
                existingModal.remove();
            }

            // Add modal to body
            document.body.insertAdjacentHTML('beforeend', modalHtml);

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
            modal.show();

            // Handle confirm button click
            document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
                modal.hide();
                window.location.href = deleteUrl;
            });

            // Clean up modal after hiding
            document.getElementById('deleteConfirmModal').addEventListener('hidden.bs.modal', function() {
                this.remove();
            });
        }

        // Show notifications based on URL parameters
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);

            if (urlParams.has('add_success')) {
                showToast('New member has been successfully added to the system.', 'success');
            } else if (urlParams.has('quick_add_success')) {
                showToast('Member and payment have been added successfully.', 'success');
            } else if (urlParams.has('success')) {
                showToast('Member information has been updated successfully.', 'success');
            } else if (urlParams.has('renew_success')) {
                showToast('Membership has been successfully renewed!', 'success');
            } else if (urlParams.has('regen_success')) {
                showToast('QR code has been regenerated successfully!', 'success');
            } else if (urlParams.has('delete_success')) {
                showToast('Member has been removed from the system.', 'success');
            } else if (urlParams.has('duplicate_error')) {
                showToast('A member with this name already exists. Please use a different name.', 'danger');
            } else if (urlParams.has('error')) {
                showToast('Failed to update member information. Please try again.', 'danger');
            } else if (urlParams.has('quick_add_error')) {
                showToast('Failed to add member via Quick Add. Please try again.', 'danger');
            }

            // Clean URL after showing notification
            if (urlParams.toString()) {
                const newUrl = window.location.pathname;
                window.history.replaceState({}, document.title, newUrl);
            }
        });

        // ---- Membership Renewal ----
        const membershipFees = <?php echo json_encode($membership_fees); ?>;

        function getPlanFee(plan) {
            const map = {
                'Per Session': membershipFees.per_session_fee,
                'Half Month': membershipFees.half_month_fee,
                '1 Month': membershipFees.one_month_fee,
                '2 Months': membershipFees.two_months_fee,
                '3 Months': membershipFees.three_months_fee,
                '4 Months': membershipFees.four_months_fee,
                '5 Months': membershipFees.five_months_fee,
                '6 Months': membershipFees.six_months_fee,
                '7 Months': membershipFees.seven_months_fee,
                '8 Months': membershipFees.eight_months_fee,
                '9 Months': membershipFees.nine_months_fee,
                '10 Months': membershipFees.ten_months_fee,
                '11 Months': membershipFees.eleven_months_fee,
                '1 Year': membershipFees.one_year_fee,
                '2 Years': membershipFees.two_years_fee,
                '3 Years': membershipFees.three_years_fee
            };
            return map[plan] || 0;
        }

        function openRenewModal(id, name, oldPlan, oldEnd) {
            document.getElementById('renewMemberId').value = id;
            document.getElementById('renewMemberName').textContent = name;
            document.getElementById('renewOldPlan').textContent = oldPlan;
            document.getElementById('renewOldEnd').textContent = oldEnd;
            document.getElementById('renewPlan').value = '';
            document.getElementById('renewStartDate').value = new Date().toISOString().split('T')[0];
            document.getElementById('renewFeeBox').style.display = 'none';
            document.getElementById('renewSubmitBtn').disabled = true;
            const modal = new bootstrap.Modal(document.getElementById('renewModal'));
            modal.show();
        }

        function updateRenewFee() {
            const plan = document.getElementById('renewPlan').value;
            const fee = getPlanFee(plan);
            if (plan) {
                document.getElementById('renewFeeAmount').textContent = fee.toFixed(2);
                document.getElementById('renewFeeBox').style.display = 'block';
                document.getElementById('renewSubmitBtn').disabled = false;
            } else {
                document.getElementById('renewFeeBox').style.display = 'none';
                document.getElementById('renewSubmitBtn').disabled = true;
            }
        }
    </script>
    <script src="../assets/sidebar.js"></script>
</body>
</html>
