<?php
	session_start();
	include "../funciones.php";
  if (isset($_SESSION['email']) and isset($_SESSION['password']) and isset($_SESSION['tipo']) and $_SESSION['tipo'] == "administrador") {
			//VALIDAR EMAIL
			//if (empty($_POST['campo_email'])) echo "FALLO";
			//else {
				$resultado_visualizacion = ver_ubicaciones_administrador("admin");
	      print_r($resultado_visualizacion);
				if (isset($resultado_visualizacion['gestionadas'])) {
					echo "<select>";
					foreach ($resultado_visualizacion['gestionadas'] as $ubicacion) {
						echo "<option value='".$ubicacion['codigo']."'>".$ubicacion['descripcion']."</option>";
					}
					echo "</select>";
				}
		//	}
  } else header('Location: /PHP/index.php');
	// INSERT INTO ubicaciones VALUES ("AB00", "AULA 00 PISO 2", null);
	// INSERT INTO ubicaciones VALUES ("AB01", "AULA 01 PISO 2", null);
	// INSERT INTO ubicaciones VALUES ("AB02", "AULA 02 PISO 2", null);
	// INSERT INTO gestiona VALUES ("AB00", "admin");
?>
