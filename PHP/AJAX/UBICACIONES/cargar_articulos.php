<?php
	/*
		DESCRIPCIÓN: SE UTILIZAR PARA LLAMAR A LA FUNCIÓN ver_articulos_por_ubicacion (funciones.php) QUE CARGA LOS ARTÍCULOS DE UNA UBICACIÓN Y DEVOLVERLE EL RESULTADO AL NAVEGADOR A TRAVÉS DE AJAX.
		RESULTADO: DEVUELVE HTML EN FUNCIÓN DEL RESULTADO DE LA CONSULTA EN LA BD
		LLAMADA: ES LLAMADA DESDE LA FUNCIÓN DE JAVASCRIPT ver_articulos
		PARÁMETROS:
			- $_POST['campo_codigo']: INDICA EL CÓDIGO DE LA UBICACIÓN QUE SE VA A LISTAR
	*/
	session_start();
	include "../../funciones.php";
	if (isset($_SESSION['email']) and isset($_SESSION['password']) and isset($_SESSION['tipo'])) { // COMPROBAR QUE UN USUARIO HAYA INICIADO SESIÓN
		$resultado_articulos_ubicacion = ver_articulos_inventariados_por_ubicacion($_POST['campo_codigo']);
	  if ($resultado_articulos_ubicacion === "ERROR EN LA BD") echo "Se ha producido un error al conectarse al servidor. Prueba a conectarte más tarde.";
		elseif ($resultado_articulos_ubicacion === "FALLO CONSULTA") echo "Se ha producido un error al consultar los datos. Prueba a actualizar la página y volver a intentarlo.";
		elseif ($resultado_articulos_ubicacion === "FALLO UBICACION") echo "La ubicación que ha tratado de listar no está disponible o no existe. Actualiza la página para recargar los datos.";
		else {
			$resultado = "<thead id='cabecera_ubicaciones' class='color_fuerte'>
											<tr>
												<th scope='col'>Código</th>
												<th scope='col'>Descripción</th>
												<th scope='col'>Cantidad</th>
												<th scope='col'>Eliminar artículo</th>
											</tr>
										</thead>
										<tbody id='contenido_ubicaciones'>
			";
			if (is_array($resultado_articulos_ubicacion)) {
				foreach ($resultado_articulos_ubicacion as $articulo) { // RECORRER EL ARRAY OBTENIDO MOSTRANDO LOS DATOS
					$resultado .= "<tr id='".$articulo['codigo']."'>
													<td><input type='text' name='campo_codigo' value='".$articulo['codigo']."' readonly></td>
													<td><input type='text' name='campo_descripcion' value='".$articulo['descripcion']."' readonly></td>
													<td><input type='number' name='campo_cantidad' value='".$articulo['cantidad']."'></td>
													<td>
														<button onclick='quitar_articulo(this)' type='button' data-toggle='tooltip' data-placement='top' title='Eliminar artículo'><i class='fas fa-trash'></i></button>
													</td>
												</tr>
					";
				}
			}
			echo $resultado;
		}
	} else header('Location: /PHP/index.php'); // SI NO ES ADMINISTRADOR LE REDIRIGIMOS A LA PÁGINA PRINCIPAL
?>
