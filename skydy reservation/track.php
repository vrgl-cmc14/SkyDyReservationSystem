<?php
$conn = new mysqli('localhost', 'root', 'haha', 'skydyreserve');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$resultHTML = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id']) && $_POST['reservation_id'] !== '') {
    $id = intval($_POST['reservation_id']);

    $stmt = $conn->prepare("SELECT reservation_id, reservation_status FROM reservation WHERE reservation_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {

    $resultHTML = '';
    $reservation_status = $row['reservation_status'];

        if ($reservation_status == 'Confirmed'){
            $resultHTML = '<div style="display: flex; background-color: #a3f6a3" class="rescon">
                            <div style="flex: 1"><p class="trackk">Reservation ID: ' . htmlspecialchars($row['reservation_id']) . '</p>
                            </div>
                            <div style="flex: 1"><p class="trackk">Status: ' . htmlspecialchars($row['reservation_status']) . '</p>
                            </div>
                            </div>';

        } else if ($reservation_status == 'Pending'){
            $resultHTML = '<div style="display: flex; background-color: #f8c297" class="rescon">
                            <div style="flex: 1"><p class="trackk">Reservation ID: ' . htmlspecialchars($row['reservation_id']) . '</p>
                            </div>
                            <div style="flex: 1"><p class="trackk">Status: ' . htmlspecialchars($row['reservation_status']) . '</p>
                            </div>
                            </div>';
        } else {
            $resultHTML = '<div style="display: flex; background-color: #f89898" class="rescon">
                            <div style="flex: 1"><p class="trackk">Reservation ID: ' . htmlspecialchars($row['reservation_id']) . '</p>
                            </div>
                            <div style="flex: 1"><p class="trackk">Status: ' . htmlspecialchars($row['reservation_status']) . '</p>
                            </div>
                            </div>';
        }

    } else {
        $resultHTML = '<p>No reservation found with that ID.</p>';
    }

    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Skydy Study Hub</title>
    <link rel="stylesheet" href="shortver.css">
    <link rel="icon" type="image/png" href="images/logo.png">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cherry+Bomb+One&family=Google+Sans+Flex:opsz,wght@6..144,1..1000&family=Modak&family=Roboto:ital,wght@0,100..900;1,100..900&family=Sour+Gummy:ital,wght@0,100..900;1,100..900&display=swap');
        .container{
            display: flex;
            height: 100vh;
            backdrop-filter: blur(8px); 
            -webkit-backdrop-filter: blur(8px);
            background-color: rgba(15, 9, 9, 0.601);
        }
    </style>
</head>

<body>
    <div id="bg-slider"></div>
    <div id="loading-screen">
        <img src="images/logo.png" alt="Skydy Logo" class="loader-logo">
    </div>
    <script>
        setTimeout(() => {
            const loader = document.getElementById('loading-screen');
            if (loader) {
                loader.style.opacity = '0';
                setTimeout(() => loader.style.display = 'none', 600); 
            }
        }, 2000);
    </script>
    <div class="container">
        <nav class="sidebar collapsed">
            <div class="menu-icon">☰</div>
            <ul class="nav-links">
                <li>
                    <div class="curve-top"></div>
                    <a href="index.html">Home</a>
                    <div class="curve-bottom"></div>
                </li>
                <li>
                    <div class="curve-top"></div>
                    <a href="book.php">Spaces &amp; Rates</a>
                    <div class="curve-bottom"></div>
                </li>
                <li>
                    <div class="curve-top"></div>
                    <a href="contact.html">Contact Us</a>
                    <div class="curve-bottom"></div>
                </li>
                <li>
                    <div class="curve-top"></div>
                    <a href="track.php">Track</a>
                    <div class="curve-bottom"></div>
                </li>
            </ul>
            <div class="logo-container">
                <a href="index.html">
                    <img src="images/logo.png" alt="Skydy Logo" class="footer-logo">
                </a> 
            </div>
        </nav>

        <div class="trackcon">
            <div class="trackchild">
                <h1 class="headtrack">Track Your Reservation </h1>
                <br>
                <form method="POST" action="track.php">
                    <input type="number" placeholder="Enter your reservation ID" class="numin" name="reservation_id" value="<?php echo isset($_POST['reservation_id']) ? htmlspecialchars($_POST['reservation_id']) : ''; ?>" required>
                    <input type="submit" value="SUBMIT" class="sub">
                </form>
                <div class="result">
                    <?php echo $resultHTML; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="iskrip.js"></script>
</body>
</html>