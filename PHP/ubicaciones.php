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
	<title>UBICACIONES | IES SERRA PERENXISA</title>
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
						<a id="articulos" class="nav-link" href="/PHP/articulos.php">Artículos</a>
					</li>
<?php
			if ($_SESSION['tipo'] === "administrador") {
				echo "
							<li class='nav-item'>
								<a class='nav-link' href='/PHP/datos.php'>Exportar/Importar</a>
							</li>
							<li class='nav-item'>
								<a id='logs' class='nav-link' href='/PHP/logs.php'>Logs</a>
							</li>
				";
			}
?>
					<li class="nav-item">
						<a id="ubicaciones" class="nav-link pagina_activa" href="/PHP/ubicaciones.php">Ubicaciones</a>
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
	<a href="../PHP/index.php"><img src="../IMG/logo1.jpg" class="rounded-circle mx-auto d-block" id="logo1" alt="Cinque Terre"></a>
</div>
<div id="pagina" class="col-xl-10 col-lg-12 offset-xl-1">
		<ul class="nav nav-tabs" id="menu_acciones" role="tablist">
			<li class="nav-item">
				<button onclick='recargar_ubicaciones()' type='button' class='nav-link active' id='home-tab' data-toggle='tab' role='tab' aria-controls='home' aria-selected='true'>Todas las ubicaciones</button>
			</li>
<?php
			if ($_SESSION['tipo'] === "editor") {
				echo "
				<li class='nav-item'>
					<button onclick='recargar_ubicaciones(\"".$_SESSION['email']."\")' type='button' class='nav-link' id='profile-tab' data-toggle='tab' role='tab' aria-controls='profile' aria-selected='false'>Mis ubicaciones</button>
				</li>";
			}
?>
			<li class="nav-item">
				<input id="campo_buscar" class='nav-link form-control' type='text' placeholder='Buscar...'>
			</li>
			<li class='nav-item'>
				<button onclick='buscar_ubicaciones()' type='button' class='nav-link' id='search-tab' data-toggle='tab' role='tab' aria-controls='search' aria-selected='false'><i class='fas fa-search'></i></button>
			</li>
<?php
			if ($_SESSION['tipo'] === "administrador") {
				echo "
				<li id='crear_ubicacion_li' class='nav-item'>
					<button id='crear_ubicacion' type='button' class='btn'><i class='fas fa-plus-circle fa-lg'></i></button>
				</li>";
			}
?>
		</ul>
		<!-- ACCIONES A REALIZAR A LA HORA DE INVENTARIAR LA UBICACIÓN (CANCELAR CAMBIOS, CONFIRMAR CAMBIOS O AÑADIR UNA UBICACIÓN), ESTÁ OCULTO HASTA QUE EL USUARIO HAGA CLICK EN "VER ARTÍCULOS" DE UNA UBICACIÓN -->
		<ul class="nav nav-tabs" id="menu_acciones_inventario" style="display:none;" role="tablist">
			<li class="nav-item">
				<button onclick='cancelar_inventario()' type='button'><i class="fas fa-chevron-circle-left fa-lg"></i></button>
			</li>
			<li class="nav-item">
				<button id='boton_confirmar_inventario' onclick='confirmar_inventario()' type='button'><i class="fas fa-save fa-lg"></i></button>
			</li>
			<li class="nav-item">
				<select id='lista_ubicaciones_seleccionables' class='custom-select' onchange='add_articulo(this)'>";
				</select>
			</li>
		</ul>
	<table id='tabla_ubicaciones' class='table table-striped table-hover table-bordered table-dark'>
		<thead id="cabecera_ubicaciones" class='color_fuerte'>
			<tr>
				<th scope='col'>Código</th>
				<th scope='col'>Descripción</th>
				<th scope='col'>Observaciones</th>
				<th scope='col'>Acciones</th>
			</tr>
		</thead>
		<tbody id="contenido_ubicaciones">
<?php
	$resultado_ubicaciones = ver_ubicaciones();
	if ($resultado_ubicaciones === "ERROR EN LA BD") echo "Se ha producido un error al conectarse a la Base de Datos. Compruebe que el servicio esté funcionando correctamente. Pruebe a conectarse más tarde.";
	elseif ($resultado_ubicaciones === "FALLO CONSULTA") echo "Se ha producido un error al consultar los datos de la BD. Pruebe a actualizar la página.";
	elseif (is_array($resultado_ubicaciones)) {
		foreach ($resultado_ubicaciones as $ubicacion) {
			echo "
				<tr>
					<td><input type='text' name='campo_codigo' value='".$ubicacion['codigo']."' readonly></td>
					<td><input type='text' name='campo_descripcion' value='".$ubicacion['descripcion']."' readonly></td>
					<td><input type='text' name='campo_observaciones' value='".$ubicacion['observaciones']."' readonly></td>
					<td>
						<button onclick='ver_articulos(this)' type='button' data-toggle='tooltip' data-placement='top' title='Ver inventario'><i class='fas fa-search'></i></button>
			";
			if ($_SESSION['tipo'] === "administrador") {
				echo "
						<button onclick='eliminar_ubicacion(this)' type='button' data-toggle='tooltip' data-placement='top' title='Eliminar ubicación'><i class='fas fa-trash'></i></button>
						<button onclick='modificar_ubicacion(this)' type='button' data-toggle='tooltip' data-placement='top' title='Modificar ubicación'><i class='fas fa-pen'></i></button>
				";
			}
			echo "
					</td>
				</tr>
			";
		}
?>
			</tbody>
		</table>
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
<script src="../JS/ubicaciones.js"></script>

</body>
</html>
