<?php

require_once __DIR__ . "/../models/Usuario.php";

class UsuarioController
{
    private $usuario;

    public function __construct($conexion)
    {
        $this->usuario = new Usuario($conexion);
    }

    // ==========================================
    // OBTENER USUARIOS
    // ==========================================

    public function obtenerTodos(
        $busqueda = "",
        $filtroRol = "",
        $desde = "",
        $hasta = ""
    ) {
        return $this->usuario->obtenerTodos(
            $busqueda,
            $filtroRol,
            $desde,
            $hasta
        );
    }

    // ==========================================
    // OBTENER ROLES
    // ==========================================

    public function obtenerRoles()
    {
        return $this->usuario->obtenerRoles();
    }

    // ==========================================
    // CAMBIAR ROL
    // ==========================================

    public function cambiarRol($usuarioId, $rolId)
    {
        $usuarioId = (int) $usuarioId;
        $rolId = (int) $rolId;

        if ($usuarioId <= 0 || $rolId <= 0) {
            throw new InvalidArgumentException(
                "El usuario o el rol no son válidos."
            );
        }

        if (!$this->usuario->existe($usuarioId)) {
            throw new InvalidArgumentException(
                "El usuario seleccionado no existe."
            );
        }

        if (!$this->usuario->existeRol($rolId)) {
            throw new InvalidArgumentException(
                "El rol seleccionado no existe."
            );
        }

        return $this->usuario->cambiarRol(
            $usuarioId,
            $rolId
        );
    }

    // ==========================================
    // BLOQUEAR USUARIO
    // ==========================================

    public function bloquear($usuarioId)
    {
        $usuarioId = (int) $usuarioId;

        if ($usuarioId <= 0) {
            throw new InvalidArgumentException(
                "El identificador del usuario no es válido."
            );
        }

        if (!$this->usuario->existe($usuarioId)) {
            throw new InvalidArgumentException(
                "El usuario seleccionado no existe."
            );
        }

        return $this->usuario->bloquear($usuarioId);
    }

    // ==========================================
    // DESBLOQUEAR USUARIO
    // ==========================================

    public function desbloquear($usuarioId)
    {
        $usuarioId = (int) $usuarioId;

        if ($usuarioId <= 0) {
            throw new InvalidArgumentException(
                "El identificador del usuario no es válido."
            );
        }

        if (!$this->usuario->existe($usuarioId)) {
            throw new InvalidArgumentException(
                "El usuario seleccionado no existe."
            );
        }

        return $this->usuario->desbloquear($usuarioId);
    }
}