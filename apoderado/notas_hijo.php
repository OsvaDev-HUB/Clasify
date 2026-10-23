<?php
// El apoderado ve las notas de su hijo (RF-15).
// Solo puede ver alumnos que tienen a este apoderado (RNF-02).
require_once "../includes/sesion.php";
solo_para("apoderado");

$id_alumno = 0;
if (isset($_GET["id_alumno"])) {
    $id_alumno = intval($_GET["id_alumno"]);
}

$resultado = mysqli_execute_query($conexion, "SELECT u.nombre FROM alumno al
                                              JOIN usuario u ON u.id_usuario = al.id_alumno
                                              WHERE al.id_alumno = ? AND al.id_apoderado = ?", [$id_alumno, $id_usuario]);
$hijo = mysqli_fetch_assoc($resultado);

if (!$hijo) {
    guardar_mensaje("Solo puedes ver las notas de tus propios hijos.", "error");
    header("Location: mis_hijos.php");
    exit;
}

// Años en que el hijo ha estado matriculado
$inscripciones = array();
$resultado = mysqli_execute_query($conexion, "SELECT i.id_inscripcion, c.nivel, c.letra, a.anio
                                              FROM inscripcion i
                                              JOIN curso c ON c.id_curso = i.id_curso
                                              JOIN anio_academico a ON a.id_anio = c.id_anio
                                              WHERE i.id_alumno = ?
                                              ORDER BY a.anio DESC", [$id_alumno]);
while ($fila = mysqli_fetch_assoc($resultado)) {
    $inscripciones[] = $fila;
}

$id_inscripcion = 0;
$elegida = null;
if (count($inscripciones) > 0) {
    $elegida = $inscripciones[0];
}
if (isset($_GET["id_inscripcion"])) {
    foreach ($inscripciones as $ins) {
        if ($ins["id_inscripcion"] == $_GET["id_inscripcion"]) {
            $elegida = $ins;
        }
    }
}
if ($elegida) {
    $id_inscripcion = $elegida["id_inscripcion"];
}

$titulo = "Notas de " . $hijo["nombre"];
include "../includes/cabecera.php";
?>

<?php if (!$elegida) { ?>
    <p class="gris">Tu hijo todavía no está matriculado en ningún curso.</p>
<?php } else { ?>

    <form method="get" class="filtros">
        <input type="hidden" name="id_alumno" value="<?php echo $id_alumno; ?>">
        Año:
        <select name="id_inscripcion">
            <?php foreach ($inscripciones as $ins) { ?>
                <option value="<?php echo $ins["id_inscripcion"]; ?>" <?php if ($ins["id_inscripcion"] == $id_inscripcion) echo "selected"; ?>>
                    <?php echo $ins["anio"] . " - " . nombre_curso($ins["nivel"], $ins["letra"]); ?>
                </option>
            <?php } ?>
        </select>
        <button type="submit" class="boton boton-chico">Ver</button>
    </form>

    <h2><?php echo nombre_curso($elegida["nivel"], $elegida["letra"]) . " - " . $elegida["anio"]; ?></h2>

    <?php include "../includes/tabla_notas.php"; ?>

<?php } ?>

<a href="mis_hijos.php" class="boton boton-gris">Volver</a>

<?php include "../includes/pie.php"; ?>
