<?php
// Años académicos y cursos (RF-05). Solo para el administrativo.
require_once "../includes/sesion.php";
solo_para("administrativo");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = $_POST["accion"];

    // ---- Crear un año académico ----
    if ($accion == "crear_anio") {
        $anio_nuevo = intval($_POST["anio"]);
        $resultado = mysqli_execute_query($conexion, "SELECT id_anio FROM anio_academico WHERE anio = ?", [$anio_nuevo]);
        if ($anio_nuevo < 2000 || $anio_nuevo > 2100) {
            guardar_mensaje("El año no es válido.", "error");
        } elseif (mysqli_num_rows($resultado) > 0) {
            guardar_mensaje("El año " . $anio_nuevo . " ya existe.", "error");
        } else {
            mysqli_execute_query($conexion, "INSERT INTO anio_academico (anio) VALUES (?)", [$anio_nuevo]);
            guardar_mensaje("Se creó el año " . $anio_nuevo . ".", "exito");
        }
        header("Location: anios_cursos.php");
        exit;
    }

    // ---- Crear un curso ----
    if ($accion == "crear_curso") {
        $id_anio = intval($_POST["id_anio"]);
        $nivel = intval($_POST["nivel"]);
        $letra = strtoupper(trim($_POST["letra"]));
        $id_jefe = intval($_POST["id_profesor_jefe"]);

        $resultado = mysqli_execute_query($conexion, "SELECT id_usuario FROM usuario WHERE id_usuario = ? AND rol = 'profesor' AND activo = 1", [$id_jefe]);
        $es_profesor = mysqli_num_rows($resultado) > 0;

        $resultado = mysqli_execute_query($conexion, "SELECT id_curso FROM curso WHERE id_anio = ? AND nivel = ? AND letra = ?", [$id_anio, $nivel, $letra]);
        $ya_existe = mysqli_num_rows($resultado) > 0;

        if ($nivel < 1 || $nivel > 12) {
            guardar_mensaje("Debes elegir un nivel.", "error");
        } elseif (strlen($letra) != 1 || !ctype_alpha($letra)) {
            guardar_mensaje("La letra del curso debe ser una sola letra (A, B, C...).", "error");
        } elseif (!$es_profesor) {
            guardar_mensaje("Debes elegir un profesor jefe.", "error");
        } elseif ($ya_existe) {
            guardar_mensaje("El curso " . nombre_curso($nivel, $letra) . " ya existe en ese año.", "error");
        } else {
            mysqli_execute_query($conexion, "INSERT INTO curso (id_anio, nivel, letra, id_profesor_jefe) VALUES (?, ?, ?, ?)",
                [$id_anio, $nivel, $letra, $id_jefe]);
            guardar_mensaje("Se creó el curso " . nombre_curso($nivel, $letra) . ".", "exito");
        }
        header("Location: anios_cursos.php?id_anio=" . $id_anio);
        exit;
    }

    // ---- Cambiar el profesor jefe de un curso ----
    if ($accion == "cambiar_jefe") {
        $id_curso = intval($_POST["id_curso"]);
        $id_jefe = intval($_POST["id_profesor_jefe"]);
        $id_anio = intval($_POST["id_anio"]);
        $resultado = mysqli_execute_query($conexion, "SELECT id_usuario FROM usuario WHERE id_usuario = ? AND rol = 'profesor'", [$id_jefe]);
        if (mysqli_num_rows($resultado) > 0) {
            mysqli_execute_query($conexion, "UPDATE curso SET id_profesor_jefe = ? WHERE id_curso = ? AND estado = 'abierto'", [$id_jefe, $id_curso]);
            guardar_mensaje("Se cambió el profesor jefe.", "exito");
        }
        header("Location: anios_cursos.php?id_anio=" . $id_anio);
        exit;
    }
}

// Año que se está mirando: el que viene en la dirección o el año abierto
if (isset($_GET["id_anio"])) {
    $id_anio_ver = intval($_GET["id_anio"]);
} else {
    $abierto = anio_abierto($conexion);
    if ($abierto) {
        $id_anio_ver = $abierto["id_anio"];
    } else {
        $id_anio_ver = 0;
    }
}

$anios = mysqli_query($conexion, "SELECT a.*, (SELECT COUNT(*) FROM curso c WHERE c.id_anio = a.id_anio) AS cursos
                                  FROM anio_academico a ORDER BY a.anio DESC");

$resultado = mysqli_execute_query($conexion, "SELECT * FROM anio_academico WHERE id_anio = ?", [$id_anio_ver]);
$anio_ver = mysqli_fetch_assoc($resultado);

$cursos = mysqli_execute_query($conexion, "SELECT c.*, u.nombre AS profesor_jefe,
                                           (SELECT COUNT(*) FROM inscripcion i WHERE i.id_curso = c.id_curso) AS alumnos
                                           FROM curso c
                                           JOIN usuario u ON u.id_usuario = c.id_profesor_jefe
                                           WHERE c.id_anio = ?
                                           ORDER BY c.nivel, c.letra", [$id_anio_ver]);

// Los profesores se guardan en un arreglo porque se usan en varios selectores
$profesores = array();
$resultado = mysqli_query($conexion, "SELECT id_usuario, nombre FROM usuario WHERE rol = 'profesor' AND activo = 1 ORDER BY nombre");
while ($p = mysqli_fetch_assoc($resultado)) {
    $profesores[] = $p;
}

$titulo = "Años y cursos";
include "../includes/cabecera.php";
?>

<h2>Años académicos</h2>

<div class="tabla-contenedor">
<table>
    <tr>
        <th>Año</th>
        <th>Estado</th>
        <th>Cursos</th>
        <th></th>
    </tr>
    <?php while ($a = mysqli_fetch_assoc($anios)) { ?>
        <tr>
            <td><?php echo $a["anio"]; ?></td>
            <td><?php echo ucfirst($a["estado"]); ?></td>
            <td><?php echo $a["cursos"]; ?></td>
            <td><a href="anios_cursos.php?id_anio=<?php echo $a["id_anio"]; ?>" class="boton boton-chico">Ver cursos</a></td>
        </tr>
    <?php } ?>
</table>
</div>

<form method="post" class="filtros" style="margin-top: 12px;">
    <input type="hidden" name="accion" value="crear_anio">
    Nuevo año académico:
    <input type="number" name="anio" value="<?php echo date("Y") + 1; ?>" style="width: 100px;">
    <button type="submit" class="boton boton-chico">Crear año</button>
</form>

<?php if ($anio_ver) { ?>

    <h2>Cursos del año <?php echo $anio_ver["anio"]; ?></h2>

    <div class="tabla-contenedor">
    <table>
        <tr>
            <th>Curso</th>
            <th>Profesor jefe</th>
            <th class="centro">Alumnos</th>
            <th>Estado</th>
            <th></th>
        </tr>
        <?php while ($c = mysqli_fetch_assoc($cursos)) { ?>
            <tr>
                <td><?php echo nombre_curso($c["nivel"], $c["letra"]); ?></td>
                <td>
                    <?php if ($c["estado"] == "abierto") { ?>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="accion" value="cambiar_jefe">
                            <input type="hidden" name="id_curso" value="<?php echo $c["id_curso"]; ?>">
                            <input type="hidden" name="id_anio" value="<?php echo $id_anio_ver; ?>">
                            <select name="id_profesor_jefe">
                                <?php foreach ($profesores as $p) { ?>
                                    <option value="<?php echo $p["id_usuario"]; ?>" <?php if ($p["id_usuario"] == $c["id_profesor_jefe"]) echo "selected"; ?>>
                                        <?php echo limpiar($p["nombre"]); ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <button type="submit" class="boton boton-chico boton-gris">Cambiar</button>
                        </form>
                    <?php } else { ?>
                        <?php echo limpiar($c["profesor_jefe"]); ?>
                    <?php } ?>
                </td>
                <td class="centro"><?php echo $c["alumnos"]; ?></td>
                <td><?php echo ucfirst($c["estado"]); ?></td>
                <td>
                    <a href="matriculas.php?id_curso=<?php echo $c["id_curso"]; ?>" class="boton boton-chico">Matrícula</a>
                    <a href="asignaciones.php?id_curso=<?php echo $c["id_curso"]; ?>" class="boton boton-chico">Profesores</a>
                </td>
            </tr>
        <?php } ?>
    </table>
    </div>

    <?php if (mysqli_num_rows($cursos) == 0) { ?>
        <p class="gris">Este año todavía no tiene cursos.</p>
    <?php } ?>

    <?php if ($anio_ver["estado"] == "abierto") { ?>
        <h2>Nuevo curso para <?php echo $anio_ver["anio"]; ?></h2>
        <form method="post" class="formulario">
            <input type="hidden" name="accion" value="crear_curso">
            <input type="hidden" name="id_anio" value="<?php echo $id_anio_ver; ?>">

            <label>Nivel</label>
            <select name="nivel">
                <option value="0">-- Elegir --</option>
                <?php for ($n = 1; $n <= 12; $n++) { ?>
                    <option value="<?php echo $n; ?>"><?php echo nombre_nivel($n); ?></option>
                <?php } ?>
            </select>

            <label>Letra</label>
            <input type="text" name="letra" maxlength="1" style="width: 60px;">

            <label>Profesor jefe</label>
            <select name="id_profesor_jefe">
                <option value="0">-- Elegir --</option>
                <?php foreach ($profesores as $p) { ?>
                    <option value="<?php echo $p["id_usuario"]; ?>"><?php echo limpiar($p["nombre"]); ?></option>
                <?php } ?>
            </select>

            <br>
            <button type="submit" class="boton">Crear curso</button>
        </form>
    <?php } ?>

<?php } ?>

<?php include "../includes/pie.php"; ?>
