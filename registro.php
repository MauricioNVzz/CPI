<?php

// ==========================================
// INICIAR SESIÓN
// ==========================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "conexion.php";


// ==========================================
// GENERAR TOKEN CSRF
// ==========================================

if (empty($_SESSION["csrf_token"])) {

    $_SESSION["csrf_token"] = bin2hex(
        random_bytes(32)
    );

}


// ==========================================
// VARIABLES DE MENSAJES
// ==========================================

$mensaje = "";
$tipoMensaje = "";


// ==========================================
// PROCESAR REGISTRO
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // ==========================================
    // VALIDAR TOKEN CSRF
    // ==========================================

    $tokenRecibido = $_POST["csrf_token"] ?? "";

    if (
        !is_string($tokenRecibido) ||
        !hash_equals(
            $_SESSION["csrf_token"],
            $tokenRecibido
        )
    ) {

        $mensaje =
            "La solicitud no es válida. Recarga la página e intenta nuevamente.";

        $tipoMensaje = "error";

    }

    else {

        // ==========================================
        // OBTENER DATOS
        // ==========================================

        $usuario = trim($_POST["usuario"] ?? "");
        $correo = trim($_POST["correo"] ?? "");
        $contrasena = $_POST["contrasena"] ?? "";
        $genero = $_POST["genero"] ?? "";
        $fecha = $_POST["fecha"] ?? "";


        // ==========================================
        // VALIDAR CAMPOS
        // ==========================================

        if (
            $usuario === "" ||
            $correo === "" ||
            $contrasena === "" ||
            $genero === "" ||
            $fecha === ""
        ) {

            $mensaje = "Todos los campos son obligatorios.";
            $tipoMensaje = "error";

        }

        // ==========================================
        // VALIDAR GÉNERO
        // ==========================================

        elseif (
            !in_array($genero, ["Hombre", "Mujer"], true)
        ) {

            $mensaje = "Selecciona un género válido.";
            $tipoMensaje = "error";

        }

        // ==========================================
        // VALIDAR NOMBRE
        // ==========================================

        elseif (strlen($usuario) > 100) {

            $mensaje =
                "El nombre no puede superar los 100 caracteres.";

            $tipoMensaje = "error";

        }

        // ==========================================
        // VALIDAR CORREO
        // ==========================================

        elseif (
            !filter_var($correo, FILTER_VALIDATE_EMAIL)
        ) {

            $mensaje =
                "El correo electrónico no es válido.";

            $tipoMensaje = "error";

        }

        elseif (strlen($correo) > 150) {

            $mensaje =
                "El correo no puede superar los 150 caracteres.";

            $tipoMensaje = "error";

        }

        // ==========================================
        // VALIDAR FECHA DE NACIMIENTO
        // ==========================================

        elseif (
            !is_string($fecha) ||
            !($fechaValida = DateTime::createFromFormat(
                "Y-m-d",
                $fecha
            )) ||
            $fechaValida->format("Y-m-d") !== $fecha
        ) {

            $mensaje =
                "La fecha de nacimiento no es válida.";

            $tipoMensaje = "error";

        }

        elseif ($fecha > date("Y-m-d")) {

            $mensaje =
                "La fecha de nacimiento no puede ser futura.";

            $tipoMensaje = "error";

        }

        // ==========================================
        // VALIDAR CONTRASEÑA
        // ==========================================

        elseif (
            strlen($contrasena) < 8 ||
            !preg_match("/[A-Z]/", $contrasena) ||
            !preg_match("/[a-z]/", $contrasena) ||
            !preg_match("/[0-9]/", $contrasena) ||
            !preg_match(
                "/[!@#$%^&*(),.?\":{}|<>_\-+=]/",
                $contrasena
            )
        ) {

            $mensaje =
                "La contraseña no cumple con todos los requisitos.";

            $tipoMensaje = "error";

        }

        else {

            try {

                // ==========================================
                // COMPROBAR SI EL CORREO YA EXISTE
                // ==========================================

                $sql = "
                    SELECT Usuario_id
                    FROM Usuarios
                    WHERE Usuario_email = ?
                ";

                $stmt = $conexion->prepare($sql);

                if (!$stmt) {

                    throw new Exception(
                        "Error al preparar la consulta."
                    );

                }

                $stmt->bind_param("s", $correo);

                $stmt->execute();

                $resultado = $stmt->get_result();

                $stmt->close();


                if ($resultado->num_rows > 0) {

                    $mensaje =
                        "Ya existe una cuenta con ese correo.";

                    $tipoMensaje = "error";

                }

                else {

                    // ==========================================
                    // INSERTAR USUARIO
                    // ==========================================

                    $contrasenaHash = password_hash(
                        $contrasena,
                        PASSWORD_DEFAULT
                    );

                    if ($contrasenaHash === false) {

                        throw new Exception(
                            "Error al proteger la contraseña."
                        );

                    }

                    $sql = "
                        INSERT INTO Usuarios
                        (
                            Usuario_nombre,
                            Usuario_genero,
                            Usuario_fechanac,
                            Usuario_email,
                            Usuario_contrasena,
                            Usuario_id_rol
                        )
                        VALUES (?, ?, ?, ?, ?, 3)
                    ";

                    $stmt = $conexion->prepare($sql);

                    if (!$stmt) {

                        throw new Exception(
                            "Error al preparar el registro."
                        );

                    }

                    /*
                     * La contraseña se almacena mediante
                     * password_hash() para protegerla.
                     */

                    $stmt->bind_param(
                        "sssss",
                        $usuario,
                        $genero,
                        $fecha,
                        $correo,
                        $contrasenaHash
                    );

                    $stmt->execute();

                    $stmt->close();


                    $mensaje =
                        "¡Cuenta creada correctamente!";

                    $tipoMensaje = "exito";

                }

            } catch (Throwable $e) {

                $mensaje =
                    "Ocurrió un error al crear la cuenta.";

                $tipoMensaje = "error";

            }

        }

    }

}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Crear una cuenta</title>

    <link
        rel="stylesheet"
        href="css/registro.css"
    >

</head>


<body>

    <div class="ventana">

        <div class="izquierda">

            <h1>crea una cuenta ahora mismo</h1>


            <?php if ($mensaje !== ""): ?>

                <p class="<?php echo $tipoMensaje; ?>">

                    <?php echo htmlspecialchars($mensaje); ?>

                </p>

            <?php endif; ?>


            <form
                id="formularioRegistro"
                method="POST"
                action="registro.php"
            >

            <input
    type="hidden"
    name="csrf_token"
    value="<?php echo htmlspecialchars(
        $_SESSION["csrf_token"],
        ENT_QUOTES,
        "UTF-8"
    ); ?>"
>

                <label for="usuario">
                    Nombre del usuario
                </label>

                <input
                    type="text"
                    id="usuario"
                    name="usuario"
                    required
                >


                <label for="correo">
                    Correo electrónico
                </label>

                <input
                    type="email"
                    id="correo"
                    name="correo"
                    required
                >


                <label for="contrasena">
                    Contraseña
                </label>

                <input
                    type="password"
                    id="contrasena"
                    name="contrasena"
                    required
                >

                <p id="mensajeContrasena"></p>


                <label>
                    Genero
                </label>

                <div class="genero">

                    <label>
                        <input
                            type="radio"
                            name="genero"
                            value="Hombre"
                            required
                        >
                        hombre
                    </label>

                    <label>
                        <input
                            type="radio"
                            name="genero"
                            value="Mujer"
                        >
                        mujer
                    </label>

                </div>


                <label for="fecha">
                    Fecha de nacimiento
                </label>

                <input
                    type="date"
                    id="fecha"
                    name="fecha"
                    class="fecha"
                    required
                >


                <button type="submit">
                    Crear cuenta
                </button>


                <a
                    href="index.php"
                    class="boton-cuenta"
                >
                    Ya tengo una cuenta
                </a>

            </form>

        </div>


        <div class="derecha">

            <h2>
                foto de perfil
            </h2>


            <div class="foto-container">

                <img
                    id="fotoPerfil"
                    src=""
                    alt="Foto de perfil"
                >

                <span id="textoFoto">
                    Sin foto
                </span>

            </div>


            <label
                for="seleccionarFoto"
                class="boton-foto"
            >
                Seleccionar foto
            </label>


            <input
                type="file"
                id="seleccionarFoto"
                accept="image/*"
                hidden
            >

        </div>

    </div>


    <script>

        // ==========================================
        // FOTO DE PERFIL
        // ==========================================

        const seleccionarFoto =
            document.getElementById("seleccionarFoto");

        const fotoPerfil =
            document.getElementById("fotoPerfil");

        const textoFoto =
            document.getElementById("textoFoto");


        seleccionarFoto.addEventListener(
            "change",
            function () {

                const archivo = this.files[0];

                if (archivo) {

                    fotoPerfil.src =
                        URL.createObjectURL(archivo);

                    fotoPerfil.style.display =
                        "block";

                    textoFoto.style.display =
                        "none";

                }

            }
        );


        // ==========================================
        // VALIDACIÓN DE CONTRASEÑA
        // ==========================================

        const formulario =
            document.getElementById(
                "formularioRegistro"
            );

        const contrasena =
            document.getElementById(
                "contrasena"
            );

        const mensajeContrasena =
            document.getElementById(
                "mensajeContrasena"
            );


        contrasena.addEventListener(
            "input",
            function () {

                const password =
                    contrasena.value;

                let faltantes = [];


                if (password.length < 8) {

                    faltantes.push(
                        "mínimo 8 caracteres"
                    );

                }


                if (!/[A-Z]/.test(password)) {

                    faltantes.push(
                        "una letra mayúscula"
                    );

                }


                if (!/[a-z]/.test(password)) {

                    faltantes.push(
                        "una letra minúscula"
                    );

                }


                if (!/[0-9]/.test(password)) {

                    faltantes.push(
                        "un número"
                    );

                }


                if (
                    !/[!@#$%^&*(),.?":{}|<>_\-+=]/
                    .test(password)
                ) {

                    faltantes.push(
                        "un carácter especial"
                    );

                }


                if (password.length === 0) {

                    mensajeContrasena.textContent =
                        "";

                }

                else if (faltantes.length > 0) {

                    mensajeContrasena.textContent =
                        "La contraseña debe tener " +
                        faltantes.join(", ") +
                        ".";

                }

                else {

                    mensajeContrasena.textContent =
                        "La contraseña cumple con todos los requisitos.";

                }

            }
        );


        // ==========================================
        // VALIDACIÓN ANTES DE ENVIAR
        // ==========================================

        formulario.addEventListener(
            "submit",
            function (evento) {

                const password =
                    contrasena.value;


                const passwordValida =
                    password.length >= 8 &&
                    /[A-Z]/.test(password) &&
                    /[a-z]/.test(password) &&
                    /[0-9]/.test(password) &&
                    /[!@#$%^&*(),.?":{}|<>_\-+=]/
                        .test(password);


                if (!passwordValida) {

                    evento.preventDefault();

                    alert(
                        "La contraseña todavía no cumple con todos los requisitos."
                    );

                }

            }
        );

    </script>

</body>

</html>