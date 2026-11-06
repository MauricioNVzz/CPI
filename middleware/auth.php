<?php

function verificarAcceso(array $rolesPermitidos = []): void
{
    // ==========================================
    // COMPROBAR SESIÓN
    // ==========================================

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    // ==========================================
    // COMPROBAR AUTENTICACIÓN
    // ==========================================

    if (
        !isset($_SESSION["usuario_id"]) ||
        !isset($_SESSION["usuario_rol"]) ||
        (int) $_SESSION["usuario_id"] <= 0 ||
        (int) $_SESSION["usuario_rol"] <= 0
    ) {
        header("Location: /CPI/index.php");
        exit;
    }

    // ==========================================
    // COMPROBAR AUTORIZACIÓN
    // ==========================================

    if (!empty($rolesPermitidos)) {

        $rolUsuario = (int) $_SESSION["usuario_rol"];

        if (!in_array($rolUsuario, $rolesPermitidos, true)) {

            http_response_code(403);

            exit(
                "Acceso denegado. No tienes permisos para entrar a esta página."
            );
        }
    }
}
