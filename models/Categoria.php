
<?php

class Categoria
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    // Obtener todas las categorías
    public function obtenerTodas()
    {
        $sql = "SELECT
                    c.Categoria_id,
                    c.Categoria_nombre,
                    COUNT(cu.Curso_id) AS total_cursos
                FROM categorias c
                LEFT JOIN cursos cu
                    ON c.Categoria_id = cu.Curso_id_cat
                GROUP BY
                    c.Categoria_id,
                    c.Categoria_nombre
                ORDER BY c.Categoria_nombre ASC";

        $resultado = $this->conexion->query($sql);

        return $resultado->fetch_all(MYSQLI_ASSOC);
    }

    // Comprobar si existe una categoría con ese nombre
    public function existeNombre($nombre, $excluirId = null)
    {
        if ($excluirId !== null) {
            $sql = "SELECT Categoria_id
                    FROM categorias
                    WHERE Categoria_nombre = ?
                    AND Categoria_id != ?
                    LIMIT 1";

            $stmt = $this->conexion->prepare($sql);
            $stmt->bind_param("si", $nombre, $excluirId);
        } else {
            $sql = "SELECT Categoria_id
                    FROM categorias
                    WHERE Categoria_nombre = ?
                    LIMIT 1";

            $stmt = $this->conexion->prepare($sql);
            $stmt->bind_param("s", $nombre);
        }

        $stmt->execute();

        $existe = $stmt->get_result()->num_rows > 0;

        $stmt->close();

        return $existe;
    }

    // Registrar categoría
    public function crear($nombre)
    {
        $sql = "INSERT INTO categorias (Categoria_nombre)
                VALUES (?)";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("s", $nombre);

        $resultado = $stmt->execute();

        $stmt->close();

        return $resultado;
    }

    // Actualizar categoría
    public function actualizar($id, $nombre)
    {
        $sql = "UPDATE categorias
                SET Categoria_nombre = ?
                WHERE Categoria_id = ?";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("si", $nombre, $id);

        $resultado = $stmt->execute();

        $stmt->close();

        return $resultado;
    }

    // Eliminar categoría
    public function eliminar($id)
    {
        $sql = "DELETE FROM categorias
                WHERE Categoria_id = ?";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $id);

        $resultado = $stmt->execute();

        $stmt->close();

        return $resultado;
    }
}
