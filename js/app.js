// =====================================================================
// js/app.js — JavaScript vanilla de RutaNeta
// Lo cargan login.php, registro.php y las páginas internas al final del
// <body>: cuando este archivo corre, el HTML ya existe. Cada bloque busca
// sus elementos y, si la página no los tiene, no hace nada.
// Etapa 3: mostrar contraseña y validar el registro.
// =====================================================================

// ===== Etapa 3 · Acceso (login y registro) =====

// Mostrar u ocultar la contraseña.
// El navegador solo revela el texto si el input cambia a type="text";
// la clase "activo" cambia el aspecto del botón desde css/estilos.css.
document.querySelectorAll('.btn-ver').forEach(function (boton) {
    boton.addEventListener('click', function () {
        const campo = document.getElementById(boton.dataset.objetivo);
        const mostrar = campo.type === 'password';

        campo.type = mostrar ? 'text' : 'password';
        boton.classList.toggle('activo', mostrar);
        boton.textContent = mostrar ? 'Ocultar' : 'Ver';
        boton.setAttribute('aria-pressed', mostrar ? 'true' : 'false');
    });
});

// Registro: JS revisa que las contraseñas coincidan antes de que PHP reciba el formulario
const formRegistro = document.getElementById('form-registro');

if (formRegistro) {
    const password = document.getElementById('password');
    const confirmar = document.getElementById('confirmar');
    const avisoPassword = document.getElementById('aviso-password');

    formRegistro.addEventListener('submit', function (evento) {
        if (password.value !== confirmar.value) {
            evento.preventDefault();   // el formulario no sale: PHP no recibe nada
            avisoPassword.textContent = 'Las contraseñas no coinciden.';
            avisoPassword.classList.remove('oculto');
            confirmar.focus();
        }
    });

    // En cuanto las contraseñas coinciden, el aviso desaparece
    confirmar.addEventListener('input', function () {
        if (password.value === confirmar.value) {
            avisoPassword.classList.add('oculto');
        }
    });
}
