<?php
$dbServer = "beispiel_IP";
$db_user = "beispiel_user";
$db_pass = "beispiel_pass";
$dbName = "temphumidity";
$charset = "utf8mb4";

$dsn = "mysql:host=$dbServer;dbname=$dbName;charset=$charset";
$opt = [
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
];
try {
    $pdoTemp = new PDO($dsn, $db_user, $db_pass, $opt);
    unset($dbServer, $db_user, $db_pass, $dbName, $charset);

    $sql = "DELETE FROM temphumidity WHERE stamp < NOW() - INTERVAL 1 MONTH";
    $stmt = $pdoTemp->prepare($sql);
    $stmt->execute();
} catch (PDOException $e) {
    die("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
}
?>
