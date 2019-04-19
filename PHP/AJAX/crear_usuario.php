<?php
	/*
		DESCRIPCIÓN: SE UTILIZAR PARA LLAMAR A LA FUNCIÓN crear_usuario (funciones.php) QUE CREA UN USUARIO EN LA BD DESDE UN FORM Y DEVOLVERLE EL RESULTADO AL NAVEGADOR A TRAVÉS DE AJAX.
		RESULTADO: DEVUELVE HTML EN FUNCIÓN DEL RESULTADO DE LA CONSULTA EN LA BD
		LLAMADA: ES LLAMADA DESDE LA FUNCIÓN DE JAVASCRIPT confirmar_crear_usuario
		PARÁMETROS:
			- $_POST['email']: EMAIL DEL USUARIO. ES LA CLAVE PRIMARIA DE LA BD. NO PUEDE SER NULL
			- $_POST['password']: CONTRASEÑA DEL USUARIO. NO PUEDE SER NULL
			- $_POST['nombre']: NOMBRE DEL USUARIO. PUEDE SER NULL
			- $_POST['tipo']: PRIVILEGIOS DEL USUARIO. NO PUEDE SER NULL
	*/
	session_start();
	include "../funciones.php";
  if (isset($_SESSION['email']) and isset($_SESSION['password']) and isset($_SESSION['tipo']) and $_SESSION['tipo'] == "administrador") { // COMPROBAR QUE SE HAYA INICIADO SESIÓN Y EL USUARIO SEA ADMINISTRADOR
			  $resultado_creacion = crear_usuario($_POST['campo_email'], $_POST['campo_password'], $_POST['campo_nombre'], $_POST['campo_tipo']);
	      if ($resultado_creacion === True) echo "CORRECTO";
				elseif ($resultado_creacion === "ERROR EN LA BD") echo "Se ha producido un error al conectar con la BD. Compruebe que el servicio está funcionando correcamente.";
	      elseif ($resultado_creacion === "FALLO EMAIL") echo "El email debe ser rellenado y tener un formato válido.";
				elseif ($resultado_creacion === "FALLO PASSWORD") echo "La contraseña debe ser rellenada.";
				elseif ($resultado_creacion === "FALLO TIPO") echo "El tipo debe ser rellenado y su valor debe ser: administrador, editor o estandar.";
				elseif ($resultado_creacion === "FALLO CREAR") echo "Se ha producido un error al crear el usuario. Pruebe a recargar la página y a comprobar que dicho usuario no exista ya en la BD.";
  } else header('Location: /PHP/index.php');
?>
