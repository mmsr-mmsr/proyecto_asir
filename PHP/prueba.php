<?php
	session_start();
	include "funciones.php";
	//registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar borrar un usuario de la Base de Datos desde la función borrar_usuario() con el email ".$_SESSION['email'], "error");
	//echo borrar_usuario("a@a.com");
	if (preg_match('/^[[:alpha:]]{2}\d{2}$/', 'XA01')) echo "bien";
	else echo "mal"; // returns true
	// $resultado = ver_ubicaciones("01");
	// echo "<pre>";
	// print_r($resultado);
?>
