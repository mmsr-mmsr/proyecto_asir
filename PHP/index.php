<?php
	session_start();
	include "funciones.php";
	/* *** INICIO DE BLOQUE COMÚN A TODAS LAS PÁGINAS WEB DE LA APLICACIÓN PARA CONTROLAR EL INICIO DE SESIÓN. 
	EN ESTA PÁGINA DIFIERE PORQUE SI YA SE HA HECHO LOGIN CORRECTO EN LA PÁGINA SE REDIRIGE A OTRO (NO TENDRÍA SENTIDO VOLVER A MOSTRAR EL FORMULARIO DE INICIO),
	EN EL RESTO SE PERMITIRÁ VER EL CONTENIDO Y EN CASO DE NO HABER HECHO LOGIN SE REDIRIGIRÁ AQUÍ *** */

	/*1- SE COMPRUEBA SI HAY UNA SESIÓN INICIADA. EN CASO AFIRMATIVO SE PERMITE LA VISUALIZACIÓN DE LA PÁGINA 
		(O REDIRIGIR A /PHP/menu.php EN CASO DE SER /PHP/index.php)*/
	if (isset($_SESSION['email']) and isset($_SESSION['password']) and isset($_SESSION['tipo'])) header('Location: /PHP/menu.php');
	
	/*2- SE COMPRUEBA SI HAY COOKIES ALMACENADAS CON CREDENCIALES, EN TAL CASO SE VALIDAN EN LA DATABASE Y SI SON CORRECTOS 
		SE CREA SESIÓN Y SE PERMITE LA VISUALIZACIÓN DE LA PÁGINA (O REDIRIGIR A /PHP/menu.php EN CASO DE SER /PHP/index.php)*/
	elseif (isset($_COOKIE['email']) and isset($_COOKIE['password'])) {
		$resultado_validacion = validar_login($_COOKIE['email'], $_COOKIE['password']);
		if (is_array($resultado_validacion)) {
			$_SESSION['email'] = $_COOKIE['email'];
			$_SESSION['password'] = $_COOKIE['password'];
			$_SESSION['tipo'] = $resultado_validacion['tipo'];
			registrar_evento(time(), $_SESSION['email'], "Login realizado correctamente", "login");
			header('Location: /PHP/menu.php');
		} else {
			//SI LAS CREDENCIALES ALMACENADAS NO SON CORRECTAS (HAN CAMBIADO EN LA DATABASE O EL USUARIO YA NO EXISTE), LAS BORRAMOS
			setcookie("email", "", time() - 1, "/");
			setcookie("password", "", time() - 1, "/");
		}
	/*3- SE COMPRUEBA SI SE HA ENVIADO EL FORMULARIO DE INICIO DE SESIÓN, SI ES INCORRECTO SE MUESTRA ERROR. EN CASO CONTRARIO SE CREA LA SESIÓN Y
		SE REDIRIGE A /PHP/menu.php*/ 
	} elseif (isset($_POST['campo_enviar'])) {
		$resultado_validacion = validar_login($_POST['campo_email'], $_POST['campo_password']);
		if (is_array($resultado_validacion)) {
			$_SESSION['email'] = $_POST['campo_email'];
			$_SESSION['password'] = $_POST['campo_password'];
			$_SESSION['tipo'] = $resultado_validacion['tipo'];
			//SI EL USUARIO HA MARCADO "RECORDAR CREDENCIALES", ALMACENAMOS COOKIES CON ELLAS CON UNA DURACIÓN DE 30 DÍAS
			if (isset($_POST['campo_recordar'])) {
				setcookie("email", $_POST['campo_email'], time() + 60 * 60 * 24 * 30, "/");
				setcookie("password", $_POST['campo_password'], time() + 60 * 60 * 24 * 30, "/");
			}
			registrar_evento(time(), $_SESSION['email'], "Login realizado correctamente", "login");
			header('Location: /PHP/menu.php');
		} else {
			registrar_evento(time(), $_POST['campo_email'], "Login fallido. Credenciales erroneas", "login");
		}
	}
	//*** FIN DE BLOQUE ***
?>
<?php
	imprimir_cabecera("index");
?>
		<div id="login" class="container col-11 col-sm-8 col-md-6 col-lg-6 col-xl-4 color_fuerte">
			<form class="form" action="" method="post">
				<div class="form-group">
					<div class="input-group mb-2 mr-sm-2">
						<div class="input-group-prepend">
							<div class="input-group-text color_intermedio">@</div>
						</div>
						<input type="text" class="form-control form-control-lg" id="campo_email" name="campo_email"placeholder="Usuario">
					</div>
				</div>
				<div class="form-group">
					<div class="input-group mb-2 mr-sm-2">
						<div class="input-group-prepend">
							<span class="input-group-text color_intermedio"><i class="fas fa-key"></i></span>
						</div>
						<input type="password" class="form-control form-control-lg" id="campo_password" name="campo_password" placeholder="Contraseña">
					</div>
				</div>
				<div class="custom-control custom-checkbox">
					<input type="checkbox" class="custom-control-input" id="campo_recordar" name="campo_recordar">
					<label class="custom-control-label" for="campo_recordar">Recordar credenciales</label>
				</div>
				<br>
				<div class="form-row justify-content-center">
					<input type="submit" class="btn color_intermedio" value="Iniciar sesión" name="campo_enviar">
				</div>
			</form>
		</div>
	</div>
</body>