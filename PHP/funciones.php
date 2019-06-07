<?php
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;
	use Slam\Excel\Helper as ExcelHelper;
	// FUNCIONES GENÉRICAS DE LA APLICACIÓN //
	//DOCUMENTAAAAAAAAAAAAAAAAAAR
	function validar_codigo($codigo) {
		if (empty($codigo) or !preg_match('/^[[:alpha:]]{2}\d{2}$/', $codigo)) { // COMPROBAR QUE SE HAYA PASADO UN CÓDIGO Y ESTE TENGA UN FORMATO CORRECTO
			return False;
		} else {
			return True;
		}
	}
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA CONECTARSE A LA BASE DE DATOS. ENVÍA UN CORREO EN CASO DE FALLAR LA BD.
		RESULTADO: DEVUELVE LA CONEXIÓN CREADA CON LA BASE DE DATOS O FALSE SI NO HA SIDO POSIBLE CONECTARSE.
		LLAMADA: ES LLAMADA CADA VEZ QUE SE QUIERE INTERACTUAR CON LA BASE DE DATOS.
	*/
	function conexion_database($usuario = "inventario") {
		$conexion = @new mysqli("localhost", $usuario, "inventario", "inventario");
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
		//$mail->send();
	}
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA VALIDAR LAS CREDENCIALES DE UN USUARIO. SE ENCARGA DE CREAR LA SESIÓN Y PERMITE CREAR COOKIES
		RESULTADO: DEVUELVE LA CONEXIÓN CREADA CON LA BASE DE DATOS O FALSE SI NO HA SIDO POSIBLE CONECTARSE.
		LLAMADA: ES LLAMADA CADA VEZ QUE SE ACCEDE A LA WEB Y NO HAYA UNA SESIÓN INICIADA
		PARAMÉTROS:
			- EMAIL: EMAIL DEL USUARIO A VALIDAR. NO PUEDE ESTAR VACÍO
			- PASSWORD: CONTRASEÑA DEL USUARIO A VALIDAR. *** LA CONTRASEÑA DEBE SER PASADA CIFRADA ***. NO PUEDE ESTAR VACÍO
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
			if (!is_object($resultado)) { // COMPROBAR SI LA CONSULTA HA DEVULETO FILAS
				$conexion->close();
				return "FALLO CONSULTA"; // SI NO DEVUELVE FILAS CONSIDERAMOS ERROR, YA QUE NO PUEDE SER QUE NO EXISTA NI 1 USUARIO ADMINISTRADOR
			} elseif ($resultado->num_rows <= 0) {
				return "NO UBICACIONES";
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
		else $codigo = strtoupper($codigo); // CONVERTIR CÓDIGO A MAYÚSCULAS
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
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA CARGAR DE LA BD (tabla stock) LOS ARTÍCULOS QUE TIENE ASOCIADOS UNA UBICACIÓN.
		RESULTADO: DEVUELVE UN ARRAY EN CASO DE ENCONTRAR ARTÍCULOS, "NO ARTICULOS" EN CASO DE NO ENCONTRAR ARTÍCULOS, "FALLO CONSULTA" EN CASO DE FALLAR LA EJECUCIÓN, "ERROR EN LA BD" O "FALLO CÓDIGO" EN CASO DE NO SUMINISTRAR UN CÓDIGO
		LLAMADA: ES LLAMADA DESDE cargar_articulos.php
		PARÁMETROS:
		- CÓDIGO: CÓDIGO DE LA UBICACIÓN. ES LA CLAVE PRIMARIA DE LA TABLE. NO PUEDE SER NULL. SE UTILIZA PARA SABER QUE UBICACIÓN LISTAR
	*/
	function ver_articulos_inventariados_por_ubicacion($codigo) {
		// VALIDAR QUE SE HAYA INTRODUCIDO CÓDIGO
		if (empty($codigo)) {
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar listar los artículos de una ubicación sin pasar un código desde la función ver_articulos_inventariados_por_ubicacion()", "error");
			return "FALLO CODIGO";
		}
		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD

		// VALIDAR QUE LA UBICACIÓN EXISTA
		$sentencia = $conexion->prepare("SELECT COUNT(*) total FROM ubicaciones WHERE codigo = ?");
		$sentencia->bind_param("s", $codigo);
		if (!$sentencia->execute()) { // COMPROBAR SI HA FALLADO LA CONSULTA
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al ejecutar la query SELECT COUNT(*) total FROM ubicaciones WHERE codigo = ?'".$codigo."' desde la función ver_articulos_inventariados_por_ubicacion()", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		} else {
			$resultado_query = $sentencia->get_result();
			$resultado_query = $resultado_query->fetch_assoc();
			if ($resultado_query['total'] !== 1) {
				$conexion->close();
				registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar listar los artículos de una ubicación inexistente (".$codigo.") desde la función ver_articulos_inventariados_por_ubicacion()", "error"); // ANOTAR EVENTO EN LA BD
				return "FALLO UBICACION";
			}
		}
		// CONSULTAR LOS ARTÍCULOS DE LA UBICACIÓN
		$sentencia = $conexion->prepare("SELECT s.articulo codigo, (SELECT a.descripcion FROM articulos a WHERE a.codigo = s.articulo) descripcion, s.cantidad cantidad FROM stock s WHERE s.ubicacion = ? ORDER BY s.articulo");
		$sentencia->bind_param("s", $codigo);
		if (!$sentencia->execute()) { // COMPROBAR SI HA FALLADO LA CONSULTA
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al ejecutar la query SELECT codigo, descripcion FROM articulos WHERE codigo IN (SELECT articulo FROM stock WHERE ubicacion = '".$codigo."' desde la función ver_articulos_inventariados_por_ubicacion()", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		} else {
			$resultado_query = $sentencia->get_result();
			if ($resultado_query->num_rows === 0) { // COMPROBAR SI NO TIENE ARTÍCULOS
				$conexion->close();
				return "NO ARTICULOS";
			} else { // SI TIENE ARTÍCULOS RECORREMOS LA CONSULTA Y DEVOLVEMOS UN ARRAY CON EL RESULTADO
				while ($fila = $resultado_query->fetch_assoc()) {
					$resultado[] = $fila;
				}
				$conexion->close();
				return $resultado;
			}
		}
	}
	/*DOCUMENTAR*/
	function ver_articulos_no_inventariados_por_ubicacion($codigo) {
		// VALIDAR QUE SE HAYA INTRODUCIDO CÓDIGO
		if (empty($codigo)) {
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar listar los artículos que no tiene una ubicación sin pasar un código desde la función ver_articulos_no_inventariados_por_ubicacion()", "error");
			return "FALLO CODIGO";
		}
		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD

		// VALIDAR QUE LA UBICACIÓN EXISTA
		$sentencia = $conexion->prepare("SELECT COUNT(*) total FROM ubicaciones WHERE codigo = ?");
		$sentencia->bind_param("s", $codigo);
		if (!$sentencia->execute()) { // COMPROBAR SI HA FALLADO LA CONSULTA
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al ejecutar la query SELECT COUNT(*) total FROM ubicaciones WHERE codigo = ?'".$codigo."' desde la función ver_articulos_por_ubicacion()", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		} else {
			$resultado_query = $sentencia->get_result();
			$resultado_query = $resultado_query->fetch_assoc();
			if ($resultado_query['total'] !== 1) {
				$conexion->close();
				registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar listar los artículos de una ubicación inexistente (".$codigo.") desde la función ver_articulos_no_inventariados_por_ubicacion()", "error"); // ANOTAR EVENTO EN LA BD
				return "FALLO UBICACION";
			}
		}
		// CONSULTAR LOS ARTÍCULOS QUE NO TIENE UNA UBICACIÓN
		$sentencia = $conexion->prepare("SELECT codigo, descripcion FROM articulos WHERE codigo NOT IN (SELECT articulo FROM stock WHERE ubicacion = ? )");
		$sentencia->bind_param("s", $codigo);
		if (!$sentencia->execute()) { // COMPROBAR SI HA FALLADO LA CONSULTA
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al ejecutar la query SELECT codigo, descripcion FROM articulos WHERE codigo NOT IN (SELECT articulo FROM stock WHERE ubicacion = '".$codigo."') desde la función ver_articulos_no_inventariados_por_ubicacion()", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		} else {
			$resultado_query = $sentencia->get_result();
			if ($resultado_query->num_rows === 0) { // COMPROBAR SI NO TIENE ARTÍCULOS
				$conexion->close();
				return "NO ARTICULOS";
			} else { // SI TIENE ARTÍCULOS RECORREMOS LA CONSULTA Y DEVOLVEMOS UN ARRAY CON EL RESULTADO
				while ($fila = $resultado_query->fetch_assoc()) {
					$resultado[] = $fila;
				}
				$conexion->close();
				return $resultado;
			}
		}
	}
	// DOCUMENTAAAAAAAAAAAAAAAAAAR
	function validar_permisos_inventariar($ubicacion, $usuario) {
		if (empty($ubicacion)) {
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar comprobar los permisos de un usuario sin pasar la ubicación desde la función validar_permisos_inventariar()", "error");
			return "FALLO CODIGO";
		}
		if ($_SESSION['tipo'] === "administrador") return "PERMISOS CORRECTOS";
		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD

		// VALIDAR QUE LA UBICACIÓN EXISTA
		$sentencia = $conexion->prepare("SELECT COUNT(*) total FROM gestiona WHERE ubicacion = ? AND usuario = ?");
		$sentencia->bind_param("ss", $ubicacion, $usuario);
		if (!$sentencia->execute()) { // COMPROBAR SI HA FALLADO LA CONSULTA
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "SELECT COUNT(*) total FROM gestiona WHERE ubicacion = '".$ubicacion."' AND usuario = '".$usuario."' desde la función ver_articulos_por_ubicacion()", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		} else {
			$resultado_query = $sentencia->get_result();
			$resultado_query = $resultado_query->fetch_assoc();
			if ($resultado_query['total'] !== 1) {
				$conexion->close();
				return "FALLO PERMISOS";
			} else {
				$conexion->close();
				return "PERMISOS CORRECTOS";
			}
		}
	}
// DOCUMENTAAAAAAAAAAAAAAAAAAR
	function inventariar_ubicacion($ubicacion, $articulos) {
		if (empty($ubicacion)) {
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar inventariar una ubicación ya que no se ha pasado el código de la ubicación desde la función inventariar_ubicacion()", "error");
			return "FALLO CÓDIGO";
		}
		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD

		// VALIDAR QUE LA UBICACIÓN EXISTA
		$sentencia = $conexion->prepare("SELECT COUNT(*) total FROM ubicaciones WHERE codigo = ?");
		$sentencia->bind_param("s", $ubicacion);
		if (!$sentencia->execute()) { // COMPROBAR SI HA FALLADO LA CONSULTA
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al ejecutar la query SELECT COUNT(*) total FROM ubicaciones WHERE codigo = ?'".$ubicacion."' desde la función inventariar_ubicacion()", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		}
		$resultado_query = $sentencia->get_result();
		$resultado_query = $resultado_query->fetch_assoc();
		if ($resultado_query['total'] !== 1) {
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar inventariar una ubicación inexistente (".$ubicacion.") desde la función inventariar_ubicacion()", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO UBICACION";
		} else {
			$sentencia = $conexion->prepare("DELETE FROM stock WHERE ubicacion = ?");
			$sentencia->bind_param("s", $ubicacion);
			if (!$sentencia->execute()) { // COMPROBAR SI HA FALLADO LA CONSULTA
				$conexion->close();
				registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al ejecutar la query SELECT COUNT(*) total FROM ubicaciones WHERE codigo = ?'".$ubicacion."' desde la función inventariar_ubicacion()", "error"); // ANOTAR EVENTO EN LA BD
				return "FALLO CONSULTA";
			}
			if ($articulos == "ninguno") {
				$conexion->close();
				return True;
			}
			$conexion->autocommit(false); // INICIAR TRANSACCIÓN
			$sentencia = $conexion->prepare("INSERT INTO stock VALUES (? , ?, ?)");
			$sentencia->bind_param("ssi", $ubicacion, $codigo, $cantidad);
			//RECORRER EL ARRAY DE UBICACIONES, SI UNA INSERCIÓN FALLA HACEMOS ROLLBACK Y DEVOLVEMOS FALSE
			foreach ($articulos as $articulo) {
				$codigo = $articulo[0];
				$cantidad = $articulo[1];
				if (empty($cantidad) or !preg_match('/^[1-9]+[0-9]*$/', $cantidad)) {
					$conexion->rollback();
					$conexion->close();
					return "FALLO CANTIDAD";
				}
				if (!$sentencia->execute()) {
					registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar ejecutar la query INSERT INTO stock VALUES ('".$ubicacion."', '".$codigo."', '".$cantidad."') desde la función inventariar_ubicacion()", "error"); // ANOTAR EVENTO EN LA BD
					$conexion->rollback(); // SI LA CONSULTA FALLA CANCELAMOS LA TRANSACCIÓN
					return "FALLO CONSULTA";
				}
			}
			$conexion->commit();
			return True;
		}
	}
	// FUNCIONES  PARA LA GESTIÓN DE ARTÍCULOS //
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA CARGAR LOS ARTÍCULOS DE LA BASE DE DATOS. PERMITE MOSTRAR TODOS LOS ARTÍCULOS O FILTRARLOS POR NOMBRE/DESCRIPCIÓN.
		RESULTADO: DEVUELVE UN ARRAY CON ARTÍCULOS EN CASO DE ENCONTRAR RESULTADOS, "ERROR EN LA BD" EN CASO DE FALLAR LA CONEXIÓN CON LA BD, "FALLO CONSULTA" EN CASO FALLAR LA CONSULTA, "NO ARTÍCULOS" EN CASO DE QUE NO ENCUENTRE ARTÍCULOS
		LLAMADA: ES LLAMADA CADA VEZ QUE SE CARGA LA PÁGINA ARTÍCULOS O DESDE AJAX (recargar_articulos.php)
		PARÁMETROS:
		- FILTRO: INDICA EL FILTRO UTILIZADO PARA MOSTRAR LOS ARTÍCULOS.
	*/
	function ver_articulos($filtro = "ninguno") {
		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD
		// MOSTRAR TODAS LAS FILAS
		if ($filtro == "ninguno") {
			$resultado = $conexion->query("SELECT codigo, descripcion, observaciones FROM articulos ORDER BY codigo");
			if ($resultado->num_rows <= 0) { // COMPROBAR SI LA CONSULTA HA DEVULETO FILAS
				$conexion->close();
				return "NO ARTICULOS";
			} else {
				while ($fila = $resultado->fetch_assoc()) { // RECORRER EL RESULTADO E IR AÑADIENDO LAS FILAS AL ARRAY $articulos
					$articulos[] = $fila;
				}
				$conexion->close();
				return $articulos;
			}
		// BUSCAR LAS UBICACIONES POR NOMBRE O DESCRIPCIÓN
		} else {
			$filtro = strtoupper($filtro);
			$filtro = "%".$filtro."%";
			$sentencia = $conexion->prepare("SELECT codigo, descripcion, observaciones FROM articulos WHERE UPPER(codigo) LIKE ? OR UPPER(descripcion) LIKE ? ORDER BY codigo");
			$sentencia->bind_param("ss", $filtro, $filtro);
			if (!$sentencia->execute()) { // COMPROBAR SI HA FALLADO LA CONSULTA
				$conexion->close();
				registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al ejecutar la query SELECT codigo, descripcion, observaciones FROM ubicaciones WHERE codigo LIKE '".$filtro."' OR descripcion LIKE '".$filtro."' ORDER BY codigo  desde la función ver_ubicaciones()", "error"); // ANOTAR EVENTO EN LA BD
				return "FALLO CONSULTA";
			} else {
				$resultado = $sentencia->get_result();
				if ($resultado->num_rows == 0) { // COMPROBAR SI HA DEVULETO FILAS
					$conexion->close();
					return "NO ARTICULOS";
				} else {
					while ($fila = $resultado->fetch_assoc()) { // RECORRER EL RESULTADO E IR AÑADIENDO LAS FILAS AL ARRAY $usuarios
						$articulos[] = $fila;
					}
					$conexion->close();
					return $articulos;
				}
			}
		}
	}
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA CREAR UN ARTÍCULO EN LA BD DESDE UN FORMULARIO.
		RESULTADO: DEVUELVE TRUE EN CASO DE CREAR EL ARTÍCULO, "ERROR EN LA BD" SI NO HAY CONECTIVIDAD CON LA BD, "FALLO CODIGO" SI NO SE HA PASADO O ES INCORRECTO, "FALLO DESCRIPCION" SI NO SE HA PASADO, "FALLO CONSULTA" SI FALLA LA EJECUCIÓN "FALLO CREAR" SI NO SE HA PODIDO CREAR.
		LLAMADA: ES LLAMADA CADA VEZ QUE SE CREA UN USUARIO (crear_ubicacion.php)
		PARÁMETROS:
		- CÓDIGO: CÓDIGO DEL ARTÍCULO. ES LA CLAVE PRIMARIA DE LA TABLE. NO PUEDE SER NULL
		- DESCRIPCIÓN: DESCRIPCIÓN DEL ARTÍCULO. NO PUEDE SER NULL
		- OBSERVACIONES: COMENTARIOS ADICIONALES DEL ARTÍCULO. PUEDE SER NULL
	*/
	function crear_articulo($codigo, $descripcion, $observaciones) {
		// VALIDAR LOS DATOS INTRODUCIDOS
		if (empty($codigo) or !preg_match('/^[[:alpha:]]{2}\d{2}$/', $codigo)) { // COMPROBAR QUE SE HAYA PASADO UN CÓDIGO Y ESTE TENGA UN FORMATO CORRECTO
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar modificar crear una ubicación código inválido '".$codigo."' crear_articulo()", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CODIGO";
		}
		if (empty($descripcion)) {
			return "FALLO DESCRIPCION";
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar crear una ubicación sin indicar su descripción crear_articulo()", "error"); // ANOTAR EVENTO EN LA BD
		}	else $descripcion = ucwords(strtolower($descripcion)); // FORMATEAR LA VARIABLE, PRIMERA LETRA MAYÚSCULA DE CADA PALABRA, RESTO MINÚNSCULA
		if (empty($observaciones)) $observaciones = null;
		else $observaciones = ucwords(strtolower($observaciones)); // FORMATEAR LA VARIABLE, PRIMERA LETRA MAYÚSCULA DE CADA PALABRA, RESTO MINÚNSCULA

		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD

		// INSERTAR EN LA BD
		$sentencia = $conexion->prepare("INSERT INTO articulos VALUES(?, ?, ?)");
		$sentencia->bind_param("sss", $codigo, $descripcion, $observaciones);
		if (!$sentencia->execute()) {
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al intentar ejecutar la query INSERT INTO articulos VALUES('".$codigo."', '".$descripcion."', '".$observaciones."') desde la función crear_articulo()", "error");
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
		DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA ELIMINAR UN ARTÍCULO DE LA BASE DE DATOS. UTILIZAR FUNCIÓN strtolower PARA HACERLA NO KEY SENSITIVE
		RESULTADO: DEVUELVE TRUE SI ELIMINA LA FILA, "FALLO CODIGO" SI NO SE HA PASADO UN CÓDIGO, "ERROR EN LA BD" SI FALLA LA CONEXIÓN, "FALLO CONSULTA" SI NO SE PUEDE EJECUTAR EL DELETE, "NO ELIMINADO" SI NO SE ELIMINA
		LLAMADA: ES LLAMADA DESDE eliminar_articulo.php
		PARÁMETROS:
			- CODIGO: RECIBE UN CODIGO QUE BORRAR
	*/
	function borrar_articulo($codigo) {
		if (empty($codigo)) {
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al llamar a la función borrar_articulo() sin pasarle un código", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CODIGO";
		}
		else $codigo = strtoupper($codigo); // CONVERTIR EMAIL A MINÚSCULAS
		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD
		$sentencia = $conexion->prepare("DELETE FROM articulos WHERE codigo = ?");
		$sentencia->bind_param("s", $codigo);
		if (!$sentencia->execute()) { // COMPROBAR QUE SE HAYA PODIDO EJECUTAR LA CONSULTA
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al intentar ejecutar la query DELETE FROM articulos WHERE codigo = ".$codigo." desde la función borrar_articulo()", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		}	elseif ($sentencia->affected_rows > 0) {  // COMPROBAR QUE SE HAYA ELIMINADO ALGUNA
			$conexion->close();
			return True;
		} else {
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "No se ha podido eliminar el artículo ".$codigo." desde la función borrar_articulo()", "error"); // ANOTAR EVENTO EN LA BD
			return "NO ELIMINADO";
		}
	}
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA MODIFICAR UNA ARTÍCULO DESDE UN FORM.
		RESULTADO: DEVUELVE TRUE SI MODIFICA LA FILA, "ERROR EN LA BD" SI FALLA LA CONEXIÓN, "FALLO CONSULTA" SI NO SE PUEDE EJECUTAR EL UPDATE, "NO MODIFICADO" SI NO SE MODIFICA, "FALLO CODIGO" SI NO SE PASA CÓDIGO O ES INCORRECTO, "FALLO DESCRIPCION" SI NO SE PASA
		LLAMADA: ES LLAMADA DESDE eliminar_articulo.php
		PARÁMETROS:
		- CÓDIGO: CÓDIGO DEL ARTÍCULO. ES LA CLAVE PRIMARIA DE LA TABLE. NO PUEDE SER NULL. SE UTILIZA PARA SABER QUE ARTÍCULO MODIFICAR
		- DESCRIPCIÓN: DESCRIPCIÓN DEL ARTÍCULO. NO PUEDE SER NULL. NUEVO VALOR PARA EL CAMPO
		- OBSERVACIONES: COMENTARIOS ADICIONALES DEL ARTÍCULO. PUEDE SER NULL. NUEVO VALOR PARA EL CAMPO
	*/
	function modificar_articulo($codigo, $descripcion, $observaciones) {
		// VALIDAR LOS DATOS INTRODUCIDOS
		if (empty($codigo) or !preg_match('/^[[:alpha:]]{2}\d{2}$/', $codigo)) {
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar modificar una artículo con el siguiente código inválido '".$codigo."' modificar_articulo()", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CODIGO";
		}
		if (empty($descripcion)) {
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar modificar un artículo sin indicar su descripción modificar_articulo()", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO DESCRIPCIÓN";
		} else $descripcion = ucwords(strtolower($descripcion)); // FORMATEAR LA DESCRIPCIÓN, PRIMERA LETRA MAYUS, EL RESTO MINUS
		if (empty($observaciones)) $observaciones = null;
		else $observaciones = ucwords(strtolower($observaciones)); // FORMATEAR LAS OBSERVACIONES, PRIMERA LETRA MAYUS, EL RESTO MINUS

		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD

		$sentencia = $conexion->prepare("UPDATE articulos SET descripcion = ?, observaciones = ? WHERE codigo = ?");
		$sentencia->bind_param("sss", $descripcion, $observaciones, $codigo);
		if (!$sentencia->execute()) {
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al intentar ejecutar la query UPDATE articulos SET descripcion = '".$descripcion."', observaciones = '".$observaciones."' WHERE codigo = '".$codigo."' desde la función modificar_articulo()", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		} elseif ($sentencia->affected_rows > 0) {  // COMPROBAR QUE SE HAYA MODIFICADO ALGUNA
			$conexion->close();
			return True;
		} else {
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "No se ha podido modificar el artículo ".$codigo." con la descripcion ".$descripcion." y las observaciones ".$observaciones." desde la función modificar_articulo()", "error"); // ANOTAR EVENTO EN LA BD
			return "NO MODIFICADO";
		}
	}

	// FUNCIONES  PARA LA GESTIÓN DE LOGS //
	// DOCUMENTAR
	function ver_logs($indice = 0, $inicio = "", $fin = "", $usuario = "", $descripcion = "", $tipo = "") {
		$conexion = conexion_database();
		$indice = $indice * 10;
		if (!empty($descripcion)) $descripcion = "%".$descripcion."%";
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD
		//CONSULTA PARA MOSTRAR LOS LOGS DE 10 EN 10 EN FUNCIÓN DE LOS FILTROS QUE HAYA INTRODUCIDO EL USUARIO
		$sentencia = $conexion->prepare("	SELECT fecha, usuario, descripcion, tipo
																			FROM logs
																			WHERE (? = '' OR fecha >= ?) 					AND
																						(? = '' OR fecha <= ?) 					AND
																						(? = '' OR usuario = ?) 				AND
																						(? = '' OR descripcion LIKE ?) 	AND
																						(? = '' OR tipo = ?)
																			ORDER BY fecha ASC
																			LIMIT ? , 10");
		$sentencia->bind_param("ssssssssssi", $inicio, $inicio, $fin, $fin, $usuario, $usuario, $descripcion, $descripcion, $tipo, $tipo, $indice);
		if (!$sentencia->execute()) { // COMPROBAR SI HA FALLADO LA CONSULTA
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al ejecutar consultar los logs desde la funcion ver_logs()", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		} else {
			$resultado = $sentencia->get_result();
			if ($resultado->num_rows == 0) { // COMPROBAR SI HA DEVULETO FILAS
				$conexion->close();
				return "NO LOGS";
			} else {
				while ($fila = $resultado->fetch_assoc()) { // RECORRER EL RESULTADO E IR AÑADIENDO LAS FILAS AL ARRAY $usuarios
					$logs[] = $fila;
				}
				$conexion->close();
				return $logs;
			}
		}
	}
	// DOCUMENTAR
	function contar_logs($inicio = "", $fin = "", $usuario = "", $descripcion = "", $tipo = "") {
		$conexion = conexion_database();
		if (!empty($descripcion)) $descripcion = "%".$descripcion."%";
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD
		$sentencia = $conexion->prepare("	SELECT COUNT(*) TOTAL
																			FROM logs
																			WHERE (? = '' OR fecha >= ?) 					AND
																						(? = '' OR fecha <= ?) 					AND
																						(? = '' OR usuario = ?) 				AND
																						(? = '' OR descripcion LIKE ?) 	AND
																						(? = '' OR tipo = ?)
																			");
		$sentencia->bind_param("isssssssss", $inicio, $inicio, $fin, $fin, $usuario, $usuario, $descripcion, $descripcion, $tipo, $tipo);
		if (!$sentencia->execute()) { // COMPROBAR SI HA FALLADO LA CONSULTA
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al ejecutar la query SELECT codigo, descripcion, observaciones FROM ubicaciones WHERE codigo LIKE '".$filtro."' OR descripcion LIKE '".$filtro."' ORDER BY codigo  desde la función ver_ubicaciones()", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		} else {
			$resultado = $sentencia->get_result();
			$resultado = $resultado->fetch_assoc();
			$resultado = ceil($resultado['TOTAL'] / 10);
			return $resultado;
			$conexion->close();
		}
	}

	// FUNCIONES  PARA LA GESTIÓN DE DATOS (EXPORTAR/IMPORTAR) //
	function ver_consultas($usuario) {
		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD
			$usuario = strtolower($usuario);
			$sentencia = $conexion->prepare("SELECT consulta FROM consultas WHERE usuario = ?");
			$sentencia->bind_param("s", $usuario);
			if (!$sentencia->execute()) { // COMPROBAR SI HA FALLADO LA CONSULTA
				$conexion->close();
				registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al ejecutar la query SELECT consulta FROM consultas WHERE usuario = '".$usuario."' desde la función ver_consultas()", "error"); // ANOTAR EVENTO EN LA BD
				return "FALLO CONSULTA";
			} else {
				$resultado = $sentencia->get_result();
				if ($resultado->num_rows == 0) { // COMPROBAR SI HA DEVULETO FILAS
					$conexion->close();
					return "NO CONSULTAS";
				} else {
					while ($fila = $resultado->fetch_assoc()) { // RECORRER EL RESULTADO E IR AÑADIENDO LAS FILAS AL ARRAY $consultas
						$consultas[] = $fila['consulta'];
					}
					$conexion->close();
					return $consultas;
				}
			}
		}
	function crear_consulta($usuario, $consulta) {
		// VALIDAR LOS DATOS INTRODUCIDOS
		if (empty($usuario)) {
			return "USUARIO ERRONEO";
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar almacenar una consulta con el campo usuario vacío desde la función crear_consulta()", "error"); // ANOTAR EVENTO EN LA BD
		}	else {
			$usuario = strtolower($usuario); // PASAR LA CONSULTA A MINÚSCULAS
		}

		if (empty($consulta)) {
			return "CONSULTA VACIA";
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar almacenar una consulta con el campo consulta vacío desde la función crear_consulta()", "error"); // ANOTAR EVENTO EN LA BD
		}	else {
			$consulta = strtolower($consulta); // PASAR LA CONSULTA A MINÚSCULAS
			$consulta = str_replace(";", "", $consulta); // ELIMINAR ";"
		}
		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD

		// INSERTAR EN LA BD
		$sentencia = $conexion->prepare("INSERT INTO consultas VALUES(?, ?)");
		$sentencia->bind_param("ss", $consulta, $usuario);
		if (!$sentencia->execute()) {
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al intentar ejecutar la query INSERT INTO consultas VALUES('".$usuario."', '".$consulta."') desde la función crear_consulta()", "error");
			return "FALLO CONSULTA";
		} elseif ($sentencia->affected_rows == 0) {
			$conexion->close();
			return "FALLO CREAR";
		} else {
			$conexion->close();
			return True;
		}
	}
	// EXPORTAR CSV

	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA GENERAR UN FICHERO CSV CON LA CONSULTA QUE INTRODUZCA EL USUARIO ADMINISTRADOR. ADEMÁS ANOTA UN LOG CON LA CONSULTA EJECUTADA
		RESULTADO: DEVUELVE UN FICHERO SI LA CONSULTA FUNCIONA Y ENCUENTRA FILAS, "CONSULTA VACIA" SI EL PARÁMETRO $consulta ESTÁ VACÍO, "ERROR EN LA BD" SI FALLA LA CONEXIÓN, "FALLO CONSULTA" SI NO SE PUEDE EJECUTAR LA CONSULTA, "CONSULTA SIN RESULTADOS" SI NO DEVUELVE NINGÚN RESULTADO
		LLAMADA: ES LLAMADA DESDE gestionar_datos.php
		PARÁMETROS:
		- CONSULTA: INSTRUCCIÓN A EJECUTAR. NO PUEDE ESTAR VACÍA
	*/
	function exportar_csv_consulta($consulta) {
		if (empty($consulta)) return "CONSULTA VACIA";
		$consulta = strtolower($consulta); // PASAR A MINÚSCULAS LA CADENA PARA EVITAR ERRORES PROVOCADOS POR ESCRIBIR EL NOMBRE DE LAS TABLAS EN MAYÚSCULA
		$consulta = str_replace(";", "", $consulta); // ELIMINAR ";"

		$conexion = conexion_database("inventario");
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD
		$resultado_query = $conexion->query($consulta); // EJECUTAR LA CONSULTA
		if (!is_object($resultado_query)) { // COMPROBAR SI DEVUELVE UN ARRAY, EN CASO NEGATIVO LA CONSULTA ES ERRÓNEA
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha intentado ejecutar una consulta errónea desde la función exportar_csv_consulta(".$consulta.")", "consulta");
			return "FALLO CONSULTA";
		} elseif ($resultado_query->num_rows <= 0) { // COMPROBAR SI NO HA DEVUELTO NINGUNA FILA
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha ejecutado una consulta desde la función exportar_csv_consulta(".$consulta.")", "consulta");
			return "CONSULTA SIN RESULTADOS";
		} else {
			registrar_evento(time(), $_SESSION['email'], "Se ha ejecutado una consulta desde la función exportar_csv_consulta(".$consulta.")", "consulta");
			$fichero = "";
			while ($fila = $resultado_query->fetch_assoc()) { // RECORRER TODAS LAS FILAS DEVUELTAS
				foreach ($fila as $campo) { // RECORRER LOS CAMPOS DE CADA FILA
					$fichero .= $campo.";"; // AÑADIMOS CAMPOS AL FICHERO
				}
				$fichero = substr($fichero, 0, -1); // ELIMINAR EL ÚLTIMO PUNTO Y COMA
				$fichero .= "\r\n"; // AÑADIMOS SALTO DE LÍNEA
			}
			// CAMBIAR LAS CABECERAS PARA QUE EL FICHERO SE DESCARGE
			header('Content-Type: text/csv; charset=utf-8');
			header("Content-Disposition: attachment; filename=consulta.csv");
			// Necesario instalar el siguiente paquete para ejecutar la función mb_convert_encoding -> apt-get install php7.0-xml php7.0-mbstring
			echo mb_convert_encoding($fichero, 'UTF-8');
			$conexion->close();
		}
	}
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA GENERAR UN FICHERO CSV CON LOS ARTÍCULOS DE LA BASE DE DATOS
		RESULTADO: DEVUELVE UN FICHERO SI LA CONSULTA FUNCIONA Y ENCUENTRA FILAS, "ERROR EN LA BD" SI FALLA LA CONEXIÓN, "FALLO CONSULTA" SI NO SE PUEDE EJECUTAR LA CONSULTA, "CONSULTA SIN RESULTADOS" SI NO DEVUELVE NINGÚN RESULTADO
		LLAMADA: ES LLAMADA DESDE gestionar_datos.php
		PARÁMETROS:
	*/
	function exportar_csv_tabla_articulos() {
		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD
		$resultado_query = $conexion->query("SELECT * FROM articulos"); // EJECUTAR LA CONSULTA
		if (!is_object($resultado_query)) { // COMPROBAR SI DEVUELVE UN ARRAY, EN CASO NEGATIVO LA CONSULTA HA FALLADO
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al ejecutar la query SELECT * FROM articulos desde la función exportar_csv_tabla_articulos", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		} elseif ($resultado_query->num_rows <= 0) {
			$conexion->close();
			return "CONSULTA SIN RESULTADOS";
		} else {
			$fichero = "";
			while ($fila = $resultado_query->fetch_assoc()) { // RECORRER TODAS LAS FILAS DEVUELTAS
				foreach ($fila as $campo) { // RECORRER LOS CAMPOS DE CADA FILA
					$fichero .= $campo.";"; // AÑADIMOS CAMPOS AL FICHERO
				}
				$fichero = substr($fichero, 0, -1); // ELIMINAR EL ÚLTIMO PUNTO Y COMA
				$fichero .= "\r\n"; // AÑADIMOS SALTO DE LÍNEA
			}
			// CAMBIAR LAS CABECERAS PARA QUE EL FICHERO SE DESCARGE
			header('Content-Type: text/csv; charset=utf-8');
			header("Content-Disposition: attachment; filename=articulos.csv");
			// Necesario instalar el siguiente paquete para ejecutar la función mb_convert_encoding -> apt-get install php7.0-xml php7.0-mbstring
			echo mb_convert_encoding($fichero, 'UTF-8');
			$conexion->close();
		}
	}
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA GENERAR UN FICHERO CSV CON LAS UBICACIONES DE LA BASE DE DATOS
		RESULTADO: DEVUELVE UN FICHERO SI LA CONSULTA FUNCIONA Y ENCUENTRA FILAS, "ERROR EN LA BD" SI FALLA LA CONEXIÓN, "FALLO CONSULTA" SI NO SE PUEDE EJECUTAR LA CONSULTA, "CONSULTA SIN RESULTADOS" SI NO DEVUELVE NINGÚN RESULTADO
		LLAMADA: ES LLAMADA DESDE gestionar_datos.php
		PARÁMETROS:
	*/
	function exportar_csv_tabla_ubicaciones() {
		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD
		$resultado_query = $conexion->query("SELECT * FROM ubicaciones"); // EJECUTAR LA CONSULTA
		if (!is_object($resultado_query)) { // COMPROBAR SI DEVUELVE UN ARRAY, EN CASO NEGATIVO LA CONSULTA HA FALLADO
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al ejecutar la query SELECT * FROM ubicaciones desde la función exportar_csv_tabla_ubicaciones", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		} elseif ($resultado_query->num_rows <= 0) {
			$conexion->close();
			return "CONSULTA SIN RESULTADOS";
		} else {
			$fichero = "";
			while ($fila = $resultado_query->fetch_assoc()) { // RECORRER TODAS LAS FILAS DEVUELTAS
				foreach ($fila as $campo) { // RECORRER LOS CAMPOS DE CADA FILA
					$fichero .= $campo.";"; // AÑADIMOS CAMPOS AL FICHERO
				}
				$fichero = substr($fichero, 0, -1); // ELIMINAR EL ÚLTIMO PUNTO Y COMA
				$fichero .= "\r\n"; // AÑADIMOS SALTO DE LÍNEA
			}
			// CAMBIAR LAS CABECERAS PARA QUE EL FICHERO SE DESCARGE
			header('Content-Type: text/csv; charset=utf-8');
			header("Content-Disposition: attachment; filename=ubicaciones.csv");
			// Necesario instalar el siguiente paquete para ejecutar la función mb_convert_encoding -> apt-get install php7.0-xml php7.0-mbstring
			echo mb_convert_encoding($fichero, 'UTF-8');
			$conexion->close();
		}
	}
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA GENERAR UN FICHERO CSV CON LOS USUARIOS DE LA BASE DE DATOS
		RESULTADO: DEVUELVE UN FICHERO SI LA CONSULTA FUNCIONA Y ENCUENTRA FILAS, "ERROR EN LA BD" SI FALLA LA CONEXIÓN, "FALLO CONSULTA" SI NO SE PUEDE EJECUTAR LA CONSULTA, "CONSULTA SIN RESULTADOS" SI NO DEVUELVE NINGÚN RESULTADO
		LLAMADA: ES LLAMADA DESDE gestionar_datos.php
		PARÁMETROS:
	*/
	function exportar_csv_tabla_usuarios() {
		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD
		$resultado_query = $conexion->query("SELECT * FROM usuarios"); // EJECUTAR LA CONSULTA
		if (!is_object($resultado_query)) { // COMPROBAR SI DEVUELVE UN ARRAY, EN CASO NEGATIVO LA CONSULTA HA FALLADO
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al ejecutar la query SELECT * FROM usuarios desde la función exportar_csv_tabla_usuarios", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		} elseif ($resultado_query->num_rows <= 0) {
			$conexion->close();
			return "CONSULTA SIN RESULTADOS";
		} else {
			$fichero = "";
			while ($fila = $resultado_query->fetch_assoc()) { // RECORRER TODAS LAS FILAS DEVUELTAS
				foreach ($fila as $campo) { // RECORRER LOS CAMPOS DE CADA FILA
					$fichero .= $campo.";"; // AÑADIMOS CAMPOS AL FICHERO
				}
				$fichero = substr($fichero, 0, -1); // ELIMINAR EL ÚLTIMO PUNTO Y COMA
				$fichero .= "\r\n"; // AÑADIMOS SALTO DE LÍNEA
			}
			// CAMBIAR LAS CABECERAS PARA QUE EL FICHERO SE DESCARGE
			header('Content-Type: text/csv; charset=utf-8');
			header("Content-Disposition: attachment; filename=usuarios.csv");
			// Necesario instalar el siguiente paquete para ejecutar la función mb_convert_encoding -> apt-get install php7.0-xml php7.0-mbstring
			echo mb_convert_encoding($fichero, 'UTF-8');
			$conexion->close();
		}
	}
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA GENERAR UN FICHERO CSV CON EL CONTENIDO DE LA TABLA STOCK
		RESULTADO: DEVUELVE UN FICHERO SI LA CONSULTA FUNCIONA Y ENCUENTRA FILAS, "ERROR EN LA BD" SI FALLA LA CONEXIÓN, "FALLO CONSULTA" SI NO SE PUEDE EJECUTAR LA CONSULTA, "CONSULTA SIN RESULTADOS" SI NO DEVUELVE NINGÚN RESULTADO
		LLAMADA: ES LLAMADA DESDE gestionar_datos.php
		PARÁMETROS:
	*/
	function exportar_csv_tabla_stock() {
		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD
		$resultado_query = $conexion->query("SELECT * FROM stock"); // EJECUTAR LA CONSULTA
		if (!is_object($resultado_query)) { // COMPROBAR SI DEVUELVE UN ARRAY, EN CASO NEGATIVO LA CONSULTA HA FALLADO
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al ejecutar la query SELECT * FROM stock desde la función exportar_csv_tabla_stock", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		} elseif ($resultado_query->num_rows <= 0) {
			$conexion->close();
			return "CONSULTA SIN RESULTADOS";
		} else {
			$fichero = "";
			while ($fila = $resultado_query->fetch_assoc()) { // RECORRER TODAS LAS FILAS DEVUELTAS
				foreach ($fila as $campo) { // RECORRER LOS CAMPOS DE CADA FILA
					$fichero .= $campo.";"; // AÑADIMOS CAMPOS AL FICHERO
				}
				$fichero = substr($fichero, 0, -1); // ELIMINAR EL ÚLTIMO PUNTO Y COMA
				$fichero .= "\r\n"; // AÑADIMOS SALTO DE LÍNEA
			}
			// CAMBIAR LAS CABECERAS PARA QUE EL FICHERO SE DESCARGE
			header('Content-Type: text/csv; charset=utf-8');
			header("Content-Disposition: attachment; filename=stock.csv");
			// Necesario instalar el siguiente paquete para ejecutar la función mb_convert_encoding -> apt-get install php7.0-xml php7.0-mbstring
			echo mb_convert_encoding($fichero, 'UTF-8');
			$conexion->close();
		}
	}
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA GENERAR UN FICHERO CSV CON EL CONTENIDO DE LA TABLA GESTIONA
		RESULTADO: DEVUELVE UN FICHERO SI LA CONSULTA FUNCIONA Y ENCUENTRA FILAS, "ERROR EN LA BD" SI FALLA LA CONEXIÓN, "FALLO CONSULTA" SI NO SE PUEDE EJECUTAR LA CONSULTA, "CONSULTA SIN RESULTADOS" SI NO DEVUELVE NINGÚN RESULTADO
		LLAMADA: ES LLAMADA DESDE gestionar_datos.php
		PARÁMETROS:
	*/
	function exportar_csv_tabla_gestiona() {
		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD
		$resultado_query = $conexion->query("SELECT * FROM gestiona"); // EJECUTAR LA CONSULTA
		if (!is_object($resultado_query)) { // COMPROBAR SI DEVUELVE UN ARRAY, EN CASO NEGATIVO LA CONSULTA HA FALLADO
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al ejecutar la query SELECT * FROM gestiona desde la función exportar_csv_tabla_articulos", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		} elseif ($resultado_query->num_rows <= 0) {
			$conexion->close();
			return "CONSULTA SIN RESULTADOS";
		} else {
			$fichero = "";
			while ($fila = $resultado_query->fetch_assoc()) { // RECORRER TODAS LAS FILAS DEVUELTAS
				foreach ($fila as $campo) { // RECORRER LOS CAMPOS DE CADA FILA
					$fichero .= $campo.";"; // AÑADIMOS CAMPOS AL FICHERO
				}
				$fichero = substr($fichero, 0, -1); // ELIMINAR EL ÚLTIMO PUNTO Y COMA
				$fichero .= "\r\n"; // AÑADIMOS SALTO DE LÍNEA
			}
			// CAMBIAR LAS CABECERAS PARA QUE EL FICHERO SE DESCARGE
			header('Content-Type: text/csv; charset=utf-8');
			header("Content-Disposition: attachment; filename=gestiona.csv");
			// Necesario instalar el siguiente paquete para ejecutar la función mb_convert_encoding -> apt-get install php7.0-xml php7.0-mbstring
			echo mb_convert_encoding($fichero, 'UTF-8');
			$conexion->close();
		}
	}
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA GENERAR UN FICHERO CSV CON EL CONTENIDO DE LA TABLA GESTIONA
		RESULTADO: DEVUELVE UN FICHERO SI LA CONSULTA FUNCIONA Y ENCUENTRA FILAS, "ERROR EN LA BD" SI FALLA LA CONEXIÓN, "FALLO CONSULTA" SI NO SE PUEDE EJECUTAR LA CONSULTA, "CONSULTA SIN RESULTADOS" SI NO DEVUELVE NINGÚN RESULTADO
		LLAMADA: ES LLAMADA DESDE gestionar_datos.php
		PARÁMETROS:
	*/
	function exportar_csv_tabla_logs() {
		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD
		$resultado_query = $conexion->query("SELECT * FROM logs"); // EJECUTAR LA CONSULTA
		if (!is_object($resultado_query)) { // COMPROBAR SI DEVUELVE UN ARRAY, EN CASO NEGATIVO LA CONSULTA HA FALLADO
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al ejecutar la query SELECT * FROM logs desde la función exportar_csv_tabla_logs", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		} elseif ($resultado_query->num_rows <= 0) {
			$conexion->close();
			return "CONSULTA SIN RESULTADOS";
		} else {
			$fichero = "";
			while ($fila = $resultado_query->fetch_assoc()) { // RECORRER TODAS LAS FILAS DEVUELTAS
				foreach ($fila as $campo) { // RECORRER LOS CAMPOS DE CADA FILA
					$fichero .= $campo.";"; // AÑADIMOS CAMPOS AL FICHERO
				}
				$fichero = substr($fichero, 0, -1); // ELIMINAR EL ÚLTIMO PUNTO Y COMA
				$fichero .= "\r\n"; // AÑADIMOS SALTO DE LÍNEA
			}
			// CAMBIAR LAS CABECERAS PARA QUE EL FICHERO SE DESCARGE
			header('Content-Type: text/csv; charset=utf-8');
			header("Content-Disposition: attachment; filename=logs.csv");
			// Necesario instalar el siguiente paquete para ejecutar la función mb_convert_encoding -> apt-get install php7.0-xml php7.0-mbstring
			echo mb_convert_encoding($fichero, 'UTF-8');
			$conexion->close();
		}
	}
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA GENERAR UN FICHERO CSV CON EL CONTENIDO DE LA TABLA GESTIONA
		RESULTADO: DEVUELVE UN FICHERO SI LA CONSULTA FUNCIONA Y ENCUENTRA FILAS, "ERROR EN LA BD" SI FALLA LA CONEXIÓN, "FALLO CONSULTA" SI NO SE PUEDE EJECUTAR LA CONSULTA, "CONSULTA SIN RESULTADOS" SI NO DEVUELVE NINGÚN RESULTADO
		LLAMADA: ES LLAMADA DESDE gestionar_datos.php
		PARÁMETROS:
	*/
	function exportar_csv_tabla_consultas() {
		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD
		$resultado_query = $conexion->query("SELECT * FROM consultas"); // EJECUTAR LA CONSULTA
		if (!is_object($resultado_query)) { // COMPROBAR SI DEVUELVE UN ARRAY, EN CASO NEGATIVO LA CONSULTA HA FALLADO
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al ejecutar la query SELECT * FROM consultas desde la función exportar_csv_tabla_consultas", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		} elseif ($resultado_query->num_rows <= 0) {
			$conexion->close();
			return "CONSULTA SIN RESULTADOS";
		} else {
			$fichero = "";
			while ($fila = $resultado_query->fetch_assoc()) { // RECORRER TODAS LAS FILAS DEVUELTAS
				foreach ($fila as $campo) { // RECORRER LOS CAMPOS DE CADA FILA
					$fichero .= $campo.";"; // AÑADIMOS CAMPOS AL FICHERO
				}
				$fichero = substr($fichero, 0, -1); // ELIMINAR EL ÚLTIMO PUNTO Y COMA
				$fichero .= "\r\n"; // AÑADIMOS SALTO DE LÍNEA
			}
			// CAMBIAR LAS CABECERAS PARA QUE EL FICHERO SE DESCARGE
			header('Content-Type: text/csv; charset=utf-8');
			header("Content-Disposition: attachment; filename=consultas.csv");
			// Necesario instalar el siguiente paquete para ejecutar la función mb_convert_encoding -> apt-get install php7.0-xml php7.0-mbstring
			echo mb_convert_encoding($fichero, 'UTF-8');
			$conexion->close();
		}
	}
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA GENERAR UN FICHERO CSV CON EL CONTENIDO DE LA UBICACIÓN QUE SE PASE POR PARÁMETRO
		RESULTADO: DEVUELVE UN FICHERO SI LA CONSULTA FUNCIONA Y ENCUENTRA FILAS, "ERROR EN LA BD" SI FALLA LA CONEXIÓN, "FALLO CONSULTA" SI NO SE PUEDE EJECUTAR LA CONSULTA, "CONSULTA SIN RESULTADOS" SI NO DEVUELVE NINGÚN RESULTADO
		LLAMADA: ES LLAMADA DESDE gestionar_datos.php
		PARÁMETROS:
			-UBICACION: CÓDIGO DE LA UBICACION DE LA QUE SE VA A HACER UN LISTADO DE SUS ARTÍCULOS
	*/
	function exportar_csv_contenido_ubicacion($ubicacion) {
		if (empty($ubicacion)) return "CONSULTA VACIA";
		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD
		$sentencia = $conexion->prepare("SELECT a.codigo, a.descripcion, a.observaciones, s.cantidad FROM stock s LEFT JOIN articulos a ON s.articulo = a.codigo WHERE s.ubicacion = ?");
		$sentencia->bind_param("s", $ubicacion);
		if (!$sentencia->execute()) { // COMPROBAR SI HA FALLADO LA CONSULTA
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al ejecutar la query SELECT a.codigo, a.descripcion, a.observaciones, s.cantidad FROM stock s LEFT JOIN articulos a ON s.articulo = a.codigo WHERE s.ubicacion = '".$ubicacion."' desde la funcion exportar_csv_contenido_ubicacion()", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		} else {
			$resultado_query = $sentencia->get_result();
			if ($resultado_query->num_rows == 0) { // COMPROBAR SI HA DEVULETO FILAS
				$conexion->close();
				return "CONSULTA SIN RESULTADOS";
			} else {
				$fichero = "";
				while ($fila = $resultado_query->fetch_assoc()) { // RECORRER TODAS LAS FILAS DEVUELTAS
					foreach ($fila as $campo) { // RECORRER LOS CAMPOS DE CADA FILA
						$fichero .= $campo.";"; // AÑADIMOS CAMPOS AL FICHERO
					}
					$fichero = substr($fichero, 0, -1); // ELIMINAR EL ÚLTIMO PUNTO Y COMA
					$fichero .= "\r\n"; // AÑADIMOS SALTO DE LÍNEA
				}
				// CAMBIAR LAS CABECERAS PARA QUE EL FICHERO SE DESCARGE
				header('Content-Type: text/csv; charset=utf-8');
				header("Content-Disposition: attachment; filename=".$ubicacion.".csv");
				// Necesario instalar el siguiente paquete para ejecutar la función mb_convert_encoding -> apt-get install php7.0-xml php7.0-mbstring
				echo mb_convert_encoding($fichero, 'UTF-8');
				$conexion->close();
			}
		}
	}
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA GENERAR UN FICHERO CSV CON LISTADO DE UBICACIONES DONDE SE ENCUENTRA UN DETERMINADO ARTÍCULO
		RESULTADO: DEVUELVE UN FICHERO SI LA CONSULTA FUNCIONA Y ENCUENTRA FILAS, "ERROR EN LA BD" SI FALLA LA CONEXIÓN, "FALLO CONSULTA" SI NO SE PUEDE EJECUTAR LA CONSULTA, "CONSULTA SIN RESULTADOS" SI NO DEVUELVE NINGÚN RESULTADO
		LLAMADA: ES LLAMADA DESDE gestionar_datos.php
		PARÁMETROS:
			-ARTICULO: CÓDIGO DEL ARTÍCULO DEL QUE SE VA A REALIZAR EL LISTADO
	*/
	function exportar_csv_ubicaciones($articulo) {
		if (empty($articulo)) return "CONSULTA VACIA";
		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD
		$sentencia = $conexion->prepare("SELECT u.codigo, u.descripcion, u.observaciones FROM ubicaciones u WHERE u.codigo IN (SELECT ubicacion FROM stock WHERE articulo = ? )");
		$sentencia->bind_param("s", $articulo);
		if (!$sentencia->execute()) { // COMPROBAR SI HA FALLADO LA CONSULTA
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al ejecutar la query SELECT u.codigo, u.descripcion, u.observaciones FROM ubicaciones u WHERE u.codigo IN (SELECT ubicacion FROM stock WHERE articulo = '".$articulo."') desde la funcion exportar_csv_ubicaciones()", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		} else {
			$resultado_query = $sentencia->get_result();
			if ($resultado_query->num_rows == 0) { // COMPROBAR SI HA DEVULETO FILAS
				$conexion->close();
				return "CONSULTA SIN RESULTADOS";
			} else {
				$fichero = "";
				while ($fila = $resultado_query->fetch_assoc()) { // RECORRER TODAS LAS FILAS DEVUELTAS
					foreach ($fila as $campo) { // RECORRER LOS CAMPOS DE CADA FILA
						$fichero .= $campo.";"; // AÑADIMOS CAMPOS AL FICHERO
					}
					$fichero = substr($fichero, 0, -1); // ELIMINAR EL ÚLTIMO PUNTO Y COMA
					$fichero .= "\r\n"; // AÑADIMOS SALTO DE LÍNEA
				}
				// CAMBIAR LAS CABECERAS PARA QUE EL FICHERO SE DESCARGE
				header('Content-Type: text/csv; charset=utf-8');
				header("Content-Disposition: attachment; filename=".$articulo.".csv");
				// Necesario instalar el siguiente paquete para ejecutar la función mb_convert_encoding -> apt-get install php7.0-xml php7.0-mbstring
				echo mb_convert_encoding($fichero, 'UTF-8');
				$conexion->close();
			}
		}
	}

	// EXPORTAR EXCEL

	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA GENERAR UN FICHERO EXCEL CON LA CONSULTA QUE INTRODUZCA EL USUARIO ADMINISTRADOR. ADEMÁS ANOTA UN LOG CON LA CONSULTA EJECUTADA
		RESULTADO: DEVUELVE UN FICHERO SI LA CONSULTA FUNCIONA Y ENCUENTRA FILAS, "CONSULTA VACIA" SI EL PARÁMETRO $consulta ESTÁ VACÍO, "ERROR EN LA BD" SI FALLA LA CONEXIÓN, "FALLO CONSULTA" SI NO SE PUEDE EJECUTAR LA CONSULTA, "CONSULTA SIN RESULTADOS" SI NO DEVUELVE NINGÚN RESULTADO
		LLAMADA: ES LLAMADA DESDE gestionar_datos.php
		PARÁMETROS:
		- CONSULTA: INSTRUCCIÓN A EJECUTAR. NO PUEDE ESTAR VACÍA
	*/
	function exportar_excel_consulta($consulta) {
		require "PHP-EXCEL/vendor/autoload.php"; // INCLUIR LIBRERÍA
		if (empty($consulta)) return "CONSULTA VACIA";
		$consulta = strtolower($consulta); // PASAR A MINÚSCULAS LA CADENA PARA EVITAR ERRORES PROVOCADOS POR ESCRIBIR EL NOMBRE DE LAS TABLAS EN MAYÚSCULA
		$consulta = str_replace(";", "", $consulta); // ELIMINAR ";"

		$conexion = conexion_database("inventario");
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD
		$resultado_query = $conexion->query($consulta); // EJECUTAR LA CONSULTA
		if (!is_object($resultado_query)) { // COMPROBAR SI DEVUELVE UN ARRAY, EN CASO NEGATIVO LA CONSULTA ES ERRÓNEA
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha intentado ejecutar una consulta errónea desde la función exportar_excel_consulta(".$consulta.")", "consulta");
			return "FALLO CONSULTA";
		} elseif ($resultado_query->num_rows <= 0) { // COMPROBAR SI NO HA DEVUELTO NINGUNA FILA
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha ejecutado una consulta desde la función exportar_excel_consulta(".$consulta.")", "consulta");
			return "CONSULTA SIN RESULTADOS";
		} else {
			$contenido = array();
			$contador = 0;
			while ($fila = $resultado_query->fetch_assoc()) { // RECORRER TODAS LAS FILAS DEVUELTAS PARA METERLAS EN DOS ARRAYS ($cabeceras Y $contenido)
				foreach ($fila as $indice => $campo) { // RECORRER LOS CAMPOS DE CADA FILA
					$cabeceras[$indice] = $indice; // ALMACENAR EL NOMBRE DE LAS CABECERAS
					$contenido[$contador][$indice] = $campo; // AÑADIMOS CAMPOS AL FICHERO
				}
				$contador++;
			}
			$conexion->close();
			// PASAR ESTOS DOS ARRAYS A UN FICHERO EXCEL HACIENDO USO DE LA LIBRERÍA PHP-EXCEL
			$contenido = new ArrayIterator($contenido);
			// CREAR LAS CABECERAS
			foreach ($cabeceras as $cabecera) {
				$cabecera_string[] = new ExcelHelper\Column('".$cabecera."',  '".$cabecera."',     10,     new ExcelHelper\CellStyle\Text());
			}
			$columnCollection = new ExcelHelper\ColumnCollection($cabecera_string);
			$filename = sprintf('%s/consulta.xls', __DIR__, uniqid());
			$phpExcel = new ExcelHelper\TableWorkbook($filename);
			$worksheet = $phpExcel->addWorksheet("Consulta"); // TÍTULO DE LA OJA
			$table = new ExcelHelper\Table($worksheet, 0, 0, $consulta, $contenido);
			$table->setColumnCollection($columnCollection);
			$phpExcel->writeTable($table); // ESCRIBIR EL CONTENIDO
			$phpExcel->close();
			// CAMBIAR LAS CABECERAS PARA QUE SE DESCARGUE EL FICHERO
			header("Content-Type:   application/vnd.ms-excel; charset=utf-8");
			header('Content-Disposition: attachment; filename="consulta.xls"');
			readfile("consulta.xls");
			unlink("consulta.xls");

		}
	}
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA GENERAR UN FICHERO EXCEL CON LOS ARTÍCULOS DE LA BASE DE DATOS
		RESULTADO: DEVUELVE UN FICHERO SI LA CONSULTA FUNCIONA Y ENCUENTRA FILAS, "ERROR EN LA BD" SI FALLA LA CONEXIÓN, "FALLO CONSULTA" SI NO SE PUEDE EJECUTAR LA CONSULTA, "CONSULTA SIN RESULTADOS" SI NO DEVUELVE NINGÚN RESULTADO
		LLAMADA: ES LLAMADA DESDE gestionar_datos.php
		PARÁMETROS:
	*/
	function exportar_excel_tabla_articulos() {
		require "PHP-EXCEL/vendor/autoload.php"; // INCLUIR LIBRERÍA
		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD
		$resultado_query = $conexion->query("SELECT * FROM articulos"); // EJECUTAR LA CONSULTA
		if (!is_object($resultado_query)) { // COMPROBAR SI DEVUELVE UN ARRAY, EN CASO NEGATIVO LA CONSULTA HA FALLADO
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al ejecutar la query SELECT * FROM articulos desde la función exportar_excel_tabla_articulos", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		} elseif ($resultado_query->num_rows <= 0) {
			$conexion->close();
			return "CONSULTA SIN RESULTADOS";
		} else {
			$contenido = array();
			$contador = 0;
			while ($fila = $resultado_query->fetch_assoc()) { // RECORRER TODAS LAS FILAS DEVUELTAS
				foreach ($fila as $indice => $campo) { // RECORRER LOS CAMPOS DE CADA FILA
					$contenido[$contador][$indice] = $campo; // AÑADIMOS LOS CAMPOS AL ARRAY CONTENIDO
				}
				$contador++;
			}
			$conexion->close();

			// GENERAR EL EXCEL
			$contenido = new ArrayIterator($contenido);
			$columnCollection = new ExcelHelper\ColumnCollection([ // DEFINIR LAS COLUMNAS
			    new ExcelHelper\Column('codigo',  'Código',     10,     new ExcelHelper\CellStyle\Text()),
			    new ExcelHelper\Column('descripcion',  'Descripción',   15,     new ExcelHelper\CellStyle\Text()),
			    new ExcelHelper\Column('observaciones',  'Observaciones',     15,     new ExcelHelper\CellStyle\Text())
			]);
			$filename = sprintf('%s/articulos.xls', __DIR__, uniqid()); // NOMBRAR EL FICHERO
			$phpExcel = new ExcelHelper\TableWorkbook($filename); // CREAR EL FICHERO
			$worksheet = $phpExcel->addWorksheet('Articulos'); // DARLE NOMBRE A LA HOJA
			$table = new ExcelHelper\Table($worksheet, 0, 0, 'Contenido de la tabla artículos', $contenido);
			$table->setColumnCollection($columnCollection);
			$phpExcel->writeTable($table); // ESCRIBIR EL CONTENIDO EN EL EXCEL
			$phpExcel->close();
			// CAMBIAR LAS CABECERAS PARA QUE SE DESCARGUE EL FICHERO
			header("Content-Type:   application/vnd.ms-excel; charset=utf-8");
			header('Content-Disposition: attachment; filename="articulos.xls"');
			readfile("articulos.xls"); // DESCARGARLO EN EL CLIENTE
			unlink("articulos.xls"); // BORRAR EL FICHERO
		}
	}
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA GENERAR UN FICHERO EXCEL CON LAS UBICACIONES DE LA BASE DE DATOS
		RESULTADO: DEVUELVE UN FICHERO SI LA CONSULTA FUNCIONA Y ENCUENTRA FILAS, "ERROR EN LA BD" SI FALLA LA CONEXIÓN, "FALLO CONSULTA" SI NO SE PUEDE EJECUTAR LA CONSULTA, "CONSULTA SIN RESULTADOS" SI NO DEVUELVE NINGÚN RESULTADO
		LLAMADA: ES LLAMADA DESDE gestionar_datos.php
		PARÁMETROS:
	*/
	function exportar_excel_tabla_ubicaciones() {
		require "PHP-EXCEL/vendor/autoload.php"; // INCLUIR LIBRERÍA NECESARIA
		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD
		$resultado_query = $conexion->query("SELECT * FROM ubicaciones"); // EJECUTAR LA CONSULTA
		if (!is_object($resultado_query)) { // COMPROBAR SI DEVUELVE UN ARRAY, EN CASO NEGATIVO LA CONSULTA HA FALLADO
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al ejecutar la query SELECT * FROM ubicaciones desde la función exportar_excel_tabla_ubicaciones", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		} elseif ($resultado_query->num_rows <= 0) {
			$conexion->close();
			return "CONSULTA SIN RESULTADOS";
		} else {
			$contenido = array();
			$contador = 0;
			while ($fila = $resultado_query->fetch_assoc()) { // RECORRER TODAS LAS FILAS DEVUELTAS
				foreach ($fila as $indice => $campo) { // RECORRER LOS CAMPOS DE CADA FILA
					$contenido[$contador][$indice] = $campo; // AÑADIMOS LOS CAMPOS AL ARRAY CONTENIDO
				}
				$contador++;
			}
			$conexion->close();
			// GENERAR EL EXCEL
			$contenido = new ArrayIterator($contenido);
			$columnCollection = new ExcelHelper\ColumnCollection([ // DEFINIR LAS COLUMNAS
			    new ExcelHelper\Column('codigo',  'Código',     10,     new ExcelHelper\CellStyle\Text()),
			    new ExcelHelper\Column('descripcion',  'Descripción',   15,     new ExcelHelper\CellStyle\Text()),
			    new ExcelHelper\Column('observaciones',  'Observaciones',     15,     new ExcelHelper\CellStyle\Text())
			]);
			$filename = sprintf('%s/ubicaciones.xls', __DIR__, uniqid()); // NOMBRAR EL FICHERO
			$phpExcel = new ExcelHelper\TableWorkbook($filename); // CREAR EL FICHERO
			$worksheet = $phpExcel->addWorksheet('Ubicaciones'); // DARLE NOMBRE A LA HOJA
			$table = new ExcelHelper\Table($worksheet, 0, 0, 'Contenido de la tabla ubicaciones', $contenido);
			$table->setColumnCollection($columnCollection);
			$phpExcel->writeTable($table); // ESCRIBIR EL CONTENIDO EN EL EXCEL
			$phpExcel->close();
			// CAMBIAR LAS CABECERAS PARA QUE SE DESCARGUE EL FICHERO
			header("Content-Type:   application/vnd.ms-excel; charset=utf-8");
			header('Content-Disposition: attachment; filename="ubicaciones.xls"');
			readfile("ubicaciones.xls"); // DESCARGARLO EN EL CLIENTE
			unlink("ubicaciones.xls"); // BORRAR EL FICHERO
		}
	}
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA GENERAR UN FICHERO EXCEL CON LOS USUARIOS DE LA BASE DE DATOS
		RESULTADO: DEVUELVE UN FICHERO SI LA CONSULTA FUNCIONA Y ENCUENTRA FILAS, "ERROR EN LA BD" SI FALLA LA CONEXIÓN, "FALLO CONSULTA" SI NO SE PUEDE EJECUTAR LA CONSULTA, "CONSULTA SIN RESULTADOS" SI NO DEVUELVE NINGÚN RESULTADO
		LLAMADA: ES LLAMADA DESDE gestionar_datos.php
		PARÁMETROS:
	*/
	function exportar_excel_tabla_usuarios() {
		require "PHP-EXCEL/vendor/autoload.php"; // INCLUIR LIBRERÍA NECESARIA
		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD
		$resultado_query = $conexion->query("SELECT * FROM usuarios"); // EJECUTAR LA CONSULTA
		if (!is_object($resultado_query)) { // COMPROBAR SI DEVUELVE UN ARRAY, EN CASO NEGATIVO LA CONSULTA HA FALLADO
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al ejecutar la query SELECT * FROM usuarios desde la función exportar_excel_tabla_usuarios", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		} elseif ($resultado_query->num_rows <= 0) {
			$conexion->close();
			return "CONSULTA SIN RESULTADOS";
		} else {
			$contenido = array();
			$contador = 0;
			while ($fila = $resultado_query->fetch_assoc()) { // RECORRER TODAS LAS FILAS DEVUELTAS
				foreach ($fila as $indice => $campo) { // RECORRER LOS CAMPOS DE CADA FILA
					$contenido[$contador][$indice] = $campo; // AÑADIMOS LOS CAMPOS AL ARRAY CONTENIDO
				}
				$contador++;
			}
			$conexion->close();
			// GENERAR EL EXCEL
			$contenido = new ArrayIterator($contenido);
			$columnCollection = new ExcelHelper\ColumnCollection([ // DEFINIR LAS COLUMNAS
			    new ExcelHelper\Column('email',  'Código',     10,     new ExcelHelper\CellStyle\Text()),
			    new ExcelHelper\Column('password',  'Contraseña',   15,     new ExcelHelper\CellStyle\Text()),
			    new ExcelHelper\Column('nombre',  'Nombre',     15,     new ExcelHelper\CellStyle\Text()),
					new ExcelHelper\Column('tipo',  'Tipo de usuario',     15,     new ExcelHelper\CellStyle\Text())
			]);
			$filename = sprintf('%s/usuarios.xls', __DIR__, uniqid()); // NOMBRAR EL FICHERO
			$phpExcel = new ExcelHelper\TableWorkbook($filename); // CREAR EL FICHERO
			$worksheet = $phpExcel->addWorksheet('Usuarios'); // DARLE NOMBRE A LA HOJA
			$table = new ExcelHelper\Table($worksheet, 0, 0, 'Contenido de la tabla usuarios', $contenido);
			$table->setColumnCollection($columnCollection);
			$phpExcel->writeTable($table); // ESCRIBIR EL CONTENIDO EN EL EXCEL
			$phpExcel->close();
			// CAMBIAR LAS CABECERAS PARA QUE SE DESCARGUE EL FICHERO
			header("Content-Type:   application/vnd.ms-excel; charset=utf-8");
			header('Content-Disposition: attachment; filename="usuarios.xls"');
			readfile("usuarios.xls"); // DESCARGARLO EN EL CLIENTE
			unlink("usuarios.xls"); // BORRAR EL FICHERO
		}
	}
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA GENERAR UN FICHERO EXCEL CON EL CONTENIDO DE LA TABLA STOCK
		RESULTADO: DEVUELVE UN FICHERO SI LA CONSULTA FUNCIONA Y ENCUENTRA FILAS, "ERROR EN LA BD" SI FALLA LA CONEXIÓN, "FALLO CONSULTA" SI NO SE PUEDE EJECUTAR LA CONSULTA, "CONSULTA SIN RESULTADOS" SI NO DEVUELVE NINGÚN RESULTADO
		LLAMADA: ES LLAMADA DESDE gestionar_datos.php
		PARÁMETROS:
	*/
	function exportar_excel_tabla_stock() {
		require "PHP-EXCEL/vendor/autoload.php"; // INCLUIR LIBRERÍA NECESARIA
		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD
		$resultado_query = $conexion->query("SELECT * FROM stock"); // EJECUTAR LA CONSULTA
		if (!is_object($resultado_query)) { // COMPROBAR SI DEVUELVE UN ARRAY, EN CASO NEGATIVO LA CONSULTA HA FALLADO
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al ejecutar la query SELECT * FROM stock desde la función exportar_excel_tabla_stock", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		} elseif ($resultado_query->num_rows <= 0) {
			$conexion->close();
			return "CONSULTA SIN RESULTADOS";
		} else {
			$contenido = array();
			$contador = 0;
			while ($fila = $resultado_query->fetch_assoc()) { // RECORRER TODAS LAS FILAS DEVUELTAS
				foreach ($fila as $indice => $campo) { // RECORRER LOS CAMPOS DE CADA FILA
					$contenido[$contador][$indice] = $campo; // AÑADIMOS LOS CAMPOS AL ARRAY CONTENIDO
				}
				$contador++;
			}
			$conexion->close();
			// GENERAR EL EXCEL
			$contenido = new ArrayIterator($contenido);
			$columnCollection = new ExcelHelper\ColumnCollection([ // DEFINIR LAS COLUMNAS
			    new ExcelHelper\Column('ubicacion',  'Ubicación',     10,     new ExcelHelper\CellStyle\Text()),
			    new ExcelHelper\Column('articulo',  'Artículo',   15,     new ExcelHelper\CellStyle\Text()),
			    new ExcelHelper\Column('cantidad',  'Cantidad',     15,     new ExcelHelper\CellStyle\Text())
			]);
			$filename = sprintf('%s/stock.xls', __DIR__, uniqid()); // NOMBRAR EL FICHERO
			$phpExcel = new ExcelHelper\TableWorkbook($filename); // CREAR EL FICHERO
			$worksheet = $phpExcel->addWorksheet("Stock de las ubicaciones"); // DARLE NOMBRE A LA HOJA
			$table = new ExcelHelper\Table($worksheet, 0, 0, 'Contenido de la tabla stock', $contenido);
			$table->setColumnCollection($columnCollection);
			$phpExcel->writeTable($table); // ESCRIBIR EL CONTENIDO EN EL EXCEL
			$phpExcel->close();
			// CAMBIAR LAS CABECERAS PARA QUE SE DESCARGUE EL FICHERO
			header("Content-Type:   application/vnd.ms-excel; charset=utf-8");
			header('Content-Disposition: attachment; filename="stock.xls"');
			readfile("stock.xls"); // DESCARGARLO EN EL CLIENTE
			unlink("stock.xls"); // BORRAR EL FICHERO
		}
	}
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA GENERAR UN FICHERO EXCEL CON EL CONTENIDO DE LA TABLA GESTIONA
		RESULTADO: DEVUELVE UN FICHERO SI LA CONSULTA FUNCIONA Y ENCUENTRA FILAS, "ERROR EN LA BD" SI FALLA LA CONEXIÓN, "FALLO CONSULTA" SI NO SE PUEDE EJECUTAR LA CONSULTA, "CONSULTA SIN RESULTADOS" SI NO DEVUELVE NINGÚN RESULTADO
		LLAMADA: ES LLAMADA DESDE gestionar_datos.php
		PARÁMETROS:
	*/
	function exportar_excel_tabla_gestiona() {
		require "PHP-EXCEL/vendor/autoload.php"; // INCLUIR LIBRERÍA NECESARIA
		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD
		$resultado_query = $conexion->query("SELECT * FROM gestiona"); // EJECUTAR LA CONSULTA
		if (!is_object($resultado_query)) { // COMPROBAR SI DEVUELVE UN ARRAY, EN CASO NEGATIVO LA CONSULTA HA FALLADO
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al ejecutar la query SELECT * FROM gestiona desde la función exportar_excel_tabla_gestiona", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		} elseif ($resultado_query->num_rows <= 0) {
			$conexion->close();
			return "CONSULTA SIN RESULTADOS";
		} else {
			$contenido = array();
			$contador = 0;
			while ($fila = $resultado_query->fetch_assoc()) { // RECORRER TODAS LAS FILAS DEVUELTAS
				foreach ($fila as $indice => $campo) { // RECORRER LOS CAMPOS DE CADA FILA
					$contenido[$contador][$indice] = $campo; // AÑADIMOS LOS CAMPOS AL ARRAY CONTENIDO
				}
				$contador++;
			}
			$conexion->close();

			// GENERAR EL EXCEL
			$contenido = new ArrayIterator($contenido);
			$columnCollection = new ExcelHelper\ColumnCollection([ // DEFINIR LAS COLUMNAS
			    new ExcelHelper\Column('ubicacion',  'Ubicación',     10,     new ExcelHelper\CellStyle\Text()),
			    new ExcelHelper\Column('usuario',  'Usuario',   15,     new ExcelHelper\CellStyle\Text())
			]);
			$filename = sprintf('%s/gestiona.xls', __DIR__, uniqid()); // NOMBRAR EL FICHERO
			$phpExcel = new ExcelHelper\TableWorkbook($filename); // CREAR EL FICHERO
			$worksheet = $phpExcel->addWorksheet("Permisos sobre las ubicaciones"); // DARLE NOMBRE A LA HOJA
			$table = new ExcelHelper\Table($worksheet, 0, 0, 'Contenido de la tabla gestiona', $contenido);
			$table->setColumnCollection($columnCollection);
			$phpExcel->writeTable($table); // ESCRIBIR EL CONTENIDO EN EL EXCEL
			$phpExcel->close();
			// CAMBIAR LAS CABECERAS PARA QUE SE DESCARGUE EL FICHERO
			header("Content-Type:   application/vnd.ms-excel; charset=utf-8");
			header('Content-Disposition: attachment; filename="gestiona.xls"');
			readfile("gestiona.xls"); // DESCARGARLO EN EL CLIENTE
			unlink("gestiona.xls"); // BORRAR EL FICHERO
		}
	}
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA GENERAR UN FICHERO CSV CON EL CONTENIDO DE LA TABLA GESTIONA
		RESULTADO: DEVUELVE UN FICHERO SI LA CONSULTA FUNCIONA Y ENCUENTRA FILAS, "ERROR EN LA BD" SI FALLA LA CONEXIÓN, "FALLO CONSULTA" SI NO SE PUEDE EJECUTAR LA CONSULTA, "CONSULTA SIN RESULTADOS" SI NO DEVUELVE NINGÚN RESULTADO
		LLAMADA: ES LLAMADA DESDE gestionar_datos.php
		PARÁMETROS:
	*/
	function exportar_excel_tabla_logs() {
		require "PHP-EXCEL/vendor/autoload.php"; // INCLUIR LIBRERÍA NECESARIA
		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD
		$resultado_query = $conexion->query("SELECT * FROM logs"); // EJECUTAR LA CONSULTA
		if (!is_object($resultado_query)) { // COMPROBAR SI DEVUELVE UN ARRAY, EN CASO NEGATIVO LA CONSULTA HA FALLADO
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al ejecutar la query SELECT * FROM logs desde la función exportar_excel_tabla_logs", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		} elseif ($resultado_query->num_rows <= 0) {
			$conexion->close();
			return "CONSULTA SIN RESULTADOS";
		} else {
			$contenido = array();
			$contador = 0;
			while ($fila = $resultado_query->fetch_assoc()) { // RECORRER TODAS LAS FILAS DEVUELTAS
				foreach ($fila as $indice => $campo) { // RECORRER LOS CAMPOS DE CADA FILA
					if ($indice === "fecha") $campo = date("d/m/Y H:i:s", $campo);
					$contenido[$contador][$indice] = $campo; // AÑADIMOS LOS CAMPOS AL ARRAY CONTENIDO
				}
				$contador++;
			}
			$conexion->close();
			// GENERAR EL EXCEL
			$contenido = new ArrayIterator($contenido);
			$columnCollection = new ExcelHelper\ColumnCollection([ // DEFINIR LAS COLUMNAS
			    new ExcelHelper\Column('fecha',  'Fecha',     10,     new ExcelHelper\CellStyle\Text()),
			    new ExcelHelper\Column('usuario',  'Usuario',   15,     new ExcelHelper\CellStyle\Text()),
					new ExcelHelper\Column('descripcion',  'Descripción del log',   15,     new ExcelHelper\CellStyle\Text()),
					new ExcelHelper\Column('tipo',  'Tipo de log',   15,     new ExcelHelper\CellStyle\Text())
			]);
			$filename = sprintf('%s/logs.xls', __DIR__, uniqid()); // NOMBRAR EL FICHERO
			$phpExcel = new ExcelHelper\TableWorkbook($filename); // CREAR EL FICHERO
			$worksheet = $phpExcel->addWorksheet("Logs"); // DARLE NOMBRE A LA HOJA
			$table = new ExcelHelper\Table($worksheet, 0, 0, 'Contenido de la tabla logs', $contenido);
			$table->setColumnCollection($columnCollection);
			$phpExcel->writeTable($table); // ESCRIBIR EL CONTENIDO EN EL EXCEL
			$phpExcel->close();
			// CAMBIAR LAS CABECERAS PARA QUE SE DESCARGUE EL FICHERO
			header("Content-Type:   application/vnd.ms-excel; charset=utf-8");
			header('Content-Disposition: attachment; filename="logs.xls"');
			readfile("logs.xls"); // DESCARGARLO EN EL CLIENTE
			unlink("logs.xls"); // BORRAR EL FICHERO
		}
	}
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA GENERAR UN FICHERO CSV CON EL CONTENIDO DE LA TABLA GESTIONA
		RESULTADO: DEVUELVE UN FICHERO SI LA CONSULTA FUNCIONA Y ENCUENTRA FILAS, "ERROR EN LA BD" SI FALLA LA CONEXIÓN, "FALLO CONSULTA" SI NO SE PUEDE EJECUTAR LA CONSULTA, "CONSULTA SIN RESULTADOS" SI NO DEVUELVE NINGÚN RESULTADO
		LLAMADA: ES LLAMADA DESDE gestionar_datos.php
		PARÁMETROS:
	*/
	function exportar_excel_tabla_consultas() {
		require "PHP-EXCEL/vendor/autoload.php";
		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD
		$resultado_query = $conexion->query("SELECT * FROM consultas"); // EJECUTAR LA CONSULTA
		if (!is_object($resultado_query)) { // COMPROBAR SI DEVUELVE UN ARRAY, EN CASO NEGATIVO LA CONSULTA HA FALLADO
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al ejecutar la query SELECT * FROM consultas desde la función exportar_excel_tabla_consultas", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		} elseif ($resultado_query->num_rows <= 0) {
			$conexion->close();
			return "CONSULTA SIN RESULTADOS";
		} else {
			$contenido = array();
			$contador = 0;
			while ($fila = $resultado_query->fetch_assoc()) { // RECORRER TODAS LAS FILAS DEVUELTAS
				foreach ($fila as $indice => $campo) { // RECORRER LOS CAMPOS DE CADA FILA
					$contenido[$contador][$indice] = $campo; // AÑADIMOS LOS CAMPOS AL ARRAY CONTENIDO
				}
				$contador++;
			}
			$conexion->close();
			// GENERAR EL EXCEL
			$contenido = new ArrayIterator($contenido);
			$columnCollection = new ExcelHelper\ColumnCollection([ // DEFINIR LAS COLUMNAS
			    new ExcelHelper\Column('consulta',  'consulta',     10,     new ExcelHelper\CellStyle\Text()),
			    new ExcelHelper\Column('usuario',  'Usuario',   15,     new ExcelHelper\CellStyle\Text())
			]);
			$filename = sprintf('%s/consultas.xls', __DIR__, uniqid()); // NOMBRAR EL FICHERO
			$phpExcel = new ExcelHelper\TableWorkbook($filename); // CREAR EL FICHERO
			$worksheet = $phpExcel->addWorksheet("Logs"); // DARLE NOMBRE A LA HOJA
			$table = new ExcelHelper\Table($worksheet, 0, 0, 'Contenido de la tabla consultas', $contenido);
			$table->setColumnCollection($columnCollection);
			$phpExcel->writeTable($table); // ESCRIBIR EL CONTENIDO EN EL EXCEL
			$phpExcel->close();
			// CAMBIAR LAS CABECERAS PARA QUE SE DESCARGUE EL FICHERO
			header("Content-Type:   application/vnd.ms-excel; charset=utf-8");
			header('Content-Disposition: attachment; filename="consultas.xls"');
			readfile("consultas.xls"); // DESCARGARLO EN EL CLIENTE
			unlink("consultas.xls"); // BORRAR EL FICHERO
		}
	}
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA GENERAR UN FICHERO EXCEL CON EL CONTENIDO DE LA UBICACIÓN QUE SE PASE POR PARÁMETRO
		RESULTADO: DEVUELVE UN FICHERO SI LA CONSULTA FUNCIONA Y ENCUENTRA FILAS, "ERROR EN LA BD" SI FALLA LA CONEXIÓN, "FALLO CONSULTA" SI NO SE PUEDE EJECUTAR LA CONSULTA, "CONSULTA SIN RESULTADOS" SI NO DEVUELVE NINGÚN RESULTADO
		LLAMADA: ES LLAMADA DESDE gestionar_datos.php
		PARÁMETROS:
			-UBICACION: CÓDIGO DE LA UBICACION DE LA QUE SE VA A HACER UN LISTADO DE SUS ARTÍCULOS
	*/
	function exportar_excel_contenido_ubicacion($ubicacion) {
		require "PHP-EXCEL/vendor/autoload.php"; // INCLUIR LIBRERÍA NECESARIA
		if (empty($ubicacion)) return "CONSULTA VACIA";
		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD
		$sentencia = $conexion->prepare("SELECT a.codigo codigo, a.descripcion descripcion, a.observaciones observaciones, s.cantidad cantidad FROM stock s LEFT JOIN articulos a ON s.articulo = a.codigo WHERE s.ubicacion = ?");
		$sentencia->bind_param("s", $ubicacion);
		if (!$sentencia->execute()) { // COMPROBAR SI HA FALLADO LA CONSULTA
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al ejecutar la query SELECT a.codigo codigo, a.descripcion descripcion, a.observaciones observaciones, s.cantidad cantidad FROM stock s LEFT JOIN articulos a ON s.articulo = a.codigo WHERE s.ubicacion = '".$ubicacion."' desde la funcion exportar_excel_contenido_ubicacion()", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		} else {
			$resultado_query = $sentencia->get_result();
			if ($resultado_query->num_rows == 0) { // COMPROBAR SI HA DEVULETO FILAS
				$conexion->close();
				return "CONSULTA SIN RESULTADOS";
			} else {
				$contenido = array();
				$contador = 0;
				while ($fila = $resultado_query->fetch_assoc()) { // RECORRER TODAS LAS FILAS DEVUELTAS
					foreach ($fila as $indice => $campo) { // RECORRER LOS CAMPOS DE CADA FILA
						$contenido[$contador][$indice] = $campo; // AÑADIMOS LOS CAMPOS AL ARRAY CONTENIDO
					}
					$contador++;
				}
				$conexion->close();
				// GENERAR EL EXCEL
				$contenido = new ArrayIterator($contenido);
				$columnCollection = new ExcelHelper\ColumnCollection([ // DEFINIR LAS COLUMNAS
				    new ExcelHelper\Column('codigo',  'Código',     10,     new ExcelHelper\CellStyle\Text()),
				    new ExcelHelper\Column('descripcion',  'Descripción',   15,     new ExcelHelper\CellStyle\Text()),
						new ExcelHelper\Column('observaciones',  'Observaciones',   15,     new ExcelHelper\CellStyle\Text()),
						new ExcelHelper\Column('cantidad',  'Cantidad',   15,     new ExcelHelper\CellStyle\Text())
				]);
				$filename = sprintf('%s/'.$ubicacion.'.xls', __DIR__, uniqid()); // NOMBRAR EL FICHERO
				$phpExcel = new ExcelHelper\TableWorkbook($filename); // CREAR EL FICHERO
				$worksheet = $phpExcel->addWorksheet($ubicacion); // DARLE NOMBRE A LA HOJA
				$table = new ExcelHelper\Table($worksheet, 0, 0, 'Contenido de la ubicación '.$ubicacion, $contenido);
				$table->setColumnCollection($columnCollection);
				$phpExcel->writeTable($table); // ESCRIBIR EL CONTENIDO EN EL EXCEL
				$phpExcel->close();
				// CAMBIAR LAS CABECERAS PARA QUE SE DESCARGUE EL FICHERO
				header("Content-Type:   application/vnd.ms-excel; charset=utf-8");
				header('Content-Disposition: attachment; filename="'.$ubicacion.'.xls"');
				readfile($ubicacion.".xls"); // DESCARGARLO EN EL CLIENTE
				unlink($ubicacion.".xls"); // BORRAR EL FICHERO
			}
		}
	}
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA GENERAR UN FICHERO EXCEL CON LISTADO DE UBICACIONES DONDE SE ENCUENTRA UN DETERMINADO ARTÍCULO
		RESULTADO: DEVUELVE UN FICHERO SI LA CONSULTA FUNCIONA Y ENCUENTRA FILAS, "ERROR EN LA BD" SI FALLA LA CONEXIÓN, "FALLO CONSULTA" SI NO SE PUEDE EJECUTAR LA CONSULTA, "CONSULTA SIN RESULTADOS" SI NO DEVUELVE NINGÚN RESULTADO
		LLAMADA: ES LLAMADA DESDE gestionar_datos.php
		PARÁMETROS:
			-ARTICULO: CÓDIGO DEL ARTÍCULO DEL QUE SE VA A REALIZAR EL LISTADO
	*/
	function exportar_excel_ubicaciones($articulo) {
		require "PHP-EXCEL/vendor/autoload.php"; // INCLUIR LIBRERÍA NECESARIA
		if (empty($articulo)) return "CONSULTA VACIA";
		$conexion = conexion_database();
		if ($conexion === False) return "ERROR EN LA BD"; // COMPROBAR LA CONECTIVIDAD CON LA BD
		$sentencia = $conexion->prepare("SELECT u.codigo codigo, u.descripcion descripcion, u.observaciones observaciones FROM ubicaciones u WHERE u.codigo IN (SELECT ubicacion FROM stock WHERE articulo = ? )");
		$sentencia->bind_param("s", $articulo);
		if (!$sentencia->execute()) { // COMPROBAR SI HA FALLADO LA CONSULTA
			$conexion->close();
			registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar al ejecutar la query SELECT u.codigo, u.descripcion, u.observaciones FROM ubicaciones u WHERE u.codigo IN (SELECT ubicacion FROM stock WHERE articulo = '".$articulo."') desde la funcion exportar_excel_ubicaciones()", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO CONSULTA";
		} else {
			$resultado_query = $sentencia->get_result();
			if ($resultado_query->num_rows == 0) { // COMPROBAR SI HA DEVULETO FILAS
				$conexion->close();
				return "CONSULTA SIN RESULTADOS";
			} else {
				$contenido = array();
				$contador = 0;
				while ($fila = $resultado_query->fetch_assoc()) { // RECORRER TODAS LAS FILAS DEVUELTAS
					foreach ($fila as $indice => $campo) { // RECORRER LOS CAMPOS DE CADA FILA
						$contenido[$contador][$indice] = $campo; // AÑADIMOS LOS CAMPOS AL ARRAY CONTENIDO
					}
					$contador++;
				}
				$conexion->close();

				// GENERAR EL EXCEL
				$contenido = new ArrayIterator($contenido);
				$columnCollection = new ExcelHelper\ColumnCollection([ // DEFINIR LAS COLUMNAS
						new ExcelHelper\Column('codigo',  'Código',     10,     new ExcelHelper\CellStyle\Text()),
						new ExcelHelper\Column('descripcion',  'Descripción',   15,     new ExcelHelper\CellStyle\Text()),
						new ExcelHelper\Column('observaciones',  'Observaciones',   15,     new ExcelHelper\CellStyle\Text())
				]);
				$filename = sprintf('%s/'.$articulo.'.xls', __DIR__, uniqid()); // NOMBRAR EL FICHERO
				$phpExcel = new ExcelHelper\TableWorkbook($filename); // CREAR EL FICHERO
				$worksheet = $phpExcel->addWorksheet($articulo); // DARLE NOMBRE A LA HOJA
				$table = new ExcelHelper\Table($worksheet, 0, 0, 'Ubicaciones donde esta '.$articulo, $contenido);
				$table->setColumnCollection($columnCollection);
				$phpExcel->writeTable($table); // ESCRIBIR EL CONTENIDO EN EL EXCEL
				$phpExcel->close();
				// CAMBIAR LAS CABECERAS PARA QUE SE DESCARGUE EL FICHERO
				header("Content-Type:   application/vnd.ms-excel; charset=utf-8");
				header('Content-Disposition: attachment; filename="'.$articulo.'.xls"');
				readfile($articulo.".xls"); // DESCARGARLO EN EL CLIENTE
				unlink($articulo.".xls"); // BORRAR EL FICHERO
			}
		}
	}

	// IMPORTAR DESDE CSV

	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA IMPORTAR DATOS A LA TABLA ARTÍCULOS DESDE UN FICHERO CSV. LAS LÍNEAS INCORRECTAS SON DESCARTADAS. UNA VEZ DESCARTADAS LAS LÍNEAS CON FORMATO ERRÓNEO SE TRATAN DE INSERTAR Y SI ALGUNA FALLA SE REALIZA UN ROLLBACK (FUNCIÓN TRANSACCIONAL)
		 	*** LOS DATOS DEBEN DE ESTAR CODIFICADOS EN UTF8 PARA EVITAR PROBLEMAS DE CARACTERES ***
		RESULTADO: DEVUELVE UN ARRAY CON LAS FILAS INSERTADAS/DESCARTADAS, "ERROR EN LA BD" SI FALLA LA CONEXIÓN, "FALLO ABRIR" SI NO SE PUEDE ABRIR EL FICHERO SUBIDO, "CONSULTA SIN RESULTADOS" SI NO DEVUELVE NINGÚN RESULTADO
		LLAMADA: ES LLAMADA DESDE gestionar_datos.php
		PARÁMETROS:
			-FICHERO: FICHERO CSV DEL QUE SE VA A IMPORTAR LOS DATOS
	*/
	function importar_csv_tabla_articulos($fichero) {
		if (@$manejador_fichero=fopen($fichero, 'r')) { // ABRIR EL FICHERO EN MODO LECTURA
			$contador = 1;
			$errores = array();
			$inserciones = array();
			// RECORRER EL FICHERO DIVIDIENDO LOS CAMPOS POR ";"
			while (($linea = fgetcsv($manejador_fichero, 1000, ";")) !== FALSE) {
				// VALIDAR LOS DATOS, EN CASO DE NO CUMPLIR LOS REQUISITOS AÑADIR LA FILA AL ARRAY $errores
				if (count($linea) < 2) $errores[] = "La línea ".$contador." ".implode(";", $linea)." tiene un número de campos incorrecto"; // COMPROBAR QUE TIENE 3 CAMPOS
				elseif (empty($linea[0])) $errores[] = "La línea ".$contador." ".implode(";", $linea)." tiene el campo código vacío y no se ha podido insertar"; // COMPROBAR QUE EL CÓDIGO NO ESTÁ VACÍO
				elseif (empty($linea[1])) $errores[] = "La línea ".$contador." ".implode(";", $linea)." tiene el campo descripción vacío y no se ha podido insertar"; // COMPROBAR QUE LA DESCRIPCIÓN NO ESTÁ VACÍA
				elseif (!validar_codigo($linea[0])) $errores[] = "La línea ".$contador." ".implode(";", $linea)." tiene un código erróneo y no se ha podido insertar"; // COMPROBAR QUE LA DESCRIPCIÓN NO ESTÁ VACÍA
				else {
					$datos_a_insertar[$contador] = $linea; // SI LA VALIDACIÓN ES CORRECTA, METEMOS LA FILA EN UN ARRAY
				}
				$contador++;
			}
			fclose($manejador_fichero);
			if (!isset($datos_a_insertar)) return "SIN DATOS"; // SI NINGUNA FILA HA PASADO LA VALIDACIÓN TERMINAMOS LA FUNCIÓN
			$conexion = conexion_database();
			$sentencia = $conexion->prepare("INSERT INTO articulos VALUES (? , ?, ?)");
			$sentencia->bind_param("sss", $codigo, $descripcion, $observaciones); // EVITAR SQL INJECTION
			// INICIAR TRANSACCIÓN
			$transaccion = True;
			$conexion->autocommit(false); // INICIAR TRANSACCIÓN
			foreach ($datos_a_insertar as $articulo) { // RECORRER LOS DATOS A INSERTAR
				// VINCULAR LOS DATOS
				$codigo = strtoupper($articulo[0]);
				$descripcion = ucwords(strtolower($articulo[1]));
				if (empty($articulo[2])) $observaciones = null;
				else $observaciones = $articulo[2];
				if (!$sentencia->execute()) { // EJECUTAR EL INSERT Y COMPROBAR SI SE HA INSERTADO CORRECTAMENTE
					registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar ejecutar la query INSERT INTO articulos VALUES ('".$codigo."', '".$descripcion."', '".$observaciones."') desde la función importar_csv_tabla_articulos()", "error"); // ANOTAR EVENTO EN LA BD
					$errores[] = "Ha fallado la inserción de la línea ".implode(";", $articulo); // ANOTAR EL ERROR
					$transaccion = False;
				} else {
					$inserciones[] = "Se ha insertado correctamente la línea ".implode(";", $articulo); // ANOTAR LA INSERCIÓN
				}
			}
			// UNA VEZ TERMINADOS LOS INSERTS COMPROBAMOS SI ALGUNO HA FALLADO. DE SER ASÍ HACEMOS UN ROLLBACK E INFORMAMOS AL USUARIO. EN CASO CONTRARIO CONFIRMAMOS LOS CAMBIOS
			if ($transaccion === False) {
				$conexion->rollback();
				$inserciones = str_replace("Se ha insertado correctamente la línea", "No se ha podido insertar la siguiente línea debido al fallo en la inserción de alguna línea anterior: ", $inserciones);
			  $errores = array_merge($errores, $inserciones);
			  $inserciones = array();
			} else $conexion->commit();
			$conexion->autocommit(True); // SI TODO HA IDO BIEN CONFIRMAMOS LOS CAMBIOS
			$conexion->close();
			return array("errores" => $errores, "inserciones" => $inserciones);
		} else { // INFORMAR DEL ERROR SI NO SE PUEDE ABRIR EL FICHERO
			registrar_evento(time(), $_SESSION['email'], "Fallo al abrir el fichero para importar datos desde la función importar_csv_tabla_articulos()", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO ABRIR";
		}
	}
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA IMPORTAR DATOS A LA TABLA UBICACIONES DESDE UN FICHERO CSV. LAS LÍNEAS INCORRECTAS SON DESCARTADAS. UNA VEZ DESCARTADAS LAS LÍNEAS CON FORMATO ERRÓNEO SE TRATAN DE INSERTAR Y SI ALGUNA FALLA SE REALIZA UN ROLLBACK (FUNCIÓN TRANSACCIONAL)
		 	*** LOS DATOS DEBEN DE ESTAR CODIFICADOS EN UTF8 PARA EVITAR PROBLEMAS DE CARACTERES ***
		RESULTADO: DEVUELVE UN ARRAY CON LAS FILAS INSERTADAS/DESCARTADAS, "ERROR EN LA BD" SI FALLA LA CONEXIÓN, "FALLO ABRIR" SI NO SE PUEDE ABRIR EL FICHERO SUBIDO, "CONSULTA SIN RESULTADOS" SI NO DEVUELVE NINGÚN RESULTADO
		LLAMADA: ES LLAMADA DESDE gestionar_datos.php
		PARÁMETROS:
			-FICHERO: FICHERO CSV DEL QUE SE VA A IMPORTAR LOS DATOS
	*/
	function importar_csv_tabla_ubicaciones($fichero) {
		if (@$manejador_fichero=fopen($fichero, 'r')) { // ABRIR EL FICHERO EN MODO LECTURA
			$contador = 1;
			$errores = array();
			$inserciones = array();
			// RECORRER EL FICHERO DIVIDIENDO LOS CAMPOS POR ";"
			while (($linea = fgetcsv($manejador_fichero, 1000, ";")) !== FALSE) {
				// VALIDAR LOS DATOS, EN CASO DE NO CUMPLIR LOS REQUISITOS AÑADIR LA FILA AL ARRAY $errores
				if (count($linea) < 2) $errores[] = "La línea ".$contador." ".implode(";", $linea)." tiene un número de campos incorrecto"; // COMPROBAR QUE TIENE 3 CAMPOS
				elseif (empty($linea[0])) $errores[] = "La línea ".$contador." ".implode(";", $linea)." tiene el campo código vacío y no se ha podido insertar"; // COMPROBAR QUE EL CÓDIGO NO ESTÁ VACÍO
				elseif (empty($linea[1])) $errores[] = "La línea ".$contador." ".implode(";", $linea)." tiene el campo descripción vacío y no se ha podido insertar"; // COMPROBAR QUE LA DESCRIPCIÓN NO ESTÁ VACÍA
				elseif (!validar_codigo($linea[0])) $errores[] = "La línea ".$contador." ".implode(";", $linea)." tiene un código erróneo y no se ha podido insertar"; // COMPROBAR QUE LA DESCRIPCIÓN NO ESTÁ VACÍA
				else {
					$datos_a_insertar[$contador] = $linea; // SI LA VALIDACIÓN ES CORRECTA, METEMOS LA FILA EN UN ARRAY
				}
				$contador++;
			}
			fclose($manejador_fichero);
			if (!isset($datos_a_insertar)) return $errores; // SI NINGUNA FILA HA PASADO LA VALIDACIÓN TERMINAMOS LA FUNCIÓN
			$conexion = conexion_database();
			$sentencia = $conexion->prepare("INSERT INTO ubicaciones VALUES (? , ?, ?)");
			$sentencia->bind_param("sss", $codigo, $descripcion, $observaciones); // EVITAR SQL INJECTION
			// INICIAR TRANSACCIÓN
			$transaccion = True;
			$conexion->autocommit(false); // INICIAR TRANSACCIÓN
			foreach ($datos_a_insertar as $ubicacion) { // RECORRER LOS DATOS A INSERTAR
				// VINCULAR LOS DATOS
				$codigo = strtoupper($ubicacion[0]);
				$descripcion = ucwords(strtolower($ubicacion[1]));
				if (empty($ubicacion[2])) $observaciones = null;
				else $observaciones = $ubicacion[2];
				if (!$sentencia->execute()) { // EJECUTAR EL INSERT Y COMPROBAR SI SE HA INSERTADO CORRECTAMENTE
					registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar ejecutar la query INSERT INTO ubicaciones VALUES ('".$codigo."', '".$descripcion."', '".$observaciones."') desde la función importar_csv_tabla_ubicaciones()", "error"); // ANOTAR EVENTO EN LA BD
					$errores[] = "Ha fallado la inserción de la línea ".implode(";", $ubicacion); // ANOTAR EL ERROR
					$transaccion = False;
				} else {
					$inserciones[] = "Se ha insertado correctamente la línea ".implode(";", $ubicacion); // ANOTAR LA INSERCIÓN
				}
			}
			// UNA VEZ TERMINADOS LOS INSERTS COMPROBAMOS SI ALGUNO HA FALLADO. DE SER ASÍ HACEMOS UN ROLLBACK E INFORMAMOS AL USUARIO. EN CASO CONTRARIO CONFIRMAMOS LOS CAMBIOS
			if ($transaccion === False) {
				$conexion->rollback();
				$inserciones = str_replace("Se ha insertado correctamente la línea", "No se ha podido insertar la siguiente línea debido al fallo en la inserción de alguna línea anterior: ", $inserciones);
			  $errores = array_merge($errores, $inserciones);
			  $inserciones = array();
			} else $conexion->commit();
			$conexion->autocommit(True); // SI TODO HA IDO BIEN CONFIRMAMOS LOS CAMBIOS
			$conexion->close();
			return array("errores" => $errores, "inserciones" => $inserciones);
		} else { // INFORMAR DEL ERROR SI NO SE PUEDE ABRIR EL FICHERO
			registrar_evento(time(), $_SESSION['email'], "Fallo al abrir el fichero para importar datos desde la función importar_csv_tabla_ubicaciones()", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO ABRIR";
		}
	}
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA IMPORTAR DATOS A LA TABLA USUARIOS DESDE UN FICHERO CSV. LAS LÍNEAS INCORRECTAS SON DESCARTADAS. UNA VEZ DESCARTADAS LAS LÍNEAS CON FORMATO ERRÓNEO SE TRATAN DE INSERTAR Y SI ALGUNA FALLA SE REALIZA UN ROLLBACK (FUNCIÓN TRANSACCIONAL)
		 	*** LOS DATOS DEBEN DE ESTAR CODIFICADOS EN UTF8 PARA EVITAR PROBLEMAS DE CARACTERES ***
		RESULTADO: DEVUELVE UN ARRAY CON LAS FILAS INSERTADAS/DESCARTADAS, "ERROR EN LA BD" SI FALLA LA CONEXIÓN, "FALLO ABRIR" SI NO SE PUEDE ABRIR EL FICHERO SUBIDO, "CONSULTA SIN RESULTADOS" SI NO DEVUELVE NINGÚN RESULTADO
		LLAMADA: ES LLAMADA DESDE gestionar_datos.php
		PARÁMETROS:
			-FICHERO: FICHERO CSV DEL QUE SE VA A IMPORTAR LOS DATOS
	*/
	function importar_csv_tabla_usuarios($fichero) {
		if (@$manejador_fichero=fopen($fichero, 'r')) { // ABRIR EL FICHERO EN MODO LECTURA
			$contador = 1;
			$errores = array();
			$inserciones = array();
			// RECORRER EL FICHERO DIVIDIENDO LOS CAMPOS POR ";"
			while (($linea = fgetcsv($manejador_fichero, 1000, ";")) !== FALSE) {
				// VALIDAR LOS DATOS, EN CASO DE NO CUMPLIR LOS REQUISITOS AÑADIR LA FILA AL ARRAY $errores
				if (count($linea) != 4) $errores[] = "La línea ".$contador." ".implode(";", $linea)." tiene un número de campos incorrecto"; // COMPROBAR QUE TIENE 4 CAMPOS
				elseif (empty($linea[0])) $errores[] = "La línea ".$contador." ".implode(";", $linea)." tiene el campo email vacío y no se ha podido insertar"; // COMPROBAR QUE EL CÓDIGO NO ESTÁ VACÍO
				elseif (empty($linea[1])) $errores[] = "La línea ".$contador." ".implode(";", $linea)." tiene el campo password vacío y no se ha podido insertar"; // COMPROBAR QUE LA PASSWORD NO ESTÁ VACÍA
				elseif (empty($linea[3])) $errores[] = "La línea ".$contador." ".implode(";", $linea)." tiene el campo tipo vacío y no se ha podido insertar"; // COMPROBAR QUE EL CAMPO TIPO NO ESTÁ VACÍO
				elseif (!filter_var($linea[0], FILTER_VALIDATE_EMAIL)) $errores[] = "La línea ".$contador." ".implode(";", $linea)." tiene un email erróneo y no se ha podido insertar"; // COMPROBAR QUE ES UN EMAIL
				elseif ($linea[3] !== "estandar" and $linea[3] !== "editor" and $linea[3] !== "administrador") "La línea ".$contador." ".implode(";", $linea)." tiene un valor incorrecto en el campo tipo"; // COMPROBAR QUE EL CAMPO TIPO NO ESTÁ VACÍO
				else {
					$datos_a_insertar[$contador] = $linea; // SI LA VALIDACIÓN ES CORRECTA, METEMOS LA FILA EN UN ARRAY
				}
				$contador++;
			}
			fclose($manejador_fichero);
			if (!isset($datos_a_insertar)) return $errores; // SI NINGUNA FILA HA PASADO LA VALIDACIÓN TERMINAMOS LA FUNCIÓN
			$conexion = conexion_database();
			$sentencia = $conexion->prepare("INSERT INTO usuarios VALUES (? , ? , ? , ?)");
			$sentencia->bind_param("ssss", $email, $password, $nombre, $tipo); // EVITAR SQL INJECTION
			// INICIAR TRANSACCIÓN
			$transaccion = True;
			$conexion->autocommit(false); // INICIAR TRANSACCIÓN
			foreach ($datos_a_insertar as $usuario) { // RECORRER LOS DATOS A INSERTAR
				// VINCULAR LOS DATOS
				$email = strtolower($usuario[0]);
				$password = $usuario[1];
				if (empty($usuario[2])) $nombre = null;
				else $nombre = ucwords(strtolower($usuario[2]));
				$tipo = strtolower($usuario[3]);
				if (!$sentencia->execute()) { // EJECUTAR EL INSERT Y COMPROBAR SI SE HA INSERTADO CORRECTAMENTE
					registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar ejecutar la query INSERT INTO usuarios VALUES ('".$email."', '".$password."', '".$nombre."', '".$tipo."') desde la función importar_csv_tabla_usuarios()", "error"); // ANOTAR EVENTO EN LA BD
					$errores[] = "Ha fallado la inserción de la línea ".implode(";", $usuario); // ANOTAR EL ERROR
					$transaccion = False;
				} else {
					$inserciones[] = "Se ha insertado correctamente la línea ".implode(";", $usuario); // ANOTAR LA INSERCIÓN
				}
			}
			// UNA VEZ TERMINADOS LOS INSERTS COMPROBAMOS SI ALGUNO HA FALLADO. DE SER ASÍ HACEMOS UN ROLLBACK E INFORMAMOS AL USUARIO. EN CASO CONTRARIO CONFIRMAMOS LOS CAMBIOS
			if ($transaccion === False) {
				$conexion->rollback();
				$inserciones = str_replace("Se ha insertado correctamente la línea", "No se ha podido insertar la siguiente línea debido al fallo en la inserción de alguna línea anterior: ", $inserciones);
			  $errores = array_merge($errores, $inserciones);
			  $inserciones = array();
			} else $conexion->commit();
			$conexion->autocommit(True);
			$conexion->close();
			return array("errores" => $errores, "inserciones" => $inserciones);
		} else { // INFORMAR DEL ERROR SI NO SE PUEDE ABRIR EL FICHERO
			registrar_evento(time(), $_SESSION['email'], "Fallo al abrir el fichero para importar datos desde la función importar_csv_tabla_usuarios()", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO ABRIR";
		}
	}
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA IMPORTAR DATOS A LA TABLA GESTIONA DESDE UN FICHERO CSV. LAS LÍNEAS INCORRECTAS SON DESCARTADAS. UNA VEZ DESCARTADAS LAS LÍNEAS CON FORMATO ERRÓNEO SE TRATAN DE INSERTAR Y SI ALGUNA FALLA SE REALIZA UN ROLLBACK (FUNCIÓN TRANSACCIONAL)
		 	*** LOS DATOS DEBEN DE ESTAR CODIFICADOS EN UTF8 PARA EVITAR PROBLEMAS DE CARACTERES ***
		RESULTADO: DEVUELVE UN ARRAY CON LAS FILAS INSERTADAS/DESCARTADAS, "ERROR EN LA BD" SI FALLA LA CONEXIÓN, "FALLO ABRIR" SI NO SE PUEDE ABRIR EL FICHERO SUBIDO, "CONSULTA SIN RESULTADOS" SI NO DEVUELVE NINGÚN RESULTADO
		LLAMADA: ES LLAMADA DESDE gestionar_datos.php
		PARÁMETROS:
			-FICHERO: FICHERO CSV DEL QUE SE VA A IMPORTAR LOS DATOS
	*/
	function importar_csv_tabla_gestiona($fichero) {
		if (@$manejador_fichero=fopen($fichero, 'r')) { // ABRIR EL FICHERO EN MODO LECTURA
			$contador = 1;
			$errores = array();
			$inserciones = array();
			// RECORRER EL FICHERO DIVIDIENDO LOS CAMPOS POR ";"
			while (($linea = fgetcsv($manejador_fichero, 1000, ";")) !== FALSE) {
				// VALIDAR LOS DATOS, EN CASO DE NO CUMPLIR LOS REQUISITOS AÑADIR LA FILA AL ARRAY $errores
				if (count($linea) != 2) $errores[] = "La línea ".$contador." ".implode(";", $linea)." tiene un número de campos incorrecto"; // COMPROBAR QUE TIENE 2 CAMPOS
				elseif (empty($linea[0])) $errores[] = "La línea ".$contador." ".implode(";", $linea)." tiene el campo ubicación vacío y no se ha podido insertar"; // COMPROBAR QUE EL CÓDIGO NO ESTÁ VACÍO
				elseif (empty($linea[1])) $errores[] = "La línea ".$contador." ".implode(";", $linea)." tiene el campo usuario vacío y no se ha podido insertar"; // COMPROBAR QUE LA DESCRIPCIÓN NO ESTÁ VACÍA
				else {
					$datos_a_insertar[$contador] = $linea; // SI LA VALIDACIÓN ES CORRECTA, METEMOS LA FILA EN UN ARRAY
				}
				$contador++;
			}
			fclose($manejador_fichero);
			if (!isset($datos_a_insertar)) return "SIN DATOS"; // SI NINGUNA FILA HA PASADO LA VALIDACIÓN TERMINAMOS LA FUNCIÓN
			$conexion = conexion_database();
			$sentencia = $conexion->prepare("INSERT INTO gestiona VALUES (? , ?)");
			$sentencia->bind_param("ss", $ubicacion, $usuario); // EVITAR SQL INJECTION
			// INICIAR TRANSACCIÓN
			$transaccion = True;
			$conexion->autocommit(false); // INICIAR TRANSACCIÓN
			foreach ($datos_a_insertar as $gestiona) { // RECORRER LOS DATOS A INSERTAR
				// VINCULAR LOS DATOS
				$ubicacion = strtoupper($gestiona[0]);
				$usuario = strtolower($gestiona[1]);
				if (!$sentencia->execute()) { // EJECUTAR EL INSERT Y COMPROBAR SI SE HA INSERTADO CORRECTAMENTE
					registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar ejecutar la query INSERT INTO gestiona VALUES ('".$ubicacion."', '".$usuario."') desde la función importar_csv_tabla_gestiona()", "error"); // ANOTAR EVENTO EN LA BD
					$errores[] = "Ha fallado la inserción de la línea ".implode(";", $gestiona); // ANOTAR EL ERROR
					$transaccion = False;
				} else {
					$inserciones[] = "Se ha insertado correctamente la línea ".implode(";", $gestiona); // ANOTAR LA INSERCIÓN
				}
			}
			// UNA VEZ TERMINADOS LOS INSERTS COMPROBAMOS SI ALGUNO HA FALLADO. DE SER ASÍ HACEMOS UN ROLLBACK E INFORMAMOS AL USUARIO. EN CASO CONTRARIO CONFIRMAMOS LOS CAMBIOS
			if ($transaccion === False) {
				$conexion->rollback();
				$inserciones = str_replace("Se ha insertado correctamente la línea", "No se ha podido insertar la siguiente línea debido al fallo en la inserción de alguna línea anterior: ", $inserciones);
			  $errores = array_merge($errores, $inserciones);
			  $inserciones = array();
			} else $conexion->commit(); // SI TODO HA IDO BIEN CONFIRMAMOS LOS CAMBIOS
			$conexion->autocommit(True);
			$conexion->close();
			return array("errores" => $errores, "inserciones" => $inserciones);
		} else { // INFORMAR DEL ERROR SI NO SE PUEDE ABRIR EL FICHERO
			registrar_evento(time(), $_SESSION['email'], "Fallo al abrir el fichero para importar datos desde la función importar_csv_tabla_articulos()", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO ABRIR";
		}
	}
	/*
		DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA IMPORTAR DATOS A LA TABLA STOCK DESDE UN FICHERO CSV. LAS LÍNEAS INCORRECTAS SON DESCARTADAS. UNA VEZ DESCARTADAS LAS LÍNEAS CON FORMATO ERRÓNEO SE TRATAN DE INSERTAR Y SI ALGUNA FALLA SE REALIZA UN ROLLBACK (FUNCIÓN TRANSACCIONAL)
			*** LOS DATOS DEBEN DE ESTAR CODIFICADOS EN UTF8 PARA EVITAR PROBLEMAS DE CARACTERES ***
		RESULTADO: DEVUELVE UN ARRAY CON LAS FILAS INSERTADAS/DESCARTADAS, "ERROR EN LA BD" SI FALLA LA CONEXIÓN, "FALLO ABRIR" SI NO SE PUEDE ABRIR EL FICHERO SUBIDO, "CONSULTA SIN RESULTADOS" SI NO DEVUELVE NINGÚN RESULTADO
		LLAMADA: ES LLAMADA DESDE gestionar_datos.php
		PARÁMETROS:
			-FICHERO: FICHERO CSV DEL QUE SE VA A IMPORTAR LOS DATOS
	*/
	function importar_csv_tabla_stock($fichero) {
		if (@$manejador_fichero=fopen($fichero, 'r')) { // ABRIR EL FICHERO EN MODO LECTURA
			$contador = 1;
			$errores = array();
			$inserciones = array();
			// RECORRER EL FICHERO DIVIDIENDO LOS CAMPOS POR ";"
			while (($linea = fgetcsv($manejador_fichero, 1000, ";")) !== FALSE) {
				// VALIDAR LOS DATOS, EN CASO DE NO CUMPLIR LOS REQUISITOS AÑADIR LA FILA AL ARRAY $errores
				if (count($linea) != 3) $errores[] = "La línea ".$contador." ".implode(";", $linea)." tiene un número de campos incorrecto"; // COMPROBAR QUE TIENE 3 CAMPOS
				elseif (empty($linea[0])) $errores[] = "La línea ".$contador." ".implode(";", $linea)." tiene el campo ubicación vacío y no se ha podido insertar"; // COMPROBAR QUE EL CÓDIGO NO ESTÁ VACÍO
				elseif (empty($linea[1])) $errores[] = "La línea ".$contador." ".implode(";", $linea)." tiene el campo artículo vacío y no se ha podido insertar"; // COMPROBAR QUE LA DESCRIPCIÓN NO ESTÁ VACÍA
				elseif (empty($linea[2])) $errores[] = "La línea ".$contador." ".implode(";", $linea)." tiene el campo cantidad vacío y no se ha podido insertar"; // COMPROBAR QUE LA CANTIDAD NO ESTÁ VACÍA
				elseif (!validar_codigo($linea[0])) $errores[] = "La línea ".$contador." ".implode(";", $linea)." tiene una ubicación con un código erróneo y no se ha podido insertar"; // COMPROBAR QUE LA DESCRIPCIÓN NO ESTÁ VACÍA
				elseif (!validar_codigo($linea[1])) $errores[] = "La línea ".$contador." ".implode(";", $linea)." tiene un artículo con un código erróneo y no se ha podido insertar"; // COMPROBAR QUE LA DESCRIPCIÓN NO ESTÁ VACÍA
				else {
					$datos_a_insertar[$contador] = $linea; // SI LA VALIDACIÓN ES CORRECTA, METEMOS LA FILA EN UN ARRAY
				}
				$contador++;
			}
			fclose($manejador_fichero);
			if (!isset($datos_a_insertar)) return "SIN DATOS"; // SI NINGUNA FILA HA PASADO LA VALIDACIÓN TERMINAMOS LA FUNCIÓN
			$conexion = conexion_database();
			$sentencia = $conexion->prepare("INSERT INTO stock VALUES (? , ? , ?)");
			$sentencia->bind_param("ssi", $ubicacion, $articulo, $cantidad); // EVITAR SQL INJECTION
			// INICIAR TRANSACCIÓN
			$transaccion = True;
			$conexion->autocommit(false); // INICIAR TRANSACCIÓN
			foreach ($datos_a_insertar as $stock) { // RECORRER LOS DATOS A INSERTAR
				// VINCULAR LOS DATOS
				$ubicacion = strtoupper($stock[0]);
				$articulo = strtoupper($stock[1]);
				$cantidad = $stock[2];
				if (!$sentencia->execute()) { // EJECUTAR EL INSERT Y COMPROBAR SI SE HA INSERTADO CORRECTAMENTE
					registrar_evento(time(), $_SESSION['email'], "Se ha producido un error al intentar ejecutar la query INSERT INTO stock VALUES ('".$ubicacion."', '".$articulo."', ".$cantidad.") desde la función importar_csv_tabla_stock()", "error"); // ANOTAR EVENTO EN LA BD
					$errores[] = "Ha fallado la inserción de la línea ".implode(";", $stock); // ANOTAR EL ERROR
					$transaccion = False;
				} else {
					$inserciones[] = "Se ha insertado correctamente la línea ".implode(";", $stock); // ANOTAR LA INSERCIÓN
				}
			}
			// UNA VEZ TERMINADOS LOS INSERTS COMPROBAMOS SI ALGUNO HA FALLADO. DE SER ASÍ HACEMOS UN ROLLBACK E INFORMAMOS AL USUARIO. EN CASO CONTRARIO CONFIRMAMOS LOS CAMBIOS
			if ($transaccion === False) {
				$conexion->rollback();
				$inserciones = str_replace("Se ha insertado correctamente la línea", "No se ha podido insertar la siguiente línea debido al fallo en la inserción de alguna línea anterior: ", $inserciones);
				$errores = array_merge($errores, $inserciones);
				$inserciones = array();
			} else $conexion->commit(); // SI TODO HA IDO BIEN CONFIRMAMOS LOS CAMBIOS
			$conexion->autocommit(True);
			$conexion->close();
			return array("errores" => $errores, "inserciones" => $inserciones);
		} else { // INFORMAR DEL ERROR SI NO SE PUEDE ABRIR EL FICHERO
			registrar_evento(time(), $_SESSION['email'], "Fallo al abrir el fichero para importar datos desde la función importar_csv_tabla_articulos()", "error"); // ANOTAR EVENTO EN LA BD
			return "FALLO ABRIR";
		}
	}
?>
