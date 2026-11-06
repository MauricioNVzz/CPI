
<?php

require_once __DIR__ . "/middleware/auth.php";

verificarAcceso([3]);

require_once __DIR__ . "/conexion.php";

$usuarioId = (int)$_SESSION["usuario_id"];

$certificadoId = filter_var(
    $_GET["id"] ?? null,
    FILTER_VALIDATE_INT
);

if (!$certificadoId || $certificadoId <= 0) {
    die("Certificado no válido.");
}

// Obtener certificado del alumno que inició sesión
$sql = "
    SELECT
        c.Certificado_id,
        c.Certificado_folio,
        c.Certificado_fecha,
        cu.Curso_nombre,
        u.Usuario_nombre
    FROM certificados c
    INNER JOIN cursos cu
        ON c.Certificado_id_curso = cu.Curso_id
    INNER JOIN usuarios u
        ON c.Certificado_id_usuario = u.Usuario_id
    WHERE c.Certificado_id = ?
      AND c.Certificado_id_usuario = ?
    LIMIT 1
";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    die("Error al consultar el certificado.");
}

$stmt->bind_param("ii", $certificadoId, $usuarioId);
$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    die("El certificado no existe o no tienes permiso para consultarlo.");
}

$certificado = $resultado->fetch_assoc();

$stmt->close();

$fecha = date(
    "d/m/Y",
    strtotime($certificado["Certificado_fecha"])
);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Certificado | CPI</title>

    <style>

        body {
            margin: 0;
            padding: 30px;
            background: #f0f2f5;
            font-family: Georgia, serif;
        }

        .certificado {
            max-width: 1000px;
            min-height: 600px;
            margin: 30px auto;
            padding: 45px;
            box-sizing: border-box;
            background: white;
            border: 12px double #b99a45;
            text-align: center;
            position: relative;
        }

        .certificado h1 {
            color: #b08d35;
            font-size: 42px;
            margin-bottom: 10px;
        }

        .subtitulo {
            font-size: 20px;
            color: #555;
        }

        .nombre {
            font-size: 36px;
            color: #183153;
            margin: 35px 0;
            border-bottom: 2px solid #b99a45;
            display: inline-block;
            padding: 0 30px 12px;
        }

        .curso {
            font-size: 29px;
            font-weight: bold;
            color: #183153;
            margin: 25px 0;
        }

        .texto {
            font-size: 19px;
            line-height: 1.7;
            color: #444;
        }

        .datos {
            margin-top: 55px;
            display: flex;
            justify-content: space-between;
            gap: 20px;
            font-size: 14px;
        }

        .folio {
            color: #555;
            margin-top: 35px;
            font-size: 14px;
        }

        .botones {
            text-align: center;
            margin: 25px;
        }

        button, a {
            display: inline-block;
            border: none;
            padding: 12px 22px;
            margin: 5px;
            border-radius: 6px;
            background: #183153;
            color: white;
            text-decoration: none;
            cursor: pointer;
            font-size: 15px;
        }

        @media print {

            body {
                background: white;
                padding: 0;
            }

            .certificado {
                margin: 0;
                max-width: none;
                width: 100%;
                min-height: 180mm;
                break-inside: avoid;
            }

            .botones {
                display: none;
            }

            @page {
                size: landscape;
                margin: 10mm;
            }

        }

    </style>

</head>

<body>

    <div class="botones">

        <button onclick="window.print()">
            Descargar / Imprimir PDF
        </button>

        <a href="certificados.php">
            Volver a mis certificados
        </a>

    </div>

    <main class="certificado">

        <h1>Certificado de Finalización</h1>

        <p class="subtitulo">
            CPI - Plataforma de Cursos en Línea
        </p>

        <p class="texto">
            Se hace constar que:
        </p>

        <div class="nombre">
            <?= htmlspecialchars($certificado["Usuario_nombre"]) ?>
        </div>

        <p class="texto">
            Ha completado satisfactoriamente todos los contenidos del curso:
        </p>

        <div class="curso">
            <?= htmlspecialchars($certificado["Curso_nombre"]) ?>
        </div>

        <p class="texto">
            Por haber cumplido con los requisitos de finalización establecidos.
        </p>

        <div class="datos">

            <div>
                **Fecha de emisión**

                <p><?= htmlspecialchars($fecha) ?></p>
            </div>

            <div>
                **Plataforma**

                <p>CPI</p>
            </div>

        </div>

        <div class="folio">
            Folio de certificado:
            <strong>
                <?= htmlspecialchars($certificado["Certificado_folio"]) ?>
            </strong>
        </div>

    </main>

</body>
</html>
