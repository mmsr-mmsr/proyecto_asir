<?php
	session_start();
	include "funciones.php";
	if (isset($_SESSION["email"]) and isset($_SESSION["password"]))	header('Location: menu.php');
	elseif (isset($_POST['campo_enviar'])) {
		echo $validacion_login = validar_login($_POST['campo_email'], $_POST['campo_password']);
		if ($validacion_login === True) {
			$_SESSION["email"] = $_POST['campo_email'];
			$_SESSION["password"] = $_POST['campo_password'];
			header('Location: menu.php');
		}
	}
?>
<!DOCTYPE html>
<html>
<head>
	<title>IES SERRA PERENXISA</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<link rel="icon" type="image/jpg" href="../IMG/logo1.jpg">
	<link rel="stylesheet" href="../CSS/bootstrap.css">
	<link rel="stylesheet" href="../CSS/estilos.css">
	<script src="../JS/jquery-3.3.1.js"></script>
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.1/css/all.css" integrity="sha384-gfdkjb5BdAXd+lj+gudLWI+BXq4IuLW5IT+brZEZsLFm++aCMlF1V92rMkPaX4PP" crossorigin="anonymous">
</head>
<body>
    <div>
		<nav class="navbar navbar-expand-lg color_fuerte">
			<a class="navbar-brand" href="../PHP/index.php">
				<img src="../IMG/logo2.png" height="60" id="logo2" class="d-inline-block align-middle rounded" alt="Serra Perenxisa">
			</a>
			<div class="collapse navbar-collapse" id="navbarTogglerDemo02">
				<ul class="navbar-nav mr-auto mt-2 mt-lg-0">
					<!-- <li class="nav-item active">
						<a class="nav-link" href="#">Home <span class="sr-only">(current)</span></a>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="#">Link</a>
					</li>
					<li class="nav-item">
						<a class="nav-link disabled" href="#" tabindex="-1" aria-disabled="true">Disabled</a>
					</li> -->
				</ul>
				<ul class="navbar-nav ml-auto nav-flex-icons">
	      			<li class="nav-item avatar">
	        			<p>No has iniciado sesión</p>
	      			</li>
	   			 </ul>
			</div>
		</nav>
		<div class="container">        
  			<a href="../PHP/index.php"><img src="../IMG/logo1.jpg" class="rounded-circle mx-auto d-block" id="logo1" alt="Cinque Terre"></a>
		</div>
        <div id="login" class="container col-11 col-sm-8 col-md-6 col-lg-6 col-xl-3 color_fuerte">
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