// =====================================================================
// js/app.js — JavaScript vanilla de RutaNeta (versión final)
// Lo cargan login.php, registro.php y las páginas internas al final del
// <body>: cuando este archivo corre, el HTML ya existe.
// Cada función busca sus elementos; si la página no los tiene, no hace nada.
// Así un solo archivo sirve a todas las páginas.
// =====================================================================
'use strict';

// Login y registro: mostrar u ocultar la contraseña.
// El navegador solo revela el texto si el input cambia a type="text";
// la clase "activo" cambia el aspecto del botón desde css/estilos.css.
function activarVerPassword() {
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
}

// Registro: JS revisa que las contraseñas coincidan antes de que PHP reciba el formulario.
function validarRegistro() {
    const formulario = document.getElementById('form-registro');
    if (!formulario) {
        return;
    }

    const password = document.getElementById('password');
    const confirmar = document.getElementById('confirmar');
    const aviso = document.getElementById('aviso-password');

    formulario.addEventListener('submit', function (evento) {
        if (password.value !== confirmar.value) {
            evento.preventDefault();   // el formulario no sale: PHP no recibe nada
            aviso.textContent = 'Las contraseñas no coinciden.';
            aviso.classList.remove('oculto');
            confirmar.focus();
        }
    });

    // En cuanto las contraseñas coinciden, el aviso desaparece
    confirmar.addEventListener('input', function () {
        if (password.value === confirmar.value) {
            aviso.classList.add('oculto');
        }
    });
}

// Ingresos y gastos: confirmar antes de enviar el POST de eliminar.
function confirmarEliminar() {
    document.querySelectorAll('.form-eliminar').forEach(function (formulario) {
        formulario.addEventListener('submit', function (evento) {
            if (!confirm('¿Eliminar este registro? No se puede deshacer.')) {
                evento.preventDefault();
            }
        });
    });
}

// Tablas: un clic marca o desmarca la fila. El hover lo hace CSS.
function resaltarFilas() {
    document.querySelectorAll('.tabla-seleccionable tbody tr').forEach(function (fila) {
        fila.addEventListener('click', function (evento) {
            // Un clic en el botón Eliminar no marca la fila
            if (evento.target.closest('button')) {
                return;
            }
            fila.classList.toggle('seleccionada');
        });
    });
}

// Historial: PHP deja el neto en data-neto y JS pinta verde o rojo al cargar la página.
function colorearNeto() {
    document.querySelectorAll('[data-neto]').forEach(function (elemento) {
        const neto = parseFloat(elemento.dataset.neto);

        elemento.classList.toggle('positivo', neto > 0);
        elemento.classList.toggle('negativo', neto < 0);
    });
}

// Gastos: filtrar la tabla por categoría sin recargar la página.
function filtrarGastos() {
    const filtro = document.getElementById('filtro-categoria');
    if (!filtro) {
        return;
    }

    const filas = document.querySelectorAll('#tabla-gastos tbody tr');
    const sinResultados = document.getElementById('sin-resultados');

    filtro.addEventListener('change', function () {
        let visibles = 0;

        filas.forEach(function (fila) {
            const coincide = filtro.value === 'todas' || fila.dataset.categoria === filtro.value;
            fila.classList.toggle('oculto', !coincide);
            if (coincide) {
                visibles++;
            }
        });

        // Si ninguna fila coincide, aparece el aviso "sin resultados"
        sinResultados.classList.toggle('oculto', visibles > 0);
    });
}

// Arranque: se activan todas las funciones; cada una revisa si su página la necesita.
activarVerPassword();
validarRegistro();
confirmarEliminar();
resaltarFilas();
colorearNeto();
filtrarGastos();
