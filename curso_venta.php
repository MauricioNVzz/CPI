<?php

require_once __DIR__ . "/middleware/auth.php";

verificarAcceso([1, 2, 3]);

include "conexion.php";
require_once "config/paypal.php";

// Obtener el ID del curso
$id = $_GET["id"] ?? "";


// Validar el ID
if (!filter_var($id, FILTER_VALIDATE_INT) || $id <= 0) {

    die("ID de curso no válido.");

}


// Obtener información del curso
$sql = "
    SELECT
        c.Curso_id,
        c.Curso_nombre,
        c.Curso_desc,
        c.Curso_precio,
        c.Curso_duracion,
        c.Curso_imagen,
        u.Usuario_nombre
    FROM Cursos c
    INNER JOIN Usuarios u
        ON c.Curso_id_maestro = u.Usuario_id
    WHERE c.Curso_id = ?
AND c.Curso_estado = 1
";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    die("Error al preparar la consulta.");
}

$stmt->bind_param("i", $id);
$stmt->execute();

$resultado = $stmt->get_result();


// Comprobar que el curso existe y está activo
if ($resultado->num_rows === 0) {

    die("El curso no existe o ya no está disponible para su compra.");

}

$curso = $resultado->fetch_assoc();

$stmt->close();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Comprar <?php echo htmlspecialchars($curso["Curso_nombre"]); ?>
    </title>

    <!-- SDK DE PAYPAL SANDBOX -->

    <script src="https://www.sandbox.paypal.com/sdk/js?client-id=<?php echo PAYPAL_CLIENT_ID; ?>&currency=MXN&intent=capture"></script>

</head>

<body>

    <div class="venta">

        <h1>Adquirir curso</h1>


        <div class="informacion-curso">

            <h2>
                <?php echo htmlspecialchars($curso["Curso_nombre"]); ?>
            </h2>

            <p>
                Instructor:
                <?php echo htmlspecialchars($curso["Usuario_nombre"]); ?>
            </p>

            <p>
                <?php echo htmlspecialchars($curso["Curso_desc"]); ?>
            </p>

            <p>
                Duración:
                <?php echo htmlspecialchars($curso["Curso_duracion"]); ?>
            </p>

            <h3>
                Precio:
                $<?php echo number_format($curso["Curso_precio"], 2); ?>
                MXN
            </h3>

        </div>


        <!-- =========================
             BOTÓN DE PAYPAL
             ========================= -->

        <div id="paypal-button-container"></div>


        <!-- MENSAJE DEL RESULTADO -->

        <p id="mensaje-pago"></p>


        <br>


        <a href="curso_ver.php?id=<?php echo $curso["Curso_id"]; ?>">
            Regresar al curso
        </a>

    </div>


    <script>

        // ID del curso obtenido desde PHP

        const cursoId = <?php echo (int)$curso["Curso_id"]; ?>;


        // Elemento para mostrar mensajes

        const mensajePago =
            document.getElementById("mensaje-pago");


        // ==========================================
        // BOTÓN DE PAYPAL
        // ==========================================

        paypal.Buttons({

            // --------------------------------------
            // CREAR ORDEN
            // --------------------------------------

            createOrder: async function() {

                try {

                    const respuesta = await fetch(
                        "api/paypal.php?accion=crear",
                        {
                            method: "POST",

                            headers: {
                                "Content-Type": "application/json"
                            },

                            body: JSON.stringify({
                                curso_id: cursoId
                            })
                        }
                    );


                    const datos = await respuesta.json();


                    if (!respuesta.ok) {

                        throw new Error(
                            datos.mensaje ||
                            "No se pudo crear la orden."
                        );

                    }


                    // Devolver ID de la orden a PayPal

                    return datos.id;


                } catch (error) {

                    console.error(error);

                    mensajePago.textContent =
                        "No se pudo iniciar el pago.";

                    throw error;

                }

            },


            // --------------------------------------
            // USUARIO APROBÓ EL PAGO
            // --------------------------------------

onApprove: async function(data) {

    mensajePago.textContent =
        "Verificando el resultado del pago...";

    try {

        const respuesta = await fetch(
            "api/paypal.php?accion=capturar&orderID="
            + encodeURIComponent(data.orderID)
            + "&curso_id="
            + encodeURIComponent(cursoId),
            {
                method: "POST"
            }
        );

        const resultado = await respuesta.json();

        if (!respuesta.ok) {

            console.error(
                "Respuesta del servidor:",
                resultado
            );

            mensajePago.textContent =
                resultado.mensaje ||
                "No se pudo confirmar el resultado del pago. Consulta el estado antes de volver a intentarlo.";

            return;
        }

        console.log("Pago confirmado:", resultado);

        mensajePago.textContent =
            "¡Pago realizado correctamente!";

    } catch (error) {

        console.error(
            "Error al confirmar el pago:",
            error
        );

        mensajePago.textContent =
            "No pudimos confirmar el resultado del pago. No vuelvas a pagar todavía; verifica el estado de la compra.";
    }
},


            // --------------------------------------
            // ERROR DE PAYPAL
            // --------------------------------------

            onError: function(error) {

                console.error(
                    "Error de PayPal:",
                    error
                );


                mensajePago.textContent =
                    "Ocurrió un error con PayPal.";

            }

        }).render("#paypal-button-container");

    </script>

</body>

</html>