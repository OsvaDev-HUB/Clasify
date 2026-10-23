<?php
// Página de inicio de sesión (RF-01)
session_start();
require_once "config/conexion.php";
require_once "includes/funciones.php";

// Si ya inició sesión, lo mandamos directo al inicio
if (isset($_SESSION["id_usuario"])) {
    header("Location: inicio.php");
    exit;
}

$error = "";
$rut = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rut = limpiar_rut($_POST["rut"]);
    $clave = $_POST["clave"];

    if ($rut == "" || $clave == "") {
        $error = "Debes ingresar tu RUT y tu contraseña.";
    } else {
        $resultado = mysqli_execute_query($conexion, "SELECT * FROM usuario WHERE rut = ?", [$rut]);
        $usuario = mysqli_fetch_assoc($resultado);

        // password_verify compara la contraseña escrita con la que está encriptada en la base de datos
        if ($usuario == null || !password_verify($clave, $usuario["clave"])) {
            $error = "RUT o contraseña incorrectos.";
        } elseif ($usuario["activo"] == 0) {
            $error = "Tu cuenta está desactivada. Habla con la secretaría del colegio.";
        } else {
            // Datos correctos: se guardan en la sesión
            session_regenerate_id(true);
            $_SESSION["id_usuario"] = $usuario["id_usuario"];
            $_SESSION["nombre"] = $usuario["nombre"];
            $_SESSION["rol"] = $usuario["rol"];
            $_SESSION["ultimo_acceso"] = time();
            header("Location: inicio.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión - Clasify</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>

<div class="caja-login">
    <h1>Clasify</h1>
    <p class="centro ayuda">Sistema de gestión educacional</p>

    <?php if (isset($_GET["expirada"])) { ?>
        <div class="mensaje error">Tu sesión se cerró por estar más de 30 minutos sin actividad.</div>
    <?php } ?>

    <?php if ($error != "") { ?>
        <div class="mensaje error"><?php echo limpiar($error); ?></div>
    <?php } ?>

    <form method="post" class="formulario">
        <label for="rut">RUT</label>
        <input type="text" name="rut" id="rut" placeholder="12345678-9" value="<?php echo limpiar($rut); ?>">

        <label for="clave">Contraseña</label>
        <input type="password" name="clave" id="clave">

        <button type="submit" class="boton">Ingresar</button>
    </form>
</div>

</body>
</html>
