<?php
	session_start();
	include "funciones.php";
	if (modificar_password("miriam", "pene2")) echo "modificado";
	else echo "no modi";
	// if (crear_usuario("miriam", "pene", "editor", array("AAAA", "BBBB"))) echo "USUARIO CREADO";
	// else echo "USUARIO NO";
		// if (borrar_usuario("miriam")) {
		// 	echo "usuario borrado";
		// } else echo "Usuario NO borrado";
	if (isset($_POST['cerrar_sesion'])) cerrar_sesion();
	/* *** INICIO DE BLOQUE COMÚN A TODAS LAS PÁGINAS WEB DE LA APLICACIÓN PARA CONTROLAR EL INICIO DE SESIÓN. 
	EN ESTA PÁGINA DIFIERE PORQUE SI YA SE HA HECHO LOGIN CORRECTO EN LA PÁGINA SE REDIRIGE A OTRO (NO TENDRÍA SENTIDO VOLVER A MOSTRAR EL FORMULARIO DE INICIO),
	EN EL RESTO SE PERMITIRÁ VER EL CONTENIDO Y EN CASO DE NO HABER HECHO LOGIN SE REDIRIGIRÁ AQUÍ *** */

	/*1- SE COMPRUEBA SI HAY UNA SESIÓN INICIADA. EN CASO AFIRMATIVO SE PERMITE LA VISUALIZACIÓN DE LA PÁGINA 
			(O REDIRIGIR A /PHP/menu.php EN CASO DE SER /PHP/index.php)*/
	if (isset($_SESSION['email']) and isset($_SESSION['password']) and isset($_SESSION['tipo'])) echo "PERMITIDO";
	
	/*2- SE COMPRUEBA SI HAY COOKIES ALMACENADAS CON CREDENCIALES, EN TAL CASO SE VALIDAN EN LA DATABASE Y SI SON CORRECTOS 
			SE CREA SESIÓN Y SE PERMITE LA VISUALIZACIÓN DE LA PÁGINA (O REDIRIGIR A /PHP/menu.php EN CASO DE SER /PHP/index.php)*/
	elseif (isset($_COOKIE['email']) and isset($_COOKIE['password'])) {
			$resultado_validacion = validar_login($_COOKIE['email'], $_COOKIE['password']);
			if (is_array($resultado_validacion)) {
					$_SESSION['email'] = $_COOKIE['email'];
					$_SESSION['password'] = $_COOKIE['password'];
					$_SESSION['tipo'] = $resultado_validacion['tipo'];
					registrar_login(time(), $_SESSION['email'], "Login realizado correctamente");
			} else {
					//SI LAS CREDENCIALES ALMACENADAS NO SON CORRECTAS (HAN CAMBIADO EN LA DATABASE O EL USUARIO YA NO EXISTE), LAS BORRAMOS
					setcookie("email", "", time() - 1, "/");
					setcookie("password", "", time() - 1, "/");
					header('Location: /PHP/index.php');
			}
	} else header('Location: /PHP/index.php');
	//*** FIN DE BLOQUE ***
	// $resultado = $_POST['email']; 
	// echo $resultado;
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
	<script type="text/javascript">
			function confirmar_delete(id) {
					if (confirm("¿Está seguro de borrar el elemento cuyo ID es "+id+" ?")) return true
					else return false
			}
	</script>
	<script>
		function realizaProceso(email){
			var parametros = {
				"email" : email
			};
			$.ajax({
				data:  parametros,
				url:   'ejemplo_ajax_proceso.php',
				type:  'post',
				
				beforeSend: function () {
					$("#resultado").html("Procesando, espere por favor...");
				},
				success:  function (response) {
					$("#resultado").html(response);
				}
			});
		}
	</script>
</head>
<body>
<form action="" method="post">
	<input type="submit" name="cerrar_sesion" value="cerrar sesión">
</form>
<?php
	if (isset($_GET['campo_borrar'])) borrar_usuario($_GET['campo_borrar']);
	$usuarios = ver_usuarios();
	if (is_array($usuarios)) {
	 // print_r($usuarios);
	 echo "<table>";
	 echo "  <tr>
				 <th>Usuario</th>
				 <th>Tipo</th>
				 <th>Acciones</th>
				 <th>Acciones</th>
				 <th>Acciones</th>
			 </tr>
	 ";
	 foreach ($usuarios as $usuario) {
		 echo "  <tr>
					 <td><input type='text' name='campo_email' id='id_email' value='".$usuario['email']."' readonly></td>
					 <td><input type='text' name='campo_tipo' id='id_tipo' value='".$usuario['tipo']."' readonly></td>
					 <td><input type='button' href='javascript:;' onclick='realizaProceso($(\"#id_email\").val());return false;' value='Borrar'/></td>
					 <td><input type='button' href='javascript:;' onclick='realizaProceso($(\"#id_email\").val());return false;' value='Modificar'/></td>
					 <td><input type='button' href='javascript:;' onclick='realizaProceso($(\"#id_email\").val());return false;' value='Visualizar'/></td>
				 </tr>";
	 }
	 echo "</table>";
	}
	// <td><a href='menu.php?campo_borrar=".$usuario['email']."' onclick=\"return confirmar_delete('".$usuario['email']."');\">Eliminar</a></td>
	//                  <td><a href='menu.php?campo_borrar=".$usuario['email']."' onclick=\"return confirmar_delete('".$usuario['email']."');\">Modificar</a></td>
	//                  <td><a href='menu.php?campo_borrar=".$usuario['email']."' onclick=\"return confirmar_delete('".$usuario['email']."');\">Visualizar</a></td>
?>

<!-- </head>
<body>
<input type="text" name="caja_texto" id="campo_email" value="0"/> 
<input type="button" href="javascript:;" onclick="realizaProceso($('#campo_email').val());return false;" value="Calcula"/>
<br/>
Resultado: <span id="resultado">0</span>
</body>
</html>  -->