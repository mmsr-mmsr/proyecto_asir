// FUNCIÓN QUE RECARGA EL CONTENIDO DE LA TABLA USUARIOS, ES LLAMADA CADA VEZ QUE UNA FUNCIÓN HACE ALGUNA MODIFICACIÓN EN LA TABLA O LA FILTRA
function recargar_usuarios(filtro = "ninguno"){
  $.post("../PHP/AJAX/recargar_usuarios.php",
  {
    campo_filtro: "ninguno"
  },
  function(resultadox) {
    $("#contenido_usuarios").html(resultadox);
  });
}
//FUNCIÓN DE ELIMINAR USUARIO, PIDE CONFIRMACIÓN ANTES DE REALIZAR EL BORRADO
function eliminar_usuario(elemento){
  //OBTENER EL EMAIL DEL USUARIO QUE SE DESEA ELIMINAR
  fila = $(elemento).parent().siblings();
  email = fila[0]['firstChild']['value'];
  $.confirm({
    title: "Eliminar usuario",
    content: "¿Estás seguro de eliminar al usuario " + email + "?",
    buttons: {
      Eliminar: function () {
        $.post("../PHP/AJAX/eliminar_usuario.php",
        {
          campo_email: email
        },
        function(resultado) {
          $("#id_resultado").html(resultado);
          $("#id_resultado").hide(5000);
          recargar_usuarios();
        });
      },
      Cancelar: function () {
      },
    }
  });
}
// AL HACER CLICK EN EL BOTÓN DE CREAR USUARIO SE AÑADE UNA FILA A LA TABLA CON UN FORM
$("#crear_usuario").click(function(){
  $("#contenido_usuarios").append(
    "<tr>" +
      "<td><input type='text' name='campo_email' class=''></td>" +
      "<td><input type='text' name='campo_nombre'></td>" +
      "<td>" +
        "<select class='custom-select'>" +
          "<option value='estandar'>Estándar</option> " +
          "<option value='editor'>Editor</option> " +
          "<option value='administrador'>Administrador</option>" +
        "</select>" +
      "</td>" +
      "<td>" +
        "<button onclick='confirmar_crear_usuario(this)' type='button' data-toggle='tooltip' data-placement='top' title='Confirmar'><i class='fas fa-check'></i></button>" +
        "<button onclick='cancelar_nuevo_usuario(this)' type='button' data-toggle='tooltip' data-placement='top' title='Cancelar'><i class='fas fa-times'></i></button>" +
      "</td>" +
    "</tr>"
  )
});
// VALIDA SI UN EMAIL ES CORRECTO, DEVUELVE TRUE O FALSE
function validar_email(email) {
  var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
  return re.test(String(email).toLowerCase());
}
function modificar_usuario(elemento) {
  var fila, botones, nombre, tipo;
  // CAMBIAR LOS INPUTS nombre Y tipo A EDITABLES
  fila = $(elemento).parent().siblings();
  nombre = fila[1]['firstChild'];
  tipo = fila[2]['children'][0];
  console.log(fila);
  $(nombre).removeAttr("readonly");
  $(tipo).removeAttr("disabled");

  // CAMBIAR LOS BOTONES DE "ACCIONES" POR LOS DE CONFIRMAR O CANCELAR LA MODIFICACIÓN
  botones = $(elemento).parent();
  $(botones).html(
    "<button onclick='confirmar_modificar_usuario(this)' type='button' data-toggle='tooltip' data-placement='top' title='Confirmar'><i class='fas fa-check'></i></button>" +
    "<button onclick='cancelar_modificar_usuario(this)' type='button' data-toggle='tooltip' data-placement='top' title='Cancelar'><i class='fas fa-times'></i></button>"
  );
}
function confirmar_modificar_usuario(elemento) {
  var fila, email, nombre, tipo;
  // CAMBIAR LOS INPUTS nombre Y tipo A EDITABLES
  fila = $(elemento).parent().siblings();
  email = fila[0]['firstChild']['value'];
  nombre = fila[1]['firstChild']['value'];
  tipo = fila[2]['children'][0]['value'];
  $.post("../PHP/AJAX/modificar_usuario.php",
  {
    campo_email: email,
    campo_nombre: nombre,
    campo_tipo: tipo
  },
  function(resultado) {
    if (resultado == "CORRECTO") {
      $.alert("Se ha modificado correctamente al usuario " + email);
      recargar_usuarios();
    } else {
      $.alert(resultado);
    }
  });
}
function cancelar_modificar_usuario(elemento) {
  var botones, fila, tipo;
  // CAMBIAR LOS INPUTS A MODO NO EDITABLE
  fila = $(elemento).parent().siblings();
  nombre = fila[1]['firstChild'];
  tipo = fila[2]['children'][0];
  $(nombre).attr("readonly", "readonly");
  $(tipo).attr("disabled", "disabled");

  // CAMBIAR LOS BOTONES DE CONFIRMAR O CANCELAR LA MODIFICACIÓN POR LOS HABITUALES DE "ACCIONES"
  botones = $(elemento).parent();
  $(botones).html(
    "<button type='button' data-toggle='tooltip' data-placement='top' title='Ver localizaciones'><i class='fas fa-search'></i></button>" +
    "<button onclick='eliminar_usuario(this)' type='button' data-toggle='tooltip' data-placement='top' title='Eliminar usuario'><i class='fas fa-trash'></i></button>" +
    "<button onclick='modificar_usuario(this)' type='button' data-toggle='tooltip' data-placement='top' title='Modificar usuario'><i class='fas fa-pen'></i></button>"
  );
}
// AL HACER CLICK EN EL BOTÓN DE CONFIRMAR NUEVO USUARIO SE COMPRUEBA QUE SE HAYA INTRODUCIDO EMAIL Y TENGA UN FORMATO CORRECTO.
// TAMBIÉN SE SOLICITA CONTRASEÑAS Y SI TODOS LOS DATOS SOLICITADOS SON CORRECTOS SE LLAMA MEDIANTE AJAX A LA FUNCIÓN PHP QUE CREA EL USUARIO
function confirmar_crear_usuario(elemento) {
  fila = $(elemento).parent().siblings();
  email = fila[0]['firstChild']['value'];
  nombre = fila[1]['firstChild']['value'];
  tipo = fila[2]['firstChild']['value'];
  // COMPROBAR QUE SE HAYA INTRODUCIDO UN EMAIL
  if (!email) {
      $.alert("Debes rellenar el email.");
      return false;
  // COMPROBAR QUE EL EMAIL TENGA UN FORMATO CORRECTO
  } else if (!validar_email(email)) {
    $.alert("El email debe tener un formato correcto, ni ");
    return false;
  } else {
    $.confirm({
      title: "Introduce la contraseña para el usuario " + email + ": ",
      columnClass: "col-sm-12 col-md-10 col-lg-6 col-xl-8",
      content: "" +
        "<form action='' class='formName'>" +
          "<div class='form-group'>" +
            "<input type='password' id='campo_password_1' placeholder='Contraseña' class='name form-control'>" +
            "<br>" +
            "<input type='password' id='campo_password_2' placeholder='Repite la contraseña' class='name form-control'>" +
          "</div>" +
        "</form>",
      buttons: {
        Crear: {
          btnClass: "btn color_intermedio",
          action: function () {
            var password_1 = $("#campo_password_1").val();
            var password_2 = $("#campo_password_2").val();
            // COMPROBAR QUE SE HAYAN INTRODUCIDO AMBAS
            if (!password_1 || !password_2) {
                $.alert("Debes rellenar ambos campos.");
                return false;
            // COMPROBAR QUE SEAN IGUALES
            } else if (password_1 != password_2) {
              $.alert("Las contraseñas deben coincidir.");
              return false;
            } else {
              $.post("../PHP/AJAX/crear_usuario.php",
              {
                campo_email: email,
                campo_password: password_1,
                campo_nombre: nombre,
                campo_tipo: tipo
              },
              function(resultado) {
                if (resultado == "CORRECTO") {
                  $.alert("Se ha creado correctamente al usuario " + email);
                  recargar_usuarios();
                } else {
                  $.alert(resultado);
                }
              });
            }
          }
        },
        Cancelar: function () {
        },
      }
    });
  }
}
// AL CANCELAR LA CREACIÓN DE UN USUARIO, SE ELIMINA LA FILA CREADA
function cancelar_nuevo_usuario(elemento) {
  fila = $(elemento).parent().parent();
  $(fila).remove();
}
function modificar_password(elemento) {
  var fila, email;
  // ALMACENAR EL EMAIL PARA SABER QUÉ USUARIO DEBEMOS MODIFICAR LA PASSWORD
  fila = $(elemento).parent().siblings();
  email = fila[0]['firstChild']['value'];

  $.post("../PHP/AJAX/modificar_password.php",
  {
    campo_email: email,
    campo_password: password_1
  },
  function(resultado) {
    if (resultado == "CORRECTO") {
      $.alert("Se ha modificado correctamente la contraseña del usuario " + email);
    } else {
      $.alert(resultado);
    }
  });
  // MOSTRAR UNA VENTANA PARA QUE ESCRIBA LA NUEVA CONTRASEÑA
  $.confirm({
    title: "Introduce la nueva contraseña para el usuario " + email + ": ",
    columnClass: "col-sm-12 col-md-10 col-lg-6 col-xl-8",
    content: "" +
      "<form action='' class='formName'>" +
        "<div class='form-group'>" +
          "<input type='password' id='campo_password_1' placeholder='Contraseña' class='name form-control'>" +
          "<br>" +
          "<input type='password' id='campo_password_2' placeholder='Repite la contraseña' class='name form-control'>" +
        "</div>" +
      "</form>",
    buttons: {
      Modificar: {
        btnClass: "btn color_intermedio",
        action: function () {
          var password_1 = $("#campo_password_1").val();
          var password_2 = $("#campo_password_2").val();
          // COMPROBAR QUE SE HAYAN INTRODUCIDO AMBAS
          if (!password_1 || !password_2) {
              $.alert("Debes rellenar ambos campos.");
              return false;
          // COMPROBAR QUE SEAN IGUALES
          } else if (password_1 != password_2) {
            $.alert("Las contraseñas deben coincidir.");
            return false;
          } else {
            $.post("../PHP/AJAX/modificar_password.php",
            {
              campo_email: email,
              campo_password: password_1
            },
            function(resultado) {
              if (resultado == "CORRECTO") {
                $.alert("Se ha modificado correctamente la contraseña del usuario " + email);
              } else {
                $.alert(resultado);
              }
            });
          }
        }
      },
      Cancelar: function () {
      },
    }
  });

}
$("#prueba").click(function(){
  alert("hola");
});
function ver_ubicaciones(elemento) {
  var fila, email, ubicaciones_seleccionadas, ubicaciones_array;
  // ALMACENAR EL EMAIL PARA SABER QUÉ USUARIO DEBEMOS MOSTRAR SUS UBICACIONES
  fila = $(elemento).parent().siblings();
  email = fila[0]['firstChild']['value'];

  // OBTENER POR AJAX LAS LOCALIZACIONES QUE EL USUARIO PUEDE GESTIONAR
  $.post("../PHP/AJAX/ver_ubicaciones_administrador.php",
  {
    campo_email: email
  },
  function(resultado) {

    $.confirm({
      title: "Ubicaciones del usuario " + email + ": ",
      columnClass: "col-sm-12 col-md-12 col-lg-12 col-xl-6",
      content: resultado,
      buttons: {
        Guardar_cambios: {
          btnClass: "btn color_intermedio",
          action: function () {
            // GUARDAMOS EN LA VAR ubicaciones_seleccionadas LAS UBICACIONES SELECCIONADAS
            ubicaciones_seleccionadas = $("#lista_ubicaciones_seleccionadas").children();
            if (ubicaciones_seleccionadas.length == 0) {
              ubicaciones_array = "ninguno";
            } else {
              ubicaciones_array = {};
              for (var i = 0; i < ubicaciones_seleccionadas.length; i++) {
                ubicaciones_array[i] = $(ubicaciones_seleccionadas[i]).attr("id");
              }
            }
            $.post("../PHP/AJAX/modificar_ubicaciones_administrador.php",
            {
              campo_email: email,
              campo_ubicaciones: ubicaciones_array
            },
            function(resultado) {
              if (resultado == "CORRECTO") {
                $.alert("Se ha modificado correctamente las ubicaciones que gestiona el usuario " + email);
              } else {
                $.alert("Se ha producido un error al intentar modificar las ubicaciones que gestiona el usuario " + email);
              }
            });
          }
        },
        Cancelar: function () {
        },
      }
    });
  });
}
function eliminar_ubicacion(elemento) {
  var codigo, descripcion;
  codigo = $(elemento).attr("id");
  descripcion = $(elemento).text();
  $("#lista_ubicaciones_seleccionables").append("<option id='" + codigo + "' value='" + codigo + "'>" + descripcion + "</option>")
  $(elemento).remove();
}
function add_ubicacion(elemento) {
  var codigo, descripcion;
  codigo = $(elemento).val();
  descripcion = $("#" + codigo).text();
  $("#lista_ubicaciones_seleccionadas").append("<li onclick='eliminar_ubicacion(this)' class='list-group-item' id='" + codigo + "'>" + descripcion + "</li>")
  $("#" + codigo).remove();

}
