<?php
	session_start();
	include "funciones.php";
	/* *** BLOQUE DE CÓDIGO DE CONTROL SOBRE LA SESIÓN. IGUAL AL DE LOGS YA QUE SOLO USUARIOS ADMIN PODRÁN ACCEDER. DIFIERE DEL RESTO. *** */
	/* 1- SE COMPRUEBA SI HAY UNA SESIÓN INICIADA. EN CASO AFIRMATIVO SE COMPRUEBA EL TIPO DE USUARIO*/
	if (isset($_SESSION['email']) and isset($_SESSION['password']) and isset($_SESSION['tipo'])) {
	}
	/* 2- SE COMPRUEBA SI HAY COOKIES ALMACENADAS CON CREDENCIALES, EN TAL CASO SE VALIDAN EN LA DATABASE. SI SON ERRÓNEAS SE ELIMINAN Y SE REDIRIGEN A index.php*/
	elseif (isset($_COOKIE['email']) and isset($_COOKIE['password'])) {
		$resultado_validacion = validar_login($_COOKIE['email'], $_COOKIE['password']);
		if ($resultado_validacion !== True) {
			setcookie("email", "", time() - 1, "/");
			setcookie("password", "", time() - 1, "/");
			header('Location: /PHP/index.php');
		}
	/* SI NO HA SE HA VALIDADO MEDIANTE LA SESIÓN NI COOKIES SE LE REDIRIGE */
} else header('Location: /PHP/index.php');
	/* *** FIN DE BLOQUE *** */
?>
<!DOCTYPE html>
<html>
<head>
	<title>ARTÍCULOS | IES SERRA PERENXISA</title>
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
				<ul class="navbar-nav mr-auto">
					<li class="nav-item">
						<a id="articulos" class="nav-link pagina_activa" href="/PHP/articulos.php">Artículos</a>
					</li>
<?php
			if ($_SESSION['tipo'] === "administrador") {
				echo "<li class='nav-item'>
								<a id='logs' class='nav-link' href='/PHP/logs.php'>Logs</a>
							</li>
				";
			}
?>
					<li class="nav-item">
						<a id="ubicaciones" class="nav-link" href="/PHP/ubicaciones.php">Ubicaciones</a>
					</li>
<?php
			if ($_SESSION['tipo'] === "administrador") {
				echo "
					<li class='nav-item'>
						<a id='usuarios' class='nav-link' href='/PHP/usuarios.php'>Usuarios</a>
					</li>
				";
			}
?>
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
	<a href="../PHP/articulos.php"><img src="../IMG/logo1.jpg" class="rounded-circle mx-auto d-block" id="logo1" alt="Cinque Terre"></a>
</div>
<div class="col-xl-10 col-lg-12 offset-xl-1">
	<ul class="nav nav-tabs" id="myTab" role="tablist">
		<li class="nav-item">
			<button onclick='recargar_articulos()' type='button' class='nav-link active' id='home-tab' data-toggle='tab' role='tab' aria-controls='home' aria-selected='true'>Todos los artículos</button>
		</li>
		<li class='nav-item'>
			<button onclick='buscar_articulos()' type='button' class='nav-link' id='search-tab' data-toggle='tab' role='tab' aria-controls='search' aria-selected='false'><i class='fas fa-search'></i></button>
		</li>
		<li class="nav-item">
			<input id="campo_buscar" class='nav-link form-control' type='text' placeholder='Buscar...'>
		</li>
		<?php
				if ($_SESSION['tipo'] === "administrador") {
					echo "
					<li class='nav-item'>
						<button id='crear_articulo' type='button' class='btn color_intermedio'>Crear artículo</button>
					</li>";
				}
		?>
	</ul>
	<table id='tabla_articulos' class='table table-responsive-sm table-striped table-hover table-bordered table-dark'>
		<thead class='color_fuerte'>
			<tr>
				<th scope='col'>Código</th>
				<th scope='col'>Descripción</th>
				<th scope='col'>Observaciones</th>
				<th scope='col'>Acciones</th>
			</tr>
		</thead>
		<tbody id="contenido_articulos">
<?php
	$resultado_articulos = ver_articulos();
	if ($resultado_articulos === "ERROR EN LA BD") echo "Se ha producido un error al conectarse a la Base de Datos. Prueba a conectarse más tarde.";
	elseif ($resultado_articulos === "FALLO CONSULTA") echo "Se ha producido un error al consultar los datos de la BD. Prueba a actualizar la página e intentarlo de nuevo.";
	elseif (is_array($resultado_articulos)) {
		foreach ($resultado_articulos as $articulo) {
			echo "
				<tr>
					<td><input type='text' name='campo_codigo' value='".$articulo['codigo']."' readonly></td>
					<td><input type='text' name='campo_descripcion' value='".$articulo['descripcion']."' readonly></td>
					<td><input type='text' name='campo_observaciones' value='".$articulo['observaciones']."' readonly></td>
					<td>
						<button onclick='eliminar_articulo(this)' type='button' data-toggle='tooltip' data-placement='top' title='Eliminar artículo'><i class='fas fa-trash'></i></button>
						<button onclick='modificar_articulo(this)' type='button' data-toggle='tooltip' data-placement='top' title='Modificar artículo'><i class='fas fa-pen'></i></button>
					</td>
				</tr>
			";
		}
	}
?>
			</tbody>
		</table>
</div>
<p id="id_resultado"></p>
<script src="../JS/jquery-3.3.1.min.js"></script>
<script src="../JS/jquery-confirm.min.js"></script>
<script src="../JS/popper.min.js"></script>
<script src="../JS/bootstrap.min.js"></script>
<script src="../JS/tooltip.js"></script>
<script src="../JS/articulos.js"></script>

</body>
</html>
