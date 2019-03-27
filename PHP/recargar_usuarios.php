<?php
	session_start();
	include "funciones.php";
	$resultado_usuarios = ver_usuarios();
	$resultado = "
		<table id='tabla_usuarios' class='table table-responsive-sm table-striped table-hover table-bordered table-dark'>
			<thead class='color_fuerte'>
				<tr>
					<th scope='col'>Email</th>
					<th scope='col'>Contraseña</th>
					<th scope='col'>Tipo</th>
					<th scope='col'>Acciones</th>
				</tr>
			</thead>
			<tbody>
	";
			foreach ($resultado_usuarios as $usuario) {
				$resultado .= "
					<tr>
						<td>".$usuario['email']."</td>
						<td>********</td>
						<td>".$usuario['tipo']."</td>
						<td>
							<a href='#' data-toggle='tooltip' data-placement='top' title='Ver localizaciones'><i class='fas fa-search'></i></a>
							<a href='#' data-toggle='tooltip' data-placement='top' title='Eliminar usuario'><i class='fas fa-trash'></i></a>
							<a href='#' data-toggle='tooltip' data-placement='top' title='Modificar usuario'><i class='fas fa-pen'></i></a>
						</td>
					</tr>
				";
			}
	$resultado .= "
			</tbody>
		</table>";
	echo $resultado;
?>