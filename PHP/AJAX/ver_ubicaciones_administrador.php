<?php
	session_start();
	include "../funciones.php";
  if (isset($_SESSION['email']) and isset($_SESSION['password']) and isset($_SESSION['tipo']) and $_SESSION['tipo'] == "administrador") {
			//VALIDAR EMAIL
			if (empty($_POST['campo_email'])) echo "FALLO";
			else {
				$resultado_visualizacion = ver_ubicaciones_administrador($_POST['campo_email']);
				$resultado = "<select id='lista_ubicaciones_seleccionables' class='custom-select' onchange='add_ubicacion(this)'>";
				$resultado .= "<option selected>Selecciona una ubicación para añadir</option>";
				if (isset($resultado_visualizacion['nogestionadas'])) {
					foreach ($resultado_visualizacion['nogestionadas'] as $ubicacion) {
						$resultado .= "<option id='".$ubicacion['codigo']."' value='".$ubicacion['codigo']."'>".$ubicacion['descripcion']."</option>";
					}
				}
				$resultado .= "</select><br><br>";
				$resultado .= "<ul id='lista_ubicaciones_seleccionadas' class='list-group'>";
				if (isset($resultado_visualizacion['gestionadas'])) {
					foreach ($resultado_visualizacion['gestionadas'] as $ubicacion) {
						$resultado .= "<li onclick='eliminar_ubicacion(this)' class='list-group-item' id='".$ubicacion['codigo']."'>".$ubicacion['descripcion']."</li>";
					}
				}
				$resultado .= "</ul>";
				echo $resultado;
			}
  } else header('Location: /PHP/index.php');
?>
