<?php
	session_start();
	include "funciones.php";
	/* *** BLOQUE DE CÓDIGO DE CONTROL SOBRE LA SESIÓN. DIFIERE DEL RESTO PORQUE EN CASO DE VALIDACIÓN CORRECTA REDIRIGE *** */
	/* 1- SE COMPRUEBA SI HAY UNA SESIÓN INICIADA. EN CASO AFIRMATIVO SE REDIRIGE A ubicaciones.php */
	if (isset($_SESSION['email']) and isset($_SESSION['password']) and isset($_SESSION['tipo'])) header('Location: /PHP/ubicaciones.php');
	
	/* 2- SE COMPRUEBA SI HAY COOKIES ALMACENADAS CON CREDENCIALES, EN TAL CASO SE VALIDAN EN LA DATABASE Y SI SON CORRECTOS
		SE CREA SESIÓN Y SE REDIRIGE A ubicaciones.php */
	elseif (isset($_COOKIE['email']) and isset($_COOKIE['password'])) {
		$resultado_validacion = validar_login($_COOKIE['email'], $_COOKIE['password']);
		if ($resultado_validacion === True)	header('Location: /PHP/ubicaciones.php');
		else {
			//SI LAS CREDENCIALES ALMACENADAS NO SON CORRECTAS (HAN CAMBIADO EN LA DATABASE O EL USUARIO YA NO EXISTE), LAS BORRAMOS PARA EVITAR FALLOS.
			setcookie("email", "", time() - 1, "/");
			setcookie("password", "", time() - 1, "/");
		}
	/* 3- SE COMPRUEBA SI SE HA ENVIADO EL FORMULARIO DE INICIO DE SESIÓN, SI ES INCORRECTO SE MUESTRA ERROR. EN CASO CONTRARIO SE REDIRIGE */
	} elseif (isset($_POST['campo_enviar'])) {
		if (isset($_POST['campo_recordar'])) $resultado_validacion = validar_login($_POST['campo_email'], $_POST['campo_password'], "S");
		else $resultado_validacion = validar_login($_POST['campo_email'], $_POST['campo_password']);
		if ($resultado_validacion === True)	header('Location: /PHP/ubicaciones.php');
		else echo $resultado_validacion;
	}
	/* *** FIN DE BLOQUE *** */
?>
<!DOCTYPE html>
<html>
<head>
	<title>USUARIOS | IES SERRA PERENXISA</title>
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
				<ul class="navbar-nav ml-auto nav-flex-icons">
					<li class="nav-item avatar dropdown">
						<a class="nav-link dropdown-toggle" id="navbarDropdownMenuLink-55" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							<?php
								if (isset($_SESSION['email'])) echo $_SESSION['email'];
								else echo "Aún no has iniciado sesión.";
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
	<script src="../JS/jquery-3.3.1.min.js"></script>
	<script src="../JS/popper.min.js"></script>
	<script src="../JS/bootstrap.min.js"></script>
</body>
