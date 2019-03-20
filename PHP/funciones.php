<?php
	function conexion_database() {
		$conexion = new mysqli("localhost", "root", "", "inventario");
		return $conexion;
	}
	function validar_login($email, $password) {
		$conexion = conexion_database();
		$sentencia = $conexion->prepare("SELECT password FROM usuarios WHERE LOWER(email) = LOWER(?)");
		$sentencia->bind_param("s", $email);
		$sentencia->execute();
		$resultado = $sentencia->get_result();
		if ($resultado->num_rows == 0) return "<p id='fallo'>El usuario no existe</p>";
		$resultado = $resultado->fetch_assoc();
		if ($resultado['password'] != md5($password)) return "<p id='fallo'>La contraseña no es correcta</p>";
		else return True;
	}
	function cerrar_sesion() {
		session_destroy();
	}
?>