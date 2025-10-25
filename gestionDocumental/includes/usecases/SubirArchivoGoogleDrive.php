<?php
// Caso de uso: Subir archivo a Google Drive y devolver el ID
require_once __DIR__ . '/../../../google-drive/vendor/autoload.php';



class SubirArchivoGoogleDrive {
    private $sharedDriveId = '0APg0nAAp2LMpUk9PVA'; // Cambia por tu ID real
    private $credPath = __DIR__ . '/../../../bitacoras/environment/service_account_cred.json';
    private $impersonate = 'subgerencia@galogistic.com'; // Cambia por tu usuario

    private function buscarOCrearCarpeta($driveService, $folderName, $parentId) {
        $query = sprintf(
            "name='%s' and mimeType='application/vnd.google-apps.folder' and '%s' in parents and trashed=false",
            addslashes($folderName),
            $parentId
        );
        $optParams = [
            'q' => $query,
            'fields' => 'files(id, name)',
            'supportsAllDrives' => true,
            'includeItemsFromAllDrives' => true,
            'corpora' => 'drive',
            'driveId' => $this->sharedDriveId
        ];
        $results = $driveService->files->listFiles($optParams);
        if (count($results->getFiles()) > 0) {
            return $results->getFiles()[0]->getId();
        }
        $folderMetadata = new Google_Service_Drive_DriveFile([
            'name' => $folderName,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parentId]
        ]);
        $folder = $driveService->files->create($folderMetadata, [
            'fields' => 'id',
            'supportsAllDrives' => true
        ]);
        return $folder->id;
    }

    /**
     * Sube un archivo a Google Drive dentro de la carpeta del importador, mes y tipo de gestión
     * @param string $archivoTmp
     * @param string $nombreArchivo
     * @param string $nombreImportador
     * @param string $tipoGestion
     * @param string $fechaDocumento (formato YYYY-MM-DD o compatible)
     * @return string ID del archivo en Drive
     */
    public function subir($archivoTmp, $nombreArchivo, $nombreImportador, $tipoGestion, $fechaDocumento) {
        $client = new Google_Client();
        $client->setApplicationName('Acceso Drive desde WordPress');
        $client->setAuthConfig($this->credPath);
        $client->setSubject($this->impersonate);
        $client->setScopes([Google_Service_Drive::DRIVE]);
        $driveService = new Google_Service_Drive($client);

       
        // Crear carpeta de mes (YYYY-MM) usando la fecha
        $mesCarpeta = '';
        if (!empty($fechaDocumento)) {
            $ts = strtotime($fechaDocumento);
            $mesCarpeta = $ts ? date('Y-m', $ts) : date('Y-m');
        } else {
            $mesCarpeta = date('Y-m');
        }
        $importadorFolderId = $this->buscarOCrearCarpeta($driveService, $nombreImportador, $this->sharedDriveId);
        $mesFolderId = $this->buscarOCrearCarpeta($driveService, $mesCarpeta, $importadorFolderId);
        $tipoGestionFolderId = $this->buscarOCrearCarpeta($driveService, $tipoGestion, $mesFolderId);
        
        error_log('SubirArchivoGoogleDrive: importadorFolderId=' . $importadorFolderId);
        error_log('SubirArchivoGoogleDrive: mesFolderId=' . $mesFolderId);
        error_log('SubirArchivoGoogleDrive: tipoGestionFolderId=' . $tipoGestionFolderId);

        $fileMetadata = new Google_Service_Drive_DriveFile([
            'name' => $nombreArchivo,
            'parents' => [$tipoGestionFolderId]
        ]);
        $content = file_get_contents($archivoTmp);
        error_log('SubirArchivoGoogleDrive: nombreArchivo=' . $nombreArchivo . ', archivoTmp=' . $archivoTmp . ', size=' . strlen($content));
        try {
            $file = $driveService->files->create($fileMetadata, [
                'data' => $content,
                'uploadType' => 'multipart',
                'fields' => 'id',
                'supportsAllDrives' => true
            ]);
            error_log('SubirArchivoGoogleDrive: file->id=' . $file->id);
            return $file->id;
        } catch (Exception $e) {
            error_log('SubirArchivoGoogleDrive: ERROR ' . $e->getMessage());
            return null;
        }
    }
}
