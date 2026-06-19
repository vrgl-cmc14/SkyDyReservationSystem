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
    </style>
    <script async type='module' src='https://interfaces.zapier.com/assets/web-components/zapier-interfaces/zapier-interfaces.esm.js'></script>
    <zapier-interfaces-chatbot-embed is-popup='true' chatbot-id='cmq0bgna10010rz0p9eiwzg1q'></zapier-interfaces-chatbot-embed>
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

        <main class="main-content">
            <?php
                require 'db_connect.php';

                $sql = "SELECT * FROM disp";
                $result = mysqli_query($conn, $sql);
                $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

                for ($i = 0; $i < count($rows); $i += 2) {
                    echo '<div class="lagayan">';

                    for ($j = $i; $j < min($i + 2, count($rows)); $j++) {
                        $row = $rows[$j];
                        echo '
                            <div class="record-card">
                                <div class="col1">
                                    <img src="getImg.php?id=' . htmlspecialchars($row['disp_id']) . '"
                                        alt="' . htmlspecialchars($row['disp_name']) . '"
                                        class="table-image" loading="lazy">
                                </div>
                                <div class="col2">
                                    <div class="row1">
                                        <p>' . strtoupper(htmlspecialchars($row['disp_name'])) . '</p>
                                        <p>' . htmlspecialchars($row['disp_price']) . ' / ' . strtoupper(htmlspecialchars($row['disp_rentCat'])) . '</p>
                                    </div>
                                    <div class="row2">
                                        <button class="btn-book" onclick="location.href=\'book/spaceselection.php?id=' . htmlspecialchars($row['disp_id']) . '\'">Book Now</button>
                                        <button class="btn-read" onclick="location.href=\'spacedescription.php?id=' . htmlspecialchars($row['disp_id']) . '\'">Read More</button>
                                    </div>
                                </div>
                            </div>';
                    }

                    echo '</div>';
                }
            ?>
        </main>
    </div>

    <script src="iskrip.js"></script>
</body>
</html>