<?php

require_once __DIR__ . "/config/connect.php";

$gestorConexion = new Conexion();

try {
    $conexion = $gestorConexion->conectar();
} catch (RuntimeException $e) {
    http_response_code(500);
    exit($e->getMessage());
}

?>