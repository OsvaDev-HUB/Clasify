<?php
// El alumno ve sus notas por año y semestre, también las de años anteriores (RF-14)
require_once "../includes/sesion.php";
solo_para("alumno");

// Todos los años en que el alumno ha estado matriculado
$inscripciones = array();
$resultado = mysqli_execute_query($conexion, "SELECT i.id_inscripcion, c.nivel, c.letra, a.anio
                                              FROM inscripcion i
                                              JOIN curso c ON c.id_curso = i.id_curso
                                              JOIN anio_academico a ON a.id_anio = c.id_anio
                                              WHERE i.id_alumno = ?
                                              ORDER BY a.anio DESC", [$id_usuario]);
while ($fila = mysqli_fetch_assoc($resultado)) {
    $inscripciones[] = $fila;
}

// Año elegido. Solo se acepta si es una inscripción del mismo alumno.
$id_inscripcion = 0;
$elegida = null;
if (count($inscripciones) > 0) {
    $elegida = $inscripciones[0];   // por defecto el año más reciente
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

$titulo = "Mis notas";
include "../includes/cabecera.php";
?>

<?php if (!$elegida) { ?>
    <p class="gris">Todavía no estás matriculado en ningún curso.</p>
<?php } else { ?>

    <form method="get" class="filtros">
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

<?php include "../includes/pie.php"; ?>
