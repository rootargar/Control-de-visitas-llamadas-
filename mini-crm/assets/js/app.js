/**
 * Mini CRM - JavaScript Principal
 */

// Toggle menu móvil
function toggleMenu() {
    const menu = document.getElementById('navbarMenu');
    menu.classList.toggle('active');
}

// Cerrar menu al hacer click fuera
document.addEventListener('click', function(e) {
    const menu = document.getElementById('navbarMenu');
    const toggle = document.querySelector('.navbar-toggle');

    if (menu && toggle && !menu.contains(e.target) && !toggle.contains(e.target)) {
        menu.classList.remove('active');
    }
});

// Confirmar eliminación
function confirmarEliminar(mensaje = '¿Está seguro de eliminar este registro?') {
    return confirm(mensaje);
}

// Formatear fecha DD/MM/YYYY
function formatearFecha(fecha) {
    if (!fecha) return '';
    const d = new Date(fecha);
    const dia = String(d.getDate()).padStart(2, '0');
    const mes = String(d.getMonth() + 1).padStart(2, '0');
    const anio = d.getFullYear();
    return `${dia}/${mes}/${anio}`;
}

// Formatear fecha y hora
function formatearFechaHora(fecha) {
    if (!fecha) return '';
    const d = new Date(fecha);
    const dia = String(d.getDate()).padStart(2, '0');
    const mes = String(d.getMonth() + 1).padStart(2, '0');
    const anio = d.getFullYear();
    const hora = String(d.getHours()).padStart(2, '0');
    const minutos = String(d.getMinutes()).padStart(2, '0');
    return `${dia}/${mes}/${anio} ${hora}:${minutos}`;
}

// Validar formulario
function validarFormulario(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;

    const inputs = form.querySelectorAll('[required]');
    let valido = true;

    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = 'var(--color-danger)';
            valido = false;
        } else {
            input.style.borderColor = '';
        }
    });

    if (!valido) {
        alert('Por favor complete todos los campos obligatorios.');
    }

    return valido;
}

// Mostrar/ocultar modal
function mostrarModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
    }
}

function cerrarModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
    }
}

// Cerrar modal al hacer click en el fondo
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('active');
    }
});

// Cerrar modal con tecla Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('.modal.active');
        modals.forEach(modal => modal.classList.remove('active'));
    }
});

// Filtrar tabla
function filtrarTabla(inputId, tablaId) {
    const input = document.getElementById(inputId);
    const tabla = document.getElementById(tablaId);

    if (!input || !tabla) return;

    const filtro = input.value.toUpperCase();
    const filas = tabla.getElementsByTagName('tr');

    for (let i = 1; i < filas.length; i++) {
        const celdas = filas[i].getElementsByTagName('td');
        let visible = false;

        for (let j = 0; j < celdas.length; j++) {
            const texto = celdas[j].textContent || celdas[j].innerText;
            if (texto.toUpperCase().indexOf(filtro) > -1) {
                visible = true;
                break;
            }
        }

        filas[i].style.display = visible ? '' : 'none';
    }
}

// Limpiar formulario
function limpiarFormulario(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.reset();
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.style.borderColor = '';
        });
    }
}

// Previsualizar archivo
function previsualizarArchivo(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);

    if (!input || !preview) return;

    input.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const fileInfo = `
                <strong>Archivo seleccionado:</strong><br>
                Nombre: ${file.name}<br>
                Tamaño: ${(file.size / 1024).toFixed(2)} KB<br>
                Tipo: ${file.type}
            `;
            preview.innerHTML = fileInfo;
            preview.style.display = 'block';
        } else {
            preview.style.display = 'none';
        }
    });
}

// Auto-ocultar alertas después de 5 segundos
document.addEventListener('DOMContentLoaded', function() {
    const alertas = document.querySelectorAll('.alert');
    alertas.forEach(alerta => {
        setTimeout(() => {
            alerta.style.opacity = '0';
            alerta.style.transition = 'opacity 0.5s';
            setTimeout(() => {
                alerta.style.display = 'none';
            }, 500);
        }, 5000);
    });
});

// Exportar tabla a CSV
function exportarTablaCSV(tablaId, nombreArchivo = 'export.csv') {
    const tabla = document.getElementById(tablaId);
    if (!tabla) return;

    let csv = [];
    const filas = tabla.querySelectorAll('tr');

    filas.forEach(fila => {
        const celdas = fila.querySelectorAll('th, td');
        const filaCsv = [];

        celdas.forEach(celda => {
            // Ignorar columnas de acciones
            if (!celda.classList.contains('table-actions')) {
                let texto = celda.innerText.replace(/"/g, '""');
                filaCsv.push(`"${texto}"`);
            }
        });

        csv.push(filaCsv.join(','));
    });

    const csvContent = csv.join('\n');
    const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');

    if (navigator.msSaveBlob) {
        navigator.msSaveBlob(blob, nombreArchivo);
    } else {
        link.href = URL.createObjectURL(blob);
        link.download = nombreArchivo;
        link.click();
    }
}

// Imprimir contenido
function imprimirContenido(elementoId) {
    const elemento = document.getElementById(elementoId);
    if (!elemento) return;

    const ventana = window.open('', '_blank');
    ventana.document.write(`
        <html>
        <head>
            <title>Imprimir</title>
            <style>
                body { font-family: Arial, sans-serif; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                @media print {
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            ${elemento.innerHTML}
        </body>
        </html>
    `);
    ventana.document.close();
    ventana.print();
}

// Formatear números
function formatearNumero(numero, decimales = 0) {
    return new Intl.NumberFormat('es-MX', {
        minimumFractionDigits: decimales,
        maximumFractionDigits: decimales
    }).format(numero);
}

// Debounce para búsqueda
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Validar email
function validarEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Validar RFC mexicano (básico)
function validarRFC(rfc) {
    const re = /^[A-ZÑ&]{3,4}\d{6}[A-Z\d]{3}$/;
    return re.test(rfc.toUpperCase());
}

// Calcular días entre fechas
function diasEntreFechas(fecha1, fecha2) {
    const d1 = new Date(fecha1);
    const d2 = new Date(fecha2);
    const diffTime = Math.abs(d2 - d1);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
}

console.log('Mini CRM - Sistema cargado correctamente');
