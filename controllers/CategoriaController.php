
<?php

require_once __DIR__ . "/../models/Categoria.php";

class CategoriaController
{
    private $categoria;

    public function __construct($conexion)
    {
        $this->categoria = new Categoria($conexion);
    }

    // Obtener categorías
    public function obtenerTodas()
    {
        return $this->categoria->obtenerTodas();
    }

    // Registrar categoría
    public function crear($nombre)
    {
        $nombre = trim($nombre);

        if ($nombre === "") {
            throw new InvalidArgumentException(
                "El nombre de la categoría es obligatorio."
            );
        }

        if (mb_strlen($nombre) > 100) {
            throw new InvalidArgumentException(
                "El nombre no puede superar los 100 caracteres."
            );
        }

        if ($this->categoria->existeNombre($nombre)) {
            throw new InvalidArgumentException(
                "Ya existe una categoría con ese nombre."
            );
        }

        return $this->categoria->crear($nombre);
    }

    // Actualizar categoría
    public function actualizar($id, $nombre)
    {
        $id = (int) $id;
        $nombre = trim($nombre);

        if ($id <= 0) {
            throw new InvalidArgumentException(
                "El identificador de la categoría no es válido."
            );
        }

        if ($nombre === "") {
            throw new InvalidArgumentException(
                "El nombre de la categoría es obligatorio."
            );
        }

        if (mb_strlen($nombre) > 100) {
            throw new InvalidArgumentException(
                "El nombre no puede superar los 100 caracteres."
            );
        }

        if ($this->categoria->existeNombre($nombre, $id)) {
            throw new InvalidArgumentException(
                "Ya existe otra categoría con ese nombre."
            );
        }

        return $this->categoria->actualizar($id, $nombre);
    }

    // Eliminar categoría
    public function eliminar($id)
    {
        $id = (int) $id;

        if ($id <= 0) {
            throw new InvalidArgumentException(
                "El identificador de la categoría no es válido."
            );
        }

        return $this->categoria->eliminar($id);
    }
}
