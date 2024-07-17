<?php
session_start();
if (isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'] === true) {
include 'backend/databaseTemperature.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dId'])) {
    $deviceId = $_POST['dId'];

    $query = "UPDATE buttonposition SET pos_x = NULL, pos_y = NULL WHERE device_ID = :device_ID";
    $stmt = $pdoTemp->prepare($query);
    $stmt->bindParam(':device_ID', $deviceId, PDO::PARAM_STR);

    if ($stmt->execute()) {
        echo "<script>window.location.href = 'positionMain.php';</script>";
    } else {
        echo "Error resetting position.";
    }
}

$pdoTemp = null;
} else {
    header("Location: login.php");
    exit;
}
?>
