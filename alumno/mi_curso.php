<?php
// El alumno ve su curso actual, su profesor jefe y sus profesores (RF-10)
require_once "../includes/sesion.php";
solo_para("alumno");

// Curso del alumno en el año abierto
$resultado = mysqli_execute_query($conexion, "SELECT c.id_curso, c.nivel, c.letra, a.anio, u.nombre AS profesor_jefe
                                              FROM inscripcion i
                                              JOIN curso c ON c.id_curso = i.id_curso
                                              JOIN anio_academico a ON a.id_anio = c.id_anio
                                              JOIN usuario u ON u.id_usuario = c.id_profesor_jefe
                                              WHERE i.id_alumno = ? AND a.estado = 'abierto'
                                              ORDER BY a.anio ASC LIMIT 1", [$id_usuario]);
$curso = mysqli_fetch_assoc($resultado);

$titulo = "Mi curso";
include "../includes/cabecera.php";
?>

<?php if (!$curso) { ?>
    <p class="gris">No estás matriculado en un curso este año.</p>
<?php } else { ?>
    <?php
    $asignaturas = mysqli_execute_query($conexion, "SELECT s.nombre AS asignatura, u.nombre AS profesor
                                                    FROM asignacion_docente ad
                                                    JOIN asignatura s ON s.id_asignatura = ad.id_asignatura
                                                    JOIN usuario u ON u.id_usuario = ad.id_profesor
                                                    WHERE ad.id_curso = ?
                                                    ORDER BY s.nombre", [$curso["id_curso"]]);
    ?>
    <p>
        <strong>Curso:</strong> <?php echo nombre_curso($curso["nivel"], $curso["letra"]); ?><br>
        <strong>Año:</strong> <?php echo $curso["anio"]; ?><br>
        <strong>Profesor jefe:</strong> <?php echo limpiar($curso["profesor_jefe"]); ?>
    </p>

    <h2>Asignaturas y profesores</h2>
    <div class="tabla-contenedor">
    <table>
        <tr>
            <th>Asignatura</th>
            <th>Profesor</th>
        </tr>
        <?php while ($fila = mysqli_fetch_assoc($asignaturas)) { ?>
            <tr>
                <td><?php echo limpiar($fila["asignatura"]); ?></td>
                <td><?php echo limpiar($fila["profesor"]); ?></td>
            </tr>
        <?php } ?>
    </table>
    </div>
<?php } ?>

<?php include "../includes/pie.php"; ?>
