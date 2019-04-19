<?php
	session_start();
	include "funciones.php";
	//registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar borrar un usuario de la Base de Datos desde la función borrar_usuario() con el email ".$_SESSION['email'], "error");
	//echo borrar_usuario("a@a.com");
	$password = "admin";
	$hash = password_hash($password, PASSWORD_DEFAULT);
	if (password_verify($password, $hash)) {
    	echo "success";
	} else {
    	echo "Invalid credentials";
	}
	if (password_verify($password, $hash)) {
    	echo "success";
	} else {
    	echo "Invalid credentials";
	}
?>
