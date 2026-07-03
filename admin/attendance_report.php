<?php
// Attendance reports are now in Reports page
$qs = http_build_query(array_merge(['rtype' => 'attendance'], $_GET));
header("Location: reports.php?" . $qs);
exit();
