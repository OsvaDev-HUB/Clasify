<?php
// Asignaturas (RF-06). Solo para el administrativo.
require_once "../includes/sesion.php";
solo_para("administrativo");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = $_POST["accion"];

    // ---- Crear asignatura ----
    if ($accion == "crear") {
        $nombre = trim($_POST["nombre"]);
        // Si no se marca la casilla, no llega nada, por eso se revisa con isset
        if (isset($_POST["influye"])) {
            $influye = 1;
        } else {
            $influye = 0;
        }

        $resultado = mysqli_execute_query($conexion, "SELECT id_asignatura FROM asignatura WHERE nombre = ?", [$nombre]);
        if ($nombre == "") {
            guardar_mensaje("Escribe el nombre de la asignatura.", "error");
        } elseif (mysqli_num_rows($resultado) > 0) {
            guardar_mensaje("Ya existe una asignatura con ese nombre.", "error");
        } else {
            mysqli_execute_query($conexion, "INSERT INTO asignatura (nombre, influye_promocion) VALUES (?, ?)", [$nombre, $influye]);
            guardar_mensaje("Se creó la asignatura " . $nombre . ".", "exito");
        }
    }

    // ---- Cambiar si influye o no en la promoción ----
    if ($accion == "cambiar_influye") {
        $id = intval($_POST["id_asignatura"]);
        mysqli_execute_query($conexion, "UPDATE asignatura SET influye_promocion = 1 - influye_promocion WHERE id_asignatura = ?", [$id]);
        guardar_mensaje("Se actualizó la asignatura.", "exito");
    }

    header("Location: asignaturas.php");
    exit;
}

$asignaturas = mysqli_query($conexion, "SELECT * FROM asignatura ORDER BY nombre");

$titulo = "Asignaturas";
include "../includes/cabecera.php";
?>

<p class="ayuda">Según el Decreto 67, Religión, Consejo de Curso y Orientación no influyen en la promoción de los alumnos.</p>

<div class="tabla-contenedor">
<table>
    <tr>
        <th>Asignatura</th>
        <th>¿Influye en la promoción?</th>
        <th></th>
    </tr>
    <?php while ($a = mysqli_fetch_assoc($asignaturas)) { ?>
        <tr>
            <td><?php echo limpiar($a["nombre"]); ?></td>
            <td>
                <?php if ($a["influye_promocion"] == 1) { ?>
                    Sí
                <?php } else { ?>
                    <span class="gris">No</span>
                <?php } ?>
            </td>
            <td>
                <form method="post" style="display: inline;">
                    <input type="hidden" name="accion" value="cambiar_influye">
                    <input type="hidden" name="id_asignatura" value="<?php echo $a["id_asignatura"]; ?>">
                    <button type="submit" class="boton boton-chico boton-gris">Cambiar</button>
                </form>
            </td>
        </tr>
    <?php } ?>
</table>
</div>

<h2>Nueva asignatura</h2>
<form method="post" class="formulario">
    <input type="hidden" name="accion" value="crear">

    <label>Nombre</label>
    <input type="text" name="nombre">

    <label>
        <input type="checkbox" name="influye" value="1" checked>
        Influye en la promoción
    </label>

    <button type="submit" class="boton">Crear asignatura</button>
</form>

<?php include "../includes/pie.php"; ?>
