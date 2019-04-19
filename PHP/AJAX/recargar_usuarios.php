<?php
	/*
		DESCRIPCIÓN: SE UTILIZAR PARA LLAMAR A LA FUNCIÓN ver_usuarios (funciones.php) QUE CARGA LOS USUARIOS DE LA BD Y DEVOLVERLE EL RESULTADO AL NAVEGADOR A TRAVÉS DE AJAX.
		RESULTADO: DEVUELVE HTML EN FUNCIÓN DEL RESULTADO DE LA CONSULTA EN LA BD
		LLAMADA: ES LLAMADA DESDE LA FUNCIÓN DE JAVASCRIPT recargar_usuarios
		PARÁMETROS:
			- $_POST['filtro']: PERMITE INDICAR UNA CADENA PARA VISUALIZAR ÚNICAMENTE LOS USUARIOS CUYO NOMBRE O EMAIL COINCIDA CON DICHA CADENA
	*/
	session_start();
	include "../funciones.php";
  if (isset($_SESSION['email']) and isset($_SESSION['password']) and isset($_SESSION['tipo']) and $_SESSION['tipo'] == "administrador") { // COMPROBAR QUE UN USUARIO HAYA INICIADO SESIÓN Y SEA ADMIN
		$resultado_usuarios = ver_usuarios($_POST['campo_filtro']);
    if ($resultado_usuarios === "ERROR EN LA BD") echo "Se ha producido un error al conectarse con la BD. Compruebe que el servicio está funcionando correctamente.";
		elseif ($resultado_usuarios === "FALLO CONSULTA") echo "Se ha producido un error al ejecutar la consulta a la BD. Pruebe a actualizar la página";
		elseif ($resultado_usuarios === "NO USUARIOS") echo "No se ha encontrado a ningún usuario que concuerde con el patrón de búsqueda.";
		//SI NO SE HA PRODUCIDO NINGÚN ERROR, RECORREMOS EL ARRAY RESULTADO
		else {
			define("TIPO_USUARIO_SELECCIONADO", " selected");
			$resultado = "";
			foreach ($resultado_usuarios as $usuario) { // RECORRER EL ARRAY OBTENIDO MOSTRANDO LOS DATOS
				$resultado .= "
				<tr>
					<td><input type='text' name='campo_email' value='".$usuario['email']."' readonly></td>
					<td><input type='text' name='campo_nombre' value='".$usuario['nombre']."' readonly></td>
					<td>
						<select class='custom-select' disabled>
							<option value='estandar'"; if ($usuario['tipo'] == "estandar") $resultado .= TIPO_USUARIO_SELECCIONADO; $resultado .= ">Estándar</option>
							<option value='editor'"; if ($usuario['tipo'] == "editor") $resultado .= TIPO_USUARIO_SELECCIONADO; $resultado .= ">Editor</option>
							<option value='administrador'"; if ($usuario['tipo'] == "administrador") $resultado .= TIPO_USUARIO_SELECCIONADO; $resultado .= ">Administrador</option>
						</select>
					<td>
						<button onclick='ver_ubicaciones(this)' type='button' data-toggle='tooltip' data-placement='top' title='Ver localizaciones'><i class='fas fa-search'></i></button>
						<button onclick='eliminar_usuario(this)' type='button' data-toggle='tooltip' data-placement='top' title='Eliminar usuario'><i class='fas fa-trash'></i></button>
						<button onclick='modificar_usuario(this)' type='button' data-toggle='tooltip' data-placement='top' title='Modificar usuario'><i class='fas fa-pen'></i></button>
						<button onclick='modificar_password(this)' type='button' data-toggle='tooltip' data-placement='top' title='Modificar contraseña'><i class='fas fa-key'></i></button>
					</td>
				</tr>
				";
			}
			echo $resultado;
		}
	} else header('Location: /PHP/index.php'); // SI NO ES ADMINISTRADOR LE REDIRIGIMOS A LA PÁGINA PRINCIPAL
?>
