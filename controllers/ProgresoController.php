<?php

require_once __DIR__ . "/../models/Progreso.php";

class ProgresoController
{
    private $progreso;

    public function __construct($conexion)
    {
        $this->progreso = new Progreso($conexion);
    }

    public function completar(
        $usuarioId,
        $cursoId,
        $multimediaId
    ) {
        return $this->progreso->completar(
            $usuarioId,
            $cursoId,
            $multimediaId
        );
    }

    public function obtenerPorCurso(
        $usuarioId,
        $cursoId
    ) {
        return $this->progreso->obtenerPorCurso(
            $usuarioId,
            $cursoId
        );
    }

    // Comprobar si el curso está terminado
    public function cursoTerminado(
        $usuarioId,
        $cursoId
    ) {
        return $this->progreso->cursoTerminado(
            $usuarioId,
            $cursoId
        );
    }
}
?>
