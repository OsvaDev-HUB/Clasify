<?php
// Activa o desactiva una cuenta (RF-04). No se borra para no perder el historial.
require_once "../includes/sesion.php";
solo_para("administrativo");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST["id"]);

    // El administrativo no se puede desactivar a sí mismo
    if ($id == $id_usuario) {
        guardar_mensaje("No puedes desactivar tu propia cuenta.", "error");
    } else {
        $resultado = mysqli_execute_query($conexion, "SELECT nombre, activo FROM usuario WHERE id_usuario = ?", [$id]);
        $usuario = mysqli_fetch_assoc($resultado);
        if ($usuario) {
            if ($usuario["activo"] == 1) {
                $nuevo_estado = 0;
                $texto = "desactivada";
            } else {
                $nuevo_estado = 1;
                $texto = "activada";
            }
            mysqli_execute_query($conexion, "UPDATE usuario SET activo = ? WHERE id_usuario = ?", [$nuevo_estado, $id]);
            guardar_mensaje("La cuenta de " . $usuario["nombre"] . " fue " . $texto . ".", "exito");
        }
    }
}

header("Location: usuarios.php");
exit;
