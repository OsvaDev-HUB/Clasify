<?php
// Parte de arriba de todas las páginas: título, datos del usuario y menú según el rol.
// Antes de incluir este archivo, cada página define la variable $titulo.
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo limpiar($titulo); ?> - Clasify</title>
    <link rel="stylesheet" href="<?php echo $url_base; ?>css/estilos.css">
</head>
<body>

<div class="barra">
    <span class="logo">Clasify</span>
    <span class="usuario-conectado">
        <?php echo limpiar($_SESSION["nombre"]); ?> (<?php echo nombre_rol($_SESSION["rol"]); ?>)
    </span>
</div>

<div class="menu">
    <a href="<?php echo $url_base; ?>inicio.php">Inicio</a>

    <?php if ($_SESSION["rol"] == "administrativo") { ?>
        <a href="<?php echo $url_base; ?>admin/usuarios.php">Usuarios</a>
        <a href="<?php echo $url_base; ?>admin/anios_cursos.php">Años y cursos</a>
        <a href="<?php echo $url_base; ?>admin/asignaturas.php">Asignaturas</a>
        <a href="<?php echo $url_base; ?>admin/asignaciones.php">Profesores por curso</a>
        <a href="<?php echo $url_base; ?>admin/matriculas.php">Matrículas</a>
        <a href="<?php echo $url_base; ?>admin/apoderados.php">Apoderados</a>
    <?php } ?>

    <?php if ($_SESSION["rol"] == "profesor") { ?>
        <a href="<?php echo $url_base; ?>profesor/mis_cursos.php">Mis cursos</a>
        <a href="<?php echo $url_base; ?>profesor/notas.php">Notas</a>
    <?php } ?>

    <?php if ($_SESSION["rol"] == "alumno") { ?>
        <a href="<?php echo $url_base; ?>alumno/mi_curso.php">Mi curso</a>
        <a href="<?php echo $url_base; ?>alumno/mis_notas.php">Mis notas</a>
    <?php } ?>

    <?php if ($_SESSION["rol"] == "apoderado") { ?>
        <a href="<?php echo $url_base; ?>apoderado/mis_hijos.php">Mis hijos</a>
    <?php } ?>

    <a href="<?php echo $url_base; ?>cambiar_clave.php">Cambiar contraseña</a>
    <a href="<?php echo $url_base; ?>logout.php">Cerrar sesión</a>
</div>

<div class="contenido">
    <h1><?php echo limpiar($titulo); ?></h1>

    <?php
    // Si alguna página dejó un mensaje, se muestra una vez y se borra
    if (isset($_SESSION["mensaje"])) {
        echo '<div class="mensaje ' . $_SESSION["tipo_mensaje"] . '">' . limpiar($_SESSION["mensaje"]) . '</div>';
        unset($_SESSION["mensaje"]);
        unset($_SESSION["tipo_mensaje"]);
    }
    ?>
