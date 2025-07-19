<?php
if (!isset($id)) {
    echo '<script>console.warn("ID no definido en PHP.");</script>';
    return;
}
?>

<!-- POPUP DETALLE / EDITAR CLIENTE -->
<input type="checkbox" id="popup-toggle">
<div class="overlay">
    <?php include __DIR__ . '/entradas/transporte.php'; ?>
    <div id="loader-overlay">
        <div class="spinner"></div>
    </div>
</div>

<script>
    function cargarEntradas(tipoTab, contenedorId, idProceso = <?= json_encode($id) ?>) {
        const acciones = {
            CTB: 'get_entradas_contabilidad',
            GRO: 'get_entradas_giros',
            TRS: 'get_entradas_transporte'
        };

        const action = acciones[tipoTab];
        if (!action) {
            document.getElementById(contenedorId).innerHTML = '<p>Tipo de pestaña desconocido.</p>';
            return;
        }

        const ajaxUrl = '/wp-content/bitacoras/plugins/cliente/entradas-ajax.php';
        const contenedor = document.getElementById(contenedorId);
        contenedor.innerHTML = '<p>Cargando entradas...</p>';

        fetch(`${ajaxUrl}?action=${action}&id_proceso=${idProceso}`)
            .then(res => res.json())
            .then(data => {
                contenedor.innerHTML = '';

                if (!Array.isArray(data) || data.length === 0) {
                    contenedor.innerHTML = '<p>No hay entradas disponibles.</p>';
                    return;
                }

                const table = document.createElement('table');
                table.innerHTML = `
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th>Usuario</th>
                        <th>Correo</th>
                        <th>Fecha</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    ${data.map(item => `
                        <tr>
                            <td>${item.TEDescripcion || 'N/A'}</td>
                            <td>${item.Descripcion || 'Sin descripción'}</td>
                            <td>${item.user_nicename || '---'}</td>
                            <td>${item.user_email || '---'}</td>
                            <td>${item.FechaCreacion ? new Date(item.FechaCreacion).toLocaleDateString() : '---'}</td>
                            <td>
                            <a href="javascript:void(0);" class="detalle-cliente"  style="color: #2980b9; text-decoration: none">
                                <svg class="w-[18px] h-[18px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" clip-rule="evenodd" />
                                </svg>
                            </a>
                            <a href="javascript:void(0);" class="modificar-cliente"  style="color: #d7ab00; text-decoration: none">
                                <svg class="w-[18px] h-[18px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M14 4.182A4.136 4.136 0 0 1 16.9 3c1.087 0 2.13.425 2.899 1.182A4.01 4.01 0 0 1 21 7.037c0 1.068-.43 2.092-1.194 2.849L18.5 11.214l-5.8-5.71 1.287-1.31.012-.012Zm-2.717 2.763L6.186 12.13l2.175 2.141 5.063-5.218-2.141-2.108Zm-6.25 6.886-1.98 5.849a.992.992 0 0 0 .245 1.026 1.03 1.03 0 0 0 1.043.242L10.282 19l-5.25-5.168Zm6.954 4.01 5.096-5.186-2.218-2.183-5.063 5.218 2.185 2.15Z" clip-rule="evenodd" />
                                </svg>
                            </a>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            `;
                contenedor.appendChild(table);
            })
            .catch(err => {
                console.error("Error al cargar datos:", err);
                contenedor.innerHTML = '<p>Error al cargar datos.</p>';
            });
    }
    // document.addEventListener('click', function(e) {
    //     const detalle = e.target.closest('.detalle-cliente');
    //     const editar = e.target.closest('.modificar-cliente');

    //     if (detalle || editar) {
    //         // Mostrar el popup
    //         document.getElementById('popup-toggle').checked = true;

    //         const modo = detalle ? 'detalle' : 'editar';
    //         document.getElementById('popup-title').textContent = modo === 'detalle' ? 'Detalle Transporte' : 'Editar Transporte';

    //         // Aquí puedes cargar los datos al formulario según el item seleccionado (ej. por data-id)
    //         // Por ejemplo, si asignas data-id al <a>: <a class="detalle-cliente" data-id="123">

    //         // document.getElementById('cliente-id').value = ID del cliente

    //         // Mostrar u ocultar botón guardar
    //         document.getElementById('btn-guardar').style.display = modo === 'editar' ? 'inline-block' : 'none';
    //     }
    // });
</script>