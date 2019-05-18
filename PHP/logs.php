<?php
	session_start();
	include "funciones.php";
	/* *** BLOQUE DE CÓDIGO DE CONTROL SOBRE LA SESIÓN. IGUAL AL DE LOGS YA QUE SOLO USUARIOS ADMIN PODRÁN ACCEDER. DIFIERE DEL RESTO. *** */
	/* 1- SE COMPRUEBA SI HAY UNA SESIÓN INICIADA. EN CASO AFIRMATIVO SE COMPRUEBA EL TIPO DE USUARIO. SI NO ES ADMINISTRADOR SE REDIRIGE A UBICACIONES.PHP*/
	if (isset($_SESSION['email']) and isset($_SESSION['password']) and isset($_SESSION['tipo'])) {
		if ($_SESSION['tipo'] !== "administrador") header('Location: /PHP/ubicaciones.php');
	}

	/* 2- SE COMPRUEBA SI HAY COOKIES ALMACENADAS CON CREDENCIALES, EN TAL CASO SE VALIDAN EN LA DATABASE. SI SON ERRÓNEAS SE ELIMINAN Y SE REDIRIGEN A index.php*/
	elseif (isset($_COOKIE['email']) and isset($_COOKIE['password'])) {
		$resultado_validacion = validar_login($_COOKIE['email'], $_COOKIE['password']);
		if ($resultado_validacion === True)	{
			if ($_SESSION['tipo'] !== "administrador") header('Location: /PHP/ubicaciones.php'); // SI EL USUARIO NO ES ADMINISTRADOR SE LE REDIRIGE
		} else {
			setcookie("email", "", time() - 1, "/");
			setcookie("password", "", time() - 1, "/");
			header('Location: /PHP/index.php');
		}
	/* SI NO HA SE HA VALIDADO MEDIANTE LA SESIÓN NI COOKIES SE LE REDIRIGE */
	} else header('Location: /PHP/ubicaciones.php');
	/* *** FIN DE BLOQUE *** */
?>
<!DOCTYPE html>
<html>
<head>
	<title>LOGS | IES SERRA PERENXISA</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<link rel="icon" type="image/jpg" href="../IMG/logo1.jpg">
	<!-- hojas de estilos -->
	<link rel="stylesheet" href="../CSS/bootstrap.css">
	<link rel="stylesheet" href="../CSS/estilos.css">
	<link rel="stylesheet" href="../CSS/iconos.css">
	<link rel="stylesheet" href="../CSS/jquery-confirm.min.css">

	<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/css/bootstrap-datetimepicker.css" rel="stylesheet"/>
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
				<ul class="navbar-nav mr-auto">
					<li class="nav-item">
						<a id="articulos" class="nav-link" href="/PHP/articulos.php">Artículos</a>
					</li>
					<li class="nav-item">
						<a id="logs" class="nav-link pagina_activa" href="/PHP/logs.php">Logs</a>
					</li>
					<li class="nav-item">
						<a id="ubicaciones" class="nav-link" href="/PHP/ubicaciones.php">Ubicaciones</a>
					</li>
					<li class="nav-item">
						<a id="usuarios" class="nav-link" href="/PHP/usuarios.php">Usuarios</a>
					</li>
				</ul>
				<ul class="navbar-nav ml-auto nav-flex-icons">
					<li class="nav-item avatar dropdown">
						<a class="nav-link dropdown-toggle" id="navbarDropdownMenuLink-55" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							<?php echo $_SESSION['email'];?>
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
	<a href="../PHP/logs.php"><img src="../IMG/logo1.jpg" class="rounded-circle mx-auto d-block" id="logo1" alt="Cinque Terre"></a>
</div>
<div class="col-xl-10 col-lg-12 offset-xl-1">
	<ul class="nav nav-tabs" id="myTab" role="tablist">
		<li class="nav-item">
			<button onclick='borrar_filtros()' type='button' class='nav-link active' id='home-tab' data-toggle='tab' role='tab' aria-controls='home' aria-selected='true'>Todos los logs</button>
		</li>
		<li class="nav-item">
			Fecha mínima: <input class='nav-link form-control' type="date" id="campo_inicio">
		</li>
		<li class="nav-item">
			Fecha final: <input class='nav-link form-control' type="date" id="campo_fin" placeholder='Descripción...'>
		</li>
		<li class="nav-item">
			<input id="campo_usuario" class='nav-link form-control' type='text' placeholder='Usuario...'>
		</li>
		<li class="nav-item">
			<input id="campo_descripcion" class='nav-link form-control' type='text' placeholder='Descripción...'>
		</li>
		<li class="nav-item">
			<select class="custom-select" id="campo_tipo">
				<option value=""></option>
				<option value="login">Login</option>
				<option value="error">Fallos</option>
			</select>
		</li>
		<li class='nav-item'>
			<button onclick='buscar_logs()' type='button' class='nav-link' id='search-tab' data-toggle='tab' role='tab' aria-controls='search' aria-selected='false'><i class='fas fa-search'></i></button>
		</li>
	</ul>
<?php
	$resultado_logs = ver_logs(0);
	if ($resultado_logs === "ERROR EN LA BD") echo "Se ha producido un error al conectarse a la Base de Datos. Prueba a conectarse más tarde.";
	elseif ($resultado_logs === "NO LOGS") echo "No se ha encontrado ningún artículo en la BD.";
	elseif ($resultado_logs === "FALLO CONSULTA") echo "Se ha producido un error al consultar los datos de la BD. Prueba a actualizar la página e intentarlo de nuevo.";
	else {
?>
	<table id='tabla_logs' class='table table-responsive-sm table-striped table-hover table-bordered table-dark'>
		<thead class='color_fuerte'>
			<tr>
				<th scope='col'>Fecha</th>
				<th scope='col'>Usuario</th>
				<th scope='col'>Descripción</th>
				<th scope='col'>Tipo</th>
			</tr>
		</thead>
		<tbody id="contenido_logs">
<?php
	foreach ($resultado_logs as $log) {
		echo "
			<tr>
				<td><input type='text' name='campo_codigo' value='".date("d/m/Y H:i:s", $log['fecha'])."' readonly></td>
				<td><input type='text' name='campo_descripcion' value='".$log['usuario']."' readonly></td>
				<td><input type='text' name='campo_observaciones' value='".$log['descripcion']."' readonly></td>
				<td><input type='text' name='campo_observaciones' value='".$log['tipo']."' readonly></td>
			</tr>
		";
	}
?>
			</tbody>
			</table>
				<nav aria-label="...">
					<ul class="pagination" id="paginar_logs">
<?php
	$resultado_paginacion = contar_logs();
	echo "
		<li class='page-item active'>
			<button onclick='recargar_logs(0, inicio, fin, usuario, descripcion, tipo)'>1</button>
		</li>
	";
	for ($i=1; $i < $resultado_paginacion; $i++) {
		echo "
			<li class='page-item'>
	      <button onclick='recargar_logs(".$i.", inicio, fin, usuario, descripcion, tipo)'>".($i + 1)."</button>
	    </li>
		";
	}
?>
					</ul>
			</nav>
<?php
}
?>
</div>
<p id="id_resultado"></p>
<script src="../JS/jquery-3.3.1.min.js"></script>
<script src="../JS/jquery-confirm.min.js"></script>
<script src="../JS/popper.min.js"></script>
<script src="../JS/bootstrap.min.js"></script>
<script src="../JS/tooltip.js"></script>
<script src="../JS/logs.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/js/bootstrap-datetimepicker.min.js"></script>

</body>
</html>
