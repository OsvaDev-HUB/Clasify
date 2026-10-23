<?php
// El apoderado ve a sus hijos y el curso actual de cada uno
require_once "../includes/sesion.php";
solo_para("apoderado");

// Hijos del apoderado. El curso es el del año abierto (si no tiene, queda vacío).
$hijos = mysqli_execute_query($conexion, "SELECT u.id_usuario, u.nombre, u.rut, al.estado,
                                          (SELECT CONCAT(c.nivel, '-', c.letra) FROM inscripcion i
                                           JOIN curso c ON c.id_curso = i.id_curso
                                           JOIN anio_academico a ON a.id_anio = c.id_anio
                                           WHERE i.id_alumno = al.id_alumno AND a.estado = 'abierto'
                                           ORDER BY a.anio ASC LIMIT 1) AS curso
                                          FROM alumno al
                                          JOIN usuario u ON u.id_usuario = al.id_alumno
                                          WHERE al.id_apoderado = ?
                                          ORDER BY u.nombre", [$id_usuario]);

$titulo = "Mis hijos";
include "../includes/cabecera.php";
?>

<div class="tabla-contenedor">
<table>
    <tr>
        <th>RUT</th>
        <th>Nombre</th>
        <th>Curso actual</th>
        <th></th>
    </tr>
    <?php while ($h = mysqli_fetch_assoc($hijos)) { ?>
        <tr>
            <td><?php echo limpiar($h["rut"]); ?></td>
            <td><?php echo limpiar($h["nombre"]); ?></td>
            <td>
                <?php
                if ($h["curso"] != null) {
                    // El curso viene como "nivel-letra", por ejemplo "9-A"
                    $partes = explode("-", $h["curso"]);
                    echo nombre_curso($partes[0], $partes[1]);
                } else {
                    echo '<span class="gris">Sin curso este año</span>';
                }
                ?>
            </td>
            <td><a href="notas_hijo.php?id_alumno=<?php echo $h["id_usuario"]; ?>" class="boton boton-chico">Ver notas</a></td>
        </tr>
    <?php } ?>
</table>
</div>

<?php include "../includes/pie.php"; ?>
