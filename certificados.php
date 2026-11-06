
<?php

require_once __DIR__ . "/middleware/auth.php";

verificarAcceso([3]);

require_once __DIR__ . "/conexion.php";

$usuarioId = (int)$_SESSION["usuario_id"];

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
    WHERE c.Certificado_id_usuario = ?
    ORDER BY c.Certificado_fecha DESC
";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    die("Error al consultar los certificados.");
}

$stmt->bind_param("i", $usuarioId);
$stmt->execute();

$resultado = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="css/navbar.css">

    <title>Mis certificados | CPI</title>

    <style>
        .contenedor-certificados {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
        }

        .certificado {
            background: white;
            padding: 25px;
            margin-bottom: 20px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,.10);
        }

        .certificado h2 {
            margin-top: 0;
        }

        .folio {
            color: #555;
            font-size: 14px;
        }

        .btn-certificado {
            display: inline-block;
            background: #1769aa;
            color: white;
            padding: 10px 18px;
            border-radius: 6px;
            text-decoration: none;
            margin-top: 12px;
        }

        .sin-certificados {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 12px;
        }
    </style>
</head>

<body>

    <?php include_once __DIR__ . "/includes/navbar.php"; ?>

    <main class="contenedor-certificados">

        <h1>Mis certificados</h1>

        <p>Consulta los certificados de los cursos que has completado.</p>

        <?php if ($resultado->num_rows > 0): ?>

            <?php while ($fila = $resultado->fetch_assoc()): ?>

                <div class="certificado">

                    <h2>
                        <?= htmlspecialchars($fila["Curso_nombre"]) ?>
                    </h2>

                    <p>
                        Alumno:
                        <?= htmlspecialchars($fila["Usuario_nombre"]) ?>
                    </p>

                    <p class="folio">
                        Folio:
                        <?= htmlspecialchars($fila["Certificado_folio"]) ?>
                    </p>

                    <p>
                        Fecha de emisión:
                        <?= date(
                            "d/m/Y",
                            strtotime($fila["Certificado_fecha"])
                        ) ?>
                    </p>

                    <a
                        class="btn-certificado"
                        href="certificado_ver.php?id=<?= (int)$fila["Certificado_id"] ?>"
                    >
                        Ver certificado
                    </a>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="sin-certificados">
                <h2>Aún no tienes certificados</h2>
                <p>Completa todos los contenidos de un curso para obtener tu certificado.</p>
            </div>

        <?php endif; ?>

    </main>

</body>
</html>

<?php
$stmt->close();
?>
