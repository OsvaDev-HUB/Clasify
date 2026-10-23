<?php
// Modificar una nota ya ingresada (RF-12)
require_once "../includes/sesion.php";
solo_para("profesor");

$id_nota = 0;
if (isset($_GET["id_nota"])) {
    $id_nota = intval($_GET["id_nota"]);
}

// Se busca la nota, pero solo si es de una asignatura que hace este profesor en ese curso
$resultado = mysqli_execute_query($conexion, "SELECT n.*, u.nombre AS alumno, s.nombre AS asignatura, c.nivel, c.letra,
                                              c.estado AS estado_curso, ad.id_asignacion
                                              FROM nota n
                                              JOIN inscripcion i ON i.id_inscripcion = n.id_inscripcion
                                              JOIN curso c ON c.id_curso = i.id_curso
                                              JOIN asignacion_docente ad ON ad.id_curso = c.id_curso AND ad.id_asignatura = n.id_asignatura
                                              JOIN usuario u ON u.id_usuario = i.id_alumno
                                              JOIN asignatura s ON s.id_asignatura = n.id_asignatura
                                              WHERE n.id_nota = ? AND ad.id_profesor = ?", [$id_nota, $id_usuario]);
$nota = mysqli_fetch_assoc($resultado);

if (!$nota) {
    guardar_mensaje("La nota no existe o no es de tus asignaturas.", "error");
    header("Location: notas.php");
    exit;
}

$volver = "notas.php?id_asignacion=" . $nota["id_asignacion"] . "&semestre=" . $nota["semestre"];

$errores = array();
$valor_escrito = mostrar_nota($nota["valor"]);
$fecha = $nota["fecha"];
$descripcion = $nota["descripcion"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($nota["estado_curso"] != "abierto") {
        $errores[] = "El curso ya está cerrado, no se pueden cambiar las notas.";
    } elseif (isset($_POST["eliminar"])) {
        // Por si la nota se ingresó a un alumno equivocado
        mysqli_execute_query($conexion, "DELETE FROM nota WHERE id_nota = ?", [$id_nota]);
        guardar_mensaje("Se eliminó la nota.", "exito");
        header("Location: " . $volver);
        exit;
    } else {
        $valor_escrito = $_POST["valor"];
        $fecha = $_POST["fecha"];
        $descripcion = trim($_POST["descripcion"]);

        $valor = convertir_nota($valor_escrito);
        if ($valor === null) {
            $errores[] = "La nota debe estar entre 1,0 y 7,0.";
        }
        if ($fecha == "") {
            $errores[] = "Debes indicar la fecha.";
        }
        if ($descripcion == "") {
            $errores[] = "Debes escribir una descripción.";
        }

        if (count($errores) == 0) {
            mysqli_execute_query($conexion, "UPDATE nota SET valor = ?, fecha = ?, descripcion = ? WHERE id_nota = ?",
                [$valor, $fecha, $descripcion, $id_nota]);
            guardar_mensaje("La nota de " . $nota["alumno"] . " se modificó.", "exito");
            header("Location: " . $volver);
            exit;
        }
    }
}

$titulo = "Modificar nota";
include "../includes/cabecera.php";
?>

<p>
    <strong>Alumno:</strong> <?php echo limpiar($nota["alumno"]); ?><br>
    <strong>Curso:</strong> <?php echo nombre_curso($nota["nivel"], $nota["letra"]); ?><br>
    <strong>Asignatura:</strong> <?php echo limpiar($nota["asignatura"]); ?><br>
    <strong>Semestre:</strong> <?php echo $nota["semestre"]; ?>
</p>

<?php foreach ($errores as $e) { ?>
    <div class="mensaje error"><?php echo limpiar($e); ?></div>
<?php } ?>

<form method="post" class="formulario">
    <label>Nota</label>
    <input type="text" name="valor" class="nota" value="<?php echo limpiar($valor_escrito); ?>">

    <label>Fecha</label>
    <input type="date" name="fecha" value="<?php echo limpiar($fecha); ?>">

    <label>Descripción</label>
    <input type="text" name="descripcion" value="<?php echo limpiar($descripcion); ?>">

    <br>
    <button type="submit" class="boton">Guardar cambios</button>
    <a href="<?php echo $volver; ?>" class="boton boton-gris">Volver</a>
    <button type="submit" name="eliminar" value="1" class="boton boton-gris" onclick="return confirm('¿Seguro que quieres eliminar esta nota?');">Eliminar nota</button>
</form>

<?php include "../includes/pie.php"; ?>
