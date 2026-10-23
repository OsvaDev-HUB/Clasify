<?php
// Asignar o cambiar el apoderado de cada alumno (RF-09). Solo para el administrativo.
// Cada alumno tiene un solo apoderado y un apoderado puede tener varios hijos.
require_once "../includes/sesion.php";
solo_para("administrativo");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_alumno = intval($_POST["id_alumno"]);
    $id_apoderado = intval($_POST["id_apoderado"]);

    $resultado = mysqli_execute_query($conexion, "SELECT id_usuario FROM usuario WHERE id_usuario = ? AND rol = 'apoderado' AND activo = 1", [$id_apoderado]);
    if (mysqli_num_rows($resultado) == 0) {
        guardar_mensaje("Debes elegir un apoderado válido.", "error");
    } else {
        mysqli_execute_query($conexion, "UPDATE alumno SET id_apoderado = ? WHERE id_alumno = ?", [$id_apoderado, $id_alumno]);
        guardar_mensaje("Se cambió el apoderado.", "exito");
    }
    header("Location: apoderados.php");
    exit;
}

$alumnos = mysqli_query($conexion, "SELECT al.id_alumno, al.id_apoderado, u.nombre, u.rut
                                    FROM alumno al
                                    JOIN usuario u ON u.id_usuario = al.id_alumno
                                    WHERE u.activo = 1
                                    ORDER BY u.nombre");

$apoderados = array();
$resultado = mysqli_query($conexion, "SELECT id_usuario, nombre, rut FROM usuario WHERE rol = 'apoderado' AND activo = 1 ORDER BY nombre");
while ($a = mysqli_fetch_assoc($resultado)) {
    $apoderados[] = $a;
}

$titulo = "Apoderados";
include "../includes/cabecera.php";
?>

<p class="ayuda">Cada alumno tiene un solo apoderado. Un apoderado puede tener varios hijos en el colegio.</p>

<div class="tabla-contenedor">
<table>
    <tr>
        <th>RUT</th>
        <th>Alumno</th>
        <th>Apoderado</th>
    </tr>
    <?php while ($al = mysqli_fetch_assoc($alumnos)) { ?>
        <tr>
            <td><?php echo limpiar($al["rut"]); ?></td>
            <td><?php echo limpiar($al["nombre"]); ?></td>
            <td>
                <form method="post" style="display: inline;">
                    <input type="hidden" name="id_alumno" value="<?php echo $al["id_alumno"]; ?>">
                    <select name="id_apoderado">
                        <?php foreach ($apoderados as $a) { ?>
                            <option value="<?php echo $a["id_usuario"]; ?>" <?php if ($a["id_usuario"] == $al["id_apoderado"]) echo "selected"; ?>>
                                <?php echo limpiar($a["nombre"]); ?>
                            </option>
                        <?php } ?>
                    </select>
                    <button type="submit" class="boton boton-chico boton-gris">Cambiar</button>
                </form>
            </td>
        </tr>
    <?php } ?>
</table>
</div>

<?php include "../includes/pie.php"; ?>
