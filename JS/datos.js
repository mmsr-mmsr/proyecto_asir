// VARIABLES UTILIZADAS PARA LA PAGINACIÓN Y FILTRADO DE LOGS. ÁMBITO GLOBAL
var indice, inicio, fin, usuario, descripcion, tipo;

/**
  DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA ELIMINAR TODOS LOS FILTROS QUE SE HAYAN APLICADO (VACÍA TANTO LAS VARIABLES COMO LOS CAMPOS DEL FORM). DESPUÉS VUELVE A CARGAR LOS DATOS EN LA TABLA Y REHACE EL MENÚ DE PAGINACIÓN
  LLAMADA: ES LLAMADA CUANDO SE HACE CLICK EN EL BOTÓN <button onclick='borrar_filtros()' type='button' class='nav-link active' id='home-tab' data-toggle='tab' role='tab' aria-controls='home' aria-selected='true'>Todos los logs</button>
*/
function mostrar_fichero(accion) {
  if (accion == "exportar") {
    $("#csv_div").show();
    $("#excel_div").show();
    $("#excel_div").attr("onclick", "mostrar_dato('exportar')");
    $("#csv_div").attr("onclick", "mostrar_dato('exportar')");
  } else if (accion == "importar") {
    $("#csv_div").show();
    $("#excel_div").hide();
    $("#csv_div").attr("onclick", "mostrar_dato('importar')");
  }
  $("#csv").prop("checked", false);
  $("#excel").prop("checked", false);
  $("#dato_div").hide();
  $("#boton_div").hide();
}

function mostrar_dato(accion) {
  if (accion == "exportar") {
    // MOSTRAR LAS OPCIONES DE EXPORTACIÓN
    $("#consulta_div").show();
    $("#tabla_div").show();
    $("#contenido_div").show();
    $("#ubicacion_div").show();

    // OCULTAR LAS OPCIONES DE IMPORTACIÓN
    $("#articulos_div").hide();
    $("#ubicaciones_div").hide();
    $("#usuarios_div").hide();
    $("#gestiona_div").hide();
    $("#stock_div").hide();
  } else if (accion == "importar") {
    // MOSTRAR LAS OPCIONES DE IMPORTACIÓN
    $("#articulos_div").show();
    $("#ubicaciones_div").show();
    $("#usuarios_div").show();
    $("#gestiona_div").show();
    $("#stock_div").show();

    // OCULTAR LAS OPCIONES DE EXPORTACIÓN
    $("#consulta_div").hide();
    $("#tabla_div").hide();
    $("#contenido_div").hide();
    $("#ubicacion_div").hide();
  }
  $("#dato_div").show();
  $("#boton_div").hide();
}

function mostar_boton() {
  $("#boton_div").show();
}
