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
	<title>EXPORTAR/IMPORTAR | IES SERRA PERENXISA</title>
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
						<a class="nav-link" href="/PHP/articulos.php">Artículos</a>
					</li>
					<li class="nav-item">
						<a class="nav-link pagina_activa" href="/PHP/datos.php">Exportar/Importar</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="/PHP/logs.php">Logs</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="/PHP/ubicaciones.php">Ubicaciones</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="/PHP/usuarios.php">Usuarios</a>
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
<div class="container-fluid">
	<form method="post" action="gestionar_datos.php" enctype="multipart/form-data">
  <div class="row col-xl-10 col-lg-12 offset-xl-1">
		<!-- ELEGIR SI SE DESEA IMPORTAR O EXPORTAR -->
    <div class="col-sm-2" id="accion_div">
			<div class="custom-control custom-radio" id="exportar_div">
				<input type="radio" id="exportar" value="exportar" name="campo_accion" class="custom-control-input" onclick="mostrar_fichero('exportar')">
				<label class="custom-control-label" for="exportar">Exportar</label>
			</div>
			<div class="custom-control custom-radio" id="importar_div">
				<input type="radio" id="importar" value="importar" name="campo_accion" class="custom-control-input" onclick="mostrar_fichero('importar')">
				<label class="custom-control-label" for="importar">Importar</label>
			</div>
    </div>
		<!-- ELEGIR SI SE DESEA CSV O EXCEL -->
    <div class="col-sm-2" id="fichero_div">
			<div class="custom-control custom-radio" id="csv_div" style="display: none;">
				<input type="radio" id="csv" value="csv" name="campo_tipo_fichero" class="custom-control-input">
				<label class="custom-control-label" for="csv">CSV</label>
			</div>
			<div class="custom-control custom-radio" id="excel_div" style="display: none;">
				<input type="radio" id="excel" value="excel" name="campo_tipo_fichero" class="custom-control-input">
				<label class="custom-control-label" for="excel">Excel</label>
			</div>
    </div>
		<!-- ELEGIR QUÉ DATOS SE DESEA EXPORTAR -->
    <div class="col-sm-6" id="dato_div" style="display: none">
			<!-- DATOS EXPORTABLES -->
			<div class="custom-control custom-radio" style="display: none;" id="consulta_div">
				<input type="radio" id="consulta" value="consulta" name="campo_tipo_dato" class="custom-control-input" onclick="mostar_boton()">
				<label class="custom-control-label" for="consulta">
					Consulta: <input type="text" id="campo_consulta" name="campo_consulta">
					<button class="button" type="button" id="guardar_consulta">Guardar</button>
				</label>
			</div>
			<div class="custom-control custom-radio" style="display: none;" id="consultas_almacenadas_div">
				<input type="radio" id="consultas_almacenadas" value="consultas_almacenadas" name="campo_tipo_dato" class="custom-control-input" onclick="mostar_boton()">
				<label class="custom-control-label" for="consultas_almacenadas">Consulta almacenada:
					<select id="lista_consultas_almacenadas" class="custom-select" name="campo_consultas_almacenadas">
<?php
	$resultado_consultas = ver_consultas($_SESSION['email']);
	if (is_array($resultado_consultas)) {
		foreach ($resultado_consultas as $consulta) {
			echo "<option value='".$consulta."'>".$consulta."</option>";
		}
	} else {
		echo $resultado_consultas;
	}
?>
					</select>
				</label>
			</div>
			<div class="custom-control custom-radio" style="display: none;" id="tabla_div">
				<input type="radio" id="tabla" value="tabla" name="campo_tipo_dato" class="custom-control-input" onclick="mostar_boton()">
				<label class="custom-control-label" for="tabla">Tabla:
					<select class="custom-select" name="campo_tabla">
					  <option value="articulos">Artículos</option>
					  <option value="ubicaciones">Ubicaciones</option>
					  <option value="usuarios">Usuarios</option>
					  <option value="stock">Stock</option>
						<option value="gestiona">Gestiona</option>
						<option value="consultas">Consultas</option>
						<option value="logs">Logs</option>
					</select>
				</label>
			</div>
			<div class="custom-control custom-radio" style="display: none;" id="contenido_div">
				<input type="radio" id="contenido" value="contenido" name="campo_tipo_dato" class="custom-control-input" onclick="mostar_boton()">
				<label class="custom-control-label" for="contenido">Contenido de:
					<select class="custom-select" name="campo_ubicacion">
<?php
	$resultado_ubicaciones = ver_ubicaciones();
	if (is_array($resultado_ubicaciones)) {
		foreach ($resultado_ubicaciones as $ubicacion) {
			echo "<option value='".$ubicacion['codigo']."'>".$ubicacion['descripcion']."</option>";
		}
	}
?>
					</select>
				</label>
			</div>
			<div class="custom-control custom-radio" style="display: none;" id="ubicacion_div">
				<input type="radio" id="ubicacion" value="ubicaciones" name="campo_tipo_dato" class="custom-control-input" onclick="mostar_boton()">
				<label class="custom-control-label" for="ubicacion">Ubicaciones dónde se encuentra:
					<select class="custom-select" name="campo_articulo">
<?php
	$resultado_articulos = ver_articulos();
	if (is_array($resultado_articulos)) {
		foreach ($resultado_articulos as $articulo) {
			echo "<option value='".$articulo['codigo']."'>".$articulo['descripcion']."</option>";
		}
	}
?>
					</select>
				</label>
			</div>
			<!-- DATOS IMPORTABLES -->
			<div class="custom-control custom-radio" style="display: none;" id="articulos_div">
				<input type="radio" id="articulos" value="articulos" name="campo_tipo_dato" class="custom-control-input" onclick="mostar_boton('campo_fichero')">
				<label class="custom-control-label" for="articulos">Artículos</label>
			</div>
			<div class="custom-control custom-radio" style="display: none;" id="ubicaciones_div">
				<input type="radio" id="ubicaciones" value="ubicaciones" name="campo_tipo_dato" class="custom-control-input" onclick="mostar_boton('campo_fichero')">
				<label class="custom-control-label" for="ubicaciones">Ubicaciones</label>
			</div>
			<div class="custom-control custom-radio" style="display: none;" id="usuarios_div">
				<input type="radio" id="usuarios" value="usuarios" name="campo_tipo_dato" class="custom-control-input" onclick="mostar_boton('campo_fichero')">
				<label class="custom-control-label" for="usuarios">Usuarios</label>
			</div>
			<div class="custom-control custom-radio" style="display: none;" id="gestiona_div">
				<input type="radio" id="gestiona" value="gestiona" name="campo_tipo_dato" class="custom-control-input" onclick="mostar_boton('campo_fichero')">
				<label class="custom-control-label" for="gestiona">Gestiona</label>
			</div>
			<div class="custom-control custom-radio" style="display: none;" id="stock_div">
				<input type="radio" id="stock" value="stock" name="campo_tipo_dato" class="custom-control-input" onclick="mostar_boton('campo_fichero')">
				<label class="custom-control-label" for="stock">Stock</label>
			</div>
			<div class="custom-control custom-radio" style="display: none;" id="logs_div">
				<input type="radio" id="logs" value="logs" name="campo_tipo_dato" class="custom-control-input" onclick="mostar_boton('campo_fichero')">
				<label class="custom-control-label" for="logs">Logs</label>
			</div>
			<div class="custom-control custom-radio" style="display: none;" id="consultas_div">
				<input type="radio" id="consultas" value="consultas" name="campo_tipo_dato" class="custom-control-input" onclick="mostar_boton('campo_fichero')">
				<label class="custom-control-label" for="consultas">Consultas</label>
			</div>
    </div>
		<div class="col-sm-1" id="campo_fichero_div" style="display: none">
			<input type="file" id="campo_fichero" name="campo_fichero" class="form-control-file" onclick="mostar_boton('campo_fichero')">
			<label class="custom-control-label" for="campo_fichero">Fichero:</label>
		</div>
		<div class="col-sm-1" id="boton_div" style="display: none">
			 <input type="submit" id="campo_enviar" name="campo_enviar" value="" class="btn btn-primary">
    </div>

  </div>
	</form>
</div>
</div>
	<script src="../JS/jquery-3.3.1.min.js"></script>
	<script src="../JS/jquery-confirm.min.js"></script>
	<script src="../JS/popper.min.js"></script>
	<script src="../JS/bootstrap.min.js"></script>
	<script src="../JS/tooltip.js"></script>
	<script src="../JS/datos.js"></script>
</body>
</html>
