<?php
// Datos de conexión a la base de datos.
// Son los que trae XAMPP por defecto (usuario root sin contraseña).
$servidor = "localhost";
$usuario_bd = "root";
$clave_bd = "";
$nombre_bd = "clasify";

// Carpeta del proyecto dentro de htdocs. Si cambian el nombre de la carpeta, hay que cambiarlo aquí.
$url_base = "/clasify/";

// Hora de Chile para las fechas
date_default_timezone_set("America/Santiago");

$conexion = mysqli_connect($servidor, $usuario_bd, $clave_bd, $nombre_bd);
mysqli_set_charset($conexion, "utf8mb4");
