<?php
	/*
		DESCRIPCIÓN: SE UTILIZAR PARA LLAMAR A LA FUNCIÓN ver_ubicaciones_administrador (funciones.php) QUE CARGA LAS UBICACIONES QUE GESTIONA UN USUARIO Y LAS QUE NO.
		RESULTADO: DEVUELVE HTML CON LAS UBICACIONES GESTIONADAS Y LAS QUE NO.
		LLAMADA: ES LLAMADA DESDE LA FUNCIÓN DE JAVASCRIPT ver_ubicaciones
		PARÁMETROS:
			- $_POST['campo_email']: EMAIL DEL USUARIO. NO NULL
	*/
	session_start();
	include "../../funciones.php";
  if (isset($_SESSION['email']) and isset($_SESSION['password']) and isset($_SESSION['tipo']) and $_SESSION['tipo'] == "administrador") {
				$resultado_visualizacion = ver_ubicaciones_administrador($_POST['campo_email']);
				if ($resultado_visualizacion === "FALLO CONSULTA") echo "Se ha producido un error al ejecutar la modificación. Pruebe a intentarlo de nuevo.";
				elseif ($resultado_visualizacion === "NO MODIFICADO") echo "No existe el usuario que se trata de modificar o no se ha cambiado la contraseña. Recarga la página para actualizar los datos.";
				elseif ($resultado_visualizacion === "ERROR EN LA BD") echo "Se ha producido un error al conectar con la BD. Compruebe que el servicio está funcionando correcamente.";
				elseif (is_array($resultado_visualizacion)) {
					$resultado = "<select id='lista_ubicaciones_seleccionables' class='custom-select' onchange='add_ubicacion(this)'>";
					$resultado .= "<option selected>Selecciona una ubicación para añadir</option>";
					// SI HAY UBICACIONES QUE NO GESTIONA, LAS MOSTRAMOS EN FORMA DE DESPLEGABLE
					if (isset($resultado_visualizacion['nogestionadas'])) {
						foreach ($resultado_visualizacion['nogestionadas'] as $ubicacion) {
							$resultado .= "<option id='".$ubicacion['codigo']."' value='".$ubicacion['codigo']."'>".$ubicacion['descripcion']."</option>";
						}
					}
					$resultado .= "</select><br><br>";
					$resultado .= "<ul id='lista_ubicaciones_seleccionadas' class='list-group'>";
					// SI HAY UBICACIONES QUE GESTIONA, LAS MOSTRAMOS EN FORMA DE LISTA
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
