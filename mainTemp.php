<?php
$faviconPath = "images/png/logo.png";
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
        }
    }
}

$temperatures = [];

$deviceQuery = "SELECT DISTINCT device_id FROM temphumidity";
$deviceStmt = $pdoTemp->prepare($deviceQuery);
$device_ids = [];

if ($deviceStmt->execute()) {
    while ($row = $deviceStmt->fetch(PDO::FETCH_ASSOC)) {
        $device_ids[] = $row['device_id'];
    }
}

$missingPosQuery = "SELECT COUNT(*) as count FROM buttonposition WHERE pos_x IS NULL OR pos_y IS NULL";
$missingPosStmt = $pdoTemp->prepare($missingPosQuery);
$missingPosCount = 0;

if ($missingPosStmt->execute()) {
    $row = $missingPosStmt->fetch(PDO::FETCH_ASSOC);
    $missingPosCount = $row['count'];
}


foreach ($device_ids as $device_id) {
    $query = "SELECT temp FROM temphumidity WHERE device_id = :device_id ORDER BY stamp DESC LIMIT 1";
    $statement = $pdoTemp->prepare($query);
    $statement->bindParam(':device_id', $device_id);
    if ($statement->execute()) {
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        $temp = $result ? $result['temp'] : 'N/A';

        if ($temp >= 25) {
            $colorClass = 'buttonRed';
        } elseif ($temp >= 16) {
            $colorClass = 'buttonGreen';
        } else {
            $colorClass = 'buttonBlue';
        }

        $temperatures[$device_id] = ['temp' => $temp, 'colorClass' => $colorClass];
    } else {
        $temperatures[$device_id] = ['temp' => 'Error', 'colorClass' => 'buttonError'];
    }
    
    $insertQuery = "INSERT INTO buttonposition (device_id) VALUES (:device_id) ON DUPLICATE KEY UPDATE device_id = device_id";
    $insertStmt = $pdoTemp->prepare($insertQuery);
    $insertStmt->bindParam(':device_id', $device_id);
    $insertStmt->execute();
}

$buttonPositions = [];
$positionQuery = "SELECT device_ID, pos_x, pos_y FROM buttonposition";
$positionStmt = $pdoTemp->prepare($positionQuery);

if ($positionStmt->execute()) {
    while ($row = $positionStmt->fetch(PDO::FETCH_ASSOC)) {
        $buttonPositions[$row['device_ID']] = ['pos_x' => $row['pos_x'], 'pos_y' => $row['pos_y']];
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manufacturing Map</title>
    <link rel="stylesheet" href="assets/styleTemp.css">
    <link rel="icon" href="<?php echo $faviconPath; ?>" type="image/x-icon">
</head>
<body class="with-background" onclick="showClickPosition(event)">
    <div class="all">
    <img src="/images/png/FertigungNeu.png" style="position : absolute; top: 0;">

    <div class="container">
        <?php foreach ($temperatures as $device_id => $data): ?>
            <?php 
            $pos_x = isset($buttonPositions[$device_id]['pos_x']) ? $buttonPositions[$device_id]['pos_x'] : null;
            $pos_y = isset($buttonPositions[$device_id]['pos_y']) ? $buttonPositions[$device_id]['pos_y'] : null;
            if ($pos_x !== null && $pos_y !== null && $device_id !== null): ?>
                <form method="get" action="tempValues.php" class="sensor-form" style="position: absolute; left: <?php echo $pos_x - 52.5; ?>px; top: <?php echo $pos_y - 125; ?>px;">
                    <button class="button <?php echo $data['colorClass']; ?>" type="submit" name="sensor" value="<?php echo htmlspecialchars($device_id); ?>">
                        <?php echo htmlspecialchars($data['temp']); ?>
                    </button>
                </form>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <form id="positionForm" method="POST">
        <input type="hidden" id="dId" name="dId" value="">
        <input type="hidden" id="pos_x" name="pos_x">
        <input type="hidden" id="pos_y" name="pos_y">
    </form>

    <script>
        function showClickPosition(event) {
            const x = event.clientX;
            const y = event.clientY;

            // Set the values in the form
            document.getElementById('pos_x').value = x;
            document.getElementById('pos_y').value = y;

            if (deviceId) {
                document.getElementById('dId').value = deviceId;

                // Submit the form
                document.getElementById('positionForm').submit();
            }
        }
    </script>
        <a href="positionMain.php" class="count-button">Devices without position: <?php echo $missingPosCount; ?></a>
    </div>
</body>
</html>
