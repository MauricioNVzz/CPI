<?php

require_once __DIR__ . "/middleware/auth.php";

verificarAcceso([1]);

require_once __DIR__ . "/conexion.php";
require_once __DIR__ . "/controllers/UsuarioController.php";

$usuarioController = new UsuarioController($conexion);

// ==========================================
// PROTECCIÓN CSRF
// ==========================================

if (empty($_SESSION["csrf_admin_usuarios"])) {
    $_SESSION["csrf_admin_usuarios"] = bin2hex(random_bytes(32));
}

$mensaje = "";
$error = "";

$administradorId = (int) $_SESSION["usuario_id"];

// ==========================================
// PROCESAR ACCIONES DE ADMINISTRACIÓN
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_admin_usuarios"], $token)) {

        $error = "La solicitud no es válida. Recarga la página.";

    } else {

        $usuarioId = filter_input(
            INPUT_POST,
            "usuario_id",
            FILTER_VALIDATE_INT
        );

        $accion = $_POST["accion"] ?? "";

        if (!$usuarioId || $usuarioId <= 0) {

            $error = "El usuario seleccionado no es válido.";

        } elseif ($usuarioId === $administradorId) {

            $error = "No puedes modificar el estado ni el rol de tu propia cuenta.";

        } else {

            try {

                // ==========================================
                // CAMBIAR ROL
                // ==========================================

                if ($accion === "cambiar_rol") {

                    $rolId = filter_input(
                        INPUT_POST,
                        "rol_id",
                        FILTER_VALIDATE_INT
                    );

                    if (!$rolId || $rolId <= 0) {

                        $error = "El rol seleccionado no es válido.";

                    } elseif (
                        $usuarioController->cambiarRol(
                            $usuarioId,
                            $rolId
                        )
                    ) {

                        $mensaje = "El rol del usuario se actualizó correctamente.";

                    } else {

                        $error = "No fue posible actualizar el rol.";

                    }

                // ==========================================
                // BLOQUEAR USUARIO
                // ==========================================

                } elseif ($accion === "bloquear") {

                    if ($usuarioController->bloquear($usuarioId)) {

                        $mensaje = "El usuario fue bloqueado correctamente.";

                    } else {

                        $error = "No fue posible bloquear al usuario.";

                    }

                // ==========================================
                // DESBLOQUEAR USUARIO
                // ==========================================

                } elseif ($accion === "desbloquear") {

                    if ($usuarioController->desbloquear($usuarioId)) {

                        $mensaje = "El usuario fue desbloqueado correctamente.";

                    } else {

                        $error = "No fue posible desbloquear al usuario.";

                    }

                } else {

                    $error = "La acción solicitada no es válida.";

                }

            } catch (InvalidArgumentException $e) {

                $error = $e->getMessage();

            } catch (Throwable $e) {

                error_log($e->getMessage());

                $error = "Ocurrió un error al administrar el usuario.";
            }
        }
    }
}

// ==========================================
// FILTROS DEL REPORTE DE USUARIOS
// ==========================================

$busqueda = trim($_GET["busqueda"] ?? "");
$filtroRol = $_GET["rol"] ?? "";
$desde = $_GET["desde"] ?? "";
$hasta = $_GET["hasta"] ?? "";

// ==========================================
// VALIDAR FECHAS
// ==========================================

$fechasValidas = true;

if (
    ($desde !== "" && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) ||
    ($hasta !== "" && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) ||
    ($desde !== "" && $hasta !== "" && $desde > $hasta)
) {

    $fechasValidas = false;

    $error = "Los filtros de fechas no son válidos.";
}

// ==========================================
// VALIDAR FILTRO DE ROL
// ==========================================

$filtroRolConsulta = $filtroRol;

if ($filtroRol !== "" && !ctype_digit((string) $filtroRol)) {

    $error = "El rol seleccionado no es válido.";

    $filtroRolConsulta = "";

    $fechasValidas = false;
}

// ==========================================
// OBTENER ROLES Y USUARIOS MEDIANTE MVC
// ==========================================

$roles = [];
$usuarios = [];

try {

    $roles = $usuarioController->obtenerRoles();

    $usuarios = $usuarioController->obtenerTodos(
        $busqueda,
        $filtroRolConsulta,
        $fechasValidas ? $desde : "",
        $fechasValidas ? $hasta : ""
    );

} catch (Throwable $e) {

    error_log($e->getMessage());

    $error = "No fue posible cargar la información de los usuarios.";

    $roles = [];
    $usuarios = [];
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Administrar usuarios - CPI</title>

    <link rel="stylesheet" href="css/admin_usuarios.css">
    <link rel="stylesheet" href="css/navbar.css">

</head>

<body>

    <?php require_once __DIR__ . "/includes/navbar.php"; ?>

    <main class="contenedor-usuarios">

        <section class="encabezado-usuarios">

            <h1>Administración de usuarios</h1>

            <p>
                Consulta los usuarios registrados y administra sus roles y estados.
            </p>

            <a href="admin.php" class="boton-regresar">
                Volver al panel
            </a>

        </section>

        <?php if ($mensaje !== ""): ?>

            <div class="mensaje-exito">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>

        <?php endif; ?>

        <?php if ($error !== ""): ?>

            <div class="mensaje-error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <!-- ========================================== -->
        <!-- FILTROS -->
        <!-- ========================================== -->

        <section class="filtros-usuarios">

            <h2>Filtrar usuarios</h2>

            <form method="GET" action="admin_usuarios.php">

                <div class="grupo-filtro">

                    <label for="busqueda">Nombre o correo:</label>

                    <input
                        type="text"
                        id="busqueda"
                        name="busqueda"
                        placeholder="Buscar usuario..."
                        value="<?php echo htmlspecialchars($busqueda); ?>"
                    >

                </div>

                <div class="grupo-filtro">

                    <label for="rol">Rol:</label>

                    <select id="rol" name="rol">

                        <option value="">Todos los roles</option>

                        <?php foreach ($roles as $rol): ?>

                            <option
                                value="<?php echo (int) $rol["Rol_id"]; ?>"
                                <?php echo ($filtroRol == $rol["Rol_id"]) ? "selected" : ""; ?>
                            >
                                <?php echo htmlspecialchars($rol["Rol_nombre"]); ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="grupo-filtro">

                    <label for="desde">Fecha inicial:</label>

                    <input
                        type="date"
                        id="desde"
                        name="desde"
                        value="<?php echo htmlspecialchars($desde); ?>"
                    >

                </div>

                <div class="grupo-filtro">

                    <label for="hasta">Fecha final:</label>

                    <input
                        type="date"
                        id="hasta"
                        name="hasta"
                        value="<?php echo htmlspecialchars($hasta); ?>"
                    >

                </div>

                <div class="acciones-filtros">

                    <button type="submit">
                        Aplicar filtros
                    </button>

                    <a href="admin_usuarios.php">
                        Limpiar filtros
                    </a>

                </div>

            </form>

        </section>

        <!-- ========================================== -->
        <!-- TABLA DE USUARIOS -->
        <!-- ========================================== -->

        <section class="tabla-usuarios">

            <h2>Usuarios registrados</h2>

            <div class="tabla-contenedor">

                <table>

                    <thead>

                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Fecha de registro</th>
                            <th>Rol actual</th>
                            <th>Estado</th>
                            <th>Administrar rol</th>
                            <th>Administrar cuenta</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php if (!empty($usuarios)): ?>

                            <?php foreach ($usuarios as $usuario): ?>

                                <tr>

                                    <td>
                                        <?php echo (int) $usuario["Usuario_id"]; ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($usuario["Usuario_nombre"]); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($usuario["Usuario_email"]); ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo date(
                                            "d/m/Y",
                                            strtotime($usuario["Usuario_fechareg"])
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($usuario["Rol_nombre"]); ?>
                                    </td>

                                    <td>

                                        <?php if ((int) $usuario["Usuario_bloqueado"] === 1): ?>

                                            <span class="estado bloqueado">
                                                Bloqueado
                                            </span>

                                        <?php else: ?>

                                            <span class="estado activo">
                                                Activo
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <!-- ADMINISTRAR ROL -->

                                    <td>

                                        <?php if ((int) $usuario["Usuario_id"] === $administradorId): ?>

                                            <span class="texto-propio">
                                                Cuenta actual
                                            </span>

                                        <?php else: ?>

                                            <form method="POST" action="admin_usuarios.php">

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?php echo htmlspecialchars($_SESSION["csrf_admin_usuarios"]); ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="usuario_id"
                                                    value="<?php echo (int) $usuario["Usuario_id"]; ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="accion"
                                                    value="cambiar_rol"
                                                >

                                                <select name="rol_id" required>

                                                    <?php foreach ($roles as $rol): ?>

                                                        <option
                                                            value="<?php echo (int) $rol["Rol_id"]; ?>"
                                                            <?php
                                                            echo ((int) $rol["Rol_id"] === (int) $usuario["Usuario_id_rol"])
                                                                ? "selected"
                                                                : "";
                                                            ?>
                                                        >
                                                            <?php echo htmlspecialchars($rol["Rol_nombre"]); ?>
                                                        </option>

                                                    <?php endforeach; ?>

                                                </select>

                                                <button type="submit">
                                                    Actualizar
                                                </button>

                                            </form>

                                        <?php endif; ?>

                                    </td>

                                    <!-- ADMINISTRAR ESTADO DE CUENTA -->

                                    <td>

                                        <?php if ((int) $usuario["Usuario_id"] === $administradorId): ?>

                                            <span class="texto-propio">
                                                Cuenta actual
                                            </span>

                                        <?php else: ?>

                                            <form method="POST" action="admin_usuarios.php">

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?php echo htmlspecialchars($_SESSION["csrf_admin_usuarios"]); ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="usuario_id"
                                                    value="<?php echo (int) $usuario["Usuario_id"]; ?>"
                                                >

                                                <?php if ((int) $usuario["Usuario_bloqueado"] === 1): ?>

                                                    <input
                                                        type="hidden"
                                                        name="accion"
                                                        value="desbloquear"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="boton-desbloquear"
                                                    >
                                                        Desbloquear
                                                    </button>

                                                <?php else: ?>

                                                    <input
                                                        type="hidden"
                                                        name="accion"
                                                        value="bloquear"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="boton-bloquear"
                                                    >
                                                        Bloquear
                                                    </button>

                                                <?php endif; ?>

                                            </form>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="8">
                                    No hay usuarios registrados.
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</body>

</html>