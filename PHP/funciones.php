<?php
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;
	/*
	DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA CONECTARSE A LA BASE DE DATOS. ENVÍA UN CORREO EN CASO DE FALLAR LA BD.
	RESULTADO: DEVUELVE LA CONEXIÓN CREADA CON LA BASE DE DATOS O FALSE SI NO HA SIDO POSIBLE CONECTARSE.
	LLAMADA: ES LLAMADA CADA VEZ QUE SE QUIERE INTERACTUAR CON LA BASE DE DATOS.
	-
	*/
	function conexion_database() {
		$conexion = @new mysqli("localhost", "inventario", "inventario", "inventario");
		if ($conexion->connect_errno) {
    	enviar_email("inventario@iesserraperenxisa.com", "Fallo en la BD", "Se ha producido un error al conectar a la Base de Datos a las ".date(DATE_RFC2822));
			return False;
		} else return $conexion;
		return $conexion;
	}
	function enviar_email($email, $asunto, $contenido) {
		// INCLUIR LIBRERÍAS NECESARIAS
		require 'PHPMAILER/Exception.php';
		require 'PHPMAILER/PHPMailer.php';
		require 'PHPMAILER/SMTP.php';
		$mail = new PHPMailer(true);
		$mail->SMTPDebug = 0;
		$mail->IsSMTP();
		$mail->Host = "smtp.gmail.com";
		$mail->SMTPAuth = true;
		$mail->Username   = 'inventario@iesserraperenxisa.com';
		$mail->Password   = 'X3nq6%qL';
		$mail->SMTPSecure = 'ssl';
		$mail->Port       = 465;
		$mail->setFrom('inventario@iesserraperenxisa.com', 'IES SERRA PERENXISA');
		$mail->addAddress($email);
		$mail->isHTML(true);
		$mail->Subject = $asunto;
		$mail->Body    = $contenido;
		$mail->AltBody = $contenido;
		$mail->send();
	}
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
	function registrar_evento($fecha, $email, $descripcion, $evento) {
		$conexion = conexion_database();
		if ($conexion !== False) {
			$email = strtolower($email);
			$sentencia = $conexion->prepare("INSERT INTO logs VALUES (?, ?, ?, ?)");
			$sentencia->bind_param("isss", $fecha, $email, $descripcion, $evento);
			$sentencia->execute();
		}
	}
	/*
	DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA CARGAR LOS USUARIOS DE LA BASE DE DATOS. PERMITE CARGAR TODOS O APLICAR UN FILTRO
	RESULTADO: DEVUELVE UN ARRAY CON USUARIOS EN CASO DE ENCONTRAR RESULTADOS, "ERROR EN LA BD" EN CASO DE FALLAR LA CONEXIÓN CON LA BD, "FALLO CONSULTA" EN CASO FALLAR LA CONSULTA O "NO USUARIOS" EN CASO DE NO DEVOLVER NINGÚN USUARIO
	LLAMADA: ES LLAMADA CADA VEZ QUE SE CARGA LA PÁGINA USUARIOS O DESDE AJAX (recargar_usuarios.php)
	PARÁMETROS:
	- FILTRO: PERMITE INDICAR UNA CADENA PARA VISUALIZAR ÚNICAMENTE LOS USUARIOS CUYO NOMBRE O EMAIL COINCIDA CON DICHA CADENA
	*/
	function ver_usuarios($filtro = "ninguno") {
		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD
		// SI NO SE HA SOLICITADO NINGÚN FILTRO CONSULTAMOS TODAS LAS FILAS
		if ($filtro == "ninguno") {
			$resultado = $conexion->query("SELECT email, nombre, tipo FROM usuarios ORDER BY email");
			if (!is_object($resultado) or $resultado->num_rows <= 0) { // COMPROBAR SI LA CONSULTA HA DEVULETO FILAS
				$conexion->close();
				registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar cargar los usuarios de la Base de Datos desde la función ver_usuarios()", "error");
				return "FALLO CONSULTA"; // SI NO DEVUELVE FILAS CONSIDERAMOS ERROR, YA QUE NO PUEDE SER QUE NO EXISTA NI 1 USUARIO ADMINISTRADOR
			} else {
				while ($fila = $resultado->fetch_assoc()) { // RECORRER EL RESULTADO E IR AÑADIENDO LAS FILAS AL ARRAY $usuarios
					$usuarios[] = $fila;
				}
				$conexion->close();
				return $usuarios;
			}
		// EN CASO CONTRARIO AÑADIMOS LOS OPERADORES % PARA BUSCAR SIMILIITUDES
		} else {
			$filtro = strtolower($filtro);
			$filtro = "%".$filtro."%";
			$sentencia = $conexion->prepare("SELECT email, nombre, tipo FROM usuarios WHERE email LIKE ? OR LOWER(nombre) LIKE ? ORDER BY email");
			$sentencia->bind_param("ss", $filtro, $filtro);
			if (!$sentencia->execute()) { // COMPROBAR SI HA FALLADO LA CONSULTA
				$conexion->close();
				registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar cargar los usuarios de la Base de Datos desde la función ver_usuarios()", "error");
				return "FALLO CONSULTA";
			} else {
				$resultado = $sentencia->get_result();
				if ($resultado->num_rows == 0) { // COMPROBAR SI HA DEVULETO FILAS
					$conexion->close();
					return "NO USUARIOS";
				} else {
					while ($fila = $resultado->fetch_assoc()) { // RECORRER EL RESULTADO E IR AÑADIENDO LAS FILAS AL ARRAY $usuarios
						$usuarios[] = $fila;
					}
					$conexion->close();
					return $usuarios;
				}
			}
		}
	}
	/*
	DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA CREAR UN USUARIO EN LA BD DESDE UN FORMULARIO.
	RESULTADO: DEVUELVE TRUE EN CASO DE CREAR EL USUARIO, "ERROR EN LA BD", "FALLO EMAIL" SI NO SE HA PASADO O ES INCORRECTO, "FALLO PASSWORD" SI NO SE HA PASADO, "FALLO TIPO" SI NO SE HA PASADO O ES INCORRECTO O "FALLO CREAR" SI NO SE HA PODIDO CREAR
	LLAMADA: ES LLAMADA CADA VEZ QUE SE CREA UN USUARIO (crear_usuario.php)
	PARÁMETROS:
	- EMAIL: EMAIL DEL USUARIO. ES LA CLAVE PRIMARIA DE LA BD. NO PUEDE SER NULL
	- PASSWORD: CONTRASEÑA DEL USUARIO. NO PUEDE SER NULL
	- NOMBRE: NOMBRE DEL USUARIO. PUEDE SER NULL
	- TIPO: PRIVILEGIOS DEL USUARIO. NO PUEDE SER NULL
	*/
	function crear_usuario($email, $password, $nombre, $tipo) {
		// VALIDAR LOS DATOS INTRODUCIDOS
		if (empty($email) or !filter_var($email, FILTER_VALIDATE_EMAIL)) return "FALLO EMAIL";
		else $email = strtolower($email); // CONVERTIR EMAIL A MINÚSCULAS
		if (empty($password)) return "FALLO PASSWORD";
		else $password_cifrada = md5($password); // CIFRAR CONTRASEÑA
		if (empty($nombre)) $nombre = null;
		else $nombre = ucwords(strtolower($nombre)); // FORMATEAR EL NOMBRE, PRIMERA LETRA MAYUS, EL RESTO MINUS
		if (empty($tipo) or ($tipo != "administrador" and $tipo != "editor") and $tipo != "estandar")	return "FALLO TIPO";

		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD

		// INSERTAR EN LA BD
		$sentencia = $conexion->prepare("INSERT INTO usuarios VALUES(?, ?, ?, ?)");
		$sentencia->bind_param("ssss", $email, $password_cifrada, $nombre, $tipo);
		if (!$sentencia->execute() or $sentencia->affected_rows == 0)	{
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al intentar ejecutar la query INSERT INTO usuarios VALUES('".$email."', '".$password_cifrada."', '".$nombre."', '".$tipo."') desde la función crear_usuario()", "error");
			$conexion->close();
			return "FALLO CREAR";
		} else {
			$conexion->close();
			enviar_email($email, "Creación de Usuario | IES SERRA PERENXISA", "El administrador del centro le ha creado una cuenta para administrar el inventario del centro. Su usuario es: ".$email." y su contraseña: ".$password);// INFORMAR AL USUARIO POR CORREO
			return True;
		}
	}
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA ELIMINAR UN USUARIO DE LA BASE DE DATOS. UTILIZAR FUNCIÓN strtolower PARA HACERLA NO KEY SENSITIVE
		RESULTADO: DEVUELVE TRUE SI ELIMINA LA FILA, "ERROR EN LA BD" SI FALLA LA CONEXIÓN, "FALLO CONSULTA" SI NO SE PUEDE EJECUTAR EL DELETE, "NO ELIMINADO" SI NO SE ELIMINA
		LLAMADA: ES LLAMADA DESDE eliminar_usuario.php
		PARÁMETROS:
			- EMAIL: RECIBE UN EMAIL QUE BORRAR
	*/
	function borrar_usuario($email) {
		if (empty($email)) return "FALLO EMAIL";
		else $email = strtolower($email); // CONVERTIR EMAIL A MINÚSCULAS
		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD
		$sentencia = $conexion->prepare("DELETE FROM usuarios WHERE email = ?");
		$sentencia->bind_param("s", $email);
		if (!$sentencia->execute()) { // COMPROBAR QUE SE HAYA PODIDO EJECUTAR LA CONSULTA
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al intentar ejecutar la query DELETE FROM usuarios WHERE email = ".$email." desde la función borrar_usuario()", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		}	elseif ($sentencia->affected_rows > 0) {  // COMPROBAR QUE SE HAYA ELIMINADO ALGUNA
			$conexion->close();
			return True;
		} else {
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "No se ha podido eliminar el usuario ".$email." desde la función borrar_usuario()", "error"); // ANOTAR EVENTO EN LA BD
			return "NO ELIMINADO";
		}
	}
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA ELIMINAR UN USUARIO DE LA BASE DE DATOS. UTILIZAR FUNCIÓN strtolower PARA HACERLA NO KEY SENSITIVE
		RESULTADO: DEVUELVE TRUE SI MODIFICA LA FILA, "ERROR EN LA BD" SI FALLA LA CONEXIÓN, "FALLO CONSULTA" SI NO SE PUEDE EJECUTAR EL UPDATE, "NO MODIFICADO" SI NO SE MODIFICA
		LLAMADA: ES LLAMADA DESDE eliminar_usuario.php
		PARÁMETROS:
		- EMAIL: EMAIL DEL USUARIO. ES LA CLAVE PRIMARIA DE LA BD. NO PUEDE SER NULL
		- NOMBRE: NOMBRE DEL USUARIO. PUEDE SER NULL
		- TIPO: PRIVILEGIOS DEL USUARIO. NO PUEDE SER NULL
	*/
	function modificar_usuario($email, $nombre, $tipo) {
		// VALIDAR LOS DATOS INTRODUCIDOS
		if (empty($email) or !filter_var($email, FILTER_VALIDATE_EMAIL)) return "FALLO EMAIL";
		else $email = strtolower($email); // CONVERTIR EMAIL A MINÚSCULAS
		if (empty($nombre)) $nombre = null;
		else $nombre = ucwords(strtolower($nombre)); // FORMATEAR EL NOMBRE, PRIMERA LETRA MAYUS, EL RESTO MINUS
		if (empty($tipo) or ($tipo != "administrador" and $tipo != "editor") and $tipo != "estandar")	return "FALLO TIPO";

		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD

		$sentencia = $conexion->prepare("UPDATE usuarios SET nombre = ?, tipo = ? WHERE email = ?");
		$sentencia->bind_param("sss", $nombre, $tipo, $email);
		if (!$sentencia->execute()) {
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al intentar ejecutar la query UPDATE usuarios SET nombre = '".$nombre."', tipo = '".$tipo."' WHERE email = '".$email."' desde la función modificar_usuario()", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		} elseif ($sentencia->affected_rows > 0) {  // COMPROBAR QUE SE HAYA MODIFICADO ALGUNA
			$conexion->close();
			return True;
		} else {
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "No se ha podido modificar el usuario ".$email." con el nombre ".$nombre." y el tipo ".$tipo." desde la función modificar_usuario()", "error"); // ANOTAR EVENTO EN LA BD
			return "NO MODIFICADO";
		}
	}
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA MODIFICAR LA PASSWORD DE UN USUARIO DE LA BASE DE DATOS.
		RESULTADO: DEVUELVE TRUE SI MODIFICA LA FILA, "ERROR EN LA BD" SI FALLA LA CONEXIÓN, "FALLO CONSULTA" SI NO SE PUEDE EJECUTAR EL UPDATE, "NO MODIFICADO" SI NO SE MODIFICA
		LLAMADA: ES LLAMADA DESDE modificar_password.php
		PARÁMETROS:
		- EMAIL: EMAIL DEL USUARIO. NO PUEDE SER NULL
		- PASSWORD: PASSWORD DEL USUARIO. NO PUEDE SER NULL
	*/
	function modificar_password($email, $password) {
		// VALIDAR LOS DATOS INTRODUCIDOS
		if (empty($email) or !filter_var($email, FILTER_VALIDATE_EMAIL)) return "FALLO EMAIL";
		else $email = strtolower($email); // CONVERTIR EMAIL A MINÚSCULAS
		if (empty($password)) return "FALLO PASSWORD";
		else $password_cifrada = md5($password); // CIFRAR CONTRASEÑA
		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD
		//UPDATE EN LA BD
		$sentencia = $conexion->prepare("UPDATE usuarios SET password = ? WHERE email = ?");
		$sentencia->bind_param("ss", $password_cifrada, $email);
		if (!$sentencia->execute()) {
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al ejecutar la query UPDATE usuarios SET password = '".$password_cifrada."' WHERE email = '".$email."' desde la función modificar_password()", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		} elseif ($sentencia->affected_rows > 0) {  // COMPROBAR QUE SE HAYA MODIFICADO ALGUNA
			$conexion->close();
			enviar_email($email, "Modificación de contrasenya | IES SERRA PERENXISA", "El administrador le ha reseteado su contrasenya. Su usuario es: ".$email." y su contrasenya: ".$password);// INFORMAR AL USUARIO POR CORREO
			return True;
		} else {
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "No se ha podido modificar la password ".$password_cifrada." del usuario ".$email." desde la función modificar_password()", "error"); // ANOTAR EVENTO EN LA BD
			return "NO MODIFICADO";
		}
	}
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA CARGAR DESDE LA BD LAS UBICACIONES QUE GESTIONA Y LAS QUE NO UN USUARIO
		RESULTADO: DEVUELVE UN ARRAY BIDIMENSIONAL SI FUNCIONA CORRECTAMENTE, "ERROR EN LA BD" SI FALLA LA CONEXIÓN O "FALLO CONSULTA" SI FALLA LA CONSULTA
		LLAMADA: ES LLAMADA DESDE ver_ubicaciones_administrador.php
		PARÁMETROS:
		- EMAIL: EMAIL DEL USUARIO. NO PUEDE SER NULL
	*/
	function ver_ubicaciones_administrador($email) {
		// VALIDAR LOS DATOS INTRODUCIDOS
		if (empty($email) or !filter_var($email, FILTER_VALIDATE_EMAIL)) return "FALLO EMAIL";
		else $email = strtolower($email); // CONVERTIR EMAIL A MINÚSCULAS
		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD

		// CONSULTAR LAS UBICACIONES QUE ACTUALMENTE GESTIONA
		$sentencia = $conexion->prepare("SELECT codigo, descripcion FROM ubicaciones WHERE codigo IN (SELECT ubicacion FROM gestiona WHERE usuario = ? ) ");
		$sentencia->bind_param("s", $email);
		if (!$sentencia->execute()) {
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al ejecutar la query SELECT codigo, descripcion FROM ubicaciones WHERE codigo IN (SELECT ubicacion FROM gestiona WHERE usuario = '".$email."' desde la función ver_ubicaciones_administrador()", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		}
		$resultado = $sentencia->get_result();
		if ($resultado->num_rows > 0) { // SI HAY UBICACIONES QUE GESTIONA LAS ALMACENAMOS EN EL ARRAY $ubicaciones["gestionadas"]
			while ($fila = $resultado->fetch_assoc()) {
				$ubicaciones["gestionadas"][] = $fila;
			}
		}

		// CONSULTAR LAS UBICACIONES QUE NO GESTIONA
		$sentencia = $conexion->prepare("SELECT codigo, descripcion FROM ubicaciones WHERE codigo NOT IN (SELECT ubicacion FROM gestiona WHERE usuario = ? ) ");
		$sentencia->bind_param("s", $email);
		if (!$sentencia->execute()) {
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al ejecutar la querySELECT codigo, descripcion FROM ubicaciones WHERE codigo NOT IN (SELECT ubicacion FROM gestiona WHERE usuario = '".$email."' desde la función ver_ubicaciones_administrador()", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		}
		$resultado = $sentencia->get_result();
		if ($resultado->num_rows > 0) { // SI HAY UBICACIONES QUE NO GESTIONA LAS ALMACENAMOS EN EL ARRAY $ubicaciones["nogestionadas"]
			while ($fila = $resultado->fetch_assoc()) {
				$ubicaciones["nogestionadas"][] = $fila;
			}
		}
		$conexion->close();
		return $ubicaciones;
	}
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA MODIFICAR LAS UBICACIONES QUE PUEDE GESTIONAR UN USUARIO. ES TRANSACCIONAL, O SE EJECUTAN TODAS LAS CONSULTAS O NINGUNA
		RESULTADO: DEVUELVE UN ARRAY BIDIMENSIONAL SI FUNCIONA CORRECTAMENTE, "ERROR EN LA BD" SI FALLA LA CONEXIÓN O "FALLO CONSULTA" SI FALLA LA CONSULTA
		LLAMADA: ES LLAMADA DESDE modificar_ubicaciones_administrador.php
		PARÁMETROS:
		- EMAIL: EMAIL DEL USUARIO. NO PUEDE SER NULL
		- UBICACIONES: UBICACIONES QUE EL USUARIO VA A GESTIONAR. PUEDE SER "NINGUNO" O UN ARRAY
	*/
	function modificar_ubicaciones_administrador($email, $ubicaciones) {
		// VALIDAR DATOS
		if (empty($email) or !filter_var($email, FILTER_VALIDATE_EMAIL)) return "FALLO EMAIL";
		else $email = strtolower($email); // CONVERTIR EMAIL A MINÚSCULAS
		if (empty($ubicaciones) or !filter_var($email, FILTER_VALIDATE_EMAIL)) return "FALLO EMAIL";
		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD

		$transaccion = True;
		$conexion->autocommit(false); // INICIAR TRANSACCIÓN

		//ELIMINAR LAS UBICACIONES QUE GESTIONA. SI NO SE HA PEDIDO AÑADIR PERMISOS SOBRE UNA UBICACIÓN DEVOLVEMOS TRUE
		$sentencia = $conexion->prepare("DELETE FROM gestiona WHERE usuario = ?");
		$sentencia->bind_param("s", $email);
		if (!$sentencia->execute())	{
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al ejecutar la query DELETE FROM gestiona WHERE usuario = '".$email."' desde la función modificar_ubicaciones_administrador()", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		}

		// SI EL USUARIO NO VA A GESTIONAR NINGUNA UBICACIÓN (PARAMETRO = "NINGUNO") NO HACE FALTA HACER NADA MÁS, EL SCRIPT ACABA AQUÍ
		if ($ubicaciones == "ninguno") {
			$conexion->commit(); // CONFIRMAR TRANSACCIÓN
			return True;
		}

		//PREPARAR LA SENTENCIA INSERT Y VINCULAR LAS VARIABLES, GANANDO EFICIENCIA EN CASO DE REALIZAR MUCHOS INSERTS
		$sentencia = $conexion->prepare("INSERT INTO gestiona VALUES (? , ?)");
		$sentencia->bind_param("ss", $indice, $email);

		//RECORRER EL ARRAY DE UBICACIONES, SI UNA INSERCIÓN FALLA HACEMOS ROLLBACK Y DEVOLVEMOS FALSE
		foreach ($ubicaciones as $indice) {
			if (!$sentencia->execute()) {
				registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al ejecutar la query DELETE FROM gestiona WHERE usuario = '".$email."' desde la función modificar_ubicaciones_administrador()", "error"); // ANOTAR EVENTO EN LA BD
				$conexion->rollback(); // SI LA CONSULTA FALLA CANCELAMOS LA TRANSACCIÓN
				return "FALLO CONSULTA";
			}
		}
		//SI LA EJECUCIÓN DEL PROGRAMA HA LLEGADO HASTA AQUÍ CONFIRMAMOS LA EJECUCIÓN Y DEVOLVEMOS True
		$conexion->commit();
		return True;
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
<link rel="stylesheet" href="../CSS/jquery-confirm.min.css">
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
