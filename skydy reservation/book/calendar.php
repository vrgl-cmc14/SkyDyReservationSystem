<?php
include 'db_connect.php';

$space_id = $_GET['space_id'];

if (!is_numeric($space_id)) {
    die('Invalid space.');
}

$space_id = (int) $space_id;

$stmt = $conn->prepare("SELECT * FROM room WHERE space_id = ?");
$stmt->bind_param("i", $space_id);
$stmt->execute();
$roomResult = $stmt->get_result();
$isRoom = $roomResult->num_rows > 0;
$stmt->close();

$availability = [];

if ($isRoom) {
    $stmt = $conn->prepare("
        SELECT reservation_date, reservation_time, expected_timeout, reservation_status
        FROM reservation
        WHERE space_id = ?
          AND reservation_status IN ('confirmed', 'pending')
    ");
    $stmt->bind_param("i", $space_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $hoursByDate = [];

    while ($row = $result->fetch_assoc()) {
        $date = $row['reservation_date'];
        $status = strtolower($row['reservation_status']);
        $startHour = (int) substr($row['reservation_time'], 0, 2);
        $endHour = (int) substr($row['expected_timeout'], 0, 2);

        if (!isset($hoursByDate[$date])) {
            $hoursByDate[$date] = [];
        }

        for ($h = $startHour; $h < $endHour; $h++) {
            if ($h >= 7 && $h <= 22) {
                if ($status === 'confirmed' || !isset($hoursByDate[$date][$h])) {
                    $hoursByDate[$date][$h] = $status;
                }
            }
        }
    }
    $stmt->close();

    $totalHours = 16;

    foreach ($hoursByDate as $date => $hours) {
        $confirmedCount = 0;
        $bookedCount = 0;

        foreach ($hours as $status) {
            $bookedCount++;
            if ($status === 'confirmed') {
                $confirmedCount++;
            }
        }

        if ($confirmedCount >= $totalHours) {
            $availability[$date] = 'occupied';
        } elseif ($bookedCount >= $totalHours) {
            $availability[$date] = 'pending';
        } else {
            $availability[$date] = 'available';
        }
    }

} else {

    $stmt = $conn->prepare("
        SELECT reservation_date, reservation_status
        FROM reservation
        WHERE space_id = ?
    ");
    $stmt->bind_param("i", $space_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $status = strtolower($row['reservation_status']);

        if ($status === 'confirmed') {
            $availability[$row['reservation_date']] = 'occupied';
        } elseif ($status === 'pending') {
            $availability[$row['reservation_date']] = 'pending';
        } elseif ($status === 'cancelled') {
            $availability[$row['reservation_date']] = 'available';
        }
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Calendar</title>
    <link rel="stylesheet" href="book css/calendar.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cherry+Bomb+One&family=Google+Sans+Flex:opsz,wght@6..144,1..1000&family=Modak&family=Roboto:ital,wght@0,100..900;1,100..900&family=Sour+Gummy:ital,wght@0,100..900;1,100..900&display=swap');
    </style>
    <script>
        function back(){
            window.open("../book.php", "_self")
        }
    </script>
</head>
<body>
<div id="bg-slider"></div>
<img src="../../assets/back-arrow.png" class="back-btn" width="7%" onclick="back()"></img>
<div class="parent">
    <div class="child">
        <div class="container">
            <div class="con">
                <div class="controls">
                    <button onclick="prevMonth()"> < </button>
                    <span id="monthLabel"></span>
                    <button onclick="nextMonth()"> > </button>
                </div>
            </div>
            <br>
            <div class="con">
                <div class="legend">
                    <span style="border-radius: 10px; background: darkblue; color:white;">Available</span>
                    <span style="border-radius: 10px; background: darkorange; color:white;">Pending</span>
                    <span style="border-radius: 10px; background: darkred; color:white;">Occupied</span>
                    <span style="border-radius: 10px;background: grey; color:white;">Past</span>
                </div>
            </div>
            <br>
            <div class="con">
                <div id="calendar"></div>
        </div>
        </div>
    </div>
</div>

<script>
let availability = <?php echo json_encode($availability); ?>;
let spaceId = <?php echo json_encode($space_id); ?>;

let currentYear = new Date().getFullYear();
let currentMonth = new Date().getMonth();

function generateCalendar(year, month) {
    const calendar = document.getElementById("calendar");
    calendar.innerHTML = "";

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const daysInMonth = new Date(year, month + 1, 0).getDate();

    const monthLabel = new Date(year, month).toLocaleString('default', {
        month: 'long',
        year: 'numeric'
    });

    document.getElementById("monthLabel").textContent = monthLabel;

    const dayNames = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
    dayNames.forEach(name => {
        const label = document.createElement("div");
        label.className = "day-label";
        label.textContent = name;
        calendar.appendChild(label);
    });

    const firstDay = new Date(year, month, 1).getDay();
    for (let i = 0; i < firstDay; i++) {
        const blank = document.createElement("div");
        calendar.appendChild(blank);
    }

    for (let day = 1; day <= daysInMonth; day++) {
        const dateStr = `${year}-${String(month+1).padStart(2,"0")}-${String(day).padStart(2,"0")}`;
        const status = availability[dateStr] || "available";
        const thisDate = new Date(year, month, day);

        const dayElem = document.createElement("div");

        if (thisDate < today) {
            dayElem.className = "day past";
        } else {
            dayElem.className = "day " + status;
            if (status === "available") {
                dayElem.addEventListener("click", () => {
                    window.location.href = `bookingform.php?date=${dateStr}&space_id=${spaceId}`;
                });
            }
        }

        dayElem.textContent = day;
        calendar.appendChild(dayElem);
    }
}

function prevMonth() {
    const now = new Date();
    if (currentYear === now.getFullYear() && currentMonth === now.getMonth()) {
        return;
    }
    currentMonth--;
    if (currentMonth < 0) {
        currentMonth = 11;
        currentYear--;
    }
    generateCalendar(currentYear, currentMonth);
}

function nextMonth() {
    currentMonth++;
    if (currentMonth > 11) {
        currentMonth = 0;
        currentYear++;
    }
    generateCalendar(currentYear, currentMonth);
}

generateCalendar(currentYear, currentMonth);
</script>
<script src="iskrip.js"></script>

</body>
</html> 