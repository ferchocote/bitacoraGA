<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Nueva Gestión Documental</title>
    <link rel="stylesheet" href="../bitacoras/styles/style.css">
</head>

<body>
    <div class="form-container">
        <h1>Nueva Gestión Documental</h1>
        <?php if (!empty($mensaje)): ?>
            <div class="<?= !empty($exito) ? 'success' : 'error' ?>"><?= esc_html($mensaje) ?></div>
        <?php endif; ?>
        <form method="post" class="form-grid" action="" id="formNuevaGestion">
            <div class="form-group">
                <label for="importador">Importador:</label>
                <select name="importador" id="importador" required>
                    <option value="">Seleccione un importador</option>
                    <?php foreach ($importadores as $imp): ?>
                        <option value="<?= esc_attr($imp->ID) ?>"><?= esc_html($imp->RazonSocial) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-buttons">
                <a href="?view=gestion_documental" class="btn close">Cerrar</a>
                <button type="submit" class="btn" id="btnCrearGestion">Crear Gestión</button>
            </div>

        </form>
        <script>
       
        document.getElementById('formNuevaGestion').addEventListener('submit', function() {
            showLoader();
        });
        window.addEventListener('DOMContentLoaded', function() {
            hideLoader(); // Por si queda abierto por error
        });
        </script>
    </div>
</body>

</html>