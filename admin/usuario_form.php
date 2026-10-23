<?php
// Crear o modificar un usuario (RF-04).
// Si llega ?id=... se modifica ese usuario, si no, se crea uno nuevo.
require_once "../includes/sesion.php";
solo_para("administrativo");

$editando = false;
$id = 0;

// Valores del formulario (vacíos al crear)
$rut = "";
$nombre = "";
$correo = "";
$rol_nuevo = "";
$id_apoderado = "";

if (isset($_GET["id"])) {
    $id = intval($_GET["id"]);
    $resultado = mysqli_execute_query($conexion, "SELECT * FROM usuario WHERE id_usuario = ?", [$id]);
    $usuario = mysqli_fetch_assoc($resultado);
    if (!$usuario) {
        guardar_mensaje("El usuario no existe.", "error");
        header("Location: usuarios.php");
        exit;
    }
    $editando = true;
    $rut = $usuario["rut"];
    $nombre = $usuario["nombre"];
    $correo = $usuario["correo"];
    $rol_nuevo = $usuario["rol"];
}

$errores = array();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST["nombre"]);
    $correo = trim($_POST["correo"]);
    $clave = $_POST["clave"];

    if (!$editando) {
        $rut = limpiar_rut($_POST["rut"]);
        $rol_nuevo = $_POST["rol"];
        $id_apoderado = $_POST["id_apoderado"];
    }

    // ---- Validaciones ----
    if ($nombre == "") {
        $errores[] = "El nombre es obligatorio.";
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo no es válido.";
    }

    if (!$editando) {
        if (!rut_valido($rut)) {
            $errores[] = "El RUT no es válido. Escríbelo sin puntos y con guion, por ejemplo 12345678-9.";
        } else {
            // El RUT no se puede repetir
            $resultado = mysqli_execute_query($conexion, "SELECT id_usuario FROM usuario WHERE rut = ?", [$rut]);
            if (mysqli_num_rows($resultado) > 0) {
                $errores[] = "Ya existe un usuario con ese RUT.";
            }
        }

        if ($rol_nuevo != "alumno" && $rol_nuevo != "apoderado" && $rol_nuevo != "profesor" && $rol_nuevo != "administrativo") {
            $errores[] = "Debes elegir un rol.";
        }

        if (strlen($clave) < 8) {
            $errores[] = "La contraseña debe tener al menos 8 caracteres.";
        }

        // Un alumno siempre tiene un apoderado
        if ($rol_nuevo == "alumno") {
            $resultado = mysqli_execute_query($conexion, "SELECT id_usuario FROM usuario WHERE id_usuario = ? AND rol = 'apoderado' AND activo = 1", [$id_apoderado]);
            if (mysqli_num_rows($resultado) == 0) {
                $errores[] = "Para crear un alumno debes elegir su apoderado. Si el apoderado no existe, créalo primero.";
            }
        }
    } else {
        // Al modificar, la contraseña es opcional
        if ($clave != "" && strlen($clave) < 8) {
            $errores[] = "La contraseña debe tener al menos 8 caracteres.";
        }
    }

    // ---- Guardar ----
    if (count($errores) == 0) {
        if ($editando) {
            mysqli_execute_query($conexion, "UPDATE usuario SET nombre = ?, correo = ? WHERE id_usuario = ?", [$nombre, $correo, $id]);
            if ($clave != "") {
                $clave_encriptada = password_hash($clave, PASSWORD_DEFAULT);
                mysqli_execute_query($conexion, "UPDATE usuario SET clave = ? WHERE id_usuario = ?", [$clave_encriptada, $id]);
            }
            guardar_mensaje("Los datos de " . $nombre . " se guardaron.", "exito");
        } else {
            $clave_encriptada = password_hash($clave, PASSWORD_DEFAULT);
            mysqli_execute_query($conexion, "INSERT INTO usuario (rut, nombre, correo, clave, rol) VALUES (?, ?, ?, ?, ?)",
                [$rut, $nombre, $correo, $clave_encriptada, $rol_nuevo]);

            // Si es alumno, también se guarda en la tabla alumno con su apoderado
            if ($rol_nuevo == "alumno") {
                $id_nuevo = mysqli_insert_id($conexion);
                mysqli_execute_query($conexion, "INSERT INTO alumno (id_alumno, id_apoderado) VALUES (?, ?)", [$id_nuevo, $id_apoderado]);
            }
            guardar_mensaje("El usuario " . $nombre . " fue creado.", "exito");
        }
        header("Location: usuarios.php");
        exit;
    }
}

// Lista de apoderados para el selector
$apoderados = mysqli_query($conexion, "SELECT id_usuario, nombre, rut FROM usuario WHERE rol = 'apoderado' AND activo = 1 ORDER BY nombre");

if ($editando) {
    $titulo = "Modificar usuario";
} else {
    $titulo = "Nuevo usuario";
}
include "../includes/cabecera.php";
?>

<?php foreach ($errores as $e) { ?>
    <div class="mensaje error"><?php echo limpiar($e); ?></div>
<?php } ?>

<form method="post" class="formulario">
    <label>RUT</label>
    <?php if ($editando) { ?>
        <input type="text" value="<?php echo limpiar($rut); ?>" disabled>
        <div class="ayuda">El RUT no se puede cambiar.</div>
    <?php } else { ?>
        <input type="text" name="rut" placeholder="12345678-9" value="<?php echo limpiar($rut); ?>">
    <?php } ?>

    <label>Nombre completo</label>
    <input type="text" name="nombre" value="<?php echo limpiar($nombre); ?>">

    <label>Correo</label>
    <input type="email" name="correo" value="<?php echo limpiar($correo); ?>">

    <label>Rol</label>
    <?php if ($editando) { ?>
        <input type="text" value="<?php echo nombre_rol($rol_nuevo); ?>" disabled>
    <?php } else { ?>
        <select name="rol">
            <option value="">-- Elegir --</option>
            <option value="alumno" <?php if ($rol_nuevo == "alumno") echo "selected"; ?>>Alumno</option>
            <option value="apoderado" <?php if ($rol_nuevo == "apoderado") echo "selected"; ?>>Apoderado</option>
            <option value="profesor" <?php if ($rol_nuevo == "profesor") echo "selected"; ?>>Profesor</option>
            <option value="administrativo" <?php if ($rol_nuevo == "administrativo") echo "selected"; ?>>Administrativo</option>
        </select>

        <label>Apoderado</label>
        <select name="id_apoderado">
            <option value="">-- Solo si el rol es Alumno --</option>
            <?php while ($a = mysqli_fetch_assoc($apoderados)) { ?>
                <option value="<?php echo $a["id_usuario"]; ?>" <?php if ($id_apoderado == $a["id_usuario"]) echo "selected"; ?>>
                    <?php echo limpiar($a["nombre"]) . " (" . $a["rut"] . ")"; ?>
                </option>
            <?php } ?>
        </select>
    <?php } ?>

    <label>Contraseña</label>
    <input type="password" name="clave">
    <?php if ($editando) { ?>
        <div class="ayuda">Déjala en blanco si no quieres cambiarla.</div>
    <?php } else { ?>
        <div class="ayuda">Mínimo 8 caracteres. El usuario la puede cambiar después.</div>
    <?php } ?>

    <br>
    <button type="submit" class="boton">Guardar</button>
    <a href="usuarios.php" class="boton boton-gris">Volver</a>
</form>

<?php include "../includes/pie.php"; ?>
