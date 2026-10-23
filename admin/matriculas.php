<?php
// Matricular alumnos en un curso (RF-08). Solo para el administrativo.
require_once "../includes/sesion.php";
solo_para("administrativo");

$id_curso = 0;
if (isset($_GET["id_curso"])) {
    $id_curso = intval($_GET["id_curso"]);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = $_POST["accion"];
    $id_curso = intval($_POST["id_curso"]);

    // Datos del curso (tiene que estar abierto)
    $resultado = mysqli_execute_query($conexion, "SELECT * FROM curso WHERE id_curso = ? AND estado = 'abierto'", [$id_curso]);
    $curso = mysqli_fetch_assoc($resultado);

    // ---- Matricular ----
    if ($accion == "matricular" && $curso) {
        $id_alumno = intval($_POST["id_alumno"]);

        // Un alumno solo puede estar en un curso por año (RN-08)
        $resultado = mysqli_execute_query($conexion, "SELECT i.id_inscripcion FROM inscripcion i
                                                      JOIN curso c ON c.id_curso = i.id_curso
                                                      WHERE i.id_alumno = ? AND c.id_anio = ?", [$id_alumno, $curso["id_anio"]]);
        if ($id_alumno == 0) {
            guardar_mensaje("Debes elegir un alumno.", "error");
        } elseif (mysqli_num_rows($resultado) > 0) {
            guardar_mensaje("Ese alumno ya está matriculado en un curso de este año.", "error");
        } else {
            mysqli_execute_query($conexion, "INSERT INTO inscripcion (id_alumno, id_curso) VALUES (?, ?)", [$id_alumno, $id_curso]);
            guardar_mensaje("El alumno fue matriculado.", "exito");
        }
    }

    // ---- Quitar una matrícula (solo si el alumno no tiene notas en ese curso) ----
    if ($accion == "quitar" && $curso) {
        $id_inscripcion = intval($_POST["id_inscripcion"]);
        $resultado = mysqli_execute_query($conexion, "SELECT COUNT(*) AS total FROM nota WHERE id_inscripcion = ?", [$id_inscripcion]);
        $notas = mysqli_fetch_assoc($resultado);
        if ($notas["total"] > 0) {
            guardar_mensaje("No se puede quitar la matrícula porque el alumno ya tiene notas en este curso.", "error");
        } else {
            mysqli_execute_query($conexion, "DELETE FROM inscripcion WHERE id_inscripcion = ? AND id_curso = ?", [$id_inscripcion, $id_curso]);
            guardar_mensaje("Se quitó la matrícula.", "exito");
        }
    }

    header("Location: matriculas.php?id_curso=" . $id_curso);
    exit;
}

$cursos = mysqli_query($conexion, "SELECT c.id_curso, c.nivel, c.letra, a.anio FROM curso c
                                   JOIN anio_academico a ON a.id_anio = c.id_anio
                                   WHERE a.estado = 'abierto' AND c.estado = 'abierto'
                                   ORDER BY a.anio DESC, c.nivel, c.letra");

$titulo = "Matrículas";
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
$curso = null;
if ($id_curso > 0) {
    $resultado = mysqli_execute_query($conexion, "SELECT c.*, a.anio FROM curso c JOIN anio_academico a ON a.id_anio = c.id_anio
                                                  WHERE c.id_curso = ? AND c.estado = 'abierto'", [$id_curso]);
    $curso = mysqli_fetch_assoc($resultado);
}

if ($curso) {
    // Alumnos matriculados en el curso, con su apoderado
    $inscritos = mysqli_execute_query($conexion, "SELECT i.id_inscripcion, u.rut, u.nombre, ap.nombre AS apoderado
                                                  FROM inscripcion i
                                                  JOIN usuario u ON u.id_usuario = i.id_alumno
                                                  JOIN alumno al ON al.id_alumno = i.id_alumno
                                                  JOIN usuario ap ON ap.id_usuario = al.id_apoderado
                                                  WHERE i.id_curso = ?
                                                  ORDER BY u.nombre", [$id_curso]);

    // Alumnos activos que todavía no tienen curso este año
    $sin_curso = mysqli_execute_query($conexion, "SELECT u.id_usuario, u.nombre, u.rut
                                                  FROM alumno al
                                                  JOIN usuario u ON u.id_usuario = al.id_alumno
                                                  WHERE u.activo = 1 AND al.estado = 'regular'
                                                  AND al.id_alumno NOT IN (SELECT i.id_alumno FROM inscripcion i
                                                                           JOIN curso c ON c.id_curso = i.id_curso
                                                                           WHERE c.id_anio = ?)
                                                  ORDER BY u.nombre", [$curso["id_anio"]]);
    ?>

    <h2><?php echo nombre_curso($curso["nivel"], $curso["letra"]) . " - " . $curso["anio"]; ?></h2>

    <div class="tabla-contenedor">
    <table>
        <tr>
            <th>N°</th>
            <th>RUT</th>
            <th>Alumno</th>
            <th>Apoderado</th>
            <th></th>
        </tr>
        <?php
        $numero = 1;
        while ($fila = mysqli_fetch_assoc($inscritos)) {
        ?>
            <tr>
                <td><?php echo $numero; ?></td>
                <td><?php echo limpiar($fila["rut"]); ?></td>
                <td><?php echo limpiar($fila["nombre"]); ?></td>
                <td><?php echo limpiar($fila["apoderado"]); ?></td>
                <td>
                    <form method="post" style="display: inline;" onsubmit="return confirm('¿Quitar la matrícula de este alumno?');">
                        <input type="hidden" name="accion" value="quitar">
                        <input type="hidden" name="id_curso" value="<?php echo $id_curso; ?>">
                        <input type="hidden" name="id_inscripcion" value="<?php echo $fila["id_inscripcion"]; ?>">
                        <button type="submit" class="boton boton-chico boton-gris">Quitar</button>
                    </form>
                </td>
            </tr>
        <?php
            $numero++;
        }
        ?>
    </table>
    </div>

    <?php if ($numero == 1) { ?>
        <p class="gris">Todavía no hay alumnos matriculados en este curso.</p>
    <?php } ?>

    <h2>Matricular alumno</h2>
    <form method="post" class="formulario">
        <input type="hidden" name="accion" value="matricular">
        <input type="hidden" name="id_curso" value="<?php echo $id_curso; ?>">

        <label>Alumno</label>
        <select name="id_alumno">
            <option value="0">-- Elegir alumno --</option>
            <?php while ($a = mysqli_fetch_assoc($sin_curso)) { ?>
                <option value="<?php echo $a["id_usuario"]; ?>"><?php echo limpiar($a["nombre"]) . " (" . $a["rut"] . ")"; ?></option>
            <?php } ?>
        </select>
        <div class="ayuda">Solo aparecen los alumnos que todavía no tienen curso este año. Si el alumno no existe, créalo primero en Usuarios.</div>

        <button type="submit" class="boton">Matricular</button>
    </form>

<?php } ?>

<?php include "../includes/pie.php"; ?>
