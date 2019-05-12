<?php
	session_start();
	include "funciones.php";
	echo $resultado_validacion = validar_permisos_inventariar("AU02", $_SESSION['email']);
?>
<html>
<head>
</head>
<body>
	<script>
		articulos_array = [];
		articulos_array[0]= [];
		articulos_array[0][0] = "AA00";
		articulos_array[0][1] = "1";
		articulos_array[1]= [];
		articulos_array[1][0] = "BB00";
		articulos_array[1][1] = "3";
		console.log(articulos_array);
	</script>
</body>
</html>
