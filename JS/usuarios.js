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
          campo_correo: email
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
        "<button onclick='crear_usuario(this) 'id='' type='button' data-toggle='tooltip' data-placement='top' title='Confirmar'><i class='fas fa-check'></i></button>" +
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

// AL HACER CLICK EN EL BOTÓN DE CONFIRMAR NUEVO USUARIO SE COMPRUEBA QUE SE HAYA INTRODUCIDO EMAIL Y TENGA UN FORMATO CORRECTO.
// TAMBIÉN SE SOLICITA CONTRASEÑAS Y SI TODOS LOS DATOS SOLICITADOS SON CORRECTOS SE LLAMA MEDIANTE AJAX A LA FUNCIÓN PHP QUE CREA EL USUARIO
function crear_usuario(elemento) {
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
    $.alert("El email debe tener un formato correcto, nada de ");
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
                campo_correo: email,
                campo_password: password_1,
                campo_nombre: nombre,
                campo_tipo: tipo
              },
              function(resultado) {
                $("#id_resultado").html(resultado);
                $("#id_resultado").hide(5000);
                recargar_usuarios();
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
