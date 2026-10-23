<?php
// Funciones que se usan en varias páginas

// Para mostrar texto en la página sin que se ejecute código HTML
function limpiar($texto)
{
    return htmlspecialchars($texto, ENT_QUOTES, "UTF-8");
}

// Deja el RUT sin puntos ni espacios y con la K en mayúscula. Ej: 12.345.678-k -> 12345678-K
function limpiar_rut($rut)
{
    $rut = str_replace(".", "", $rut);
    $rut = str_replace(" ", "", $rut);
    $rut = strtoupper(trim($rut));
    return $rut;
}

// Revisa que el RUT tenga el formato 12345678-9 y que el dígito verificador sea correcto
function rut_valido($rut)
{
    $partes = explode("-", $rut);
    if (count($partes) != 2) {
        return false;
    }
    $numero = $partes[0];
    $dv = $partes[1];
    if (!ctype_digit($numero) || strlen($numero) < 7 || strlen($numero) > 8) {
        return false;
    }

    // Cálculo del dígito verificador (módulo 11)
    $suma = 0;
    $multiplo = 2;
    for ($i = strlen($numero) - 1; $i >= 0; $i--) {
        $suma = $suma + intval($numero[$i]) * $multiplo;
        $multiplo++;
        if ($multiplo > 7) {
            $multiplo = 2;
        }
    }
    $resto = 11 - ($suma % 11);
    if ($resto == 11) {
        $dv_correcto = "0";
    } elseif ($resto == 10) {
        $dv_correcto = "K";
    } else {
        $dv_correcto = strval($resto);
    }
    return $dv == $dv_correcto;
}

// Nombre del nivel. En la base de datos el nivel es un número del 1 al 12.
function nombre_nivel($nivel)
{
    $niveles = array(
        1 => "1° Básico", 2 => "2° Básico", 3 => "3° Básico", 4 => "4° Básico",
        5 => "5° Básico", 6 => "6° Básico", 7 => "7° Básico", 8 => "8° Básico",
        9 => "1° Medio", 10 => "2° Medio", 11 => "3° Medio", 12 => "4° Medio"
    );
    if (isset($niveles[$nivel])) {
        return $niveles[$nivel];
    }
    return "Nivel " . $nivel;
}

// Ej: nombre_curso(9, "A") -> "1° Medio A"
function nombre_curso($nivel, $letra)
{
    return nombre_nivel($nivel) . " " . $letra;
}

// Muestra una nota con coma y un decimal. Ej: 6.5 -> 6,5
function mostrar_nota($valor)
{
    if ($valor === null) {
        return "-";
    }
    return number_format($valor, 1, ",", "");
}

// Convierte lo que escribe el profesor en una nota. Acepta coma o punto (6,5 o 6.5).
// Devuelve null si no es un número o si está fuera del rango 1,0 a 7,0 (RF-11).
function convertir_nota($texto)
{
    $texto = str_replace(",", ".", trim($texto));
    if (!is_numeric($texto)) {
        return null;
    }
    $valor = floatval($texto);
    if ($valor < 1.0 || $valor > 7.0) {
        return null;
    }
    return round($valor, 1);
}

// Promedio simple de una lista de notas, redondeado a un decimal.
// Si la lista está vacía devuelve null.
function promedio($notas)
{
    if (count($notas) == 0) {
        return null;
    }
    $suma = 0;
    foreach ($notas as $n) {
        $suma = $suma + $n;
    }
    return round($suma / count($notas), 1);
}

// Busca el año académico en curso: el más antiguo que sigue abierto.
// Así, si ya se creó el año siguiente, se sigue trabajando con el actual hasta que se cierre.
function anio_abierto($conexion)
{
    $resultado = mysqli_query($conexion, "SELECT * FROM anio_academico WHERE estado = 'abierto' ORDER BY anio ASC LIMIT 1");
    return mysqli_fetch_assoc($resultado);
}

// Guarda un mensaje para mostrarlo en la siguiente página (tipo: exito o error)
function guardar_mensaje($texto, $tipo)
{
    $_SESSION["mensaje"] = $texto;
    $_SESSION["tipo_mensaje"] = $tipo;
}

// Nombre del rol para mostrarlo en pantalla
function nombre_rol($rol)
{
    if ($rol == "administrativo") {
        return "Administrativo";
    } elseif ($rol == "profesor") {
        return "Profesor";
    } elseif ($rol == "apoderado") {
        return "Apoderado";
    } else {
        return "Alumno";
    }
}
