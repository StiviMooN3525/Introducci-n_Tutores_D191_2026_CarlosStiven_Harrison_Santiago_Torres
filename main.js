// Archivo main.js: utilidades simples para confirmaciones y UX
function confirmAction(form, message) {
  if (!message) message = '¿Confirmar acción?';
  if (confirm(message)) {
    // si es un elemento DOM (botón dentro de form), recibimos el form
    try { form.submit(); } catch (e) { return true; }
    return true;
  }
  return false;
}

// Confirmar logout
document.addEventListener('DOMContentLoaded', function() {
  var logoutLinks = document.querySelectorAll('a[href="logout.php"]');
  logoutLinks.forEach(function(a){
    a.addEventListener('click', function(ev){
      if (!confirm('¿Cerrar sesión?')) ev.preventDefault();
    });
  });
});
