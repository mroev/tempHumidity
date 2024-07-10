<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Temp Values</title>
    <link rel="stylesheet" href="assets/styleTemp.css">
    <link rel="icon" href="images/png/logo.png" type="image/x-icon">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function exportToCSV(sensorId) {
            window.location.href = 'export.php?sensor=' + sensorId;
        }
    </script>
</head>
<body class="no-background">
    <div class="container">
        <?php
        include 'backend/databaseTemperature.php';
        if (isset($_GET['sensor'])) {
            $clickedSensor = $_GET['sensor'];
            $today = date('Y-m-d');
            $startOfWeek = date('Y-m-d', strtotime('monday this week', strtotime($today)));

            $query = "SELECT stamp, temp, humidity FROM temphumidity WHERE device_id = :device_id AND stamp >= :start_of_week ORDER BY stamp DESC";
            $statement = $pdoTemp->prepare($query);
            $statement->bindParam(':device_id', $clickedSensor);
            $statement->bindParam(':start_of_week', $startOfWeek);
            $statement->execute();
            $result = $statement->fetchAll(PDO::FETCH_ASSOC);

            if ($result) {
                echo "<h2 class=\"sensor-title\">Sensor $clickedSensor</h2>";
                echo "<canvas id='tempChart'></canvas>";
                echo "<table class=\"sensor-table\">";
                echo "<tr><th>Zeitstempel</th><th>Temperatur (°C)</th><th>Luftfeuchtigkeit (%)</th></tr>";

                $timestamps = [];
                $temps = [];
                $humidities = [];

                foreach ($result as $row) {
                    $trimmedStamp = substr($row['stamp'], 0, -3);
                    echo "<tr>";
                    echo "<td>" . $trimmedStamp . "</td>";
                    echo "<td>" . $row['temp'] . "</td>";
                    echo "<td>" . $row['humidity'] . "</td>";
                    echo "</tr>";

                    // For the chart
                    $timestamps[] = $trimmedStamp;
                    $temps[] = $row['temp'];
                    $humidities[] = $row['humidity'];
                }
                
                echo "</table>";
                echo '<a href="export.php?sensor=' . $clickedSensor . '" class="exportButton">Export as CSV</a>';
            } else {
                echo "<p>Keine Daten für Sensor $clickedSensor gefunden.</p>";
            }
        }
        ?>
    </div>
    <a href="mainTemp.php" class="back-button">back</a>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('tempChart');
    canvas.width = 800;
    canvas.height = 400;
    const ctx = canvas.getContext('2d');
    const tempChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_reverse($timestamps)); ?>,
            datasets: [{
                label: 'Temperatur (°C)',
                data: <?php echo json_encode(array_reverse($temps)); ?>,
                borderColor: 'red',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                borderWidth: 1
            }, {
                label: 'Luftfeuchtigkeit (%)',
                data: <?php echo json_encode(array_reverse($humidities)); ?>,
                borderColor: 'blue',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    canvas.addEventListener('click', function() {
        if (!document.fullscreenElement) {
            if (canvas.requestFullscreen) {
                canvas.requestFullscreen();
            } else if (canvas.mozRequestFullScreen) { /* Firefox */
                canvas.mozRequestFullScreen();
            } else if (canvas.webkitRequestFullscreen) { /* Chrome, Safari and Opera */
                canvas.webkitRequestFullscreen();
            } else if (canvas.msRequestFullscreen) { /* IE/Edge */
                canvas.msRequestFullscreen();
            }
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.mozCancelFullScreen) { /* Firefox */
                document.mozCancelFullScreen();
            } else if (document.webkitExitFullscreen) { /* Chrome, Safari and Opera */
                document.webkitExitFullscreen();
            } else if (document.msExitFullscreen) { /* IE/Edge */
                document.msExitFullscreen();
            }
        }
    });
});
</script>
</body>
</html>
