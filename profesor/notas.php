<?php
// Registrar y ver las notas de una asignatura en un curso (RF-11 y RF-13).
// El profesor ingresa la nota de todo el curso en una sola pantalla (RNF-06).
require_once "../includes/sesion.php";
solo_para("profesor");

$id_asignacion = 0;
if (isset($_GET["id_asignacion"])) {
    $id_asignacion = intval($_GET["id_asignacion"]);
}

// Semestre que se está mirando. Si no viene, se calcula con el mes actual.
if (isset($_GET["semestre"]) && $_GET["semestre"] == "2") {
    $semestre = 2;
} elseif (isset($_GET["semestre"]) && $_GET["semestre"] == "1") {
    $semestre = 1;
} elseif (date("n") >= 7) {
    $semestre = 2;
} else {
    $semestre = 1;
}

// Se busca la asignación, pero solo si es de este profesor (así no puede ver cursos de otros)
$asignacion = null;
if ($id_asignacion > 0) {
    $resultado = mysqli_execute_query($conexion, "SELECT ad.*, c.nivel, c.letra, c.estado AS estado_curso, s.nombre AS asignatura
                                                  FROM asignacion_docente ad
                                                  JOIN curso c ON c.id_curso = ad.id_curso
                                                  JOIN asignatura s ON s.id_asignatura = ad.id_asignatura
                                                  WHERE ad.id_asignacion = ? AND ad.id_profesor = ?", [$id_asignacion, $id_usuario]);
    $asignacion = mysqli_fetch_assoc($resultado);
}

$errores = array();
$fecha = date("Y-m-d");
$descripcion = "";
$notas_escritas = array();

// ---- Guardar una evaluación nueva para todo el curso ----
if ($_SERVER["REQUEST_METHOD"] == "POST" && $asignacion) {
    $fecha = $_POST["fecha"];
    $descripcion = trim($_POST["descripcion"]);
    $semestre = intval($_POST["semestre"]);
    $notas_escritas = $_POST["nota"];   // arreglo: id_inscripcion => nota escrita

    if ($asignacion["estado_curso"] != "abierto") {
        $errores[] = "El curso ya está cerrado, no se pueden agregar notas.";
    }
    if ($fecha == "") {
        $errores[] = "Debes indicar la fecha.";
    }
    if ($descripcion == "") {
        $errores[] = "Debes escribir una descripción (por ejemplo: Prueba 1).";
    }
    if ($semestre != 1 && $semestre != 2) {
        $errores[] = "El semestre debe ser 1 o 2.";
    }

    // Se revisan todas las notas antes de guardar
    $notas_para_guardar = array();
    foreach ($notas_escritas as $id_inscripcion => $texto) {
        if (trim($texto) == "") {
            continue;   // si está vacía, ese alumno no tiene nota en esta evaluación
        }
        $valor = convertir_nota($texto);
        if ($valor === null) {
            $errores[] = "La nota \"" . $texto . "\" no es válida. Debe estar entre 1,0 y 7,0.";
        } else {
            $notas_para_guardar[intval($id_inscripcion)] = $valor;
        }
    }
    if (count($notas_para_guardar) == 0 && count($errores) == 0) {
        $errores[] = "No escribiste ninguna nota.";
    }

    if (count($errores) == 0) {
        $guardadas = 0;
        foreach ($notas_para_guardar as $id_inscripcion => $valor) {
            // Se revisa que la inscripción sea de este curso
            $resultado = mysqli_execute_query($conexion, "SELECT id_inscripcion FROM inscripcion WHERE id_inscripcion = ? AND id_curso = ?",
                [$id_inscripcion, $asignacion["id_curso"]]);
            if (mysqli_num_rows($resultado) > 0) {
                mysqli_execute_query($conexion, "INSERT INTO nota (id_inscripcion, id_asignatura, valor, fecha, descripcion, semestre)
                                                 VALUES (?, ?, ?, ?, ?, ?)",
                    [$id_inscripcion, $asignacion["id_asignatura"], $valor, $fecha, $descripcion, $semestre]);
                $guardadas++;
            }
        }
        guardar_mensaje("Se guardaron " . $guardadas . " notas de \"" . $descripcion . "\".", "exito");
        header("Location: notas.php?id_asignacion=" . $id_asignacion . "&semestre=" . $semestre);
        exit;
    }
}

$titulo = "Notas";
include "../includes/cabecera.php";

// ---- Si no eligió asignatura, se muestra la lista para elegir ----
if (!$asignacion) {
    $anio = anio_abierto($conexion);
    $id_anio = 0;
    if ($anio) {
        $id_anio = $anio["id_anio"];
    }
    $clases = mysqli_execute_query($conexion, "SELECT ad.id_asignacion, c.nivel, c.letra, s.nombre AS asignatura
                                               FROM asignacion_docente ad
                                               JOIN curso c ON c.id_curso = ad.id_curso
                                               JOIN asignatura s ON s.id_asignatura = ad.id_asignatura
                                               WHERE ad.id_profesor = ? AND c.id_anio = ?
                                               ORDER BY c.nivel, c.letra, s.nombre", [$id_usuario, $id_anio]);
    ?>
    <p>Elige el curso y la asignatura:</p>
    <ul>
        <?php while ($fila = mysqli_fetch_assoc($clases)) { ?>
            <li>
                <a href="notas.php?id_asignacion=<?php echo $fila["id_asignacion"]; ?>">
                    <?php echo nombre_curso($fila["nivel"], $fila["letra"]) . " - " . limpiar($fila["asignatura"]); ?>
                </a>
            </li>
        <?php } ?>
    </ul>
    <?php
    include "../includes/pie.php";
    exit;
}

// ---- Alumnos del curso ----
$alumnos = array();
$resultado = mysqli_execute_query($conexion, "SELECT i.id_inscripcion, u.nombre
                                              FROM inscripcion i
                                              JOIN usuario u ON u.id_usuario = i.id_alumno
                                              WHERE i.id_curso = ?
                                              ORDER BY u.nombre", [$asignacion["id_curso"]]);
while ($fila = mysqli_fetch_assoc($resultado)) {
    $alumnos[] = $fila;
}
?>

<h2><?php echo nombre_curso($asignacion["nivel"], $asignacion["letra"]) . " - " . limpiar($asignacion["asignatura"]); ?></h2>

<form method="get" class="filtros">
    <input type="hidden" name="id_asignacion" value="<?php echo $id_asignacion; ?>">
    Semestre:
    <select name="semestre">
        <option value="1" <?php if ($semestre == 1) echo "selected"; ?>>Primer semestre</option>
        <option value="2" <?php if ($semestre == 2) echo "selected"; ?>>Segundo semestre</option>
    </select>
    <button type="submit" class="boton boton-chico">Ver</button>
</form>

<?php foreach ($errores as $e) { ?>
    <div class="mensaje error"><?php echo limpiar($e); ?></div>
<?php } ?>

<!-- Notas que ya están registradas -->
<h2>Notas del <?php echo $semestre == 1 ? "primer" : "segundo"; ?> semestre</h2>
<p class="ayuda">Haz clic en una nota para modificarla. Las notas en rojo están bajo 4,0.</p>

<div class="tabla-contenedor">
<table>
    <tr>
        <th>N°</th>
        <th>Alumno</th>
        <th>Notas</th>
        <th class="centro">Promedio</th>
    </tr>
    <?php
    $numero = 1;
    foreach ($alumnos as $al) {
        $resultado = mysqli_execute_query($conexion, "SELECT * FROM nota WHERE id_inscripcion = ? AND id_asignatura = ? AND semestre = ?
                                                      ORDER BY fecha, id_nota",
            [$al["id_inscripcion"], $asignacion["id_asignatura"], $semestre]);
        $valores = array();
    ?>
        <tr>
            <td><?php echo $numero; ?></td>
            <td><?php echo limpiar($al["nombre"]); ?></td>
            <td>
                <?php
                while ($n = mysqli_fetch_assoc($resultado)) {
                    $valores[] = $n["valor"];
                    $clase = "";
                    if ($n["valor"] < 4.0) {
                        $clase = "reprobada";
                    }
                    echo '<a href="nota_editar.php?id_nota=' . $n["id_nota"] . '" class="' . $clase . '" title="' . limpiar($n["descripcion"]) . ' (' . date("d-m-Y", strtotime($n["fecha"])) . ')">'
                        . mostrar_nota($n["valor"]) . '</a> &nbsp; ';
                }
                if (count($valores) == 0) {
                    echo '<span class="gris">Sin notas</span>';
                }
                ?>
            </td>
            <td class="centro">
                <?php
                $prom = promedio($valores);
                if ($prom !== null && $prom < 4.0) {
                    echo '<span class="reprobada">' . mostrar_nota($prom) . '</span>';
                } else {
                    echo mostrar_nota($prom);
                }
                ?>
            </td>
        </tr>
    <?php
        $numero++;
    }
    ?>
</table>
</div>

<!-- Formulario para una evaluación nueva -->
<?php if ($asignacion["estado_curso"] == "abierto") { ?>
    <h2>Ingresar notas de una evaluación</h2>
    <form method="post">
        <div class="filtros">
            Fecha: <input type="date" name="fecha" value="<?php echo limpiar($fecha); ?>">
            &nbsp; Descripción: <input type="text" name="descripcion" placeholder="Ej: Prueba 2" value="<?php echo limpiar($descripcion); ?>">
            &nbsp; Semestre:
            <select name="semestre">
                <option value="1" <?php if ($semestre == 1) echo "selected"; ?>>1</option>
                <option value="2" <?php if ($semestre == 2) echo "selected"; ?>>2</option>
            </select>
        </div>

        <div class="tabla-contenedor">
        <table>
            <tr>
                <th>N°</th>
                <th>Alumno</th>
                <th>Nota</th>
            </tr>
            <?php
            $numero = 1;
            foreach ($alumnos as $al) {
                $escrita = "";
                if (isset($notas_escritas[$al["id_inscripcion"]])) {
                    $escrita = $notas_escritas[$al["id_inscripcion"]];
                }
            ?>
                <tr>
                    <td><?php echo $numero; ?></td>
                    <td><?php echo limpiar($al["nombre"]); ?></td>
                    <td><input type="text" class="nota" name="nota[<?php echo $al["id_inscripcion"]; ?>]" value="<?php echo limpiar($escrita); ?>" placeholder="1,0 - 7,0"></td>
                </tr>
            <?php
                $numero++;
            }
            ?>
        </table>
        </div>
        <p class="ayuda">Si un alumno no rindió la evaluación, deja su nota en blanco.</p>
        <button type="submit" class="boton">Guardar notas</button>
    </form>
<?php } else { ?>
    <p class="gris">Este curso ya está cerrado. Las notas solo se pueden consultar.</p>
<?php } ?>

<?php include "../includes/pie.php"; ?>
