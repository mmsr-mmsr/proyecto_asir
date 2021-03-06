<?php
	/*
		DESCRIPCIÓN: SE UTILIZAR PARA LLAMAR A LA FUNCIÓN modificar_ubicaciones_administrador (funciones.php) QUE ELIMINA LAS UBICACIONES QUE GESTIONABA UN USUARIO Y ESTABLECE LAS NUEVAS
		RESULTADO: DEVUELVE HTML CON EL RESULTADO DE LA FUNCIÓN.
		LLAMADA: ES LLAMADA DESDE LA FUNCIÓN DE JAVASCRIPT ver_ubicaciones
		PARÁMETROS:
			- $_POST['campo_email']: EMAIL DEL USUARIO. NO NULL
			- $_POST['campo_ubicaciones']: ARRAY CON LAS UBICACIONES
	*/
	session_start();
	include "../../funciones.php";
  if (isset($_SESSION['email']) and isset($_SESSION['password']) and isset($_SESSION['tipo']) and $_SESSION['tipo'] == "administrador") {
				$resultado_modificacion = modificar_ubicaciones_administrador($_POST['campo_email'], $_POST['campo_ubicaciones']);
				if ($resultado_modificacion === True) echo "CORRECTO";
				elseif ($resultado_modificacion === "FALLO CONSULTA") echo "Se ha producido un error al ejecutar la modificación. Pruebe a intentarlo de nuevo.";
				elseif ($resultado_modificacion === "ERROR EN LA BD") echo "Se ha producido un error al conectar con la BD. Compruebe que el servicio está funcionando correcamente.";
  } else header('Location: /PHP/index.php');
?>
