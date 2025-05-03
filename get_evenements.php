<?php
header("Content-Type: application/json");

$host = 'localhost';
$dbname = 'pge';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT * FROM evenements ORDER BY date_event DESC";
    $evenements = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($evenements);
} catch (PDOException $e) {
    echo json_encode(["error" => "Erreur de connexion : " . $e->getMessage()]);
}
?>
