<?php
include 'backend/databaseTemperature.php';

if (isset($_GET['sensor'])) {
    $clickedSensor = $_GET['sensor'];
    $query = "SELECT stamp, temp, humidity FROM temphumidity WHERE device_id = :device_id ORDER BY stamp DESC";
    $statement = $pdoTemp->prepare($query);
    $statement->bindParam(':device_id', $clickedSensor);
    $statement->execute();
    $result = $statement->fetchAll(PDO::FETCH_ASSOC);

    if ($result) {
        $filename = "sensor-" . $clickedSensor . "-" . date("Y-m-d") . ".csv";
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=' . $filename);

        $output = fopen('php://output', 'w');
        fputcsv($output, array('Zeitstempel', 'Temperatur (°C)', 'Luftfeuchtigkeit (%)'));

        foreach ($result as $row) {
            $trimmedStamp = substr($row['stamp'], 0, -3);
            fputcsv($output, array($trimmedStamp, $row['temp'], $row['humidity']));
        }
        
        fclose($output);
        exit();
    } else {
        echo "<p>Keine Daten für Sensor $clickedSensor gefunden.</p>";
    }
}
?>
