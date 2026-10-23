<?php
// Asignar profesores a las asignaturas de un curso (RF-07). Solo para el administrativo.
require_once "../includes/sesion.php";
solo_para("administrativo");

$id_curso = 0;
if (isset($_GET["id_curso"])) {
    $id_curso = intval($_GET["id_curso"]);
}

// ---- Guardar los profesores de todas las asignaturas del curso ----
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_curso = intval($_POST["id_curso"]);
    $profesores_elegidos = $_POST["profesor"];   // arreglo: id_asignatura => id_profesor
    $no_se_quitaron = 0;

    foreach ($profesores_elegidos as $id_asignatura => $id_profesor) {
        $id_asignatura = intval($id_asignatura);
        $id_profesor = intval($id_profesor);

        // ¿Ya tenía un profesor esa asignatura en este curso?
        $resultado = mysqli_execute_query($conexion, "SELECT id_asignacion FROM asignacion_docente WHERE id_curso = ? AND id_asignatura = ?",
            [$id_curso, $id_asignatura]);
        $asignacion = mysqli_fetch_assoc($resultado);

        if ($id_profesor > 0) {
            if ($asignacion) {
                mysqli_execute_query($conexion, "UPDATE asignacion_docente SET id_profesor = ? WHERE id_asignacion = ?",
                    [$id_profesor, $asignacion["id_asignacion"]]);
            } else {
                mysqli_execute_query($conexion, "INSERT INTO asignacion_docente (id_curso, id_asignatura, id_profesor) VALUES (?, ?, ?)",
                    [$id_curso, $id_asignatura, $id_profesor]);
            }
        } elseif ($asignacion) {
            // Se quiere dejar sin profesor: solo se permite si todavía no hay notas
            $resultado = mysqli_execute_query($conexion, "SELECT COUNT(*) AS total FROM nota n
                                                          JOIN inscripcion i ON i.id_inscripcion = n.id_inscripcion
                                                          WHERE i.id_curso = ? AND n.id_asignatura = ?", [$id_curso, $id_asignatura]);
            $notas = mysqli_fetch_assoc($resultado);
            if ($notas["total"] == 0) {
                mysqli_execute_query($conexion, "DELETE FROM asignacion_docente WHERE id_asignacion = ?", [$asignacion["id_asignacion"]]);
            } else {
                $no_se_quitaron++;
            }
        }
    }

    if ($no_se_quitaron > 0) {
        guardar_mensaje("Se guardaron los cambios, pero hay asignaturas que ya tienen notas y no se pueden dejar sin profesor.", "error");
    } else {
        guardar_mensaje("Se guardaron los profesores del curso.", "exito");
    }
    header("Location: asignaciones.php?id_curso=" . $id_curso);
    exit;
}

// Cursos de los años abiertos, para el selector
$cursos = mysqli_query($conexion, "SELECT c.id_curso, c.nivel, c.letra, a.anio FROM curso c
                                   JOIN anio_academico a ON a.id_anio = c.id_anio
                                   WHERE a.estado = 'abierto' AND c.estado = 'abierto'
                                   ORDER BY a.anio DESC, c.nivel, c.letra");

$titulo = "Profesores por curso";
include "../includes/cabecera.php";
?>

<form method="get" class="filtros">
    Curso:
    <select name="id_curso">
        <option value="0">-- Elegir curso --</option>
        <?php while ($c = mysqli_fetch_assoc($cursos)) { ?>
            <option value="<?php echo $c["id_curso"]; ?>" <?php if ($c["id_curso"] == $id_curso) echo "selected"; ?>>
                <?php echo nombre_curso($c["nivel"], $c["letra"]) . " - " . $c["anio"]; ?>
            </option>
        <?php } ?>
    </select>
    <button type="submit" class="boton boton-chico">Ver</button>
</form>

<?php
if ($id_curso > 0) {
    $resultado = mysqli_execute_query($conexion, "SELECT * FROM curso WHERE id_curso = ? AND estado = 'abierto'", [$id_curso]);
    $curso = mysqli_fetch_assoc($resultado);
}

if ($id_curso > 0 && $curso) {
    // Profesores activos
    $profesores = array();
    $resultado = mysqli_query($conexion, "SELECT id_usuario, nombre FROM usuario WHERE rol = 'profesor' AND activo = 1 ORDER BY nombre");
    while ($p = mysqli_fetch_assoc($resultado)) {
        $profesores[] = $p;
    }

    // Todas las asignaturas con el profesor que tienen en este curso (si tienen)
    $asignaturas = mysqli_execute_query($conexion, "SELECT s.id_asignatura, s.nombre, ad.id_profesor
                                                    FROM asignatura s
                                                    LEFT JOIN asignacion_docente ad ON ad.id_asignatura = s.id_asignatura AND ad.id_curso = ?
                                                    ORDER BY s.nombre", [$id_curso]);
    ?>

    <h2><?php echo nombre_curso($curso["nivel"], $curso["letra"]); ?></h2>

    <form method="post">
        <input type="hidden" name="id_curso" value="<?php echo $id_curso; ?>">
        <div class="tabla-contenedor">
        <table>
            <tr>
                <th>Asignatura</th>
                <th>Profesor</th>
            </tr>
            <?php while ($s = mysqli_fetch_assoc($asignaturas)) { ?>
                <tr>
                    <td><?php echo limpiar($s["nombre"]); ?></td>
                    <td>
                        <select name="profesor[<?php echo $s["id_asignatura"]; ?>]">
                            <option value="0">-- Sin profesor --</option>
                            <?php foreach ($profesores as $p) { ?>
                                <option value="<?php echo $p["id_usuario"]; ?>" <?php if ($p["id_usuario"] == $s["id_profesor"]) echo "selected"; ?>>
                                    <?php echo limpiar($p["nombre"]); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </td>
                </tr>
            <?php } ?>
        </table>
        </div>
        <button type="submit" class="boton">Guardar profesores</button>
    </form>

<?php } ?>

<?php include "../includes/pie.php"; ?>
