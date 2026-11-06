<?php

class Usuario
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    // ==========================================
    // OBTENER TODOS LOS USUARIOS CON FILTROS
    // ==========================================

    public function obtenerTodos(
        $busqueda = "",
        $filtroRol = "",
        $desde = "",
        $hasta = ""
    ) {
        $sql = "
            SELECT
                u.Usuario_id,
                u.Usuario_nombre,
                u.Usuario_email,
                u.Usuario_fechareg,
                u.Usuario_id_rol,
                u.Usuario_bloqueado,
                r.Rol_nombre
            FROM Usuarios u
            INNER JOIN Roles r
                ON u.Usuario_id_rol = r.Rol_id
            WHERE 1 = 1
        ";

        $tipos = "";
        $parametros = [];

        if ($busqueda !== "") {
            $sql .= "
                AND (
                    u.Usuario_nombre LIKE ?
                    OR u.Usuario_email LIKE ?
                )
            ";

            $buscar = "%" . $busqueda . "%";

            $tipos .= "ss";
            $parametros[] = $buscar;
            $parametros[] = $buscar;
        }

        if ($filtroRol !== "") {
            $sql .= " AND u.Usuario_id_rol = ?";

            $tipos .= "i";
            $parametros[] = (int) $filtroRol;
        }

        if ($desde !== "") {
            $sql .= " AND u.Usuario_fechareg >= ?";

            $tipos .= "s";
            $parametros[] = $desde;
        }

        if ($hasta !== "") {
            $sql .= "
                AND u.Usuario_fechareg
                < DATE_ADD(?, INTERVAL 1 DAY)
            ";

            $tipos .= "s";
            $parametros[] = $hasta;
        }

        $sql .= " ORDER BY u.Usuario_id DESC";

        $stmt = $this->conexion->prepare($sql);

        if (!empty($parametros)) {
            $stmt->bind_param($tipos, ...$parametros);
        }

        $stmt->execute();

        $resultado = $stmt->get_result();

        $usuarios = $resultado->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

        return $usuarios;
    }

    // ==========================================
    // OBTENER ROLES
    // ==========================================

    public function obtenerRoles()
    {
        $sql = "
            SELECT Rol_id, Rol_nombre
            FROM Roles
            ORDER BY Rol_id
        ";

        $resultado = $this->conexion->query($sql);

        return $resultado->fetch_all(MYSQLI_ASSOC);
    }

    // ==========================================
    // COMPROBAR SI EXISTE UN USUARIO
    // ==========================================

    public function existe($usuarioId)
    {
        $sql = "
            SELECT Usuario_id
            FROM Usuarios
            WHERE Usuario_id = ?
            LIMIT 1
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bind_param("i", $usuarioId);

        $stmt->execute();

        $existe = $stmt->get_result()->num_rows > 0;

        $stmt->close();

        return $existe;
    }

    // ==========================================
    // COMPROBAR SI EXISTE UN ROL
    // ==========================================

    public function existeRol($rolId)
    {
        $sql = "
            SELECT Rol_id
            FROM Roles
            WHERE Rol_id = ?
            LIMIT 1
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bind_param("i", $rolId);

        $stmt->execute();

        $existe = $stmt->get_result()->num_rows > 0;

        $stmt->close();

        return $existe;
    }

    // ==========================================
    // CAMBIAR ROL
    // ==========================================

    public function cambiarRol($usuarioId, $rolId)
    {
        $sql = "
            UPDATE Usuarios
            SET Usuario_id_rol = ?
            WHERE Usuario_id = ?
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bind_param(
            "ii",
            $rolId,
            $usuarioId
        );

        $resultado = $stmt->execute();

        $stmt->close();

        return $resultado;
    }

    // ==========================================
    // BLOQUEAR USUARIO
    // ==========================================

    public function bloquear($usuarioId)
    {
        $sql = "
            UPDATE Usuarios
            SET Usuario_bloqueado = 1
            WHERE Usuario_id = ?
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bind_param("i", $usuarioId);

        $resultado = $stmt->execute();

        $stmt->close();

        return $resultado;
    }

    // ==========================================
    // DESBLOQUEAR USUARIO
    // ==========================================

    public function desbloquear($usuarioId)
    {
        $sql = "
            UPDATE Usuarios
            SET
                Usuario_bloqueado = 0,
                Usuario_intentos_fallidos = 0
            WHERE Usuario_id = ?
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bind_param("i", $usuarioId);

        $resultado = $stmt->execute();

        $stmt->close();

        return $resultado;
    }
}