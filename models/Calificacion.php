
<?php

class Calificacion
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    // Registrar o actualizar la calificación de un alumno.
    public function guardar($usuarioId, $cursoId, $valor)
    {
        $sql = "
            INSERT INTO Calificaciones (
                Calificacion_id_usuario,
                Calificacion_id_curso,
                Calificacion_valor
            )
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
                Calificacion_valor = VALUES(Calificacion_valor)
        ";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                "No se pudo preparar el registro de la calificación."
            );
        }

        $stmt->bind_param(
            "iii",
            $usuarioId,
            $cursoId,
            $valor
        );

        $stmt->execute();
        $stmt->close();

        return true;
    }

    // Obtener la calificación de un alumno para un curso.
    public function obtenerPorUsuarioCurso($usuarioId, $cursoId)
    {
        $sql = "
            SELECT
                Calificacion_id,
                Calificacion_valor,
                Calificacion_fecha,
                Calificacion_actualizacion
            FROM Calificaciones
            WHERE Calificacion_id_usuario = ?
            AND Calificacion_id_curso = ?
        ";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                "No se pudo consultar la calificación."
            );
        }

        $stmt->bind_param("ii", $usuarioId, $cursoId);
        $stmt->execute();

        $resultado = $stmt->get_result();
        $calificacion = $resultado->fetch_assoc();

        $stmt->close();

        return $calificacion;
    }

    // Obtener el promedio y cantidad de calificaciones de un curso.
    public function obtenerPromedio($cursoId)
    {
        $sql = "
            SELECT
                ROUND(AVG(Calificacion_valor), 2) AS promedio,
                COUNT(*) AS total_calificaciones
            FROM Calificaciones
            WHERE Calificacion_id_curso = ?
        ";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                "No se pudo calcular el promedio."
            );
        }

        $stmt->bind_param("i", $cursoId);
        $stmt->execute();

        $resultado = $stmt->get_result();
        $promedio = $resultado->fetch_assoc();

        $stmt->close();

        return $promedio;
    }
}
