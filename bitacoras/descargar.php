<?php
require_once '../google-drive/vendor/autoload.php';

if (!isset($_GET['id'])) {
    die('ID de archivo no especificado.');
}

$fileId = $_GET['id'];

$client = new Google_Client();
$client->setApplicationName('Acceso Drive desde WordPress');
$client->setAuthConfig('environment/service_account_cred.json');
$client->setSubject('subgerencia@galogistic.com');
$client->setScopes([
    Google_Service_Drive::DRIVE
]);

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

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    echo $response->getBody()->getContents();
} catch (Google_Service_Exception $e) {
    if ($e->getCode() == 404) {
        echo "Archivo no encontrado o sin permisos.";
    } else {
        echo "Error al descargar archivo: " . $e->getMessage();
    }
} catch (Exception $e) {
    echo "Error al descargar archivo: " . $e->getMessage();
}
?>