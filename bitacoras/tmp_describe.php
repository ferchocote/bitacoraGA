<?php
require_once __DIR__ . '/../../wp-config.php';
global $wpdb;
$cols = $wpdb->get_results('DESCRIBE bc_proceso_estado_historial');
foreach ($cols as $c) {
    echo "{$c->Field} {$c->Type} NULL:{$c->Null} Default:{$c->Default} Key:{$c->Key}\n";
}
