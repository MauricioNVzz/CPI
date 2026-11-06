<?php

require_once __DIR__ . "/../models/curso.php";

class CursoController
{
    private $curso;

    public function __construct($conexion)
    {
        $this->curso = new Curso($conexion);
    }

    public function obtenerTodos()
    {
        return $this->curso->obtenerTodos();
    }

    public function obtenerPorId($id)
    {
        return $this->curso->obtenerPorId($id);
    }

    public function perteneceAMaestro($id, $maestroId)
{
    return $this->curso->perteneceAMaestro(
        $id,
        $maestroId
    );
}

    public function comprobarCategoria($categoria)
    {
        return $this->curso->comprobarCategoria($categoria);
    }

    public function comprobarInstructor($instructor)
    {
        return $this->curso->comprobarInstructor($instructor);
    }

    public function crearCurso(
        $categoria,
        $instructor,
        $titulo,
        $precio,
        $descripcion,
        $duracion,
        $imagen
    ) {
        return $this->curso->crearCurso(
            $categoria,
            $instructor,
            $titulo,
            $precio,
            $descripcion,
            $duracion,
            $imagen
        );
    }

    public function actualizarCurso(
        $id,
        $categoria,
        $instructor,
        $titulo,
        $precio,
        $descripcion,
        $duracion,
        $imagen
    ) {
        return $this->curso->actualizarCurso(
            $id,
            $categoria,
            $instructor,
            $titulo,
            $precio,
            $descripcion,
            $duracion,
            $imagen
        );
    }

    public function eliminarCurso($id)
    {
        return $this->curso->eliminarCurso($id);
    }

    // Cambiar el estado de un curso
public function cambiarEstadoCurso($id, $estado)
{
    return $this->curso->cambiarEstadoCurso($id, $estado);
}

    public function obtenerCategorias()
    {
        return $this->curso->obtenerCategorias();
    }

    public function obtenerPorMaestro($maestroId)
    {
        return $this->curso
            ->obtenerPorMaestro($maestroId);
    }

    public function actualizarImagen($id, $maestroId, $imagen)
{
    return $this->curso->actualizarImagen(
        $id,
        $maestroId,
        $imagen
    );
}
public function actualizarImagenAdmin($id, $imagen)
{
    return $this->curso->actualizarImagenAdmin(
        $id,
        $imagen
    );
}
}

?>