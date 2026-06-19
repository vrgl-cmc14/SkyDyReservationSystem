<?php
include 'db_connect.php';

header('Content-Type: application/json'); 

$space_id = 'space_id';
$month = $_GET['month'] ?? date('Y-m');

$start_date = $month . "-01";
$end_date = date("Y-m-t", strtotime($start_date));

$availability = [];

$sql = "
    SELECT reservation_date, status
    FROM reservation
    WHERE space_id = ?
    AND reservation_date BETWEEN ? AND ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $space_id, $start_date, $end_date);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {

    if ($row['status'] === 'confirmed') {
        $availability[$row['reservation_date']] = 'occupied';
    }
    elseif ($row['status'] === 'pending') {
        $availability[$row['reservation_date']] = 'pending';
    }
    elseif ($row['status'] === 'cancelled') {
        $availability[$row['reservation_date']] = 'available';
    }
}

echo json_encode($availability);
exit;
?>