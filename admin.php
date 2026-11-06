<?php

require_once __DIR__ . "/middleware/auth.php";

verificarAcceso([1]);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Panel de administración - CPI</title>

    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/navbar.css">
</head>

<body>

    <?php require_once __DIR__ . "/includes/navbar.php"; ?>

    <main class="contenedor-admin">

        <section class="bienvenida-admin">

            <h1>Panel de administración</h1>

            <p>
                Bienvenido,
                <?php
                echo htmlspecialchars(
                    $_SESSION["usuario_nombre"] ?? "Administrador"
                );
                ?>.
            </p>

            <p>
                Desde aquí puedes gestionar los usuarios,
                cursos y comunicaciones de la plataforma CPI.
            </p>

        </section>

        
<section class="opciones-admin">

    <a href="admin_usuarios.php" class="tarjeta-admin">
        <h2>Usuarios</h2>
        <p>Consultar usuarios y administrar sus roles.</p>
    </a>

    <a href="admin_cursos.php" class="tarjeta-admin">
        <h2>Cursos</h2>
        <p>Consultar y administrar los cursos registrados.</p>
    </a>

    <a href="admin_categorias.php" class="tarjeta-admin">
        <h2>Categorías</h2>
        <p>Crear, modificar y eliminar las categorías de los cursos.</p>
    </a>

    <a href="admin_comentarios.php" class="tarjeta-admin">
        <h2>Comentarios</h2>
        <p>Consultar comentarios y eliminarlos indicando un motivo.</p>
    </a>

    <a href="reporte_ventas.php" class="tarjeta-admin">
        <h2>Reporte de ventas</h2>
        <p>Consultar las ventas de los cursos, filtrar por fechas y revisar los ingresos.</p>
    </a>

    <a href="mensajes.php" class="tarjeta-admin">
        <h2>Mensajes</h2>
        <p>Consultar y responder mensajes de los maestros.</p>
    </a>

    <a href="perfil.php" class="tarjeta-admin">
        <h2>Mi perfil</h2>
        <p>Consultar y modificar tu información personal.</p>
    </a>

</section>


    </main>

</body>

</html>