<?php
  session_start();
  // function validar_codigo($codigo) {
	// 	if (empty($codigo) or !preg_match('/^[[:alpha:]]{2}\d{2}$/', $codigo)) { // COMPROBAR QUE SE HAYA PASADO UN CÓDIGO Y ESTE TENGA UN FORMATO CORRECTO
	// 		return "CODIGO ERRONEO";
	// 	} else {
	// 		return "CODIGO VALIDO";
	// 	}
	// }
  include "funciones.php";
  exportar_csv_tabla_ubicaciones();
  // $manejador_fichero=fopen("ubicaciones(2).csv", 'r');
  // // $conexion = conexion_database();
  // while (!feof($manejador_fichero)) {
  //   //ALMACENAR CADA LÍNEA EN UN ARRAY SEPARANDO POR "|"
  //   $linea=explode(";", fgets($manejador_fichero));
  //   //SI LA MATRÍCULA YA EXISTE, CERRAMOS EL FICHERO Y DEVOLVEMOS ERROR
  //   echo $linea[0];
  //   echo strlen($linea[0]);
  // }
  // while (($linea = fgetcsv($manejador_fichero, 1000, ";")) !== FALSE) {
  //   //$linea = array_map("utf8_encode", $linea);
  //   echo $linea[0]."<br>";
  //
  //
  //   echo strlen(str_replace(array("\n", "\r"), array('\n', '\r'), $linea[0]));
  //   echo strlen($linea[0]);
  //   $linea[0] = trim($linea[0]);
  //   echo validar_codigo(trim($linea[0]));
    //if (!validar_codigo($linea[0])) echo "CODIGO INVALIDO";
    //else echo "CODIGO VALIDO";
    // $sentencia = $conexion->prepare("INSERT INTO articulos VALUES (? , ?, ?)");
    // $sentencia->bind_param("sss", $linea[0], $linea[1], $linea[2]);
    // $sentencia->execute();
   // echo exportar_csv_tabla_logs();
?>
