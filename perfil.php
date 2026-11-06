<?php

require_once __DIR__ . "/middleware/auth.php";

verificarAcceso([1, 2, 3]);

require_once __DIR__ . "/conexion.php";

$usuarioId = (int) $_SESSION["usuario_id"];


// ==========================================
// OBTENER DATOS DEL USUARIO
// ==========================================

$sqlUsuario = "
    SELECT
        Usuario_nombre,
        Usuario_foto,
        Usuario_genero,
        Usuario_fechanac,
        Usuario_email
    FROM Usuarios
    WHERE Usuario_id = ?
";

$stmtUsuario = $conexion->prepare($sqlUsuario);

if (!$stmtUsuario) {

    die("Error al preparar la consulta del usuario.");

}

$stmtUsuario->bind_param(
    "i",
    $usuarioId
);

$stmtUsuario->execute();

$resultadoUsuario =
    $stmtUsuario->get_result();


if ($resultadoUsuario->num_rows === 0) {

    session_destroy();

    header("Location: index.php");

    exit;
}


$usuario =
    $resultadoUsuario->fetch_assoc();

$stmtUsuario->close();


// ==========================================
// OBTENER CURSOS COMPRADOS
// ==========================================

$sqlCursos = "
    SELECT
        v.Venta_id,
        v.Venta_id_curso,
        v.Venta_costo,
        v.Venta_fecha,
        c.Curso_nombre,
        c.Curso_imagen,
        u.Usuario_nombre
    FROM Ventas v

    INNER JOIN Cursos c
        ON v.Venta_id_curso = c.Curso_id

    INNER JOIN Usuarios u
        ON c.Curso_id_maestro = u.Usuario_id

    WHERE v.Venta_id_usuario = ?
    AND v.Venta_estado = 'Completada'

    ORDER BY v.Venta_fecha DESC
";

$stmtCursos = $conexion->prepare($sqlCursos);

if (!$stmtCursos) {

    die("Error al preparar la consulta de cursos.");

}

$stmtCursos->bind_param(
    "i",
    $usuarioId
);

$stmtCursos->execute();

$resultadoCursos =
    $stmtCursos->get_result();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Perfil</title>

    <link
        rel="stylesheet"
        href="css/perfil.css"
    >

</head>

<body>

    <div class="ventana-perfil">


        <!-- =========================
             PARTE SUPERIOR
             ========================= -->

        <div class="barra-superior">

            <h1>
                Perfil
            </h1>

        </div>


        <!-- =========================
             INFORMACIÓN DEL USUARIO
             ========================= -->

        <div class="informacion-usuario">


            <!-- FOTO DE PERFIL -->

            <div class="foto-perfil">

                <?php if (!empty($usuario["Usuario_foto"])): ?>

                    <img
                        src="<?php
                        echo htmlspecialchars(
                            $usuario["Usuario_foto"]
                        );
                        ?>"
                        alt="Foto de perfil"
                    >

                <?php else: ?>

                    <img
                        src=""
                        alt="Foto de perfil"
                    >

                <?php endif; ?>

            </div>


            <!-- DATOS DEL USUARIO -->

            <div class="datos-usuario">

                <p class="nombre-usuario">

                    <?php
                    echo htmlspecialchars(
                        $usuario["Usuario_nombre"]
                    );
                    ?>

                </p>


                <p>

                    Género:

                    <span>

                        <?php
                        echo htmlspecialchars(
                            $usuario["Usuario_genero"]
                        );
                        ?>

                    </span>

                </p>


                <p>

                    Fecha:

                    <span>

                        <?php
                        echo date(
                            "d/m/Y",
                            strtotime(
                                $usuario["Usuario_fechanac"]
                            )
                        );
                        ?>

                    </span>

                </p>

            </div>

        </div>


        <!-- =========================
             CURSOS ADQUIRIDOS
             ========================= -->

        
<?php if ((int) $_SESSION["usuario_rol"] === 3): ?>

    <!-- CURSOS ADQUIRIDOS DEL ALUMNO -->

    <div class="seccion-cursos">

        <h2>Mis cursos</h2>

        <div class="cuadro-cursos">

            <?php if ($resultadoCursos->num_rows > 0): ?>

                <?php while ($curso = $resultadoCursos->fetch_assoc()): ?>

                    <a
                        href="curso_ver.php?id=<?php echo $curso["Venta_id_curso"]; ?>"
                        class="curso-adquirido"
                    >

                        <div class="video-mini">

                            <?php if (!empty($curso["Curso_imagen"])): ?>

                                <img
                                    src="<?php echo htmlspecialchars($curso["Curso_imagen"]); ?>"
                                    alt="Curso"
                                >

                            <?php else: ?>

                                <span>||</span>

                            <?php endif; ?>

                        </div>

                        <div class="informacion-curso">

                            <p class="nombre-curso">
                                <?php echo htmlspecialchars($curso["Curso_nombre"]); ?>
                            </p>

                            <p class="tutor-curso">
                                Tutor: <?php echo htmlspecialchars($curso["Usuario_nombre"]); ?>
                            </p>

                        </div>

                        <p class="precio-curso">
                            $<?php echo number_format($curso["Venta_costo"], 2); ?>
                        </p>

                    </a>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="sin-cursos">
                    <p>Todavía no has adquirido ningún curso.</p>
                    <a href="cursos_inicio.php">Explorar cursos</a>
                </div>

            <?php endif; ?>

        </div>

    </div>

<?php elseif ((int) $_SESSION["usuario_rol"] === 2): ?>

    <!-- INFORMACIÓN DEL MAESTRO -->

    <div class="seccion-cursos">

        <h2>Área de maestro</h2>

        <p>Administra tus cursos y el contenido que compartes en CPI.</p>

        <a href="maestro.php">Ir a mis cursos</a>

        <a href="mensajes.php">Mensajes con administración</a>

    </div>

<?php elseif ((int) $_SESSION["usuario_rol"] === 1): ?>

    <!-- INFORMACIÓN DEL ADMINISTRADOR -->

    <div class="seccion-cursos">

        <h2>Área de administración</h2>

        <p>Desde aquí puedes gestionar la plataforma CPI.</p>

        <a href="admin.php">Ir al panel de administración</a>

    </div>

<?php endif; ?>



    </div>

</body>

</html>

<?php

$stmtCursos->close();

?>