/**
  DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA COMPROBAR SI UN CÓDIGO TIENE EL FORMATO CORRECTO MEDIANTE UNA EXPRESIÓN REGULAR
  RESULTADO: DEVUELVE FALSE SI NO TIENE UN FORMATO CORRECTO O TRUE SI SÍ QUE LO TIENE
  LLAMADA: ES LLAMADA AL CREAR UN ARTÍCULO.
  PARÁMETROS:
    - CÓDIGO: CADENA QUE VALIDAR
*/
function validor_codigo(codigo) {
  var re = /^[A-Z]{2}\d{2}$/;
  return re.test(String(codigo));
}
/**
  DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA RECARGAR LA TABLA QUE CONTIENE INFORMACIÓN SOBRE LOS ARTÍCULOS.
  LLAMADA: ES LLAMADA CADA VEZ QUE SE REALIZA UN CAMBIO VISIBLE EN LA TABLA (BORRADO, MODIFICACIÓN O ADICIÓN DE ARTÍCULOS) O DESDE LA FUNCIÓN buscar_articulos()
  PARÁMETROS:
    - FILTRO: CADENA POR LA QUE FILTRAR EL RESULTADO, SI NO SE LE PASA TOMA EL VALOR = "ninguno"
*/
function recargar_articulos(filtro = "ninguno"){
  $.post("../PHP/AJAX/ARTICULOS/recargar_articulos.php",
  {
    campo_filtro: filtro
  },
  function(resultado) {
    if (resultado.substring(0,3) == "<tr") {
      $("#contenido_articulos").html(resultado); //EL RESULTADO DEL SCRIPT PHP SUSTITUYE EL CONTENIDO DE LA TABLA
    } else {
      $("#contenido_articulos").html("");
      $.alert({
        title: "ERROR",
        content: resultado,
        columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
      });
    }
  });
}
/**
  DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA FILTRAR LA TABLA ARTÍCULOS POR NOMBRE/DESCRIPCIÓN. SI EL CAMPO 'campo_buscar' ESTÁ VACÍO MUESTRA LA TABLA ENTERA
  LLAMADA: ES LLAMADA CADA VEZ QUE SE HACE CLICK EN EL BOTÓN <button onclick='buscar_articulos()'>
  PARÁMETROS:
*/
function buscar_articulos() {
  var texto;
  texto = $("#campo_buscar").val();
  if (texto.length > 0) {
    recargar_articulos(texto);
  } else {
    recargar_articulos();
  }
}
// AL HACER CLICK EN EL BOTÓN DE CREAR ARTÍCULO SE AÑADE UNA FILA A LA TABLA CON UN FORM
$("#crear_articulo").click(function(){
  $("#contenido_articulos").append(
    "<tr>" +
      "<td><input type='text' name='campo_codigo' class=''></td>" +
      "<td><input type='text' name='campo_descripcion'></td>" +
      "<td><input type='text' name='campo_observaciones'></td>" +
      "<td>" +
        "<button onclick='confirmar_crear_articulo(this)' type='button' data-toggle='tooltip' data-placement='top' title='Confirmar'><i class='fas fa-check'></i></button>" +
        "<button onclick='cancelar_nuevo_articulo(this)' type='button' data-toggle='tooltip' data-placement='top' title='Cancelar'><i class='fas fa-times'></i></button>" +
      "</td>" +
    "</tr>"
  );
});
/**
  DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA RECOGER LOS DATOS DE UN NUEVO ARTÍCULO Y LLAMAR AL SCRIPT PHP (crear_articulo.php) QUE LO CREE. ADEMÁS COMPROBARÁ QUE EL CÓDIGO ES VÁLIDO Y QUE SE HAYA PROPORCIONADO UNA DESCRIPCIÓN
  LLAMADA: ES LLAMADA AL HACER CLICK EN EL BOTÓN DE CONFIRMAR ARTÍCULO NUEVO NUEVO.
  PARÁMETROS:
    - ELEMENTO: ELEMENTO DEL ÁRBOL DOM QUE HA LLAMADO A LA FUNCIÓN
*/
function confirmar_crear_articulo(elemento) {
  var fila, codigo, descripcion, observaciones;
  // ALMACENAR LOS DATOS QUE EL USUARIO HA INTRODUCIDO
  fila = $(elemento).parent().siblings();
  codigo = fila[0]['firstChild']['value'];
  descripcion = fila[1]['firstChild']['value'];
  observaciones = fila[2]['firstChild']['value'];
  // COMPROBAR QUE SE HAYA INTRODUCIDO UN CÓDIGO
  if (!codigo) {
    $.alert({
      title: "ERROR",
      content: "Se debe rellenar el campo código",
      columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
    });
      return false;
  // COMPROBAR QUE EL CÓDIGO TENGA UN FORMATO CORRECTO
  } else if (!validor_codigo(codigo)) {
    $.alert({
      title: "ERROR",
      content: "El código debe tener un formato correcto. Por ejemplo AA00.",
      columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
    });
    return false;
  // COMPROBAR QUE SE HAYA INTRODUCIDO UNA DESCRIPCIÓN
  } else if (!descripcion) {
    $.alert({
      title: "ERROR",
      content: "Se debe rellenar el campo descripción.",
      columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
    });
      return false;
  } else {
    $.post("../PHP/AJAX/ARTICULOS/crear_articulo.php",
    {
      campo_codigo: codigo,
      campo_descripcion: descripcion,
      campo_observaciones: observaciones
    },
    function(resultado) {
      if (resultado == "CORRECTO") {
        $.alert({
          title: "ARTÍCULO CREADO",
          content: "Se ha creado correctamente el artículo " + codigo,
          columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
        });
        recargar_articulos(); // RECARGAR LA TABLA
      } else {
        $.alert({
          title: "ERROR",
          content: resultado,
          columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
        });
      }
    });
  }
} //
/**
  DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA ELIMINAR LA FILA GENERADA PARA CREAR UN NUEVO ARTÍCULO
  LLAMADA: ES LLAMADA AL HACER CLICK EN EL BOTÓN DE CANCELAR LA CREACIÓN DE UN NUEVO ARTÍCULO
  PARÁMETROS:
    - ELEMENTO: ELEMENTO DEL ÁRBOL DOM QUE HA LLAMADO A LA FUNCIÓN
*/
function cancelar_nuevo_articulo(elemento) {
  var fila;
  fila = $(elemento).parent().parent();
  $(fila).remove(); // ******** COMPLETADO ********
} // **** COMPLETADO **** //
/**
  DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA ELIMINAR UN ARTÍCULO MEDIANTE UNA LLAMADA A PHP (eliminar_articulo.php). TRAS REALIZAR LA ACCIÓN RECARGA LA PÁGINA
  LLAMADA: ES LLAMADA CUANDO EL USUARIO PULSA EL BOTÓN DE BORRAR ARTÍCULO -> <button onclick='eliminar_articulo(this)'>
  PARÁMETROS:
    - ELEMENTO: ELEMENTO DEL ÁRBOL DOM QUE HA LLAMADO A LA FUNCIÓN. SERÁ UTILIZADO PARA OBTENER EL CÓDIGO DEL ARTÍCULO
*/
function eliminar_articulo(elemento) {
  var fila, codigo;
  fila = $(elemento).parent().siblings();
  codigo = fila[0]['firstChild']['value']; // OBTENER EL CÓDIGO
  $.confirm({
    title: "Eliminar artículo",
    columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6",
    content: "¿Estás seguro de eliminar el artículo " + codigo + "?",
    buttons: {
      Eliminar: {
        btnClass: "btn color_intermedio",
        action: function () { // TRAS PULSAR EL BOTÓN DE "ELIMINAR" LLAMA AL FICHERO PHP DE ELIMINAR ARTÍCULO
          $.post("../PHP/AJAX/ARTICULOS/eliminar_articulo.php",
          {
            campo_codigo: codigo // PASAR COMO PARÁMETRO EL CÓDIGO
          },
          function(resultado) {
            if (resultado == "CORRECTO") {
              $.alert({
                title: "Artículo eliminado",
                content: "Se ha eliminado correctamente el artículo " + codigo,
                columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
              });
              recargar_articulos(); // RECARGAR LA TABLA
            } else {
              $.alert({
                title: "ERROR",
                content: resultado,
                columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
              });
            }
          });
        }
      },
      Cancelar: function () {
      },
    }
  }); // ******** COMPLETADO ********
}
/**
  DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA CONVERTIR UNA FILA DE LA TABLA ARTÍCULOS EN MODIFICABLE
  RESULTADO: NO DEVUELVE NINGÚN VALOR
  LLAMADA: ES LLAMADA AL PULSAR SOBRE EL BOTÓN DE MODIFICAR ARTÍCULO <button onclick='modificar_articulo(this)'
  PARÁMETROS:
    - ELEMENTO: ELEMENTO DEL ÁRBOL DOM QUE HA LLAMADO A LA FUNCIÓN
*/
function modificar_articulo(elemento) {
  var fila, botones, descripcion, observaciones;
  // CAMBIAR LOS INPUTS descripcion Y observaciones A EDITABLES
  fila = $(elemento).parent().siblings();
  descripcion = fila[1]['firstChild']; // ALMACENAR DESCRIPCION
  observaciones = fila[2]['firstChild']; // ALMACENAR OBSERVACIONES
  $(descripcion).removeAttr("readonly");
  $(observaciones).removeAttr("readonly");

  // CAMBIAR LOS BOTONES DE "ACCIONES" POR LOS DE CONFIRMAR O CANCELAR LA MODIFICACIÓN
  botones = $(elemento).parent();
  $(botones).html(
    "<button onclick='confirmar_modificar_articulo(this)' type='button' data-toggle='tooltip' data-placement='top' title='Confirmar'><i class='fas fa-check'></i></button>" +
    "<button onclick='cancelar_modificar_articulo(this)' type='button' data-toggle='tooltip' data-placement='top' title='Cancelar'><i class='fas fa-times'></i></button>"
  );
}
/**
  DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA RECOGER LOS DATOS DE LA MODIFICACIÓN DE UN ARTÍCULO Y LLAMAR AL SCRIPT PHP (modificar.php) QUE LO MODIFIQUE.
  LLAMADA: ES LLAMADA AL HACER CLICK EN EL BOTÓN DE CONFIRMAR MODIFICACIÓN DEL ARTÍCULO <button onclick='confirmar_modificar_articulo(this)'
  PARÁMETROS:
    - ELEMENTO: ELEMENTO DEL ÁRBOL DOM QUE HA LLAMADO A LA FUNCIÓN
*/
function confirmar_modificar_articulo(elemento) {
  var fila, codigo, descripcion, observaciones;
  // OBTENER LOS DATOS
  fila = $(elemento).parent().siblings();
  codigo = fila[0]['firstChild']['value'];
  descripcion = fila[1]['firstChild']['value'];
  observaciones = fila[2]['children'][0]['value'];
  // VALIDAR QUE SE HAYA INTRODUCIDO UNA DESCRIPCIÓN
  if (!descripcion) {
    $.alert({
      title: "ERROR",
      content: "Se debe rellenar el campo descripción.",
      columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
    });
      return false;
  } else {
    $.post("../PHP/AJAX/ARTICULOS/modificar_articulo.php", // LLAMAR AL SCRIPT PHP CON LOS DATOS RECOGIDOS
    {
      campo_codigo: codigo,
      campo_descripcion: descripcion,
      campo_observaciones: observaciones
    },
    function(resultado) {
      if (resultado == "CORRECTO") {
        $.alert({
          title: "ARTÍCULO MODIFICADO",
          content: "Se ha modificado correctamente el artículo " + codigo,
          columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
        });
        recargar_articulos();
      } else {
        $.alert({
          title: "ERROR",
          content: resultado,
          columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
        });
      }
    });
  }
}
/**
  DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA VOLVER A HACER LA FILA DE LA TABLA ARTÍCULOS NO MODIFICABLE
  LLAMADA: ES LLAMADA AL HACER CLICK EN EL BOTÓN DE CANCELAR LA MODIFICACIÓN DE UN ARTÍCULO <button onclick='cancelar_modificar_articulo(this)'
  RESULTADO: NO DEVUELVE NINGÚN VALOR
  PARÁMETROS:
    - ELEMENTO: ELEMENTO DEL ÁRBOL DOM QUE HA LLAMADO A LA FUNCIÓN
*/
function cancelar_modificar_articulo(elemento) {
  var botones, fila, descripcion, observaciones;
  // CAMBIAR LOS INPUTS A MODO NO EDITABLE
  fila = $(elemento).parent().siblings();
  descripcion = fila[1]['firstChild'];
  observaciones = fila[2]['firstChild'];
  $(descripcion).attr("readonly", "readonly");
  $(observaciones).attr("readonly", "readonly");

  // CAMBIAR LOS BOTONES DE CONFIRMAR O CANCELAR LA MODIFICACIÓN POR LOS HABITUALES DE "ACCIONES"
  botones = $(elemento).parent();
  $(botones).html(
    "<button onclick='eliminar_articulo(this)' type='button' data-toggle='tooltip' data-placement='top' title='Eliminar artículo'><i class='fas fa-trash'></i></button>" +
    "<button onclick='modificar_articulo(this)' type='button' data-toggle='tooltip' data-placement='top' title='Modificar artículo'><i class='fas fa-pen'></i></button>"
  );
}
