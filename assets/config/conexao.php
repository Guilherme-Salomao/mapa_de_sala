<?php
$host = "localhost";
$user = "root";
$pass = "123456";
$db = "mapa_de_sala";

$conn = new mysqli($host, $user, $pass, $db);

// Verifica erro
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}
?>