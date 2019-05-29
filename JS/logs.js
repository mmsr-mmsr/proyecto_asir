// VARIABLES UTILIZADAS PARA LA PAGINACIÓN Y FILTRADO DE LOGS. ÁMBITO GLOBAL
var indice, inicio, fin, usuario, descripcion, tipo;

/**
  DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA ELIMINAR TODOS LOS FILTROS QUE SE HAYAN APLICADO (VACÍA TANTO LAS VARIABLES COMO LOS CAMPOS DEL FORM). DESPUÉS VUELVE A CARGAR LOS DATOS EN LA TABLA Y REHACE EL MENÚ DE PAGINACIÓN
  LLAMADA: ES LLAMADA CUANDO SE HACE CLICK EN EL BOTÓN <button onclick='borrar_filtros()' type='button' class='nav-link active' id='home-tab' data-toggle='tab' role='tab' aria-controls='home' aria-selected='true'>Todos los logs</button>
*/
function borrar_filtros() {
  inicio = "";
  fin = "";
  usuario = "";
  descripcion = "";
  tipo = "";
  // inicio = $("#campo_inicio").val("");
  // fin = $("#campo_fin").val("");
  // usuario = $("#campo_usuario").val("");
  // descripcion = $("#campo_descripcion").val("");
  // tipo = $("#campo_tipo").val("");
  recargar_logs(); // LLAMAR A LA FUNCIÓN QUE RECARGA LOS DATOS DE LA TABLA Y REHACE EL MENÚ DE PAGINACIÓN
}
/**
  DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA CARGAR LOS LOGS DE LA BD LLAMANDO AL FICHERO ../PHP/AJAX/LOGS/recargar_logs.php. DESPUÉS REHACE EL MENÚ DE PAGINACIÓN MEDIANTE LA LLAMADA AL SCRIPT ../PHP/AJAX/LOGS/paginar_logs.php
  LLAMADA: ES LLAMADA CUANDO SE HACE CLICK EN EL BOTÓN <button onclick='borrar_filtros()' type='button' class='nav-link active' id='home-tab' data-toggle='tab' role='tab' aria-controls='home' aria-selected='true'>Todos los logs</button>
*/
function recargar_logs(indice = 0, inicio = "", fin = "", usuario = "", descripcion = "", tipo = "") {
  alert("Inicio: " + inicio + " Fin: " + fin);
  //alert("Indice: " + indice + " Inicio: " + inicio + " Fin: " + fin + " Usuario: " + usuario + " Descripción: " + descripcion + " Tipo: " + tipo);
  $.post("../PHP/AJAX/LOGS/recargar_logs.php",
  {
    campo_indice: indice,
    campo_inicio: inicio,
    campo_fin: fin,
    campo_usuario: usuario,
    campo_descripcion: descripcion,
    campo_tipo: tipo
  },
  function(resultado) {
    $("#contenido_logs").html(resultado); //EL RESULTADO DEL SCRIPT PHP SUSTITUYE EL CONTENIDO DE LA TABLA LOGS
  });
  $.post("../PHP/AJAX/LOGS/paginar_logs.php",
  {
    campo_indice: indice,
    campo_inicio: inicio,
    campo_fin: fin,
    campo_usuario: usuario,
    campo_descripcion: descripcion,
    campo_tipo: tipo
  },
  function(resultado) {
    $("#paginar_logs").html(resultado); //EL RESULTADO DEL SCRIPT PHP SUSTITUYE EL CONTENIDO DEL MENÚ DE PAGINACIÓN, ADAPTÁNDOSE A LOS NUEVOS RESULTADOS
  });
}

/**
  DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA OBTENER LOS DATOS QUE EL USUARIO HAYA INTRODUCIDO EN EL FORMULARIO DE FILTRADO Y LLAMAR A LA FUNCIÓN recargar_logs QUE RECARGA LA TABLA CON LOS NUEVOS FILTROS
  LLAMADA: ES LLAMADA CADA VEZ QUE SE HACE CLICK EN EL BOTÓN <button onclick='buscar_logs()' type='button' class='nav-link' id='search-tab' data-toggle='tab' role='tab' aria-controls='search' aria-selected='false'><i class='fas fa-search'></i></button>
*/
function buscar_logs() {
  inicio = $("#campo_inicio").val();
  if (inicio.length > 0) {
    inicio = new Date(inicio);
    inicio = inicio.getTime() / 1000;
  }
  fin = $("#campo_fin").val();
  if (fin.length > 0) {
    fin = new Date(fin);
    fin = fin.getTime() / 1000;
  }
  usuario = $("#campo_usuario").val();
  descripcion = $("#campo_descripcion").val();
  tipo = $("#campo_tipo").val();
  recargar_logs(0, inicio, fin, usuario, descripcion, tipo);
}
