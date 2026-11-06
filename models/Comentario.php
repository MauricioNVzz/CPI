<?php

class Comentario
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    
public function obtenerPorMultimedia($multimediaId)
{
    $sql = "
        SELECT
            c.Comentario_id,
            c.Comentario_contenido,
            c.Comentario_fecha,
            u.Usuario_id,
            u.Usuario_nombre,
            u.Usuario_foto,
            0 AS Comentario_eliminado
        FROM Comentarios c
        INNER JOIN Usuarios u
            ON c.Comentario_id_usuario = u.Usuario_id
        WHERE c.Comentario_id_multimedia = ?

        UNION ALL

        SELECT
            ce.Comentario_id,
            'Este comentario fue eliminado por incumplir las normas de la plataforma.' AS Comentario_contenido,
            ce.Comentario_fecha,
            ce.Comentario_id_usuario AS Usuario_id,
            u.Usuario_nombre,
            u.Usuario_foto,
            1 AS Comentario_eliminado
        FROM comentarios_eliminados ce
        INNER JOIN Usuarios u
            ON ce.Comentario_id_usuario = u.Usuario_id
        WHERE ce.Comentario_id_multimedia = ?

        ORDER BY Comentario_fecha DESC
    ";

    $stmt = $this->conexion->prepare($sql);

    if (!$stmt) {
        throw new Exception(
            "No se pudo preparar la consulta de comentarios."
        );
    }

    $stmt->bind_param(
        "ii",
        $multimediaId,
        $multimediaId
    );

    $stmt->execute();

    $resultado = $stmt->get_result();
    $comentarios = $resultado->fetch_all(MYSQLI_ASSOC);

    $stmt->close();

    return $comentarios;
}


    public function crear($usuarioId, $multimediaId, $contenido)
    {
        $contenido = trim($contenido);

        if ($contenido === "") {
            throw new InvalidArgumentException(
                "Escribe un comentario antes de enviarlo."
            );
        }

        if (mb_strlen($contenido) > 2000) {
            throw new InvalidArgumentException(
                "El comentario no puede superar los 2000 caracteres."
            );
        }

        $sql = "
            INSERT INTO Comentarios (
                Comentario_id_usuario,
                Comentario_id_multimedia,
                Comentario_contenido
            )
            VALUES (?, ?, ?)
        ";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                "No se pudo preparar el registro del comentario."
            );
        }

        $stmt->bind_param(
            "iis",
            $usuarioId,
            $multimediaId,
            $contenido
        );

        $stmt->execute();

        $id = $stmt->insert_id;

        $stmt->close();

        return $id;
    }

    
public function obtenerTodos()
{
    $sql = "
        SELECT
            co.Comentario_id,
            co.Comentario_contenido,
            co.Comentario_fecha,
            u.Usuario_nombre,
            cu.Curso_nombre,
            m.Multimedia_titulo,
            m.Multimedia_tipo
        FROM comentarios co
        INNER JOIN usuarios u
            ON co.Comentario_id_usuario = u.Usuario_id
        INNER JOIN multimedia m
            ON co.Comentario_id_multimedia = m.Multimedia_id
        INNER JOIN cursos cu
            ON m.Multimedia_id_curso = cu.Curso_id
        ORDER BY co.Comentario_fecha DESC
    ";

    $resultado = $this->conexion->query($sql);

    return $resultado->fetch_all(MYSQLI_ASSOC);
}

    
    // Eliminar comentario y guardar auditoría
    public function eliminarConMotivo(
        $comentarioId,
        $adminId,
        $motivo
    ) {
        $this->conexion->begin_transaction();

        try {

            // Obtener comentario original
            $sql = "SELECT
                        Comentario_id,
                        Comentario_id_usuario,
                        Comentario_id_multimedia,
                        Comentario_contenido,
                        Comentario_fecha
                    FROM comentarios
                    WHERE Comentario_id = ?
                    FOR UPDATE";

            $stmt = $this->conexion->prepare($sql);
            $stmt->bind_param("i", $comentarioId);
            $stmt->execute();

            $comentario = $stmt->get_result()->fetch_assoc();

            $stmt->close();

            if (!$comentario) {
                throw new RuntimeException(
                    "El comentario no existe."
                );
            }

            // Guardar copia en la tabla de auditoría
            $sql = "INSERT INTO comentarios_eliminados (
                        Comentario_id,
                        Comentario_id_usuario,
                        Comentario_id_multimedia,
                        Comentario_contenido,
                        Comentario_fecha,
                        Eliminacion_id_admin,
                        Eliminacion_motivo
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->conexion->prepare($sql);

            $stmt->bind_param(
                "iiissis",
                $comentario["Comentario_id"],
                $comentario["Comentario_id_usuario"],
                $comentario["Comentario_id_multimedia"],
                $comentario["Comentario_contenido"],
                $comentario["Comentario_fecha"],
                $adminId,
                $motivo
            );

            $stmt->execute();
            $stmt->close();

            // Eliminar comentario original
            $sql = "DELETE FROM comentarios
                    WHERE Comentario_id = ?";

            $stmt = $this->conexion->prepare($sql);
            $stmt->bind_param("i", $comentarioId);
            $stmt->execute();

            $eliminados = $stmt->affected_rows;

            $stmt->close();

            if ($eliminados !== 1) {
                throw new RuntimeException(
                    "No fue posible eliminar el comentario."
                );
            }

            $this->conexion->commit();

            return true;

        } catch (Throwable $e) {

            $this->conexion->rollback();

            throw $e;
        }
    }

}
