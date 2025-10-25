<?php
if (!class_exists('SubirArchivoGoogleDrive')) {
    class SubirArchivoGoogleDrive
    {
        /** @var Google_Service_Drive */
        private $driveService;
        private $sharedDriveId;
        private $impersonateUser;

        public function __construct($sharedDriveId = null, $impersonateUser = null)
        {
            $this->sharedDriveId = $sharedDriveId ?: getenv('GOOGLE_DRIVE_SHARED_ID');
            $this->impersonateUser = $impersonateUser ?: getenv('GOOGLE_DRIVE_IMPERSONATE');
        }

        private function cargarAutoload()
        {
            $rutas = [
                dirname(__DIR__, 3) . '/google-drive/vendor/autoload.php',
                dirname(__DIR__, 2) . '/../google-drive/vendor/autoload.php',
                dirname(__DIR__, 4) . '/google-drive/vendor/autoload.php',
            ];

            foreach ($rutas as $ruta) {
                if (file_exists($ruta)) {
                    require_once $ruta;
                    return;
                }
            }
        }

        private function inicializarCliente()
        {
            if ($this->driveService) {
                return $this->driveService;
            }

            if (!class_exists('Google_Client')) {
                $this->cargarAutoload();
            }

            if (!class_exists('Google_Client')) {
                throw new RuntimeException('No se encontró Google_Client. Instala google/apiclient.');
            }

            $credenciales = $this->obtenerRutaCredenciales();
            if (!file_exists($credenciales)) {
                throw new RuntimeException('No se encontraron credenciales para Google Drive.');
            }

            $cliente = new Google_Client();
            $cliente->setApplicationName('Gestión Documental GA');
            $cliente->setAuthConfig($credenciales);

            if ($this->impersonateUser) {
                $cliente->setSubject($this->impersonateUser);
            }

            $cliente->setScopes([
                Google_Service_Drive::DRIVE,
            ]);

            $this->driveService = new Google_Service_Drive($cliente);

            if (!$this->sharedDriveId) {
                $this->sharedDriveId = getenv('GOOGLE_DRIVE_DEFAULT_DRIVE') ?: '0APg0nAAp2LMpUk9PVA';
            }

            return $this->driveService;
        }

        private function obtenerRutaCredenciales()
        {
            $posiblesRutas = [];

            if (defined('WP_CONTENT_DIR')) {
                $posiblesRutas[] = WP_CONTENT_DIR . '/environment/service_account_cred.json';
            }

            $posiblesRutas[] = dirname(__DIR__, 2) . '/environment/service_account_cred.json';
            $posiblesRutas[] = dirname(__DIR__, 3) . '/environment/service_account_cred.json';
            $posiblesRutas[] = dirname(__DIR__, 4) . '/environment/service_account_cred.json';

            foreach ($posiblesRutas as $ruta) {
                if (is_string($ruta) && file_exists($ruta)) {
                    return $ruta;
                }
            }

            return end($posiblesRutas);
        }

        private function normalizarNombre($valor)
        {
            if (function_exists('sanitize_text_field')) {
                $valor = sanitize_text_field($valor);
            }

            $valor = trim($valor);
            $valor = preg_replace('/[\\\\\/:*?"<>|]/', '-', $valor);
            $valor = preg_replace('/\s+/', ' ', $valor);

            return $valor !== '' ? $valor : 'Sin-Nombre';
        }

        private function escaparParaConsulta($valor)
        {
            return str_replace("'", "\'", $valor);
        }

        private function buscarOCrearCarpeta($drive, $nombre, $parentId)
        {
            $sharedDriveId = $this->sharedDriveId;
            $nombreNormalizado = $this->normalizarNombre($nombre);
            $nombreConsulta = $this->escaparParaConsulta($nombreNormalizado);

            $query = "name = '{$nombreConsulta}' and mimeType = 'application/vnd.google-apps.folder' and trashed = false";

            if ($parentId && $parentId !== $sharedDriveId) {
                $query .= " and '{$parentId}' in parents";
            }

            $parametros = [
                'q' => $query,
                'driveId' => $sharedDriveId,
                'corpora' => 'drive',
                'includeItemsFromAllDrives' => true,
                'supportsAllDrives' => true,
                'fields' => 'files(id, name)',
            ];

            $resultado = $drive->files->listFiles($parametros);
            if ($resultado->getFiles()) {
                return $resultado->getFiles()[0]->getId();
            }

            $metadata = new Google_Service_Drive_DriveFile([
                'name' => $nombreNormalizado,
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents' => $parentId ? [$parentId] : null,
            ]);

            $folder = $drive->files->create($metadata, [
                'fields' => 'id',
                'supportsAllDrives' => true,
            ]);

            return $folder->id;
        }

        public function subir($rutaTemporal, $nombreArchivo, $nombreImportador, $tipoGestionNombre, $fechaDocumento)
        {
            $drive = $this->inicializarCliente();

            if (!$this->sharedDriveId) {
                throw new RuntimeException('No se configuró el ID de la unidad compartida.');
            }

            $carpetaImportador = $this->buscarOCrearCarpeta($drive, $nombreImportador, $this->sharedDriveId);
            $carpetaTipo = $this->buscarOCrearCarpeta($drive, $tipoGestionNombre ?: 'General', $carpetaImportador);

            $nombreFecha = $fechaDocumento ? date('Y-m', strtotime($fechaDocumento)) : date('Y-m');
            $carpetaFecha = $this->buscarOCrearCarpeta($drive, $nombreFecha, $carpetaTipo);

            $metadata = new Google_Service_Drive_DriveFile([
                'name' => $nombreArchivo,
                'parents' => [$carpetaFecha],
            ]);

            $contenido = file_get_contents($rutaTemporal);

            $archivo = $drive->files->create($metadata, [
                'data' => $contenido,
                'uploadType' => 'multipart',
                'fields' => 'id, webViewLink, webContentLink',
                'supportsAllDrives' => true,
            ]);

            return $archivo->getWebViewLink() ?: sprintf('https://drive.google.com/file/d/%s/view', $archivo->id);
        }
    }
}
