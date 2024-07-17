<?php
include 'backend/databaseTemperature.php';

if (isset($_GET['sensor'])) {
    $device_id = $_GET['sensor'];
    $query = "SELECT stamp, temp, humidity FROM temphumidity WHERE device_id = :device_id ORDER BY stamp DESC";
    $stmt = $pdoTemp->prepare($query);
    $stmt->bindParam(':device_id', $device_id);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $timestamps = [];
$temps = [];
$humidities = [];
foreach ($result as $row) {
    $timestamps[] = $row['stamp'];
    $temps[] = $row['temp'];
    $humidities[] = $row['humidity'];
}
    ?>
    <h2 class="sensor-title">Sensor <?php echo htmlspecialchars($device_id); ?></h2>
    <canvas id="tempChart" width="600" height="400"></canvas>
    <table class="sensor-table">
        <tr><th>Zeitstempel</th><th>Temperatur (°C)</th><th>Luftfeuchtigkeit (%)</th></tr>
        <?php foreach ($result as $row): ?>
            <tr>
                <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($row['stamp']))); ?></td>
                <td><?php echo htmlspecialchars($row['temp']); ?></td>
                <td><?php echo htmlspecialchars($row['humidity']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <a href="mainTemp.php" class="back-button">back</a>
    <?php
}
?>

<script src="backend/chart.js"></script>
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
</script>
