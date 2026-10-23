<?php
// Cursos del profesor en el año abierto (RF-10)
require_once "../includes/sesion.php";
solo_para("profesor");

$anio = anio_abierto($conexion);
$id_anio = 0;
if ($anio) {
    $id_anio = $anio["id_anio"];
}

// Cursos donde es profesor jefe
$jefaturas = mysqli_execute_query($conexion, "SELECT c.*, (SELECT COUNT(*) FROM inscripcion i WHERE i.id_curso = c.id_curso) AS alumnos
                                              FROM curso c
                                              WHERE c.id_profesor_jefe = ? AND c.id_anio = ?
                                              ORDER BY c.nivel, c.letra", [$id_usuario, $id_anio]);

// Asignaturas que hace en cada curso
$clases = mysqli_execute_query($conexion, "SELECT ad.id_asignacion, c.nivel, c.letra, s.nombre AS asignatura,
                                           (SELECT COUNT(*) FROM inscripcion i WHERE i.id_curso = c.id_curso) AS alumnos
                                           FROM asignacion_docente ad
                                           JOIN curso c ON c.id_curso = ad.id_curso
                                           JOIN asignatura s ON s.id_asignatura = ad.id_asignatura
                                           WHERE ad.id_profesor = ? AND c.id_anio = ?
                                           ORDER BY c.nivel, c.letra, s.nombre", [$id_usuario, $id_anio]);

$titulo = "Mis cursos";
include "../includes/cabecera.php";
?>

<h2>Profesor jefe de</h2>
<?php if (mysqli_num_rows($jefaturas) == 0) { ?>
    <p class="gris">No eres profesor jefe de ningún curso este año.</p>
<?php } else { ?>
    <ul>
        <?php while ($c = mysqli_fetch_assoc($jefaturas)) { ?>
            <li><?php echo nombre_curso($c["nivel"], $c["letra"]); ?> (<?php echo $c["alumnos"]; ?> alumnos)</li>
        <?php } ?>
    </ul>
<?php } ?>

<h2>Asignaturas que haces</h2>
<div class="tabla-contenedor">
<table>
    <tr>
        <th>Curso</th>
        <th>Asignatura</th>
        <th class="centro">Alumnos</th>
        <th></th>
    </tr>
    <?php while ($fila = mysqli_fetch_assoc($clases)) { ?>
        <tr>
            <td><?php echo nombre_curso($fila["nivel"], $fila["letra"]); ?></td>
            <td><?php echo limpiar($fila["asignatura"]); ?></td>
            <td class="centro"><?php echo $fila["alumnos"]; ?></td>
            <td><a href="notas.php?id_asignacion=<?php echo $fila["id_asignacion"]; ?>" class="boton boton-chico">Notas</a></td>
        </tr>
    <?php } ?>
</table>
</div>

<?php include "../includes/pie.php"; ?>
