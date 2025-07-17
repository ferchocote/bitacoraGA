<?php
if (!isset($id)) {
    echo '<script>console.warn("ID no definido en PHP.");</script>';
    return;
}
?>

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
                            <td>${item.Tipo || 'N/A'}</td>
                            <td>${item.Descripcion || 'Sin descripción'}</td>
                            <td>${item.Usuario || '---'}</td>
                            <td>${item.Correo || '---'}</td>
                            <td>${item.FechaCreacion ? new Date(item.FechaCreacion).toLocaleDateString() : '---'}</td>
                            <td><button class="btn">Ver detalle</button></td>
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
</script>
