<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>set Position</title>
    <link rel="stylesheet" href="assets/styleTemp.css">
    <link rel="icon" href="<?php echo $faviconPath; ?>" type="image/x-icon">
</head>
<body class="with-background" onclick="showClickPosition(event)">
<img src="/images/png/FertigungNeu.png" style="position : relative;">
    <a href="positionMain.php" class="back-button">back</a>

    <?php
    $faviconPath = "images/png/logo.png";
    session_start();
    if (isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'] === true) {
    include 'backend/databaseTemperature.php';

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dId'])) {
        $deviceId = $_POST['dId'];
        $posX = isset($_POST['pos_x']) ? $_POST['pos_x'] : null;
        $posY = isset($_POST['pos_y']) ? $_POST['pos_y'] : null;

        if ($posX !== null && $posY !== null) {
            $query = "UPDATE buttonposition SET pos_x = :pos_x, pos_y = :pos_y WHERE device_ID = :device_ID";
            $stmt = $pdoTemp->prepare($query);
            $stmt->bindParam(':pos_x', $posX, PDO::PARAM_INT);
            $stmt->bindParam(':pos_y', $posY, PDO::PARAM_INT);
            $stmt->bindParam(':device_ID', $deviceId, PDO::PARAM_STR);

            if ($stmt->execute()) {
                echo "<script>window.location.href = 'mainTemp.php';</script>";
            } 
            } 
        }
    } else {
        header("Location: login.php");
        exit;
    }
    ?>

    <form id="positionForm" method="POST">
        <input type="hidden" id="dId" name="dId" value="<?php echo htmlspecialchars($_POST['dId']); ?>">
        <input type="hidden" id="pos_x" name="pos_x">
        <input type="hidden" id="pos_y" name="pos_y">
    </form>

    <script>
        function showClickPosition(event) {
            const x = event.clientX;
            const y = event.clientY;

            document.getElementById('pos_x').value = x;
            document.getElementById('pos_y').value = y;

            document.getElementById('positionForm').submit();
        }
    </script>
    </img>
</body>
</html>
