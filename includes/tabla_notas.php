<?php
// Tabla con las notas y promedios de una inscripción (un alumno en un curso de un año).
// La usan "Mis notas" del alumno y "Notas de mi hijo" del apoderado (RF-13, RF-14 y RF-15).
// Antes de incluir este archivo hay que tener la variable $id_inscripcion.

// Asignaturas del curso, o asignaturas donde el alumno tiene notas
$asignaturas = mysqli_execute_query($conexion, "SELECT s.* FROM asignatura s
                                                WHERE s.id_asignatura IN (SELECT ad.id_asignatura FROM asignacion_docente ad
                                                                          JOIN inscripcion i ON i.id_curso = ad.id_curso
                                                                          WHERE i.id_inscripcion = ?)
                                                OR s.id_asignatura IN (SELECT n.id_asignatura FROM nota n WHERE n.id_inscripcion = ?)
                                                ORDER BY s.nombre", [$id_inscripcion, $id_inscripcion]);

$promedios_anuales = array();   // solo de las asignaturas que influyen en la promoción
?>

<div class="tabla-contenedor">
<table>
    <tr>
        <th>Asignatura</th>
        <th>Notas 1° semestre</th>
        <th class="centro">Prom. 1°</th>
        <th>Notas 2° semestre</th>
        <th class="centro">Prom. 2°</th>
        <th class="centro">Promedio anual</th>
    </tr>
    <?php while ($s = mysqli_fetch_assoc($asignaturas)) { ?>
        <?php
        $promedios_semestre = array();
        ?>
        <tr>
            <td>
                <?php echo limpiar($s["nombre"]); ?>
                <?php if ($s["influye_promocion"] == 0) { ?>
                    <br><span class="ayuda">No influye en la promoción</span>
                <?php } ?>
            </td>

            <?php for ($sem = 1; $sem <= 2; $sem++) { ?>
                <?php
                $resultado = mysqli_execute_query($conexion, "SELECT valor, descripcion FROM nota
                                                              WHERE id_inscripcion = ? AND id_asignatura = ? AND semestre = ?
                                                              ORDER BY fecha, id_nota", [$id_inscripcion, $s["id_asignatura"], $sem]);
                $valores = array();
                ?>
                <td>
                    <?php
                    while ($n = mysqli_fetch_assoc($resultado)) {
                        $valores[] = $n["valor"];
                        $clase = "";
                        if ($n["valor"] < 4.0) {
                            $clase = "reprobada";
                        }
                        echo '<span class="' . $clase . '" title="' . limpiar($n["descripcion"]) . '">' . mostrar_nota($n["valor"]) . '</span> &nbsp; ';
                    }
                    if (count($valores) == 0) {
                        echo '<span class="gris">-</span>';
                    }
                    ?>
                </td>
                <td class="centro">
                    <?php
                    $prom = promedio($valores);
                    if ($prom !== null) {
                        $promedios_semestre[] = $prom;
                    }
                    echo mostrar_nota($prom);
                    ?>
                </td>
            <?php } ?>

            <td class="centro">
                <?php
                // El promedio anual es el promedio de los dos semestres
                $anual = promedio($promedios_semestre);
                if ($anual !== null && $s["influye_promocion"] == 1) {
                    $promedios_anuales[] = $anual;
                }
                if ($anual !== null && $anual < 4.0) {
                    echo '<strong class="reprobada">' . mostrar_nota($anual) . '</strong>';
                } else {
                    echo '<strong>' . mostrar_nota($anual) . '</strong>';
                }
                ?>
            </td>
        </tr>
    <?php } ?>
    <tr>
        <td colspan="5"><strong>Promedio general</strong> <span class="ayuda">(sin las asignaturas que no influyen en la promoción)</span></td>
        <td class="centro"><strong><?php echo mostrar_nota(promedio($promedios_anuales)); ?></strong></td>
    </tr>
</table>
</div>
<p class="ayuda">Las notas en rojo están bajo 4,0. Pasa el mouse sobre una nota para ver a qué evaluación corresponde.</p>
