<?php
$faviconPath = "images/png/logo.png";
include 'backend/databaseTemperature.php';

$temperatures = [];
$device_ids = [];

$deviceQuery = "SELECT DISTINCT device_id FROM temphumidity";
$deviceStmt = $pdoTemp->prepare($deviceQuery);

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
    }
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
<body class="with-background">
    <div class="all">
        <img src="/images/png/FertigungNeu.png" style="position: absolute; top: 0;">
        <div class="container">
            <?php foreach ($temperatures as $device_id => $data): ?>
                <?php 
                $pos_x = isset($buttonPositions[$device_id]['pos_x']) ? $buttonPositions[$device_id]['pos_x'] : null;
                $pos_y = isset($buttonPositions[$device_id]['pos_y']) ? $buttonPositions[$device_id]['pos_y'] : null;
                if ($pos_x !== null && $pos_y !== null && $device_id !== null): ?>
                    <button onclick="showTemperatureDetails('<?php echo $device_id; ?>')" class="button <?php echo $data['colorClass']; ?>" style="position: absolute; left: <?php echo $pos_x - 52.5; ?>px; top: <?php echo $pos_y - 125; ?>px;">
                        <?php echo htmlspecialchars($data['temp']); ?>
                    </button>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <div id="detailsArea" style="position: absolute; right: 0; top: 10px; width: 620px; height: 100%; overflow-y: auto; display: none;"></div>
    </div>
    <a href="positionMain.php" class="count-button">Devices without position: <?php echo $missingPosCount; ?></a>

    <script>
    function showTemperatureDetails(deviceId) {
        var detailsArea = document.getElementById("detailsArea");
        if (detailsArea.style.display === 'block' && lastClickedDeviceId === deviceId) {
            detailsArea.style.display = 'none';
            lastClickedDeviceId = null;
        } else {
            lastClickedDeviceId = deviceId;
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "getTemperatureDetails.php?sensor=" + deviceId, true);
            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    detailsArea.innerHTML = xhr.responseText;
                    detailsArea.style.display = 'block';
                }
            };
            xhr.send();
        }
    }
    var lastClickedDeviceId = null;
    </script>
</body>
</html>
