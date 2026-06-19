<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" href="desc.css">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Cherry+Bomb+One&family=Google+Sans+Flex:opsz,wght@6..144,1..1000&family=Modak&family=Roboto:ital,wght@0,100..900;1,100..900&family=Sour+Gummy:ital,wght@0,100..900;1,100..900&display=swap');
        </style>
        <script>
            function back(){
                window.open("book.php", "_self")
            }
        </script>
    </head>
    <body>
        <div id="bg-slider"></div>
        <img src="../../assets/back-arrow.png" class="back-btn"onclick="back()"></img>
        <div class="parent">
            <div class="child">
                <?php 
                    require 'db_connect.php';

                    $id = $_GET['id'] ?? 0;

                    $sql = "SELECT * FROM disp WHERE disp_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($row = $result->fetch_assoc()) {
                ?>
                        <h1><?= htmlspecialchars($row['disp_name']) ?></h1>

                        <div class="par1" style="display: flex">

                            <div class="col1" style="flex: 1; justify-content: center; align-items: center;">
                                <img src="getImg.php?id=<?= htmlspecialchars($row['disp_id']) ?>"
                                    alt="<?= htmlspecialchars($row['disp_name']) ?>"
                                    class="table-image">
                            </div>

                            <div class="col2" style="flex: 1">
                                <br><br>
                                <p><strong>Rental Type:</strong> <span><?= htmlspecialchars($row['disp_rentCat']) ?></span></p>
                                <br>
                                <p><strong>Price:</strong> <span><?= htmlspecialchars($row['disp_price']) ?></span></p>
                                <br>
                                <p><strong>Description:</strong><br><span><?= htmlspecialchars($row['disp_description']) ?></span></p>
                            </div>

                        </div>
                <?php
                    } else {
                        echo '<h1>No records found</h1>';
                    }
                ?>


            </div>
        </div>
        <script src="iskrip.js"></script>
    </body>
    </html>