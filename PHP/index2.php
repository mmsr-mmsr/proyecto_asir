<!DOCTYPE html>
<html>
<head>
	<title>IES SERRA PERENXISA</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<link rel="icon" type="image/jpg" href="../IMG/logo.jpg">
	<link rel="stylesheet" href="../CSS/bootstrap.css">
	<link rel="stylesheet" href="../CSS/estilos.css">
	<script src="../JS/jquery-3.3.1.js"></script>
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.1/css/all.css" integrity="sha384-gfdkjb5BdAXd+lj+gudLWI+BXq4IuLW5IT+brZEZsLFm++aCMlF1V92rMkPaX4PP" crossorigin="anonymous">
</head>
<body>
    <div>
		<nav class="navbar navbar-expand-lg color_fuerte">
			<a class="navbar-brand" href="/PHP/index2.php">
				<img src="../IMG/logo2.png" height="50" class="d-inline-block align-middle rounded" alt="Serra Perenxisa">
			</a>
			<div class="collapse navbar-collapse" id="navbarTogglerDemo02">
				<ul class="navbar-nav mr-auto mt-2 mt-lg-0">
					<li class="nav-item active">
						<a class="nav-link" href="#">Home <span class="sr-only">(current)</span></a>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="#">Link</a>
					</li>
					<li class="nav-item">
						<a class="nav-link disabled" href="#" tabindex="-1" aria-disabled="true">Disabled</a>
					</li>
				</ul>
				<ul class="navbar-nav ml-auto nav-flex-icons">
	      			<li class="nav-item avatar">
	        			<p>No has iniciado sesión</p>
	      			</li>
	   			 </ul>
			</div>
		</nav>
		<div class="container">        
  			<img src="../IMG/logo.jpg" class="rounded-circle logo1 mx-auto d-block" alt="Cinque Terre"> 
		</div>
        <div id="login" class="container col-xl-3 color_fuerte">
            <form class="form" action="" method="post">
				<div class="form-group">
					<div class="input-group mb-2 mr-sm-2">
						<div class="input-group-prepend">
							<div class="input-group-text color_intermedio">@</div>
						</div>
						<input type="text" class="form-control form-control-lg" id="campo_usuario" name="campo_usuario"placeholder="Usuario">
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
				<div class="form-group form-check">
					<input type="checkbox" class="form-check-input" id="campo_recordar" name="campo_recordar">
					<label class="form-check-label" for="campo_recordar">Recordar credenciales</label>
				</div>
				<button type="submit" class="btn color_intermedio">Iniciar sesión</button>
			</form>
        </div>
    </div>
</body>
