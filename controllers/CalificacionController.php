<?php

require_once __DIR__ . "/../models/Calificacion.php";

class CalificacionController
{
    private $calificacion;

    public function __construct($conexion)
    {
        $this->calificacion = new Calificacion($conexion);
    }

    // Registrar o actualizar una calificación.
    public function guardar($usuarioId, $cursoId, $valor)
    {
        $usuarioId = (int) $usuarioId;
        $cursoId = (int) $cursoId;
        $valor = filter_var($valor, FILTER_VALIDATE_INT);

        if ($usuarioId <= 0 || $cursoId <= 0) {
            throw new InvalidArgumentException(
                "El usuario o el curso no son válidos."
            );
        }

        if ($valor === false || $valor < 1 || $valor > 10) {
            throw new InvalidArgumentException(
                "La calificación debe ser un número entero entre 1 y 10."
            );
        }

        return $this->calificacion->guardar(
            $usuarioId,
            $cursoId,
            $valor
        );
    }

    // Consultar la calificación de un alumno.
    public function obtenerPorUsuarioCurso($usuarioId, $cursoId)
    {
        $usuarioId = (int) $usuarioId;
        $cursoId = (int) $cursoId;

        if ($usuarioId <= 0 || $cursoId <= 0) {
            throw new InvalidArgumentException(
                "El usuario o el curso no son válidos."
            );
        }

        return $this->calificacion->obtenerPorUsuarioCurso(
            $usuarioId,
            $cursoId
        );
    }

    // Consultar promedio y cantidad de calificaciones.
    public function obtenerPromedio($cursoId)
    {
        $cursoId = (int) $cursoId;

        if ($cursoId <= 0) {
            throw new InvalidArgumentException(
                "El curso no es válido."
            );
        }

        return $this->calificacion->obtenerPromedio($cursoId);
    }
}
