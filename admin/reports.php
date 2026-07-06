<?php
include "../config/db.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$user     = $_SESSION['user'];
$settings = $conn->query("SELECT * FROM gym_settings WHERE id = 1")->fetch_assoc();
if (!$settings) {
    $conn->query("INSERT INTO gym_settings (gym_name, logo_path, background_path) VALUES ('Gym Management System', 'gym logo.jpg', 'gym background.jpg')");
    $settings = $conn->query("SELECT * FROM gym_settings WHERE id = 1")->fetch_assoc();
}

$gym_name = $settings['gym_name'] ?? 'Gym Management System';

/* ── Date helpers ──────────────────────────────────────────────────────── */
$curStart  = date('Y-m-01');
$curEnd    = date('Y-m-t');
$prevStart = date('Y-m-01', strtotime('-1 month'));
$prevEnd   = date('Y-m-t', strtotime('-1 month'));

function qval($conn, $sql) {
    $res = $conn->query($sql);
    if (!$res) return 0;
    $row = $res->fetch_assoc();
    if (!$row) return 0;
    $v = reset($row);
    return $v === null ? 0 : $v;
}
function percentChange($current, $previous) {
    $current = (float)$current; $previous = (float)$previous;
    if ($previous == 0) return ['pct'=>$current > 0 ? 100 : 0,'dir'=>$current > 0 ? 'up' : 'flat'];
    $pct = (($current - $previous) / $previous) * 100;
    return ['pct'=>abs($pct), 'dir'=>$pct > 0.0001 ? 'up' : ($pct < -0.0001 ? 'down' : 'flat')];
}
function trendBadge($change) {
    $cls   = $change['dir']==='up' ? 'o-trend-up' : ($change['dir']==='down' ? 'o-trend-down' : 'o-trend-flat');
    $arrow = $change['dir']==='up' ? '&#9650;' : ($change['dir']==='down' ? '&#9660;' : '&#9679;');
    return '<span class="o-stat-trend '.$cls.'">'.$arrow.' '.number_format($change['pct'],2).'%</span>';
}
function peso($n) { return '&#8369;'.number_format((float)$n,2); }
function fmtDur($secs) {
    if ($secs === null) return '-';
    $h = floor($secs/3600); $m = floor(($secs%3600)/60); $s = $secs%60;
    return $h > 0 ? "{$h}h {$m}m {$s}s" : "{$m}m {$s}s";
}

/* ── Overview stats ────────────────────────────────────────────────────── */
$total_members    = qval($conn, "SELECT COUNT(*) c FROM members");
$active_members   = qval($conn, "SELECT COUNT(*) c FROM members WHERE status = 'ACTIVE'");
$total_attendance = qval($conn, "SELECT COUNT(*) c FROM attendance WHERE checkin_time BETWEEN '$curStart 00:00:00' AND '$curEnd 23:59:59'");
$attendance_prev  = qval($conn, "SELECT COUNT(*) c FROM attendance WHERE checkin_time BETWEEN '$prevStart 00:00:00' AND '$prevEnd 23:59:59'");
$chg_attendance   = percentChange($total_attendance, $attendance_prev);
$membership_cur   = qval($conn, "SELECT COALESCE(SUM(amount),0) t FROM payments WHERE payment_date BETWEEN '$curStart 00:00:00' AND '$curEnd 23:59:59'");
$membership_prev  = qval($conn, "SELECT COALESCE(SUM(amount),0) t FROM payments WHERE payment_date BETWEEN '$prevStart 00:00:00' AND '$prevEnd 23:59:59'");
$pos_cur          = qval($conn, "SELECT COALESCE(SUM(total_amount),0) t FROM pos_sales WHERE sale_date BETWEEN '$curStart 00:00:00' AND '$curEnd 23:59:59'");
$pos_prev         = qval($conn, "SELECT COALESCE(SUM(total_amount),0) t FROM pos_sales WHERE sale_date BETWEEN '$prevStart 00:00:00' AND '$prevEnd 23:59:59'");
$total_cur        = $membership_cur + $pos_cur;
$total_prev       = $membership_prev + $pos_prev;
$chg_total        = percentChange($total_cur, $total_prev);
$monthly_pos_items = qval($conn, "SELECT COALESCE(SUM(psi.quantity),0) t FROM pos_sale_items psi JOIN pos_sales ps ON psi.sale_id = ps.id WHERE ps.sale_date BETWEEN '$curStart 00:00:00' AND '$curEnd 23:59:59'");
$low_stock_items   = qval($conn, "SELECT COUNT(*) c FROM pos_items WHERE stock_quantity <= 10 AND is_active = 1");

$catRevenue = ['beverage'=>0,'snack'=>0,'supplement'=>0,'other'=>0];
$catLabels  = ['beverage'=>'Beverage','snack'=>'Snack','supplement'=>'Supplement','other'=>'Other'];
$catRes = $conn->query("SELECT pi.category, COALESCE(SUM(psi.total_price),0) AS rev FROM pos_sale_items psi JOIN pos_sales ps ON psi.sale_id=ps.id JOIN pos_items pi ON psi.item_id=pi.id WHERE ps.sale_date BETWEEN '$curStart 00:00:00' AND '$curEnd 23:59:59' GROUP BY pi.category");
if ($catRes) while ($row = $catRes->fetch_assoc()) if (isset($catRevenue[$row['category']])) $catRevenue[$row['category']] = (float)$row['rev'];
$catMax = max(1, max($catRevenue));
$hasCatData = array_sum($catRevenue) > 0;
$splitTotal = $total_cur > 0 ? $total_cur : 1;
$membershipPct = round(($membership_cur/$splitTotal)*100,1);
$posPct = round(100-$membershipPct,1);
if ($total_cur <= 0) { $membershipPct=0; $posPct=0; }
function attendanceRate($conn,$start,$end) {
    $eligible  = qval($conn,"SELECT COUNT(*) c FROM members WHERE start_date <= '$end' AND (end_date IS NULL OR end_date >= '$start')");
    $checkedIn = qval($conn,"SELECT COUNT(DISTINCT member_id) c FROM attendance WHERE checkin_time BETWEEN '$start 00:00:00' AND '$end 23:59:59'");
    if ($eligible <= 0) return 0;
    return min(100, round(($checkedIn/$eligible)*100));
}
$attendanceRateCur  = attendanceRate($conn,$curStart,$curEnd);
$attendanceRatePrev = attendanceRate($conn,$prevStart,$prevEnd);
$lowStockList = [];
$lsRes = $conn->query("SELECT name, stock_quantity FROM pos_items WHERE is_active=1 AND stock_quantity<=10 ORDER BY stock_quantity ASC LIMIT 6");
if ($lsRes) while ($row=$lsRes->fetch_assoc()) $lowStockList[]=$row;
$lowStockMax = 10;
foreach ($lowStockList as $li) $lowStockMax = max($lowStockMax,(int)$li['stock_quantity']);
$donutGradient = "conic-gradient(#3461ff 0% {$membershipPct}%, #ff6b00 {$membershipPct}% 100%)";

/* ═══════════════════════════════════════════════════════════════════════
   EXCEL EXPORT — data fetch helpers
   ═══════════════════════════════════════════════════════════════════════ */
function fetchSalesData($conn, $ef, $et) {
    $sr = $conn->query("SELECT ps.id, ps.sale_date, ps.total_amount, ps.payment_method, ps.reference_no, ps.customer_name, u.fullname AS cashier,
        GROUP_CONCAT(CONCAT(pi.name,' x',psi.quantity) ORDER BY pi.name SEPARATOR ', ') AS items
        FROM pos_sales ps
        LEFT JOIN users u ON ps.created_by = u.id
        LEFT JOIN pos_sale_items psi ON psi.sale_id = ps.id
        LEFT JOIN pos_items pi ON psi.item_id = pi.id
        WHERE DATE(ps.sale_date) BETWEEN '$ef' AND '$et' GROUP BY ps.id ORDER BY ps.sale_date DESC");
    $rows = [];
    if ($sr) while ($row = $sr->fetch_assoc()) $rows[] = $row;
    $grandTotal = array_sum(array_column($rows,'total_amount'));
    return [$rows, $grandTotal];
}
function fetchAttendanceData($conn, $ef, $et) {
    $ar = $conn->query("SELECT a.id, m.fullname, m.plan, a.checkin_time, a.checkout_time,
        CASE WHEN a.checkout_time IS NOT NULL THEN TIMESTAMPDIFF(SECOND,a.checkin_time,a.checkout_time) ELSE NULL END AS duration_secs
        FROM attendance a JOIN members m ON a.member_id=m.id
        WHERE DATE(a.checkin_time) BETWEEN '$ef' AND '$et' ORDER BY a.checkin_time DESC");
    $rows = [];
    if ($ar) while ($row=$ar->fetch_assoc()) $rows[]=$row;
    $total     = count($rows);
    $completed = count(array_filter($rows,fn($r)=>$r['checkout_time']!==null));
    $active    = $total-$completed;
    $durations = array_filter(array_column($rows,'duration_secs'),fn($d)=>$d!==null);
    $avgDur    = count($durations) ? array_sum($durations)/count($durations) : 0;
    return [$rows, $total, $completed, $active, $avgDur];
}
function salesSheetHtml($gym_name, $user, $ef, $et, $rows, $grandTotal) {
    $html  = '<table>';
    $html .= '<tr><td colspan="8" class="title">'.htmlspecialchars($gym_name).' &mdash; Sales Report</td></tr>';
    $html .= '<tr><td colspan="8" class="subtitle">Period: '.date('F j, Y',strtotime($ef)).' &nbsp;to&nbsp; '.date('F j, Y',strtotime($et)).'</td></tr>';
    $html .= '<tr><td colspan="8" class="subtitle">Generated: '.date('F j, Y h:i A').' &nbsp;|&nbsp; Prepared by: '.htmlspecialchars($user['fullname']).'</td></tr>';
    $html .= '<tr><td colspan="8">&nbsp;</td></tr>';
    $html .= '<tr><td colspan="8" class="section">Summary</td></tr>';
    $html .= '<tr>
        <td class="stat-label">Total Transactions</td><td class="center num">'.count($rows).'</td>
        <td class="stat-label">Total Revenue</td><td class="center">&#8369;'.number_format($grandTotal,2).'</td>
        <td class="stat-label">Period</td><td class="center" colspan="3">'.date('M j',strtotime($ef)).' &ndash; '.date('M j, Y',strtotime($et)).'</td>
    </tr>';
    $html .= '<tr><td colspan="8">&nbsp;</td></tr>';
    $html .= '<tr><td colspan="8" class="section">Sales Records</td></tr>';
    $html .= '<tr>
        <th>#</th><th>Date &amp; Time</th><th>Customer</th><th>Items Sold</th>
        <th>Payment Method</th><th>Reference No.</th><th>Cashier</th><th>Amount</th>
    </tr>';
    $i=1;
    foreach ($rows as $row) {
        $html .= '<tr>
            <td class="center num">'.$i++.'</td>
            <td class="center">'.date('M j, Y h:i A',strtotime($row['sale_date'])).'</td>
            <td>'.htmlspecialchars($row['customer_name'] ?: '—').'</td>
            <td>'.htmlspecialchars($row['items'] ?: '—').'</td>
            <td class="center">'.ucfirst($row['payment_method']).'</td>
            <td class="center">'.htmlspecialchars($row['reference_no'] ?: '—').'</td>
            <td>'.htmlspecialchars($row['cashier'] ?: '—').'</td>
            <td class="right">&#8369;'.number_format($row['total_amount'],2).'</td>
        </tr>';
    }
    if (empty($rows)) $html .= '<tr><td colspan="8" style="text-align:center;color:#999;padding:20px;">No sales records found for the selected period.</td></tr>';
    $html .= '<tr class="total-row"><td colspan="7" style="text-align:right;padding-right:12px;">GRAND TOTAL</td><td class="right">&#8369;'.number_format($grandTotal,2).'</td></tr>';
    $html .= '</table>';
    $html .= '<br><table><tr><td style="font-size:9pt;color:#999;">This report was automatically generated by '.htmlspecialchars($gym_name).' Management System.</td></tr></table>';
    return $html;
}
function attendanceSheetHtml($gym_name, $user, $ef, $et, $rows, $total, $completed, $active, $avgDur) {
    $html  = '<table>';
    $html .= '<tr><td colspan="7" class="title">'.htmlspecialchars($gym_name).' &mdash; Attendance Report</td></tr>';
    $html .= '<tr><td colspan="7" class="subtitle">Period: '.date('F j, Y',strtotime($ef)).' &nbsp;to&nbsp; '.date('F j, Y',strtotime($et)).'</td></tr>';
    $html .= '<tr><td colspan="7" class="subtitle">Generated: '.date('F j, Y h:i A').' &nbsp;|&nbsp; Prepared by: '.htmlspecialchars($user['fullname']).'</td></tr>';
    $html .= '<tr><td colspan="7">&nbsp;</td></tr>';
    $html .= '<tr><td colspan="7" class="section">Summary</td></tr>';
    $html .= '<tr>
        <td class="stat-label">Total Records</td><td class="center num">'.$total.'</td>
        <td class="stat-label">Completed</td><td class="center num">'.$completed.'</td>
        <td class="stat-label">Still Inside</td><td class="center num">'.$active.'</td>
        <td class="stat-label">Avg Duration: <b>'.fmtDur(round($avgDur)).'</b></td>
    </tr>';
    $html .= '<tr><td colspan="7">&nbsp;</td></tr>';
    $html .= '<tr><td colspan="7" class="section">Attendance Records</td></tr>';
    $html .= '<tr><th>#</th><th>Member Name</th><th>Plan</th><th>Date</th><th>Check-in</th><th>Check-out</th><th>Duration</th></tr>';
    $i=1;
    foreach ($rows as $row) {
        $html .= '<tr>
            <td class="center num">'.$i++.'</td>
            <td>'.htmlspecialchars($row['fullname']).'</td>
            <td class="center">'.htmlspecialchars(ucwords($row['plan'])).'</td>
            <td class="center">'.date('M j, Y',strtotime($row['checkin_time'])).'</td>
            <td class="center">'.date('h:i:s A',strtotime($row['checkin_time'])).'</td>
            <td class="center">'.($row['checkout_time'] ? date('h:i:s A',strtotime($row['checkout_time'])) : '<span class="badge-active">Still Inside</span>').'</td>
            <td class="center">'.fmtDur($row['duration_secs']).'</td>
        </tr>';
    }
    if (empty($rows)) $html .= '<tr><td colspan="7" style="text-align:center;color:#999;padding:20px;">No attendance records found for the selected period.</td></tr>';
    $html .= '</table>';
    $html .= '<br><table><tr><td style="font-size:9pt;color:#999;">This report was automatically generated by '.htmlspecialchars($gym_name).' Management System.</td></tr></table>';
    return $html;
}
function xlsStyleBlock() {
    return '<style>
        body{font-family:Calibri,Arial;font-size:11pt;}table{border-collapse:collapse;width:100%;margin-bottom:18px;}
        .title{font-size:16pt;font-weight:bold;color:#005f73;}.subtitle{font-size:10pt;color:#666;}
        .section{font-size:12pt;font-weight:bold;background:#005f73;color:#fff;padding:4px 8px;}
        .stat-label{font-weight:bold;background:#e8f4f8;}
        th{background:#005f73;color:#fff;font-weight:bold;text-align:center;border:1px solid #ccc;padding:6px;}
        td{border:1px solid #ddd;padding:5px 8px;vertical-align:middle;}
        tr:nth-child(even) td{background:#f9f9f9;}.center{text-align:center;}.right{text-align:right;}.num{mso-number-format:"0";}
        .total-row td{font-weight:bold;background:#e8f4f8;}.badge-active{color:#856404;font-weight:bold;}
    </style>';
}

/* ═══════════════════════════════════════════════════════════════════════
   EXCEL EXPORT — Sales Report (single sheet)
   ═══════════════════════════════════════════════════════════════════════ */
if (isset($_GET['export']) && $_GET['export'] === 'sales') {
    $ef = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
    $et = isset($_GET['date_to'])   ? $_GET['date_to']   : date('Y-m-d');
    list($rows, $grandTotal) = fetchSalesData($conn, $ef, $et);

    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="Sales_Report_'.$ef.'_to_'.$et.'.xls"');
    header('Cache-Control: max-age=0');
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8">'.xlsStyleBlock().'</head><body>';
    echo salesSheetHtml($gym_name, $user, $ef, $et, $rows, $grandTotal);
    echo '</body></html>';
    exit();
}

/* ═══════════════════════════════════════════════════════════════════════
   EXCEL EXPORT — Attendance Report (single sheet)
   ═══════════════════════════════════════════════════════════════════════ */
if (isset($_GET['export']) && $_GET['export'] === 'attendance') {
    $ef = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
    $et = isset($_GET['date_to'])   ? $_GET['date_to']   : date('Y-m-d');
    list($rows, $total, $completed, $active, $avgDur) = fetchAttendanceData($conn, $ef, $et);

    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="Attendance_Report_'.$ef.'_to_'.$et.'.xls"');
    header('Cache-Control: max-age=0');
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8">'.xlsStyleBlock().'</head><body>';
    echo attendanceSheetHtml($gym_name, $user, $ef, $et, $rows, $total, $completed, $active, $avgDur);
    echo '</body></html>';
    exit();
}

/* ═══════════════════════════════════════════════════════════════════════
   EXCEL EXPORT — Combined Report (Sales + Attendance, two sheets)
   ═══════════════════════════════════════════════════════════════════════ */
if (isset($_GET['export']) && $_GET['export'] === 'both') {
    $ef = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
    $et = isset($_GET['date_to'])   ? $_GET['date_to']   : date('Y-m-d');
    list($salesRows, $grandTotal) = fetchSalesData($conn, $ef, $et);
    list($attRows, $attTotal, $attCompleted, $attActive, $attAvgDur) = fetchAttendanceData($conn, $ef, $et);

    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="Full_Report_'.$ef.'_to_'.$et.'.xls"');
    header('Cache-Control: max-age=0');
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8">';
    /* Multi-sheet metadata so Excel opens this as a workbook with two named tabs */
    echo '<!--[if gte mso 9]><xml>
        <x:ExcelWorkbook><x:ExcelWorksheets>
        <x:ExcelWorksheet><x:Name>Sales Report</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet>
        <x:ExcelWorksheet><x:Name>Attendance Report</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet>
        </x:ExcelWorksheets></x:ExcelWorkbook>
        </xml><![endif]-->';
    echo xlsStyleBlock().'</head><body>';
    echo salesSheetHtml($gym_name, $user, $ef, $et, $salesRows, $grandTotal);
    echo attendanceSheetHtml($gym_name, $user, $ef, $et, $attRows, $attTotal, $attCompleted, $attActive, $attAvgDur);
    echo '</body></html>';
    exit();
}

$sidebarTextClass = ($settings['sidebar_theme'] == 'light') ? 'text-dark' : 'text-white';

function icon($name) {
    $icons = [
        'dashboard'=>'<path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>',
        'users'    =>'<path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>',
        'cart'     =>'<path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96C5 13.1 5.9 14 7 14h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12L8.1 13h7.45c.75 0 1.41-.41 1.75-1.03L20.93 6c.1-.18.07-.4-.07-.55-.13-.13-.32-.19-.51-.16L4.27 6 3.21 4H1zM17 18c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>',
        'calendar' =>'<path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11zM7 11h5v5H7z"/>',
        'chart'    =>'<path d="M5 9.2h3V19H5zM10.6 5h2.8v14h-2.8zm5.6 8H19v6h-2.8z"/>',
        'employee' =>'<path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>',
        'cog'      =>'<path d="M19.43 12.98c.04-.32.07-.64.07-.98s-.03-.66-.07-.98l2.11-1.65c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.39-.3-.61-.22l-2.49 1c-.52-.4-1.08-.73-1.69-.98l-.38-2.65A.488.488 0 0 0 14 2h-4c-.24 0-.44.17-.48.41l-.38 2.65c-.61.25-1.17.59-1.69.98l-2.49-1a.493.493 0 0 0-.61.22l-2 3.46c-.12.22-.07.49.12.64l2.11 1.65c-.04.32-.07.65-.07.98s.03.66.07.98l-2.11 1.65c-.19.15-.24.42-.12.64l2 3.46c.12.22.39.3.61.22l2.49-1c.52.4 1.08.73 1.69.98l.38 2.65c.05.24.25.41.49.41h4c.24 0 .44-.17.48-.41l.38-2.65c.61-.25 1.17-.58 1.69-.98l2.49 1c.23.09.49 0 .61-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.65zM12 15.5c-1.93 0-3.5-1.57-3.5-3.5s1.57-3.5 3.5-3.5 3.5 1.57 3.5 3.5-1.57 3.5-3.5 3.5z"/>',
        'globe'    =>'<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zM4 12c0-.61.08-1.21.21-1.78L8.5 14.5V15.5c0 1.1.9 2 2 2v1.93C6.58 19.44 4 16.02 4 12zm14.34 4.9c-.26-.85-1.03-1.4-1.94-1.4H15v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.11 5.4-.19-.19-.36-.34-.55-.5z"/>',
        'logout'   =>'<path d="M17 7l-1.41 1.41L17.17 10H9v2h8.17l-1.58 1.59L17 15l4-4zM5 5h7V3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h7v-2H5V5z"/>',
        'bars'     =>'<path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>',
        'peso'     =>'<path d="M7 3h6.5c2.76 0 5 2.24 5 5s-2.24 5-5 5H10v2h5v2h-5v3H7v-3H5v-2h2v-2H5V11h2V3zm3 8h3.5c1.38 0 2.5-1.12 2.5-2.5S14.88 6 13.5 6H10v5z"/>',
        'warning'  =>'<path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>',
        'file'     =>'<path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm4 18H6V4h7v5h5v11zM8 15h8v2H8zm0-4h8v2H8zm0-4h5v2H8z"/>',
        'download' =>'<path d="M5 20h14v-2H5v2zM19 9h-4V3H9v6H5l7 7 7-7z"/>',
    ];
    $path = $icons[$name] ?? '';
    return '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">'.$path.'</svg>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - <?php echo htmlspecialchars($gym_name); ?></title>
    <link href="../assets/reports-offline.css?v=5" rel="stylesheet">
    <style>
    /* ── Generate Report section ─────────────────────────────── */
    .gr-topbar { display:flex; justify-content:flex-end; margin-bottom:1.5rem; }
    .gr-btn-generate { padding:.65rem 1.4rem; background:#005f73; color:#fff; border:none; border-radius:8px; font-weight:700; font-size:.9rem; cursor:pointer; display:inline-flex; align-items:center; gap:.5rem; transition:background .15s; box-shadow:0 2px 8px rgba(0,95,115,.25); }
    .gr-btn-generate svg { width:17px; height:17px; }
    .gr-btn-generate:hover { background:#0a9396; }

    /* Modal */
    .gr-modal-overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,.55); z-index:2000; align-items:center; justify-content:center; padding:1rem; }
    .gr-modal-overlay.show { display:flex; }
    .gr-modal { background:#fff; border-radius:14px; max-width:480px; width:100%; box-shadow:0 20px 50px rgba(0,0,0,.28); overflow:hidden; }
    .gr-modal-head { display:flex; align-items:center; justify-content:space-between; padding:1.1rem 1.4rem; border-bottom:1px solid #eef0f3; }
    .gr-modal-head h3 { margin:0; font-size:1rem; font-weight:700; color:#33394a; }
    .gr-modal-close { background:none; border:none; font-size:1.5rem; line-height:1; cursor:pointer; color:#8a8f98; padding:0; }
    .gr-modal-close:hover { color:#212529; }
    .gr-modal-body { padding:1.4rem; min-height:170px; }
    .gr-modal-foot { display:flex; justify-content:space-between; align-items:center; gap:.6rem; padding:1rem 1.4rem; border-top:1px solid #eef0f3; background:#f8fafc; }
    .gr-step-label { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#0a9396; margin-bottom:.6rem; }
    .gr-presets { display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:1.1rem; }
    .gr-preset { padding:.4rem 1rem; border:1.5px solid #dee2e6; background:#fff; border-radius:20px; font-size:.8rem; font-weight:600; cursor:pointer; color:#495057; }
    .gr-preset.active { border-color:#005f73; background:#005f73; color:#fff; }
    .gr-daterange { display:flex; gap:1rem; flex-wrap:wrap; }
    .gr-daterange > div { flex:1 1 140px; }
    .gr-daterange label { font-size:.8rem; font-weight:600; color:#495057; display:block; margin-bottom:.3rem; }
    .gr-daterange input { padding:.5rem .7rem; border:1.5px solid #dee2e6; border-radius:8px; font-size:.9rem; width:100%; color:#212529; }
    .gr-daterange input:focus { border-color:#005f73; outline:none; }
    .gr-range-error { color:#b91c1c; font-size:.78rem; margin-top:.6rem; display:none; }
    .gr-typecards { display:flex; gap:1rem; flex-wrap:wrap; }
    .gr-typecard { flex:1 1 160px; border:2px solid #dee2e6; border-radius:12px; padding:1.1rem .75rem; text-align:center; cursor:pointer; position:relative; transition:all .15s; display:block; }
    .gr-typecard input { position:absolute; top:.65rem; right:.65rem; width:17px; height:17px; cursor:pointer; }
    .gr-typecard-icon { font-size:1.7rem; display:block; margin-bottom:.45rem; }
    .gr-typecard-label { font-size:.85rem; font-weight:700; color:#33394a; display:block; }
    .gr-typecard-sub { font-size:.72rem; color:#8a8f98; margin-top:.15rem; display:block; }
    .gr-typecard.selected { border-color:#005f73; background:#f0f9fa; }
    .gr-hint { font-size:.78rem; color:#8a8f98; margin-top:1rem; margin-bottom:0; }
    .gr-btn-secondary { padding:.55rem 1.1rem; background:#fff; border:1.5px solid #dee2e6; border-radius:8px; font-weight:600; font-size:.85rem; color:#495057; cursor:pointer; }
    .gr-btn-secondary:hover { border-color:#adb5bd; }
    .gr-btn-next { padding:.55rem 1.4rem; background:#005f73; color:#fff; border:none; border-radius:8px; font-weight:700; font-size:.85rem; cursor:pointer; }
    .gr-btn-next:hover { background:#0a9396; }
    .gr-btn-export { padding:.55rem 1.4rem; background:#217346; color:#fff; border:none; border-radius:8px; font-weight:700; font-size:.85rem; cursor:pointer; display:none; align-items:center; gap:.45rem; }
    .gr-btn-export:hover { background:#1a5c38; }
    .gr-btn-export svg { width:16px; height:16px; }

    /* ── Sidebar collapse (defensive duplicate of the fix in reports-offline.css,
       inlined here so it works even if that external file is stale/cached) ── */
    .o-sidebar.sidebar-collapsed { width: 70px !important; flex: 0 0 70px !important; }
    .o-sidebar.sidebar-collapsed .o-brand { padding: .75rem .5rem !important; }
    .o-sidebar.sidebar-collapsed .o-brand img { width: 40px !important; height: 40px !important; }
    .o-sidebar.sidebar-collapsed .o-brand h5,
    .o-sidebar.sidebar-collapsed .o-nav a span { display: none !important; }
    .o-sidebar.sidebar-collapsed .o-nav { padding: 0 .5rem !important; }
    .o-sidebar.sidebar-collapsed .o-nav a { justify-content: center !important; padding: .6rem !important; gap: 0 !important; }
    @media (max-width: 1024px) {
        .o-sidebar.sidebar-open { transform: translateX(0) !important; }
    }
    </style>
</head>
<body class="offline-body">
<div class="o-flex" id="appShell">
    <!-- Sidebar -->
    <nav id="sidebar" class="o-sidebar bg-<?php echo htmlspecialchars($settings['sidebar_theme']); ?> <?php echo $sidebarTextClass; ?>">
        <div class="o-brand">
            <img src="../<?php echo htmlspecialchars($settings['logo_path']); ?>" alt="Gym Logo">
            <h5><?php echo htmlspecialchars($gym_name); ?></h5>
        </div>
        <ul class="o-nav">
            <li><a href="dashboard.php"><?php echo icon('dashboard'); ?><span>Dashboard</span></a></li>
            <li><a href="members.php"><?php echo icon('users'); ?><span>Members</span></a></li>
            <li><a href="pos.php"><?php echo icon('cart'); ?><span>Point of Sale</span></a></li>
            <li><a href="attendance.php"><?php echo icon('calendar'); ?><span>Attendance</span></a></li>
            <li><a class="active" href="reports.php"><?php echo icon('chart'); ?><span>Reports</span></a></li>
            <li><a href="employees.php"><?php echo icon('employee'); ?><span>Employees</span></a></li>
            <li><a href="website_settings.php"><?php echo icon('globe'); ?><span>Website</span></a></li>
            <li><a href="settings.php"><?php echo icon('cog'); ?><span>Settings</span></a></li>
            <li style="margin-top:1.5rem;"><a href="../logout.php"><?php echo icon('logout'); ?><span>Logout</span></a></li>
        </ul>
    </nav>

    <div class="o-grow" id="mainContent">
        <div class="o-topbar">
            <button class="o-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <p class="o-title">Reports — <?php echo htmlspecialchars($user['fullname']); ?> (Admin)</p>
        </div>

        <div class="o-container">

            <!-- ══ Generate Report — top-side button only, flow via modal ══ -->
            <div class="gr-topbar">
                <button type="button" class="gr-btn-generate" id="openGenReportBtn">
                    <?php echo icon('file'); ?> Generate Report
                </button>
            </div>

            <!-- ══ Monthly Overview ══════════════════════════════════════ -->
            <h1 class="o-section-title">Monthly Overview</h1>

            <div class="o-row">
                <div class="o-col o-col-3"><div class="o-card o-stat"><div class="o-stat-label">Total Members</div><div class="o-stat-value"><?php echo (int)$total_members; ?></div><small style="color:#8a8f98;"><?php echo (int)$active_members; ?> active</small></div></div>
                <div class="o-col o-col-3"><div class="o-card o-stat"><div class="o-stat-label">Monthly Revenue</div><div class="o-stat-value"><?php echo peso($total_cur); ?></div><?php echo trendBadge($chg_total); ?></div></div>
                <div class="o-col o-col-3"><div class="o-card o-stat"><div class="o-stat-label">Monthly Check-ins</div><div class="o-stat-value"><?php echo (int)$total_attendance; ?></div><?php echo trendBadge($chg_attendance); ?></div></div>
                <div class="o-col o-col-3"><div class="o-card o-stat"><div class="o-stat-label">Items Sold</div><div class="o-stat-value"><?php echo (int)$monthly_pos_items; ?></div><small style="color:#8a8f98;"><?php echo (int)$low_stock_items; ?> low stock</small></div></div>
            </div>

            <div class="o-row">
                <div class="o-col o-col-6"><div class="o-card"><div class="o-card-header"><h6>Revenue by Product Category</h6></div><div class="o-card-body"><?php if($hasCatData): ?><div class="o-bars"><?php foreach($catRevenue as $key=>$val): $h=$catMax>0?max(3,round(($val/$catMax)*100)):3; ?><div class="o-bar-col"><div class="o-bar-val"><?php echo peso($val); ?></div><div class="o-bar" style="height:<?php echo $h; ?>%"></div><div class="o-bar-label"><?php echo $catLabels[$key]; ?></div></div><?php endforeach; ?></div><?php else: ?><div class="o-empty">No POS sales recorded this month yet.</div><?php endif; ?></div></div></div>
                <div class="o-col o-col-6"><div class="o-card"><div class="o-card-header"><h6>Revenue Split &mdash; Membership vs POS</h6></div><div class="o-card-body"><?php if($total_cur>0): ?><div class="o-donut-wrap"><div class="o-donut" style="background:<?php echo $donutGradient; ?>;"><div class="o-donut-center"><div class="o-dc-num"><?php echo peso($total_cur); ?></div><div class="o-dc-lbl">Total</div></div></div><div class="o-legend"><div class="o-legend-item"><span class="o-legend-dot" style="background:#3461ff;"></span>Membership <b><?php echo $membershipPct; ?>%</b></div><div class="o-legend-item"><span class="o-legend-dot" style="background:#ff6b00;"></span>POS Sales <b><?php echo $posPct; ?>%</b></div></div></div><?php else: ?><div class="o-empty">No revenue recorded this month yet.</div><?php endif; ?></div></div></div>
            </div>

            <div class="o-row">
                <div class="o-col o-col-6"><div class="o-card"><div class="o-card-header"><h6>Attendance Rate</h6></div><div class="o-card-body"><div class="o-gauge-row"><div class="o-gauge-block"><div class="o-gauge-title">Previous Month</div><div class="o-gauge" style="--gc:#1e2530;--gp:<?php echo $attendanceRatePrev; ?>;"><div class="o-gauge-val"><?php echo $attendanceRatePrev; ?>%</div></div></div><div class="o-gauge-block"><div class="o-gauge-title">Current Month</div><div class="o-gauge" style="--gc:#ff6b00;--gp:<?php echo $attendanceRateCur; ?>;"><div class="o-gauge-val"><?php echo $attendanceRateCur; ?>%</div></div></div></div></div></div></div>
                <div class="o-col o-col-6"><div class="o-card"><div class="o-card-header"><h6>Low Stock Items (&le; 10 pcs)</h6></div><div class="o-card-body"><?php if(count($lowStockList)>0): ?><div class="o-bars"><?php foreach($lowStockList as $li): $qty=(int)$li['stock_quantity']; $h=max(3,round(($qty/$lowStockMax)*100)); ?><div class="o-bar-col"><div class="o-bar-val"><?php echo $qty; ?></div><div class="o-bar" style="height:<?php echo $h; ?>%"></div><div class="o-bar-label"><?php echo htmlspecialchars($li['name']); ?></div></div><?php endforeach; ?></div><?php else: ?><div class="o-empty">No low-stock items right now.</div><?php endif; ?></div></div></div>
            </div>

            <!-- Generate Report Modal -->
            <div class="gr-modal-overlay" id="genReportOverlay">
                <div class="gr-modal">
                    <div class="gr-modal-head">
                        <h3 id="genModalTitle">Select Date Range</h3>
                        <button type="button" class="gr-modal-close" id="genModalClose" aria-label="Close">&times;</button>
                    </div>
                    <div class="gr-modal-body">

                        <!-- Step 1: Date range -->
                        <div class="gr-step" id="genStep1">
                            <div class="gr-step-label">Step 1 of 2</div>
                            <div class="gr-presets">
                                <button type="button" class="gr-preset" data-preset="today">Today</button>
                                <button type="button" class="gr-preset" data-preset="week">Last 7 Days</button>
                                <button type="button" class="gr-preset active" data-preset="month">This Month</button>
                                <button type="button" class="gr-preset" data-preset="lastmonth">Last Month</button>
                                <button type="button" class="gr-preset" data-preset="custom">Custom</button>
                            </div>
                            <div class="gr-daterange">
                                <div>
                                    <label>Date From</label>
                                    <input type="date" id="genDateFrom" value="<?php echo date('Y-m-01'); ?>">
                                </div>
                                <div>
                                    <label>Date To</label>
                                    <input type="date" id="genDateTo" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            <div class="gr-range-error" id="genRangeError">Please select a valid date range ("Date From" must not be later than "Date To").</div>
                        </div>

                        <!-- Step 2: Report type -->
                        <div class="gr-step" id="genStep2" style="display:none;">
                            <div class="gr-step-label">Step 2 of 2</div>
                            <div class="gr-typecards">
                                <label class="gr-typecard selected" id="genCardSales">
                                    <input type="checkbox" name="rtype" value="sales" checked>
                                    <span class="gr-typecard-icon">&#128722;</span>
                                    <span class="gr-typecard-label">POS Report</span>
                                    <span class="gr-typecard-sub">Sales &amp; transactions</span>
                                </label>
                                <label class="gr-typecard" id="genCardAttendance">
                                    <input type="checkbox" name="rtype" value="attendance">
                                    <span class="gr-typecard-icon">&#128197;</span>
                                    <span class="gr-typecard-label">Attendance Report</span>
                                    <span class="gr-typecard-sub">Member check-ins</span>
                                </label>
                            </div>
                            <p class="gr-hint">Select one, or both for a combined Excel file with separate sheets for each report.</p>
                        </div>

                    </div>
                    <div class="gr-modal-foot">
                        <button type="button" class="gr-btn-secondary" id="genBackBtn" style="visibility:hidden;">&larr; Back</button>
                        <div>
                            <button type="button" class="gr-btn-next" id="genNextBtn">Next &rarr;</button>
                            <button type="button" class="gr-btn-export" id="genDownloadBtn"><?php echo icon('download'); ?> Generate &amp; Download</button>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /o-container -->

        <div class="o-footer">Developed by Tyron Del Valle</div>
    </div>
</div>

<script src="../assets/sidebar.js"></script>
<script>
(function(){
    var overlay    = document.getElementById('genReportOverlay');
    var openBtn    = document.getElementById('openGenReportBtn');
    var closeBtn   = document.getElementById('genModalClose');
    var step1      = document.getElementById('genStep1');
    var step2      = document.getElementById('genStep2');
    var nextBtn    = document.getElementById('genNextBtn');
    var backBtn    = document.getElementById('genBackBtn');
    var downloadBtn= document.getElementById('genDownloadBtn');
    var title      = document.getElementById('genModalTitle');
    var dateFrom   = document.getElementById('genDateFrom');
    var dateTo     = document.getElementById('genDateTo');
    var rangeError = document.getElementById('genRangeError');
    var presets    = document.querySelectorAll('.gr-preset');
    var typeInputs = document.querySelectorAll('input[name="rtype"]');

    function pad(n){ return n < 10 ? '0'+n : ''+n; }
    function fmt(d){ return d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate()); }

    presets.forEach(function(btn){
        btn.addEventListener('click', function(){
            presets.forEach(function(b){ b.classList.remove('active'); });
            btn.classList.add('active');
            var today = new Date();
            var preset = btn.dataset.preset;
            var from, to;
            if (preset === 'today') { from = new Date(); to = new Date(); }
            else if (preset === 'week') { to = new Date(); from = new Date(); from.setDate(today.getDate() - 6); }
            else if (preset === 'month') { from = new Date(today.getFullYear(), today.getMonth(), 1); to = new Date(); }
            else if (preset === 'lastmonth') { from = new Date(today.getFullYear(), today.getMonth()-1, 1); to = new Date(today.getFullYear(), today.getMonth(), 0); }
            else { return; /* custom — leave inputs as-is for manual editing */ }
            dateFrom.value = fmt(from);
            dateTo.value   = fmt(to);
            rangeError.style.display = 'none';
        });
    });

    [dateFrom, dateTo].forEach(function(inp){
        inp.addEventListener('change', function(){
            presets.forEach(function(b){ b.classList.remove('active'); });
            document.querySelector('.gr-preset[data-preset="custom"]').classList.add('active');
            rangeError.style.display = 'none';
        });
    });

    typeInputs.forEach(function(cb){
        function sync(){ cb.closest('.gr-typecard').classList.toggle('selected', cb.checked); }
        cb.addEventListener('change', sync);
        sync();
    });

    function showStep(n){
        if (n === 1){
            step1.style.display = 'block'; step2.style.display = 'none';
            title.textContent = 'Select Date Range';
            backBtn.style.visibility = 'hidden';
            nextBtn.style.display = 'inline-block';
            downloadBtn.style.display = 'none';
        } else {
            step1.style.display = 'none'; step2.style.display = 'block';
            title.textContent = 'Choose Report Type';
            backBtn.style.visibility = 'visible';
            nextBtn.style.display = 'none';
            downloadBtn.style.display = 'inline-flex';
        }
    }

    function openModal(){
        overlay.classList.add('show');
        showStep(1);
    }
    function closeModal(){
        overlay.classList.remove('show');
    }

    openBtn.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', function(e){ if (e.target === overlay) closeModal(); });
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && overlay.classList.contains('show')) closeModal(); });

    nextBtn.addEventListener('click', function(){
        if (!dateFrom.value || !dateTo.value || dateFrom.value > dateTo.value){
            rangeError.style.display = 'block';
            return;
        }
        rangeError.style.display = 'none';
        showStep(2);
    });
    backBtn.addEventListener('click', function(){ showStep(1); });

    downloadBtn.addEventListener('click', function(){
        var checked = Array.prototype.slice.call(typeInputs).filter(function(c){ return c.checked; }).map(function(c){ return c.value; });
        if (checked.length === 0){
            alert('Please select at least one report type.');
            return;
        }
        var type = checked.length === 2 ? 'both' : checked[0];
        var url = 'reports.php?export=' + type + '&date_from=' + encodeURIComponent(dateFrom.value) + '&date_to=' + encodeURIComponent(dateTo.value);
        window.location.href = url;
        closeModal();
    });
})();
</script>
</body>
</html>
