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
<body class="color_fuerte">
    <div>
        <h3 class="text-center text-black pt-3 pb-3 color_fuerte">Aplicación de gestión del inventario del IES Serra Perenxisa</h3>
        <div id="login" class="container col-xl-3 color_intermedio">
            <form class="form" action="" method="post">
				<div class="form-group">
					<div class="input-group mb-2 mr-sm-2">
						<div class="input-group-prepend">
							<div class="input-group-text color_suave">@</div>
						</div>
						<input type="text" class="form-control form-control-lg" id="campo_usuario" name="campo_usuario"placeholder="Usuario">
					</div>
				</div>
				<div class="form-group">
					<div class="input-group mb-2 mr-sm-2">
						<div class="input-group-prepend">
							<span class="input-group-text color_suave"><i class="fas fa-key"></i></span>
						</div>
						<input type="password" class="form-control form-control-lg" id="campo_password" name="campo_password" placeholder="Contraseña">
					</div>
				</div>
				<div class="form-group form-check">
					<input type="checkbox" class="form-check-input" id="campo_recordar" name="campo_recordar">
					<label class="form-check-label" for="campo_recordar">Recordar credenciales</label>
				</div>
				<button type="submit" class="btn color_suave">Iniciar sesión</button>
			</form>
        </div>
    </div>
</body>
