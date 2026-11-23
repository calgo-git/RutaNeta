// =====================================================================
// js/app.js — JavaScript vanilla de RutaNeta
// Lo cargan login.php, registro.php y las páginas internas al final del
// <body>: cuando este archivo corre, el HTML ya existe. Cada bloque busca
// sus elementos y, si la página no los tiene, no hace nada.
// Etapa 3: mostrar contraseña y validar el registro.
// Etapa 4: confirmar antes de eliminar y resaltar filas.
// Etapa 5: color del neto y filtro de gastos.
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

// ===== Etapa 4 · Tablas y eliminación =====

// Confirmar antes de eliminar: si el conductor cancela, el POST no sale
document.querySelectorAll('.form-eliminar').forEach(function (formulario) {
    formulario.addEventListener('submit', function (evento) {
        if (!confirm('¿Eliminar este registro? No se puede deshacer.')) {
            evento.preventDefault();
        }
    });
});

// Clic en una fila: se marca o desmarca con la clase "seleccionada".
// El hover lo hace CSS; JS solo responde al clic.
document.querySelectorAll('.tabla-seleccionable tbody tr').forEach(function (fila) {
    fila.addEventListener('click', function (evento) {
        // Un clic en el botón Eliminar no marca la fila
        if (evento.target.closest('button')) {
            return;
        }
        fila.classList.toggle('seleccionada');
    });
});

// ===== Etapa 5 · Historial y gastos =====

// Historial: PHP deja el neto en data-neto y JS pinta verde o rojo al cargar la página
document.querySelectorAll('[data-neto]').forEach(function (elemento) {
    const neto = parseFloat(elemento.dataset.neto);

    elemento.classList.toggle('positivo', neto > 0);
    elemento.classList.toggle('negativo', neto < 0);
});

// Gastos: filtrar la tabla por categoría sin recargar la página
const filtroCategoria = document.getElementById('filtro-categoria');

if (filtroCategoria) {
    const filasGastos = document.querySelectorAll('#tabla-gastos tbody tr');
    const sinResultados = document.getElementById('sin-resultados');

    filtroCategoria.addEventListener('change', function () {
        let visibles = 0;

        filasGastos.forEach(function (fila) {
            const coincide = filtroCategoria.value === 'todas' || fila.dataset.categoria === filtroCategoria.value;
            fila.classList.toggle('oculto', !coincide);
            if (coincide) {
                visibles++;
            }
        });

        // Si ninguna fila coincide, aparece el aviso "sin resultados"
        sinResultados.classList.toggle('oculto', visibles > 0);
    });
}
