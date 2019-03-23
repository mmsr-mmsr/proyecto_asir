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
	function crear_usuario($email, $password, $tipo, $ubicaciones) {
		$password = md5($password);
		$conexion = conexion_database();
		$transaccion = True;
		$conexion->autocommit(false);
		$sentencia = $conexion->prepare("INSERT INTO usuarios VALUES(?, ?, ?)");
		$sentencia->bind_param("sss", $email, $password, $tipo);
		if (!$sentencia->execute() or $sentencia->affected_rows == 0)	$transaccion = False;
		if (is_array($ubicaciones)) {
			$sentencia = $conexion->prepare("INSERT INTO gestiona VALUES(?, ?)");
			foreach ($ubicaciones as $ubicacion) {
				$sentencia->bind_param("ss", $ubicacion, $email);
				if (!$sentencia->execute() or $sentencia->affected_rows == 0) $transaccion = False;
			}
		}
		if ($transaccion === False) {
			$conexion->rollback();
			return False;
		} else {
			$conexion->commit();
			return True;
		}
	}

	function borrar_usuario($email) {
		$conexion = conexion_database();
		$sentencia = $conexion->prepare("DELETE FROM usuarios WHERE LOWER(email) = LOWER(?)");
		$sentencia->bind_param("s", $email);
		if (!$sentencia->execute() or $sentencia->affected_rows == 0) return False;
		else return True;
	}

	function modificar_password($email, $password) {
		$conexion = conexion_database();
		$password = md5($password);
		$sentencia = $conexion->prepare("UPDATE usuarios SET password = ? WHERE email = ?");
		$sentencia->bind_param("ss", $password, $email);
		if (!$sentencia->execute() or $sentencia->affected_rows == 0) return False;
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