<?php
// Caso de uso: Descargar archivo de Google Drive por ID
require_once __DIR__ . '/../../../google-drive/vendor/autoload.php';

use Google_Client;
use Google_Service_Drive;
use Google_Service_Exception;

class DescargarArchivoGoogleDrive {
    private $credPath = __DIR__ . '/../../../bitacoras/environment/service_account_cred.json';
    private $impersonate = 'subgerencia@galogistic.com';

    public function descargar($fileId) {
        $client = new Google_Client();
        $client->setApplicationName('Acceso Drive desde WordPress');
        $client->setAuthConfig($this->credPath);
        $client->setSubject($this->impersonate);
        $client->setScopes([Google_Service_Drive::DRIVE]);
        $driveService = new Google_Service_Drive($client);

        try {
            $file = $driveService->files->get($fileId, [
                'fields' => 'name',
                'supportsAllDrives' => true
            ]);
            $fileName = $file->getName();

            $response = $driveService->files->get($fileId, [
                'alt' => 'media',
                'supportsAllDrives' => true
            ]);

            // Retornar nombre y contenido para que el controlador lo use
            return [
                'name' => $fileName,
                'content' => $response->getBody()->getContents()
            ];
        } catch (Google_Service_Exception $e) {
            return [
                'error' => $e->getCode() == 404 ? 'Archivo no encontrado o sin permisos.' : 'Error: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Error: ' . $e->getMessage()
            ];
        }
    }
}
