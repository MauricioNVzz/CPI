<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$rol = (int) ($_SESSION["usuario_rol"] ?? 0);
$nombre = $_SESSION["usuario_nombre"] ?? "Usuario";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

?>

<header class="barra-navegacion">

    <div class="logo">
        <a href="cursos_inicio.php">CPI</a>
    </div>

    <nav>

        <a href="cursos_inicio.php">Inicio</a>

        <?php if ($rol === 1): ?>

            <!-- ADMINISTRADOR -->

            <a href="admin.php">Panel de administración</a>
            <a href="admin_usuarios.php">Usuarios</a>
            <a href="admin_cursos.php">Cursos</a>
            <a href="reporte_ventas.php">Reporte de ventas</a>
            <a href="mensajes.php">Mensajes</a>

        <?php elseif ($rol === 2): ?>

            <!-- MAESTRO -->

            <a href="maestro.php">Mis cursos</a>
            <a href="reporte_ventas.php">Reporte de ventas</a>
            <a href="mensajes.php">Mensajes</a>
            <a href="perfil.php">Mi perfil</a>

        <?php elseif ($rol === 3): ?>

            <!-- ALUMNO -->

            <a href="cursos_lista.php">Explorar cursos</a>
            <a href="kardex.php">Mi Kardex</a>
            <a href="certificados.php">Mis certificados</a>
            <a href="perfil.php">Mi perfil</a>

        <?php endif; ?>

        <form action="cerrar_sesion.php" method="POST" style="display: inline;">

    <input
        type="hidden"
        name="csrf_token"
        value="<?php echo htmlspecialchars(
            $_SESSION["csrf_token"],
            ENT_QUOTES,
            "UTF-8"
        ); ?>"
    >

    <button type="submit">
        Cerrar sesión
    </button>

</form>
    </nav>

</header>