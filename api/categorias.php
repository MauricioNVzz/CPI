<?php

// =====================================================
// CONFIGURACIÓN DE LA API
// =====================================================

header("Content-Type: application/json; charset=UTF-8");


// =====================================================
// CONEXIÓN Y CONTROLADOR
// =====================================================

include "../conexion.php";

require_once "../controllers/CursoController.php";

$cursoController = new CursoController($conexion);


// =====================================================
// OBTENER CATEGORÍAS
// =====================================================

try {

    $categorias = $cursoController->obtenerCategorias();

    http_response_code(200);

    echo json_encode(
        $categorias,
        JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
    );

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        "error" => "Error interno del servidor."
    ], JSON_UNESCAPED_UNICODE);

}

?>