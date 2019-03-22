<?php
	session_start();
	include "funciones.php";
	if (isset($_POST['cerrar_sesion'])) cerrar_sesion();
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<link rel="stylesheet" href="../CSS/estilos.css">
	<title></title>
	<script type="text/javascript">
		function confirmar_delete(id) {
			if (confirm("¿Está seguro de borrar el elemento cuyo ID es "+id+" ?")) return true
			else return false
		}
	</script>
</head>
<body>
<form action="" method="post">
	<input type="submit" name="cerrar_sesion" value="cerrar sesión">
</form>
<?php
	if (isset($_GET['campo_borrar'])) borrar_usuario($_GET['campo_borrar']);
	$usuarios = ver_usuarios();
	if (is_array($usuarios)) {
		// print_r($usuarios);
		echo "<table>";
		echo "	<tr>
					<th>Usuario</th>
					<th>Tipo</th>
					<th>Acciones</th>
				</tr>
		";
		foreach ($usuarios as $usuario) {
			echo "	<tr>
						<td>".$usuario['email']."</td>
						<td>".$usuario['tipo']."</td>
						<td><a href='menu.php?campo_borrar=".$usuario['email']."' onclick=\"return confirmar_delete('".$usuario['email']."');\">Eliminar</a></td>
					</tr>";
		}
		echo "</table>";
	}
?>
</body>
</html>