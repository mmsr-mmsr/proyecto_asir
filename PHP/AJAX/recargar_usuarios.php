<?php
	session_start();
	include "../funciones.php";
  if (isset($_SESSION['email']) and isset($_SESSION['password']) and isset($_SESSION['tipo']) and $_SESSION['tipo'] == "administrador") {
		$resultado_usuarios = ver_usuarios($_POST['campo_filtro']);
    if ($resultado_usuarios !== False) {
			define("TIPO_USUARIO_SELECCIONADO", " selected");
			$resultado = "";
			foreach ($resultado_usuarios as $usuario) {
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
		} else echo "XXXXXXXXXXX";
	} else header('Location: /PHP/index.php');
?>
