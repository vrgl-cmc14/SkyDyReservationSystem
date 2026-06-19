<?php
require 'db_connect.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$sql = "SELECT disp_image FROM disp WHERE disp_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

if ($row && $row['disp_image']) {
    header("Content-Type: image/jpeg"); 
    echo $row['disp_image'];
} else {
    header("Content-Type: image/png");
    readfile("images/temp-table.png");
}
?>