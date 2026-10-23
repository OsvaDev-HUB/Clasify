<?php
// Página de bienvenida. Cada rol ve un resumen distinto.
require_once "includes/sesion.php";

$titulo = "Inicio";
$anio = anio_abierto($conexion);

include "includes/cabecera.php";
?>

<p>Hola, <strong><?php echo limpiar($_SESSION["nombre"]); ?></strong>. Bienvenido a Clasify.</p>

<?php if ($anio) { ?>
    <p>Año académico en curso: <strong><?php echo $anio["anio"]; ?></strong></p>
<?php } else { ?>
    <p class="gris">No hay un año académico abierto.</p>
<?php } ?>

<?php if ($rol == "administrativo") { ?>
    <?php
    $total_usuarios = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS total FROM usuario WHERE activo = 1"));
    $total_alumnos = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS total FROM alumno WHERE estado = 'regular'"));
    ?>
    <h2>Resumen</h2>
    <ul>
        <li>Usuarios activos: <?php echo $total_usuarios["total"]; ?></li>
        <li>Alumnos regulares: <?php echo $total_alumnos["total"]; ?></li>
    </ul>
    <p>Usa el menú de arriba para administrar usuarios, cursos, asignaturas y matrículas.</p>
<?php } ?>

<?php if ($rol == "profesor") { ?>
    <p>En <strong>Mis cursos</strong> puedes ver los cursos donde haces clases, y en <strong>Notas</strong> registrar y modificar las notas de tus asignaturas.</p>
<?php } ?>

<?php if ($rol == "alumno") { ?>
    <p>En <strong>Mi curso</strong> puedes ver tu curso y tus profesores, y en <strong>Mis notas</strong> tus notas y promedios.</p>
<?php } ?>

<?php if ($rol == "apoderado") { ?>
    <p>En <strong>Mis hijos</strong> puedes ver el curso y las notas de cada uno de tus pupilos.</p>
<?php } ?>

<?php include "includes/pie.php"; ?>
