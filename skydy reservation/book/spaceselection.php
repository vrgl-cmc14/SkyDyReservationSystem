<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Select a Space</title>
    <link rel="stylesheet" href="book css/selection.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cherry+Bomb+One&family=Google+Sans+Flex:opsz,wght@6..144,1..1000&family=Modak&family=Roboto:ital,wght@0,100..900;1,100..900&family=Sour+Gummy:ital,wght@0,100..900;1,100..900&display=swap');
    </style>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
        function back(){
            window.open("../book.php", "_self")
        }
    </script>
</head>
<body>

<div id="bg-slider"></div>
<img src="../../assets/back-arrow.png" class="back-btn" width="7%" onclick="back()"</img>
<div class="parent">
    <div class="child">
        <?php
            $conn1 = new mysqli('localhost', 'root', 'haha', 'display');

            if ($conn1->connect_error) {
                die('Connection failed (DB1): ' . $conn1->connect_error);
            }

            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                die('Invalid request. No item specified.');
            }

            $disp_id = (int) $_GET['id'];

            $stmt = $conn1->prepare("SELECT disp_name FROM disp WHERE disp_id = ?");
            $stmt->bind_param("i", $disp_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $disp = $result->fetch_assoc();
            $stmt->close();
            $conn1->close();

            if (!$disp) {
                die('Item not found.');
            }

            $disp_name = $disp['disp_name'];

            require 'db_connect.php';

            $escaped = str_replace(['%', '_'], ['\%', '\_'], $disp_name);
            $search = $escaped . '%';

            $stmt2 = $conn->prepare("SELECT * FROM space WHERE space_name LIKE ?");
            $stmt2->bind_param("s", $search);
            $stmt2->execute();
            $spacesResult = $stmt2->get_result();
            $spaces = $spacesResult->fetch_all(MYSQLI_ASSOC);
            $stmt2->close();
            $conn->close();
        ?>
        <h1>AVAILABLE SPACES FOR <span><?php echo htmlspecialchars($disp_name); ?></span></h1>
        <img src="../../assets/FLOOR_PLAN.png"> 

        <?php if (count($spaces) > 0): ?>

            <form action="calendar.php" method="GET">

                <label for="space_id">Choose a Spot: </label>
                <br>

                <div class="dropdown">
                    <select name="space_id" id="space_id" required>
                        <option value="" disabled selected>Select a Seat</option>
                        <?php foreach ($spaces as $space): ?>
                            <option value="<?php echo htmlspecialchars($space['space_id']); ?>">
                                <?php echo htmlspecialchars($space['space_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="button">
                    <button type="submit">Process Booking</button>
                </div>

            </form>

        <?php else: ?>
            <p>No matching spaces found for "<?php echo htmlspecialchars($disp_name); ?>".</p>
            
        <?php endif; ?>
    </div>
</div>

<script src="iskrip.js"></script>
</body>
</html>