<?php
// Listado de usuarios (RF-04). Solo para el administrativo.
require_once "../includes/sesion.php";
solo_para("administrativo");

// Filtros: por rol y por texto (nombre o RUT)
$filtro_rol = "";
if (isset($_GET["rol"])) {
    $filtro_rol = $_GET["rol"];
}
$buscar = "";
if (isset($_GET["buscar"])) {
    $buscar = trim($_GET["buscar"]);
}

// Se arma la consulta según los filtros que se usaron
$sql = "SELECT * FROM usuario WHERE 1 = 1";
$valores = array();
if ($filtro_rol != "") {
    $sql = $sql . " AND rol = ?";
    $valores[] = $filtro_rol;
}
if ($buscar != "") {
    $sql = $sql . " AND (nombre LIKE ? OR rut LIKE ?)";
    $valores[] = "%" . $buscar . "%";
    $valores[] = "%" . $buscar . "%";
}
$sql = $sql . " ORDER BY rol, nombre";
$resultado = mysqli_execute_query($conexion, $sql, $valores);

$titulo = "Usuarios";
include "../includes/cabecera.php";
?>

<a href="usuario_form.php" class="boton">+ Nuevo usuario</a>

<form method="get" class="filtros" style="margin-top: 15px;">
    Rol:
    <select name="rol">
        <option value="">Todos</option>
        <option value="alumno" <?php if ($filtro_rol == "alumno") echo "selected"; ?>>Alumno</option>
        <option value="apoderado" <?php if ($filtro_rol == "apoderado") echo "selected"; ?>>Apoderado</option>
        <option value="profesor" <?php if ($filtro_rol == "profesor") echo "selected"; ?>>Profesor</option>
        <option value="administrativo" <?php if ($filtro_rol == "administrativo") echo "selected"; ?>>Administrativo</option>
    </select>
    Buscar:
    <input type="text" name="buscar" placeholder="Nombre o RUT" value="<?php echo limpiar($buscar); ?>">
    <button type="submit" class="boton boton-chico">Filtrar</button>
</form>

<div class="tabla-contenedor">
<table>
    <tr>
        <th>RUT</th>
        <th>Nombre</th>
        <th>Correo</th>
        <th>Rol</th>
        <th>Estado</th>
        <th>Acciones</th>
    </tr>
    <?php while ($fila = mysqli_fetch_assoc($resultado)) { ?>
        <tr>
            <td><?php echo limpiar($fila["rut"]); ?></td>
            <td><?php echo limpiar($fila["nombre"]); ?></td>
            <td><?php echo limpiar($fila["correo"]); ?></td>
            <td><?php echo nombre_rol($fila["rol"]); ?></td>
            <td>
                <?php if ($fila["activo"] == 1) { ?>
                    Activo
                <?php } else { ?>
                    <span class="gris">Desactivado</span>
                <?php } ?>
            </td>
            <td>
                <a href="usuario_form.php?id=<?php echo $fila["id_usuario"]; ?>" class="boton boton-chico">Editar</a>
                <?php if ($fila["id_usuario"] != $id_usuario) { ?>
                    <form method="post" action="usuario_estado.php" style="display: inline;">
                        <input type="hidden" name="id" value="<?php echo $fila["id_usuario"]; ?>">
                        <?php if ($fila["activo"] == 1) { ?>
                            <button type="submit" class="boton boton-chico boton-gris">Desactivar</button>
                        <?php } else { ?>
                            <button type="submit" class="boton boton-chico">Activar</button>
                        <?php } ?>
                    </form>
                <?php } ?>
            </td>
        </tr>
    <?php } ?>
</table>
</div>

<?php include "../includes/pie.php"; ?>
