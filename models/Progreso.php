<?php

class Progreso
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    // Registrar un contenido como completado
    public function completar(
        $usuarioId,
        $cursoId,
        $multimediaId
    ) {
        $sql = "
            INSERT INTO progreso (
                Progreso_id_usuario,
                Progreso_id_curso,
                Progreso_id_multimedia,
                Progreso_completado
            )
            VALUES (?, ?, ?, 1)

            ON DUPLICATE KEY UPDATE
                Progreso_completado = 1,
                Progreso_fecha = CURRENT_TIMESTAMP
        ";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                "Error al preparar el registro de progreso."
            );
        }

        $stmt->bind_param(
            "iii",
            $usuarioId,
            $cursoId,
            $multimediaId
        );

        if (!$stmt->execute()) {
            throw new Exception(
                "No se pudo registrar el progreso."
            );
        }

        $stmt->close();

        return true;
    }

    // Obtener contenidos completados por el alumno
    public function obtenerPorCurso(
        $usuarioId,
        $cursoId
    ) {
        $sql = "
            SELECT Progreso_id_multimedia
            FROM progreso
            WHERE Progreso_id_usuario = ?
              AND Progreso_id_curso = ?
              AND Progreso_completado = 1
        ";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                "Error al consultar el progreso."
            );
        }

        $stmt->bind_param(
            "ii",
            $usuarioId,
            $cursoId
        );

        $stmt->execute();

        $resultado = $stmt->get_result();

        $completados = [];

        while ($fila = $resultado->fetch_assoc()) {
            $completados[] = (int)$fila["Progreso_id_multimedia"];
        }

        $stmt->close();

        return $completados;
    }

    // Comprobar si el alumno terminó todos los contenidos
    public function cursoTerminado(
        $usuarioId,
        $cursoId
    ) {
        $sql = "
            SELECT
                COUNT(DISTINCT m.Multimedia_id) AS total,
                COUNT(DISTINCT CASE
                    WHEN p.Progreso_completado = 1
                    THEN m.Multimedia_id
                END) AS completados
            FROM niveles n
            INNER JOIN multimedia m
                ON m.Multimedia_id_nivel = n.Nivel_id
            LEFT JOIN progreso p
                ON p.Progreso_id_multimedia = m.Multimedia_id
                AND p.Progreso_id_usuario = ?
                AND p.Progreso_id_curso = ?
            WHERE n.Nivel_id_curso = ?
        ";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                "Error al comprobar la finalización del curso."
            );
        }

        $stmt->bind_param(
            "iii",
            $usuarioId,
            $cursoId,
            $cursoId
        );

        $stmt->execute();

        $resultado = $stmt->get_result();
        $datos = $resultado->fetch_assoc();

        $stmt->close();

        $total = (int)$datos["total"];
        $completados = (int)$datos["completados"];

        return $total > 0 && $total === $completados;
    }
}
?>
