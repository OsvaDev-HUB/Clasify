<?php
// Cambiar la contraseña propia (RF-03, la parte de recuperar por correo se hace en la semana 3)
require_once "includes/sesion.php";

$errores = array();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $actual = $_POST["actual"];
    $nueva = $_POST["nueva"];
    $repetir = $_POST["repetir"];

    $resultado = mysqli_execute_query($conexion, "SELECT clave FROM usuario WHERE id_usuario = ?", [$id_usuario]);
    $usuario = mysqli_fetch_assoc($resultado);

    if (!password_verify($actual, $usuario["clave"])) {
        $errores[] = "La contraseña actual no es correcta.";
    }
    if (strlen($nueva) < 8) {
        $errores[] = "La nueva contraseña debe tener al menos 8 caracteres.";
    }
    if ($nueva != $repetir) {
        $errores[] = "Las contraseñas nuevas no coinciden.";
    }

    if (count($errores) == 0) {
        // La contraseña se guarda encriptada (RNF-01)
        $clave_encriptada = password_hash($nueva, PASSWORD_DEFAULT);
        mysqli_execute_query($conexion, "UPDATE usuario SET clave = ? WHERE id_usuario = ?", [$clave_encriptada, $id_usuario]);
        guardar_mensaje("Tu contraseña se cambió correctamente.", "exito");
        header("Location: inicio.php");
        exit;
    }
}

$titulo = "Cambiar contraseña";
include "includes/cabecera.php";
?>

<?php foreach ($errores as $e) { ?>
    <div class="mensaje error"><?php echo limpiar($e); ?></div>
<?php } ?>

<form method="post" class="formulario">
    <label>Contraseña actual</label>
    <input type="password" name="actual">

    <label>Nueva contraseña</label>
    <input type="password" name="nueva">
    <div class="ayuda">Mínimo 8 caracteres.</div>

    <label>Repetir nueva contraseña</label>
    <input type="password" name="repetir">

    <br>
    <button type="submit" class="boton">Guardar</button>
</form>

<?php include "includes/pie.php"; ?>
