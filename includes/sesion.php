<?php
// Este archivo va al inicio de todas las páginas que necesitan sesión iniciada.
session_start();
require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/funciones.php";

// Si no ha iniciado sesión, vuelve al login
if (!isset($_SESSION["id_usuario"])) {
    header("Location: " . $url_base . "index.php");
    exit;
}

// La sesión se cierra sola después de 30 minutos sin actividad (RNF-03)
$minutos_maximos = 30;
if (isset($_SESSION["ultimo_acceso"]) && time() - $_SESSION["ultimo_acceso"] > $minutos_maximos * 60) {
    session_unset();
    session_destroy();
    header("Location: " . $url_base . "index.php?expirada=1");
    exit;
}
$_SESSION["ultimo_acceso"] = time();

// Datos del usuario conectado, para usarlos en cualquier página
$id_usuario = $_SESSION["id_usuario"];
$rol = $_SESSION["rol"];

// Se llama al principio de cada página para que solo entre el rol que corresponde (RF-02 y RNF-02)
function solo_para($rol_permitido)
{
    global $url_base;
    if ($_SESSION["rol"] != $rol_permitido) {
        guardar_mensaje("No tienes permiso para entrar a esa página.", "error");
        header("Location: " . $url_base . "inicio.php");
        exit;
    }
}
