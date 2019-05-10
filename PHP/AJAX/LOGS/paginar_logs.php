<?php
	/*
		DESCRIPCIÓN: SE UTILIZAR PARA LLAMAR A LA FUNCIÓN ver_logs (funciones.php) QUE CARGA LOS LOGS DE LA BD Y DEVOLVERLE EL RESULTADO AL NAVEGADOR A TRAVÉS DE AJAX.
		RESULTADO: DEVUELVE HTML EN FUNCIÓN DEL RESULTADO DE LA CONSULTA EN LA BD
		LLAMADA: ES LLAMADA DESDE LA FUNCIÓN DE JAVASCRIPT recargar_logs
		PARÁMETROS:
			- $_POST['campo_filtro']: PERMITE INDICAR UNA CADENA PARA VISUALIZAR ÚNICAMENTE LOS ARTÍCULOS QUE COINCIDAN CON EL CÓDIGO O DESCRIPCIÓN DADO POR EL USUARIO
	*/
	session_start();
	include "../../funciones.php";
  	if (isset($_SESSION['email']) and isset($_SESSION['password']) and isset($_SESSION['tipo']) and $_SESSION['tipo'] === "administrador") { // COMPROBAR QUE UN USUARIO HAYA INICIADO SESIÓN
			$resultado_paginacion = contar_logs($_POST['campo_inicio'], $_POST['campo_fin'], $_POST['campo_usuario'], $_POST['campo_descripcion'], $_POST['campo_tipo']);
			$resultado = "
				<li class='page-item active'>
					<button onclick='recargar_logs(0, inicio, fin, usuario, descripcion, tipo)'>1</button>
				</li>
			";
			for ($i=1; $i < $resultado_paginacion; $i++) {
				$resultado .= "
					<li class='page-item"; if ($i == $_POST['campo_indice']) $resultado .= " active"; $resultado .= "'>
			      <button onclick='recargar_logs(".$i.", inicio, fin, usuario, descripcion, tipo)'>".($i + 1)."</button>
			    </li>
				";
			}
			echo $resultado;
	} else header('Location: /PHP/index.php'); // SI NO ES ADMINISTRADOR LE REDIRIGIMOS A LA PÁGINA PRINCIPAL
?>
