
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================
// VALIDAR MÉTODO POST
// ==========================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

// ==========================================
// VALIDAR TOKEN CSRF
// ==========================================

$tokenRecibido = $_POST["csrf_token"] ?? "";

if (
    !isset($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $tokenRecibido)
) {
    http_response_code(403);
    exit("Solicitud no válida.");
}

// ==========================================
// ELIMINAR VARIABLES DE SESIÓN
// ==========================================

$_SESSION = [];

// ==========================================
// ELIMINAR COOKIE DE SESIÓN
// ==========================================

if (ini_get("session.use_cookies")) {

    $parametros = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $parametros["path"],
        $parametros["domain"],
        $parametros["secure"],
        $parametros["httponly"]
    );
}

// ==========================================
// DESTRUIR SESIÓN
// ==========================================

session_destroy();

// ==========================================
// REGRESAR AL LOGIN
// ==========================================

header("Location: index.php");
exit;

?>
