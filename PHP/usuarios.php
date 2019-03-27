<?php
	session_start();
	include "funciones.php";
	if (isset($_POST['cerrar_sesion'])) cerrar_sesion();
	/* *** INICIO DE BLOQUE COMÚN A TODAS LAS PÁGINAS WEB DE LA APLICACIÓN PARA CONTROLAR EL INICIO DE SESIÓN. 
	EN ESTA PÁGINA DIFIERE PORQUE SI YA SE HA HECHO LOGIN CORRECTO EN LA PÁGINA SE REDIRIGE A OTRO (NO TENDRÍA SENTIDO VOLVER A MOSTRAR EL FORMULARIO DE INICIO),
	EN EL RESTO SE PERMITIRÁ VER EL CONTENIDO Y EN CASO DE NO HABER HECHO LOGIN SE REDIRIGIRÁ AQUÍ *** */

	/*1- SE COMPRUEBA SI HAY UNA SESIÓN INICIADA. EN CASO AFIRMATIVO SE PERMITE LA VISUALIZACIÓN DE LA PÁGINA 
			(O REDIRIGIR A /PHP/menu.php EN CASO DE SER /PHP/index.php)*/
	if (isset($_SESSION['email']) and isset($_SESSION['password']) and isset($_SESSION['tipo'])) $variable = null;
	
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

	if ($_SESSION['tipo'] != "administrador") header('Location: /PHP/menu.php');
?>
<?php
	echo imprimir_cabecera("usuarios");
	$resultado_usuarios = ver_usuarios();
	if (is_array($resultado_usuarios)) {
?>
	<div class="col-xl-8 offset-xl-2">
		<table id="tabla_usuarios" class="table table-responsive-sm table-striped table-hover table-bordered table-dark">
			<thead class="color_fuerte">
				<tr>
					<th scope="col">Email</th>
					<th scope="col">Contraseña</th>
					<th scope="col">Tipo</th>
					<th scope="col">Acciones</th>
				</tr>
			</thead>
			<tbody>
<?php
			foreach ($resultado_usuarios as $usuario) {
				echo "
					<tr>
						<td>".$usuario['email']."</td>
						<td>********</td>
						<td>".$usuario['tipo']."</td>
						<td>
							<a href='#' data-toggle='tooltip' data-placement='top' title='Ver localizaciones'><i class='fas fa-search'></i></a>
							<a href='#' data-toggle='tooltip' data-placement='top' title='Eliminar usuario'><i class='fas fa-trash'></i></a>
							<a href='#' data-toggle='tooltip' data-placement='top' title='Modificar usuario'><i class='fas fa-pen'></i></a>
						</td>
					</tr>
				";
		}
?>
			</tbody>
		</table>
		<script type="text/javascript">
		    $(document).ready(function(){
		      refreshTable();
		    });

		    function refreshTable(){
		        $('#tabla_usuarios').load('recargar_usuarios.php', function(){
		           setTimeout(refreshTable, 5000);
		        });
		    }
		</script>
<?php
	} else {
		echo "error";
	}
?>
</div>
</body>
</html>