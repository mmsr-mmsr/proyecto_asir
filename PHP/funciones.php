<?php
	//FUNCIÓN PARA CONECTARSE A LA BD DE LA APLICACIÓN
	function conexion_database() {
		$conexion = new mysqli("localhost", "root", "", "inventario");
		return $conexion;
	}
	//
	function validar_login($email, $password) {
		$conexion = conexion_database();
		$sentencia = $conexion->prepare("SELECT password, tipo FROM usuarios WHERE email = LOWER(?)");
		$sentencia->bind_param("s", $email);
		if (!$sentencia->execute()) echo "ERRROOOOOR";
		$resultado = $sentencia->get_result();
		//echo "ERROR: ".$resultado->num_rows;
		if ($resultado->num_rows == 0) return "El usuario no existe";
		$resultado = $resultado->fetch_assoc();
		if ($resultado['password'] != md5($password)) return "La contraseña no es correcta";
		else return array('tipo' => $resultado['tipo']);
	}
	function cerrar_sesion() {
		if (isset($_SESSION['email']) and isset($_SESSION['password']) and isset($_SESSION['tipo'])) session_destroy();
		if (isset($_COOKIE['email'])) setcookie("email", "", time() - 1, "/");
		if (isset($_COOKIE['password'])) setcookie("password", "", time() - 1, "/");
		header('Location: /PHP/index.php');
	}

	function ver_usuarios($filtro = "ninguno") {
		$conexion = conexion_database();
		if ($filtro == "ninguno") {
			$resultado = $conexion->query("SELECT email, nombre, tipo FROM usuarios ORDER BY email");
			if (is_object($resultado) and $resultado->num_rows > 0) {
				while ($fila = $resultado->fetch_assoc()) {
					$usuarios[] = $fila;
				}
				return $usuarios;
			}
			else return False;
		} else {
			$filtro = "%".$filtro."%";
			$sentencia = $conexion->prepare("SELECT email, nombre, tipo FROM usuarios WHERE email LIKE LOWER(?) OR LOWER(nombre) LIKE LOWER(?) ORDER BY email");
			$sentencia->bind_param("ss", $filtro, $filtro);
			if (!$sentencia->execute() or $sentencia->num_rows <= 0) return False;
			else {
				while ($fila = $resultado->fetch_assoc()) {
					$usuarios[] = $fila;
				}
				return $usuarios;
			}
		}
	}
	/*AÑADIR COMPROBACIONES DE NO NULL, ¿AÑADIR NOMBRE Y APELLIDOS?*/
	function crear_usuario($email, $password, $nombre, $tipo, $ubicaciones = NULL) {
		$password = md5($password); // CIFRAR CONTRASEÑA
		if (empty($nombre)) $nombre = null;
		else $nombre = ucwords($nombre); //
		$conexion = conexion_database();
		// $transaccion = True;
		// $conexion->autocommit(false);
		$sentencia = $conexion->prepare("INSERT INTO usuarios VALUES(LOWER(?), ?, ?, LOWER(?))");
		$sentencia->bind_param("ssss", $email, $password, $nombre, $tipo);
		if (!$sentencia->execute() or $sentencia->affected_rows == 0)	return false;
		else return True;
		// if (is_array($ubicaciones)) {
		// 	$sentencia = $conexion->prepare("INSERT INTO gestiona VALUES(UPPER(?), LOWER(?))");
		// 	foreach ($ubicaciones as $ubicacion) {
		// 		$sentencia->bind_param("ss", $ubicacion, $email);
		// 		if (!$sentencia->execute() or $sentencia->affected_rows == 0) $transaccion = False;
		// 	}
		// }
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
	function registrar_evento($fecha, $email, $descripcion, $evento) {
		$conexion = conexion_database();
		echo $email."<br>";
		$sentencia = $conexion->prepare("INSERT INTO logs VALUES (?, LOWER(?), ?, ?)");
		$sentencia->bind_param("isss", $fecha, $email, $descripcion, $evento);
		$sentencia->execute();
	}
	function crear_ubicacion($codigo, $descripcion, $observaciones) {
		if (empty($codigo)) return "El código de la ubicación se debe rellenar";
		elseif (empty($descripcion)) return "La descripción de la ubicación se debe rellenar";
		elseif (strlen($codigo) != 4) return "El código debe ser de 4 dígitos";
		elseif (empty($observaciones)) $observaciones = null;

		$conexion = conexion_database();
		$sentencia = $conexion->prepare("INSERT INTO ubicaciones VALUES (UPPER(?), ?, ?)");
		$sentencia->bind_param("sss", $codigo, $descripcion, $observaciones);
		if (!$sentencia->execute() or $sentencia->affected_rows == 0) {
			if ($conexion->errno == 1062) return "El código introducido ya se está utilizando";
			else return False;
		} else return True;
	}

	function elimina_ubicacion($codigo, $confirmacion = null) {
		$conexion = conexion_database();
		$sentencia = $conexion->prepare("SELECT codigo FROM ubicaciones WHERE codigo = UPPER(?)");
		$sentencia->bind_param("s", $codigo);
		// $resultado = $sentencia->get_result();
		if (!$sentencia->execute() or $resultado->num_rows == 0) return "La ubicacion que se desea eliminar no existe";

		$sentencia = $conexion->prepare("SELECT COUNT(*) FROM stock WHERE ubicacion = UPPER(?)");
		$sentencia->bind_param("s", $codigo);
		if (!$sentencia->execute() or $resultado->num_rows == 0) registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al consultar cuantas dependencias tenia una ubicacion que previamente se había comprobado su existencia", "error");
	}
	function imprimir_cabecera($pagina_activa) {
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<title>IES SERRA PERENXISA</title>
			<meta charset="utf-8">
			<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
			<link rel="icon" type="image/jpg" href="../IMG/logo1.jpg">
			<!-- hojas de estilos -->
			<link rel="stylesheet" href="../CSS/bootstrap.css">
			<link rel="stylesheet" href="../CSS/estilos.css">
			<link rel="stylesheet" href="../CSS/iconos.css">
			<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.css">
		</head>
		<body>
			<div>
			<nav class="mb-1 navbar navbar-expand-lg color_fuerte">
				<a class="navbar-brand" href="../PHP/index.php">
					<img src="../IMG/logo2.png" height="70" id="logo2" class="d-inline-block align-middle rounded" alt="Serra Perenxisa">
				</a>
				<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent-555" aria-controls="navbarSupportedContent-555" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span>
				</button>
				<div class="collapse navbar-collapse" id="navbarSupportedContent-555">
		<?php
			if (isset($_SESSION['email']) and isset($_SESSION['password']) and isset($_SESSION['tipo'])) {
		?>
					<ul class="navbar-nav mr-auto">
						<li class="nav-item">
							<a id="articulos" class="nav-link" href="/PHP/articulos.php">Artículos</a>
						</li>
		<?php
				if ($_SESSION['tipo'] == "administrador") {
		?>
						<li class="nav-item">
							<a id="logs" class="nav-link" href="/PHP/logs.php">Logs</a>
						</li>
		<?php
				}
		?>
						<li class="nav-item">
							<a id="ubicaciones" class="nav-link" href="/PHP/ubicaciones.php">Ubicaciones</a>
						</li>
		<?php
				if ($_SESSION['tipo'] == "administrador") {
		?>

						<li class="nav-item">
							<a id="usuarios" class="nav-link" href="/PHP/usuarios.php">Usuarios</a>
						</li>
		<?php
				}
			}
		?>
					</ul>
					<ul class="navbar-nav ml-auto nav-flex-icons">
						<li class="nav-item avatar dropdown">
							<a class="nav-link dropdown-toggle" id="navbarDropdownMenuLink-55" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
		<?php
				if (isset($_SESSION['email']) and isset($_SESSION['password']) and isset($_SESSION['tipo'])) {
					echo $_SESSION['email'];
				} else {
					echo "No has iniciado sesión";
				}
		?>
							</a>
							<div class="dropdown-menu dropdown-menu-right dropdown-secondary" aria-labelledby="navbarDropdownMenuLink-55">
								<a class="dropdown-item" href="/PHP/logout.php">Cerrar sesión</a>
							</div>
						</li>
					</ul>
				</div>
			</nav>

		<!--/.Navbar -->
				<div class="container">
					<a href="../PHP/index.php"><img src="../IMG/logo1.jpg" class="rounded-circle mx-auto d-block" id="logo1" alt="Cinque Terre"></a>
				</div>
		<?php
			}
		?>
