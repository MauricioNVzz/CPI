<?php

session_start();

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/../conexion.php";
require_once __DIR__ . "/../controllers/PayPalController.php";
require_once __DIR__ . "/../controllers/VentaController.php";

$paypalController = new PayPalController();
$ventaController = new VentaController($conexion);

$accion = $_GET["accion"] ?? "";

try {

    // Ambas acciones requieren una sesión iniciada.
    if (in_array($accion, ["crear", "capturar"], true)) {
        if (!isset($_SESSION["usuario_id"])) {
            http_response_code(401);

            echo json_encode([
                "error" => true,
                "mensaje" => "Debes iniciar sesión para realizar una compra."
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }
    }

    // CREAR ORDEN DE PAYPAL
    if ($accion === "crear") {

        $entrada = json_decode(
            file_get_contents("php://input"),
            true
        );

        if (!is_array($entrada)) {
            http_response_code(400);

            echo json_encode([
                "error" => true,
                "mensaje" => "No se recibieron datos válidos."
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $cursoId = $entrada["curso_id"] ?? null;

        if (
            !filter_var($cursoId, FILTER_VALIDATE_INT) ||
            (int) $cursoId <= 0
        ) {
            http_response_code(400);

            echo json_encode([
                "error" => true,
                "mensaje" => "El ID del curso no es válido."
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $cursoId = (int) $cursoId;
        $usuarioId = (int) $_SESSION["usuario_id"];

        // Comprobar que el curso no haya sido comprado.
        if ($ventaController->comprobarCompra($usuarioId, $cursoId)) {
            http_response_code(409);

            echo json_encode([
                "error" => true,
                "mensaje" => "Ya has comprado este curso."
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        // Obtener el precio real desde la base de datos.
        $sql = "
    SELECT Curso_id, Curso_nombre, Curso_precio, Curso_estado
    FROM Cursos
    WHERE Curso_id = ?
";

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                "Error al preparar la consulta del curso."
            );
        }

        $stmt->bind_param("i", $cursoId);
        $stmt->execute();

        $resultado = $stmt->get_result();
        $curso = $resultado->fetch_assoc();

        $stmt->close();

        if (!$curso) {
            http_response_code(404);

            echo json_encode([
                "error" => true,
                "mensaje" => "El curso no existe."
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        if ((int) $curso["Curso_estado"] !== 1) {
    http_response_code(409);

    echo json_encode([
        "error" => true,
        "mensaje" => "Este curso ya no está disponible para su compra."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

        $precio = (float) $curso["Curso_precio"];
        $descripcion = $curso["Curso_nombre"];

        if (!is_finite($precio) || $precio < 0) {
            http_response_code(400);

            echo json_encode([
                "error" => true,
                "mensaje" => "El precio del curso no es válido."
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        // Buscar si el usuario ya tiene una venta pendiente para este curso.
$ventaPendiente = $ventaController->obtenerVentaPendiente(
    $usuarioId,
    $cursoId
);

// Si existe una venta pendiente con una orden de PayPal, reutilizarla.
if (
    $ventaPendiente &&
    !empty($ventaPendiente["Venta_paypal_order_id"])
) {
    $ordenPayPalId = $ventaPendiente["Venta_paypal_order_id"];

    try {
        $ordenExistente = $paypalController->consultarOrden(
            $ordenPayPalId
        );

        if (
            isset($ordenExistente["id"]) &&
            $ordenExistente["id"] === $ordenPayPalId &&
            in_array(
                $ordenExistente["status"] ?? "",
                ["CREATED", "APPROVED"],
                true
            )
        ) {
            echo json_encode([
                "id" => $ordenPayPalId,
                "venta_id" => $ventaPendiente["Venta_id"],
                "reutilizada" => true
            ]);
            exit;
        }

        // Si PayPal ya completó o anuló la orden, no crear otra
        // automáticamente. Primero debemos resolver ese estado.
        http_response_code(409);
        echo json_encode([
            "mensaje" =>
                "Tu compra anterior tiene una orden que requiere revisión. " .
                "No intentes pagar de nuevo por ahora."
        ]);
        exit;

    } catch (Exception $e) {
        error_log(
            "Error al consultar la orden pendiente de PayPal: " .
            $e->getMessage()
        );

        http_response_code(503);
        echo json_encode([
            "mensaje" =>
                "No pudimos verificar tu compra pendiente. " .
                "Inténtalo más tarde."
        ]);
        exit;
    }
}

// Si no existe una venta pendiente, crear una orden nueva.
$orden = $paypalController->crearOrden($precio, $descripcion);

        if (
            empty($orden["id"]) ||
            !is_string($orden["id"])
        ) {
            throw new Exception(
                "PayPal no devolvió un ID de orden válido."
            );
        }

        // Guardar la venta pendiente vinculada al usuario,
        // al curso, al importe y a la orden de PayPal.
        $ventaId = $ventaController->registrarVentaPendiente(
            $usuarioId,
            $cursoId,
            $precio,
            $orden["id"]
        );

        // Se conserva el ID de orden que utiliza el frontend.
        $orden["venta_id"] = $ventaId;

        http_response_code(201);

        echo json_encode(
            $orden,
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );

        exit;
    }

    // ==========================================
    // CAPTURAR ORDEN DE PAYPAL
    // ==========================================
    if ($accion === "capturar") {

        $orderID = trim($_GET["orderID"] ?? "");
        $cursoId = $_GET["curso_id"] ?? null;

        if (
            $orderID === "" ||
            !preg_match('/^[A-Za-z0-9-]+$/', $orderID)
        ) {
            http_response_code(400);

            echo json_encode([
                "error" => true,
                "mensaje" => "El ID de la orden no es válido."
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        if (
            !filter_var($cursoId, FILTER_VALIDATE_INT) ||
            (int) $cursoId <= 0
        ) {
            http_response_code(400);

            echo json_encode([
                "error" => true,
                "mensaje" => "El ID del curso no es válido."
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $cursoId = (int) $cursoId;
        $usuarioId = (int) $_SESSION["usuario_id"];

        // Buscar la orden asociada al usuario autenticado.
        $venta = $ventaController->obtenerPorOrdenPayPal(
            $orderID,
            $usuarioId
        );

        if (!$venta) {
            http_response_code(404);

            echo json_encode([
                "error" => true,
                "mensaje" => "No se encontró una venta asociada a esta orden."
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        // No permitir cambiar el curso asociado a la orden.
        if ((int) $venta["Venta_id_curso"] !== $cursoId) {
            http_response_code(400);

            echo json_encode([
                "error" => true,
                "mensaje" => "La orden no corresponde al curso seleccionado."
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        // Comprobar que el curso siga activo.
$sql = "
    SELECT Curso_estado
    FROM Cursos
    WHERE Curso_id = ?
";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    throw new Exception(
        "Error al comprobar el estado del curso."
    );
}

$stmt->bind_param("i", $cursoId);
$stmt->execute();

$resultadoCurso = $stmt->get_result();
$cursoActual = $resultadoCurso->fetch_assoc();

$stmt->close();

if (!$cursoActual || (int) $cursoActual["Curso_estado"] !== 1) {
    http_response_code(409);

    echo json_encode([
        "error" => true,
        "mensaje" => "Este curso ya no está disponible. No se puede completar la compra."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

        if (($venta["Venta_estado"] ?? "") === "Completada") {
    echo json_encode([
        "mensaje" => "Esta compra ya fue completada.",
        "estado" => "Completada",
        "venta_id" => $venta["Venta_id"]
    ]);
    exit;
}
        if ($venta["Venta_estado"] !== "Pendiente") {
            http_response_code(409);

            echo json_encode([
                "error" => true,
                "mensaje" => "La venta no se encuentra pendiente."
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        // Intentar capturar el pago en PayPal.
        try {

            $resultadoPayPal = $paypalController->capturarOrden(
                $orderID
            );

        } catch (Throwable $errorCaptura) {

            // La captura pudo haberse procesado aunque la respuesta
            // se haya perdido. Consultamos la orden sin volver a cobrar.
            error_log(
                "Resultado incierto al capturar la orden " .
                $orderID . ": " . $errorCaptura->getMessage()
            );

            try {

                $resultadoPayPal = $paypalController->consultarOrden(
                    $orderID
                );

            } catch (Throwable $errorConsulta) {

                error_log(
                    "No se pudo consultar la orden " .
                    $orderID . ": " . $errorConsulta->getMessage()
                );

                http_response_code(503);

                echo json_encode([
                    "error" => true,
                    "mensaje" => "No pudimos confirmar el estado del pago. No vuelvas a pagar todavía; intenta consultar el estado de esta misma orden más tarde."
                ], JSON_UNESCAPED_UNICODE);

                exit;
            }
        }

        // Si la captura no está confirmada, consultar el estado actual.
        if (
            !isset($resultadoPayPal["status"]) ||
            $resultadoPayPal["status"] !== "COMPLETED"
        ) {

            try {

                $resultadoPayPal = $paypalController->consultarOrden(
                    $orderID
                );

            } catch (Throwable $errorConsulta) {

                error_log(
                    "No se pudo verificar el estado de la orden " .
                    $orderID . ": " . $errorConsulta->getMessage()
                );

                http_response_code(503);

                echo json_encode([
                    "error" => true,
                    "mensaje" => "No pudimos confirmar el estado del pago. Consulta nuevamente esta misma orden más tarde."
                ], JSON_UNESCAPED_UNICODE);

                exit;
            }
        }

        // Continuar únicamente si PayPal confirma que la orden
        // está completada. La validación de captura, importe y
        // moneda que ya tienes debajo se conserva.
        if (
            !isset($resultadoPayPal["id"]) ||
            $resultadoPayPal["id"] !== $orderID ||
            !isset($resultadoPayPal["status"]) ||
            $resultadoPayPal["status"] !== "COMPLETED"
        ) {

            http_response_code(409);

            echo json_encode([
                "error" => true,
                "mensaje" => "PayPal todavía no confirma que el pago esté completado."
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        // Verificar el importe y la moneda devueltos por PayPal.
        $captura = $resultadoPayPal["purchase_units"][0]
            ["payments"]["captures"][0] ?? null;

        $importePagado = $captura["amount"]["value"] ?? null;
        $monedaPagada = $captura["amount"]["currency_code"] ?? null;

        $importeEsperado = number_format(
            (float) $venta["Venta_costo"],
            2,
            ".",
            ""
        );

        if (
            !is_array($captura) ||
            !isset($captura["status"]) ||
            $captura["status"] !== "COMPLETED" ||
            !is_string($importePagado) ||
            !is_string($monedaPagada) ||
            $monedaPagada !== "MXN" ||
            $importePagado !== $importeEsperado
        ) {
            http_response_code(400);

            echo json_encode([
                "error" => true,
                "mensaje" => "El importe o la moneda del pago no coinciden con la venta registrada."
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        // Completar exclusivamente la venta pendiente asociada
        // a este usuario y a esta orden de PayPal.
        $completada = $ventaController->completarVenta(
            (int) $venta["Venta_id"],
            $usuarioId,
            $orderID
        );

        if (!$completada) {
            http_response_code(409);

            echo json_encode([
                "error" => true,
                "mensaje" => "No se pudo completar la venta. Verifica su estado antes de volver a intentarlo."
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        http_response_code(200);

        echo json_encode([
            "mensaje" => "Pago confirmado y venta registrada correctamente.",
            "venta_id" => (int) $venta["Venta_id"],
            "usuario_id" => $usuarioId,
            "curso_id" => $cursoId,
            "costo" => $importeEsperado,
            "moneda" => $monedaPagada,
            "estado" => "Completada",
            "paypal" => $resultadoPayPal
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        exit;
    }

    // ==========================================
    // ACCIÓN NO VÁLIDA
    // ==========================================
    http_response_code(400);

    echo json_encode([
        "error" => true,
        "mensaje" => "Acción no válida. Usa crear o capturar."
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    error_log("Error en api/paypal.php: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "error" => true,
        "mensaje" => "Ocurrió un error al procesar la operación."
    ], JSON_UNESCAPED_UNICODE);
}
?>