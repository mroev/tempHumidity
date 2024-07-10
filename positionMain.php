<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Button Positions</title>
    <link rel="stylesheet" href="assets/styleTemp.css">
    <link rel="icon" href="<?php echo $faviconPath; ?>" type="image/x-icon">
</head>
<a href="mainTemp.php" class="back-button">back</a>
<body>
<?php
$faviconPath = "images/png/logo.png";
session_start();
if (isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'] === true) {
include 'backend/databaseTemperature.php';

$query = "SELECT * FROM buttonposition";
$result = $pdoTemp->query($query);

if ($result->rowCount() > 0) {
    echo "<table class='sensor-table'>";
    echo "<tr><th>MAC</th><th>pos_x</th><th>pos_y</th><th>Set Position</th><th>Reset Position</th></tr>";

    while ($row = $result->fetch()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['device_ID']) . "</td>";
        echo "<td>" . htmlspecialchars($row['pos_x']) . "</td>";
        echo "<td>" . htmlspecialchars($row['pos_y']) . "</td>";

        if (empty($row['pos_x']) || empty($row['pos_y'])) {
            echo "<td>";
            echo "<form method='POST' action='positionValues.php'>";
            echo "<input type='hidden' name='dId' value='" . htmlspecialchars($row['device_ID']) . "'>";
            echo "<button type='submit' class='otherButton' name='set_position'>Set</button>";
            echo "</form>";
            echo "</td>";
            echo "<td></td>";
        } else {
            echo "<td></td>";
            echo "<td>";
            echo "<form method='POST' action='resetPosition.php'>";
            echo "<input type='hidden' name='dId' value='" . htmlspecialchars($row['device_ID']) . "'>";
            echo "<button type='submit' class='otherButton' name='reset_position'>Reset</button>";
            echo "</form>";
            echo "</td>";
        }

        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Keine Daten gefunden.";
}

$pdoTemp = null;
} else {
    header("Location: login.php");
    exit;
}
?>
</body>
</html>
