<?php
ob_start();
header('Content-Type: application/json');

try {
    if (session_status() == PHP_SESSION_NONE) session_start();

    $conn = new mysqli("localhost", "root", "", "gym_db");
    if ($conn->connect_error) {
        ob_end_clean();
        echo json_encode(['error' => 'DB failed: ' . $conn->connect_error]);
        exit();
    }

    $qrlib = __DIR__ . '/phpqrcode/qrlib.php';
    if (!file_exists($qrlib)) {
        ob_end_clean();
        echo json_encode(['error' => 'QR library not found.']);
        exit();
    }
    require_once $qrlib;

    $dir = __DIR__ . '/qr_codes';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if (!is_writable($dir)) {
        ob_end_clean();
        echo json_encode(['error' => 'qr_codes not writable.']);
        exit();
    }

    // Accept optional member name for footer label
    $member_name = isset($_POST['member_name']) ? trim($_POST['member_name']) : '';

    // Load gym settings
    $gym_name  = 'Gym Management System';
    $logo_path = '';
    $gs = $conn->query("SELECT gym_name, logo_path FROM gym_settings WHERE id = 1");
    if ($gs && $gs->num_rows > 0) {
        $gsr       = $gs->fetch_assoc();
        $gym_name  = $gsr['gym_name'];
        $logo_path = __DIR__ . '/' . $gsr['logo_path'];
    }

    // Generate a unique QR token
    do {
        $qr_token = bin2hex(random_bytes(32));
        $stmt = $conn->prepare("SELECT id FROM members WHERE qr_token = ?");
        $stmt->bind_param("s", $qr_token);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
    } while ($exists || empty($qr_token));

    // File paths
    $tmp_file = $dir . '/tmp_' . $qr_token . '.png';
    $filename = $qr_token . '.png';
    $filepath = $dir . '/' . $filename;
    $qr_path  = 'qr_codes/' . $filename;

    // Generate raw QR PNG to temp file
    QRcode::png($qr_token, $tmp_file, QR_ECLEVEL_H, 10, 4);
    if (!file_exists($tmp_file)) {
        ob_end_clean();
        echo json_encode(['error' => 'QR not created. Enable GD extension.']);
        exit();
    }

    // --- Layout: [TOP BANNER: gym name] [QR + logo overlay] [BOTTOM FOOTER: member name] ---
    $qr_img   = imagecreatefrompng($tmp_file);
    $qr_w     = imagesx($qr_img);
    $qr_h     = imagesy($qr_img);

    $font      = 5;
    $banner_h  = 48;                                          // top teal banner height
    $footer_h  = !empty($member_name) ? 36 : 0;              // bottom white footer (member name)
    $padding   = 16;
    $total_w   = $qr_w + $padding * 2;
    $total_h   = $qr_h + $banner_h + $footer_h + $padding * 2;

    $canvas    = imagecreatetruecolor($total_w, $total_h);
    $dark_teal = imagecolorallocate($canvas, 0, 80, 80);
    $white     = imagecolorallocate($canvas, 255, 255, 255);
    $dark_text = imagecolorallocate($canvas, 30, 30, 30);

    // White background
    imagefill($canvas, 0, 0, $white);

    // Top teal banner (gym name)
    imagefilledrectangle($canvas, 0, 0, $total_w, $banner_h, $dark_teal);
    $max_chars = (int) floor($total_w / imagefontwidth($font)) - 2;
    $label     = strlen($gym_name) > $max_chars
        ? substr($gym_name, 0, $max_chars - 1) . '.'
        : $gym_name;
    $text_w = strlen($label) * imagefontwidth($font);
    $text_x = (int) (($total_w - $text_w) / 2);
    $text_y = (int) (($banner_h - imagefontheight($font)) / 2);
    imagestring($canvas, $font, $text_x, $text_y, $label, $white);

    // Paste QR code below banner
    imagecopy($canvas, $qr_img, $padding, $banner_h + $padding, 0, 0, $qr_w, $qr_h);

    // Overlay gym logo in QR center (if available)
    $logo_size   = (int) ($qr_w * 0.22);
    $logo_placed = false;
    if (!empty($logo_path) && file_exists($logo_path)) {
        $ext      = strtolower(pathinfo($logo_path, PATHINFO_EXTENSION));
        $logo_src = null;
        if ($ext === 'png') {
            $logo_src = imagecreatefrompng($logo_path);
        } elseif (in_array($ext, ['jpg', 'jpeg'])) {
            $logo_src = imagecreatefromjpeg($logo_path);
        } elseif ($ext === 'gif') {
            $logo_src = imagecreatefromgif($logo_path);
        }

        if ($logo_src) {
            $lr = imagecreatetruecolor($logo_size, $logo_size);
            imagealphablending($lr, false);
            imagesavealpha($lr, true);
            $tr = imagecolorallocatealpha($lr, 0, 0, 0, 127);
            imagefill($lr, 0, 0, $tr);
            imagecopyresampled($lr, $logo_src, 0, 0, 0, 0, $logo_size, $logo_size, imagesx($logo_src), imagesy($logo_src));

            $lx = $padding + (int) (($qr_w - $logo_size) / 2);
            $ly = $banner_h + $padding + (int) (($qr_h - $logo_size) / 2);

            // White circle background behind logo
            imagefilledellipse($canvas, $lx + (int) ($logo_size / 2), $ly + (int) ($logo_size / 2), $logo_size + 16, $logo_size + 16, $white);
            imagecopy($canvas, $lr, $lx, $ly, 0, 0, $logo_size, $logo_size);

            imagedestroy($lr);
            imagedestroy($logo_src);
            $logo_placed = true;
        }
    }

    // Bottom footer: member name centered in white strip
    if (!empty($member_name)) {
        $footer_y   = $banner_h + $padding + $qr_h + $padding;
        $name_label = strlen($member_name) > $max_chars
            ? substr($member_name, 0, $max_chars - 1) . '.'
            : $member_name;
        $name_w  = strlen($name_label) * imagefontwidth($font);
        $name_x  = (int) (($total_w - $name_w) / 2);
        $name_y  = (int) ($footer_y + ($footer_h - imagefontheight($font)) / 2);
        imagestring($canvas, $font, $name_x, $name_y, $name_label, $dark_text);
    }

    // Save final image
    imagepng($canvas, $filepath);
    imagedestroy($canvas);
    imagedestroy($qr_img);
    @unlink($tmp_file);

    if (!file_exists($filepath)) {
        ob_end_clean();
        echo json_encode(['error' => 'QR not saved.']);
        exit();
    }

    ob_end_clean();
    echo json_encode([
        'success'      => true,
        'qr_token'     => $qr_token,
        'path'         => $qr_path,
        'filename'     => $filename,
        'logo_applied' => $logo_placed,
        'gym_name'     => $gym_name,
        'member_name'  => $member_name,
    ]);

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['error' => 'Exception: ' . $e->getMessage()]);
}
exit();
