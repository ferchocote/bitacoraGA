<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap gestion-documental-form">
    <h1 class="wp-heading-inline">Nueva gestión documental</h1>
    <a href="<?php echo esc_url(add_query_arg(['view' => 'gestion_documental'], remove_query_arg(['action']))); ?>" class="page-title-action">Volver a la lista</a>

    <?php if (!empty($mensaje)) : ?>
        <div class="notice notice-<?php echo !empty($exito) ? 'success' : 'error'; ?>" style="margin-top:1em;">
            <p><?php echo wp_kses_post($mensaje); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" style="margin-top:1em;">
        <?php wp_nonce_field('crear_gestion_documental', 'gestion_documental_nonce'); ?>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><label for="importador">Importador</label></th>
                    <td>
                        <select name="importador" id="importador" required class="regular-text">
                            <option value="">Seleccione un importador</option>
                            <?php foreach ($importadores as $importador) : ?>
                                <option value="<?php echo esc_attr($importador->ID); ?>" <?php selected(!empty($_POST['importador']) && (int) $_POST['importador'] === (int) $importador->ID); ?>>
                                    <?php echo esc_html($importador->RazonSocial); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="observaciones">Observaciones</label></th>
                    <td>
                        <textarea name="observaciones" id="observaciones" rows="4" class="large-text"><?php echo isset($_POST['observaciones']) ? esc_textarea(wp_unslash($_POST['observaciones'])) : ''; ?></textarea>
                        <p class="description">Campo opcional para registrar comentarios internos sobre la gestión.</p>
                    </td>
                </tr>
            </tbody>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary">Crear gestión</button>
        </p>
    </form>
</div>
