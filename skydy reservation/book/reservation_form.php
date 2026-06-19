<?php
include 'db_connect.php';

$date     = isset($_GET['date'])     ? $_GET['date']     : '';
$space_id = isset($_GET['space_id']) ? $_GET['space_id'] : '';

$success  = '';
$error    = '';
$is_taken = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $first_name  = $conn->real_escape_string(trim($_POST['first_name']));
    $middle_name = $conn->real_escape_string(trim($_POST['middle_name']));
    $last_name   = $conn->real_escape_string(trim($_POST['last_name']));
    $suffix      = $conn->real_escape_string(trim($_POST['suffix']));
    $email       = $conn->real_escape_string(trim($_POST['email']));
    $phone       = $conn->real_escape_string(trim($_POST['phone']));
    $sex         = $conn->real_escape_string(trim($_POST['sex']));
    $res_date    = $conn->real_escape_string(trim($_POST['reservation_date']));
    $res_space   = $conn->real_escape_string(trim($_POST['space_id']));


    $sql_reservation = "
        INSERT INTO reservation (space_id, reservation_date, reservation_time, expected_timeout, reservation_status)
        VALUES ('$res_space', '$res_date', '10:00:00', '22:00:00', 'Pending')
    ";

    try {

        $conn->query($sql_reservation);
        $reservation_id = $conn->insert_id;

        $sql_customer = "
            INSERT INTO customer (reservation_id, first_name, middle_name, last_name, suffix, gender, email_address, phone_number)
            VALUES ('$reservation_id', '$first_name', '$middle_name', '$last_name', '$suffix', '$sex', '$email', '$phone')
        ";

        try {
            $conn->query($sql_customer);
            $success = "Reservation submitted successfully for " . htmlspecialchars($res_date) . "!";
        } catch (mysqli_sql_exception $e) {
            $conn->query("DELETE FROM reservation WHERE reservation_id = $reservation_id");
            $error = "Failed to save customer details. Please try again.";
        }

    } catch (mysqli_sql_exception $e) {

        if ($conn->errno === 1062) {
            $error = "⚠️ Aray! naunahan ka.";
            $is_taken = true;
        } else {
            $error = "Failed to save reservation. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Reservation Form</title>
<style>
body {
    font-family: Arial, sans-serif;
    max-width: 500px;
    margin: 40px auto;
    padding: 20px;
}

h2 {
    text-align: center;
}

.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 20px;
    text-align: center;
    font-weight: bold;
}

.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 20px;
    text-align: center;
    font-weight: bold;
}

.name-row {
    display: flex;
    gap: 10px;
}

.name-row > div {
    flex: 1;
}



</style>
<link rel="stylesheet" href="formstyle.css">
</head>
<body>

<div class="parent">
    <div class="child">
        <h2>Reservation Form</h2>

        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
            <p style="text-align:center;"><a href="../book.php">← Balik</a></p>

        <?php else: ?>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($is_taken): ?>
            <p style="text-align:center;"><a href="../book.php">← Choose another date or space</a></p>
        <?php else: ?>

        <div class="info-box">
            <strong>Selected Date:</strong> <?php echo htmlspecialchars($date); ?><br>
            <strong>Time:</strong> 10:00 AM – 10:00 PM
        </div>

        <form method="POST">
            <input type="hidden" name="reservation_date" value="<?php echo htmlspecialchars($date); ?>">
            <input type="hidden" name="space_id"          value="<?php echo htmlspecialchars($space_id); ?>">

            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="first_name" placeholder="Juan" required>
            </div>

            <div class="form-group">
                <label>Middle Name</label>
                <input type="text" name="middle_name" placeholder="Santos (optional)">
            </div>

            <div class="form-group name-row">
                <div>
                    <label>Last Name</label>
                    <input type="text" name="last_name" placeholder="Dela Cruz" required>
                </div>
                <div>
                    <label>Suffix</label>
                    <select name="suffix">
                        <option value="">— None —</option>
                        <option value="Jr.">Jr.</option>
                        <option value="Sr.">Sr.</option>
                        <option value="II">II</option>
                        <option value="III">III</option>
                        <option value="IV">IV</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Gender</label>
                <select name="sex" required>
                    <option value="">— Select —</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="juan@email.com" required>
            </div>

            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="phone" placeholder="09XXXXXXXXX" pattern="09[0-9]{9}" title="Enter a valid PH mobile number" required>
            </div>

            <button type="submit">Submit Reservation</button>
        </form>

        <?php endif; ?>

        <?php endif; ?>

</body>
</html>