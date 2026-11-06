<?php

require_once __DIR__ . "/../models/nivel.php";

class NivelController
{
    private $nivel;

    public function __construct($conexion)
    {
        $this->nivel = new Nivel($conexion);
    }

    public function obtenerPorCurso($cursoId)
    {
        return $this->nivel->obtenerPorCurso($cursoId);
    }

    public function crear(
    $cursoId,
    $nombre,
    $descripcion,
    $orden
) {
    return $this->nivel->crear(
        $cursoId,
        $nombre,
        $descripcion,
        $orden
    );
}

public function actualizar(
    $nivelId,
    $cursoId,
    $nombre,
    $descripcion,
    $orden
) {
    return $this->nivel->actualizar(
        $nivelId,
        $cursoId,
        $nombre,
        $descripcion,
        $orden
    );
}
}
?>