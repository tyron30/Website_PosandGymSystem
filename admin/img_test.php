<?php
// Place this in: PosandGymSystem/admin/img_test.php
// Visit: http://localhost/PosandGymSystem_updated/PosandGymSystem/admin/img_test.php

$upload_dir_disk = __DIR__ . '/../uploads/products/';
$files = glob($upload_dir_disk . '*');

echo "<h2>Image Path Diagnostic</h2>";
echo "<p><b>SCRIPT_NAME:</b> " . $_SERVER['SCRIPT_NAME'] . "</p>";

$imgBase = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
echo "<p><b>imgBase (URL):</b> $imgBase</p>";
echo "<p><b>Upload folder (disk):</b> $upload_dir_disk</p>";
echo "<p><b>Files found:</b> " . count($files) . "</p>";
echo "<hr>";

foreach (array_slice($files, 0, 5) as $f) {
    $fname   = basename($f);
    $imgUrl  = $imgBase . '/uploads/products/' . $fname;
    $relUrl  = '../uploads/products/' . $fname;
    echo "<div style='margin:10px 0; padding:10px; border:1px solid #ccc'>";
    echo "<b>$fname</b><br>";
    echo "Absolute URL: <code>$imgUrl</code><br>";
    echo "Relative URL: <code>$relUrl</code><br>";
    echo "Disk exists: " . (file_exists($f) ? '<span style="color:green">YES</span>' : '<span style="color:red">NO</span>') . "<br>";
    echo "<img src='$imgUrl' style='height:80px;border:1px solid blue' onerror=\"this.style.border='2px solid red';this.alt='BROKEN'\"> ";
    echo "<img src='$relUrl' style='height:80px;border:1px solid green' onerror=\"this.style.border='2px solid red';this.alt='BROKEN'\"> ";
    echo "<br><small>blue=absolute &nbsp; green=relative</small>";
    echo "</div>";
}
?>
