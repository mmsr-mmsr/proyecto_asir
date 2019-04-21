<?php
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;

	// FUNCIONES GENÉRICAS DE LA APLICACIÓN //
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA CONECTARSE A LA BASE DE DATOS. ENVÍA UN CORREO EN CASO DE FALLAR LA BD.
		RESULTADO: DEVUELVE LA CONEXIÓN CREADA CON LA BASE DE DATOS O FALSE SI NO HA SIDO POSIBLE CONECTARSE.
		LLAMADA: ES LLAMADA CADA VEZ QUE SE QUIERE INTERACTUAR CON LA BASE DE DATOS.
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
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA VALIDAR LAS CREDENCIALES DE UN USUARIO. DEVUELVE TRUE SI SON CORRECTAS O UNA STRING SI ALGO FALLA. SE ENCARGA DE FIJAR LA SESIÓN Y PERMITE FIJAR COOKIES
		RESULTADO: DEVUELVE LA CONEXIÓN CREADA CON LA BASE DE DATOS O FALSE SI NO HA SIDO POSIBLE CONECTARSE.
		LLAMADA: ES LLAMADA CADA VEZ QUE SE ACCEDE A LA WEB Y NO HAYA UNA SESIÓN INICIADA
		PARAMÉTROS:
			- EMAIL: EMAIL DEL USUARIO A VALIDAR. NO NULL
			- PASSWORD: PASSWORD DEL USUARIO A VALIDAR. *** LA CONTRASEÑA DEBE SER PASADA CIFRADA ***. NO NULL
			- COOKIES: INDICA SI SE HAN DE ESTABLECER COOKIES O NO. SI NO SE PASA ESTE PARÁMETRO TOMA EL VALOR "N"
	*/
	function validar_login($email, $password, $cookies = "N") {
		// VALIDAR DATOS
		if (empty($email)) return "El campo correo es obligatorio.";
		else $email = strtolower($email); // CONVERTIR EMAIL A MINÚSCULAS
		if (empty($password)) return "El campo contraseña es obligatorio.";
		// CONEXIÓN CON LA BD
		$conexion = conexion_database();
		if ($conexion === False) return "Se ha producido un error en el servidor. Prueba a acceder más tarde."; // COMPROBAR LA CONECTIVIDAD CON LA BD
		$sentencia = $conexion->prepare("SELECT password, tipo FROM usuarios WHERE email = ?");
		$sentencia->bind_param("s", $email);
		// COMPROBAR QUE SE HAYA PODIDO EJECUTAR LA SENTENCIA
		if (!$sentencia->execute()) {
			$conexion->close();
			registrar_evento(time(), $email, "Se ha producido un error al intentar al ejecutar la query SELECT password, tipo FROM usuarios WHERE email = '".$email."' desde la función validar_login()", "error"); // ANOTAR EVENTO EN LA BD
			return "No se ha podido iniciar sesión. Prueba a intentario de nuevo.";
		}	else $resultado = $sentencia->get_result();
		// COMPROBAR QUE HAYA DEVUELTO FILAS, SI NO DEVUELVE FILAS INFORMAMOS DE QUE EL USUARIO NO EXISTE
		if ($resultado->num_rows == 0) {
			$conexion->close();
			registrar_evento(time(), $email, "Se ha intentado iniciar sesión con un usuario inexistente en la Base de Datos", "login");
			return "El usuario introducido no existe. Comprueba que lo hayas escrito correctamente. Avisa al administrador del centro si necesitas un usuario.";
		} else $resultado = $resultado->fetch_assoc();
		// COMPROBAR QUE LA CONTRASEÑA SEA CORRECTA
		if ($resultado['password'] != md5($password)) {
			$conexion->close();
			registrar_evento(time(), $email, "Se ha intentado iniciar sesión con unas credenciales incorrectas", "login");
			return "La contraseña introducida no es correcta. Si no te acuerdas pídele al Administrador que te la restablezca.";
		// CREDENCIALES CORRECTAS
		} else {
			registrar_evento(time(), $email, "Se ha logueado correctamente en la aplicación.", "login"); // ANOTAR LOGIN
			// ESTABLECER SESIÓN
			$_SESSION['email'] = $email;
			$_SESSION['password'] = $password;
			$_SESSION['tipo'] = $resultado['tipo'];
			// ESTABLECER COOKIES CON UNA DURACIÓN DE 15 DÍAS
			if ($cookies === "S") {
				setcookie("email", $email, time() + 60 * 60 * 24 * 15, "/");
				setcookie("password", $password, time() + 60 * 60 * 24 * 15, "/");
			}
			return True;
		}
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

	// FUNCIONES  PARA LA GESTIÓN DE USUARIOS //
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

	// FUNCIONES  PARA LA GESTIÓN DE UBICACIONES //
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA CARGAR LAS UBICACIONES DE LA BASE DE DATOS. PERMITE MOSTRAR TODAS LAS UBICACIONES, FILTRARLAS POR NOMBRE O MOSTRAR LAS QUE EL USUARIO PUEDE GESTIONAR.
		RESULTADO: DEVUELVE UN ARRAY CON UBICACIONES EN CASO DE ENCONTRAR RESULTADOS, "ERROR EN LA BD" EN CASO DE FALLAR LA CONEXIÓN CON LA BD, "FALLO CONSULTA" EN CASO FALLAR LA CONSULTA, "NO UBICACIONES" EN CASO DE QUE NINGUNA UBICACIÓN COINCIDA CON EL PATRÓN DE BÚSQUEDA O "NO UBICACIONES USUARIO" EN CASO DE QUE EL USUARIO NO PUEDA GESTIONAR NINGUNA
		LLAMADA: ES LLAMADA CADA VEZ QUE SE CARGA LA PÁGINA UBICACIONES O DESDE AJAX (recargar_ubicaciones.php)
		PARÁMETROS:
		- FILTRO: INDICA EL FILTRO UTILIZADO PARA MOSTRAR LAS UBICACIONES.
	*/
	function ver_ubicaciones($filtro = "ninguno") {
		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD
		// MOSTRAR TODAS LAS FILAS
		if ($filtro == "ninguno") {
			$resultado = $conexion->query("SELECT codigo, descripcion, observaciones FROM ubicaciones ORDER BY codigo");
			if (!is_object($resultado) or $resultado->num_rows <= 0) { // COMPROBAR SI LA CONSULTA HA DEVULETO FILAS
				$conexion->close();
				return "FALLO CONSULTA"; // SI NO DEVUELVE FILAS CONSIDERAMOS ERROR, YA QUE NO PUEDE SER QUE NO EXISTA NI 1 USUARIO ADMINISTRADOR
			} else {
				while ($fila = $resultado->fetch_assoc()) { // RECORRER EL RESULTADO E IR AÑADIENDO LAS FILAS AL ARRAY $usuarios
					$ubicaciones[] = $fila;
				}
				$conexion->close();
				return $ubicaciones;
			}
		// BUSCAR LAS UBICACIONES GESTIONADAS POR EL USUARIO
		} elseif ($filtro === $_SESSION['email']) {
			$filtro = strtoupper($filtro);
			$sentencia = $conexion->prepare("SELECT codigo, descripcion, observaciones FROM ubicaciones WHERE codigo IN (SELECT ubicacion FROM gestiona WHERE usuario = ? ) ORDER BY codigo");
			$sentencia->bind_param("s", $filtro);
			if (!$sentencia->execute()) { // COMPROBAR SI HA FALLADO LA CONSULTA
				$conexion->close();
				registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al ejecutar la query SELECT codigo, descripcion, observaciones FROM ubicaciones WHERE codigo IN (SELECT ubicacion FROM gestiona WHERE usuario = '".$filtro."' desde la función ver_ubicaciones()", "error"); // ANOTAR EVENTO EN LA BD
				return "FALLO CONSULTA";
			} else {
				$resultado = $sentencia->get_result();
				if ($resultado->num_rows == 0) { // COMPROBAR SI HA DEVULETO FILAS
					$conexion->close();
					return "NO UBICACIONES USUARIO";
				} else {
					while ($fila = $resultado->fetch_assoc()) { // RECORRER EL RESULTADO E IR AÑADIENDO LAS FILAS AL ARRAY $usuarios
						$ubicaciones[] = $fila;
					}
					$conexion->close();
					return $ubicaciones;
				}
			}
		// BUSCAR LAS UBICACIONES POR NOMBRE O DESCRIPCIÓN
		} else {
			$filtro = strtoupper($filtro);
			$filtro = "%".$filtro."%";
			$sentencia = $conexion->prepare("SELECT codigo, descripcion, observaciones FROM ubicaciones WHERE UPPER(codigo) LIKE ? OR UPPER(descripcion) LIKE ? ORDER BY codigo");
			$sentencia->bind_param("ss", $filtro, $filtro);
			if (!$sentencia->execute()) { // COMPROBAR SI HA FALLADO LA CONSULTA
				$conexion->close();
				registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al ejecutar la query SELECT codigo, descripcion, observaciones FROM ubicaciones WHERE codigo LIKE '".$filtro."' OR descripcion LIKE '".$filtro."' ORDER BY codigo  desde la función ver_ubicaciones()", "error"); // ANOTAR EVENTO EN LA BD
				return "FALLO CONSULTA";
			} else {
				$resultado = $sentencia->get_result();
				if ($resultado->num_rows == 0) { // COMPROBAR SI HA DEVULETO FILAS
					$conexion->close();
					return "NO UBICACIONES";
				} else {
					while ($fila = $resultado->fetch_assoc()) { // RECORRER EL RESULTADO E IR AÑADIENDO LAS FILAS AL ARRAY $usuarios
						$ubicaciones[] = $fila;
					}
					$conexion->close();
					return $ubicaciones;
				}
			}
		}
	}
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA CREAR UNA UBICACIÓN EN LA BD DESDE UN FORMULARIO.
		RESULTADO: DEVUELVE TRUE EN CASO DE CREAR LA UBICACIÓN, "ERROR EN LA BD" SI NO HAY CONECTIVIDAD CON LA BD, "FALLO CODIGO" SI NO SE HA PASADO O ES INCORRECTO, "FALLO DESCRIPCION" SI NO SE HA PASADO, "FALLO CONSULTA" SI FALLA LA EJECUCIÓN "FALLO CREAR" SI NO SE HA PODIDO CREAR.
		LLAMADA: ES LLAMADA CADA VEZ QUE SE CREA UN USUARIO (crear_ubicacion.php)
		PARÁMETROS:
		- CÓDIGO: CÓDIGO DE LA UBICACIÓN. ES LA CLAVE PRIMARIA DE LA TABLE. NO PUEDE SER NULL
		- DESCRIPCIÓN: DESCRIPCIÓN DE LA UBICACIÓN. NO PUEDE SER NULL
		- OBSERVACIONES: COMENTARIOS ADICIONALES DE LA UBICACIÓN. PUEDE SER NULL
	*/
	function crear_ubicacion($codigo, $descripcion, $observaciones) {
		// VALIDAR LOS DATOS INTRODUCIDOS
		if (empty($codigo) or !preg_match('/^[[:alpha:]]{2}\d{2}$/', $codigo)) { // COMPROBAR QUE SE HAYA PASADO UN CÓDIGO Y ESTE TENGA UN FORMATO CORRECTO
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar modificar crear una ubicación código inválido '".$codigo."' crear_ubicacion()", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CODIGO";
		}
		if (empty($descripcion)) {
			return "FALLO DESCRIPCION";
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar crear una ubicación sin indicar su descripción crear_ubicacion()", "error"); // ANOTAR EVENTO EN LA BD
		}	else $descripcion = ucwords(strtolower($descripcion)); // FORMATEAR LA VARIABLE, PRIMERA LETRA MAYÚSCULA DE CADA PALABRA, RESTO MINÚNSCULA
		if (empty($observaciones)) $observaciones = null;
		else $observaciones = ucwords(strtolower($observaciones)); // FORMATEAR LA VARIABLE, PRIMERA LETRA MAYÚSCULA DE CADA PALABRA, RESTO MINÚNSCULA

		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD

		// INSERTAR EN LA BD
		$sentencia = $conexion->prepare("INSERT INTO ubicaciones VALUES(?, ?, ?)");
		$sentencia->bind_param("sss", $codigo, $descripcion, $observaciones);
		if (!$sentencia->execute()) {
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al intentar ejecutar la query INSERT INTO ubicaciones VALUES('".$codigo."', '".$descripcion."', '".$observaciones."') desde la función crear_ubicacion()", "error");
			return "FALLO CONSULTA";
		} elseif ($sentencia->affected_rows == 0) {
			$conexion->close();
			return "FALLO CREAR";
		} else {
			$conexion->close();
			return True;
		}
	}
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA ELIMINAR UNA UBICACIÓN DE LA BASE DE DATOS. UTILIZAR FUNCIÓN strtolower PARA HACERLA NO KEY SENSITIVE
		RESULTADO: DEVUELVE TRUE SI ELIMINA LA FILA, "FALLO CODIGO" SI NO SE HA PASADO UN CÓDIGO, "ERROR EN LA BD" SI FALLA LA CONEXIÓN, "FALLO CONSULTA" SI NO SE PUEDE EJECUTAR EL DELETE, "NO ELIMINADO" SI NO SE ELIMINA
		LLAMADA: ES LLAMADA DESDE eliminar_ubicacion.php
		PARÁMETROS:
			- CODIGO: RECIBE UN CODIGO QUE BORRAR
	*/
	function borrar_ubicacion($codigo) {
		if (empty($codigo)) {
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al llamar a la función borrar_ubicacion() sin pasarle un código", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CODIGO";
		}
		else $codigo = strtolower($codigo); // CONVERTIR EMAIL A MINÚSCULAS
		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD
		$sentencia = $conexion->prepare("DELETE FROM ubicaciones WHERE codigo = ?");
		$sentencia->bind_param("s", $codigo);
		if (!$sentencia->execute()) { // COMPROBAR QUE SE HAYA PODIDO EJECUTAR LA CONSULTA
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al intentar ejecutar la query DELETE FROM ubicaciones WHERE codigo = ".$codigo." desde la función borrar_ubicacion()", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		}	elseif ($sentencia->affected_rows > 0) {  // COMPROBAR QUE SE HAYA ELIMINADO ALGUNA
			$conexion->close();
			return True;
		} else {
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "No se ha podido eliminar la ubicacion ".$codigo." desde la función borrar_ubicacion()", "error"); // ANOTAR EVENTO EN LA BD
			return "NO ELIMINADO";
		}
	}
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA MODIFICAR UNA UBICACIÓN DESDE UN FORM.
		RESULTADO: DEVUELVE TRUE SI MODIFICA LA FILA, "ERROR EN LA BD" SI FALLA LA CONEXIÓN, "FALLO CONSULTA" SI NO SE PUEDE EJECUTAR EL UPDATE, "NO MODIFICADO" SI NO SE MODIFICA, "FALLO CODIGO" SI NO SE PASA CÓDIGO O ES INCORRECTO, "FALLO DESCRIPCION" SI NO SE PASA
		LLAMADA: ES LLAMADA DESDE eliminar_usuario.php
		PARÁMETROS:
		- CÓDIGO: CÓDIGO DE LA UBICACIÓN. ES LA CLAVE PRIMARIA DE LA TABLE. NO PUEDE SER NULL. SE UTILIZA PARA SABER QUE UBICACIÓN MODIFICAR
		- DESCRIPCIÓN: DESCRIPCIÓN DE LA UBICACIÓN. NO PUEDE SER NULL. NUEVO VALOR PARA EL CAMPO
		- OBSERVACIONES: COMENTARIOS ADICIONALES DE LA UBICACIÓN. PUEDE SER NULL. NUEVO VALOR PARA EL CAMPO
	*/
	function modificar_ubicacion($codigo, $descripcion, $observaciones) {
		// VALIDAR LOS DATOS INTRODUCIDOS
		if (empty($codigo) or !preg_match('/^[[:alpha:]]{2}\d{2}$/', $codigo)) {
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar modificar una ubicación con el siguiente código inválido '".$codigo."' modificar_ubicacion()", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CODIGO";
		}
		if (empty($descripcion)) {
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar modificar una ubicación sin indicar su descripción modificar_ubicacion()", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO DESCRIPCIÓN";
		} else $descripcion = ucwords(strtolower($descripcion)); // FORMATEAR LA DESCRIPCIÓN, PRIMERA LETRA MAYUS, EL RESTO MINUS
		if (empty($observaciones)) $observaciones = null;
		else $observaciones = ucwords(strtolower($observaciones)); // FORMATEAR LAS OBSERVACIONES, PRIMERA LETRA MAYUS, EL RESTO MINUS

		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD

		$sentencia = $conexion->prepare("UPDATE ubicaciones SET descripcion = ?, observaciones = ? WHERE codigo = ?");
		$sentencia->bind_param("sss", $descripcion, $observaciones, $codigo);
		if (!$sentencia->execute()) {
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al intentar ejecutar la query UPDATE ubicaciones SET descripcion = '".$descripcion."', observaciones = '".$observaciones."' WHERE codigo = '".$codigo."' desde la función modificar_ubicacion()", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		} elseif ($sentencia->affected_rows > 0) {  // COMPROBAR QUE SE HAYA MODIFICADO ALGUNA
			$conexion->close();
			return True;
		} else {
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "No se ha podido modificar la ubicacion ".$codigo." con la descripcion ".$descripcion." y las observaciones ".$observaciones." desde la función modificar_ubicacion()", "error"); // ANOTAR EVENTO EN LA BD
			return "NO MODIFICADO";
		}
	}

?>
