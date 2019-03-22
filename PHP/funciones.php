<?php
	//FUNCIÓN PARA CONECTARSE A LA BD DE LA APLICACIÓN
	function conexion_database() {
		$conexion = new mysqli("localhost", "root", "", "inventario");
		return $conexion;
	}
	//
	function validar_login($email, $password) {
		$conexion = conexion_database();
		$sentencia = $conexion->prepare("SELECT password, tipo FROM usuarios WHERE LOWER(email) = LOWER(?)");
		$sentencia->bind_param("s", $email);
		$sentencia->execute();
		$resultado = $sentencia->get_result();
		if ($resultado->num_rows == 0) return "<p id='fallo'>El usuario no existe</p>";
		$resultado = $resultado->fetch_assoc();
		if ($resultado['password'] != md5($password)) return "<p id='fallo'>La contraseña no es correcta</p>";
		else return array('tipo' => $resultado['tipo']);
	}
	function cerrar_sesion() {
		session_destroy();
		if (isset($_COOKIE['email'])) setcookie("email", "", time() - 1, "/");
		if (isset($_COOKIE['password'])) setcookie("password", "", time() - 1, "/");
		header('Location: /PHP/index.php');
	}

	function ver_usuarios() {
		$conexion = conexion_database();
		$resultado = $conexion->query("SELECT email, tipo FROM usuarios");
		if (is_object($resultado) and $resultado->num_rows > 0) {
			echo "<pre>";
			while ($fila = $resultado->fetch_assoc()) {
				$usuarios[] = $fila;
			}
			return $usuarios;
		}
		else echo "la consulta ha fallado o no hay usuarios que mostrar";
	}
	function crear_usuario() {
		
	}

	function borrar_usuario($email) {
		$conexion = conexion_database();
		$sentencia = $conexion->prepare("DELETE FROM usuarios WHERE LOWER(email) = LOWER(?)");
		$sentencia->bind_param("s", $email);
		if (!$sentencia->execute())	return False;
		else return True;
	}

	function registrar_login($fecha, $email, $descripcion) {
		$conexion = conexion_database();
		$evento = "login";
		$sentencia = $conexion->prepare("INSERT INTO logs VALUES (?, ?, ?, ?)");
		$sentencia->bind_param("isss", $fecha, $email, $descripcion, $evento);
		$sentencia->execute();
	}

	function crear_localizacion() {

	}
?>