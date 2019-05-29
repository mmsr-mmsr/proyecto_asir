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
			$indice = $_POST['campo_indice'];
			if ($indice - 10 <= 0) $inicio = 1;
			else $inicio = $indice - 9;

			if ($indice + 10 >= $resultado_paginacion) $final = $resultado_paginacion - 2;
			else $final = $indice + 9;

			if ($indice > 0) {
				echo "
					<li class='page-item active'>
						<button onclick='recargar_logs(0, inicio, fin, usuario, descripcion, tipo)'>Primero</button>
					</li>
				";
				for ($i = $inicio; $i < $indice ; $i++) {
					echo "
						<li class='page-item active'>
							<button onclick='recargar_logs(".($i).", inicio, fin, usuario, descripcion, tipo)'>".($i + 1)."</button>
						</li>
					";
				}
				if ($indice != $resultado_paginacion - 1) {
					echo "
						<li class='page-item active' style='background-color: red;'>
							<button onclick='recargar_logs(".$indice.", inicio, fin, usuario, descripcion, tipo)'>".($indice + 1)."</button>
						</li>
					";
				}
			} else {
				echo "
					<li class='page-item active' style='background-color: red;'>
						<button onclick='recargar_logs(0, inicio, fin, usuario, descripcion, tipo)'>Primero</button>
					</li>
				";
			}
			if ($indice < $resultado_paginacion - 1) {
				for ($i = $indice + 1; $i <= $final ; $i++) {
					echo "
						<li class='page-item active'>
							<button onclick='recargar_logs(".$i.", inicio, fin, usuario, descripcion, tipo)'>".($i + 1)."</button>
						</li>
					";
				}
				echo "
					<li class='page-item active'>
						<button onclick='recargar_logs(".($resultado_paginacion - 1).", inicio, fin, usuario, descripcion, tipo)'>Último</button>
					</li>
				";
			} elseif ($resultado_paginacion > 1) {
				echo "
					<li class='page-item active' style='background-color: red;'>
						<button onclick='recargar_logs(".($resultado_paginacion - 1).", inicio, fin, usuario, descripcion, tipo)'>Último</button>
					</li>
				";
			}

	} else header('Location: /PHP/index.php'); // SI NO ES ADMINISTRADOR LE REDIRIGIMOS A LA PÁGINA PRINCIPAL
?>
