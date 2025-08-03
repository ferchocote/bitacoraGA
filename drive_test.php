<h1>📤 Cargar Archivo a Google Drive</h1>
<form method="POST" enctype="multipart/form-data">
    <label>📁 Do:</label>
    <input type="text" name="do" required><br>
    
    <label>👤 Rol:</label>
    <input type="text" name="rol" required><br>

    <label>🗂️ Archivo:</label>
    <input type="file" name="archivo" required><br>

    <button type="submit">Subir</button>
</form>
<?php
define('WP_USE_THEMES', false);
require_once('../../wp-load.php');
require_once '../google-drive/vendor/autoload.php';

function registrar_error($mensaje) {
    $log = __DIR__ . '/log_errores.txt';
    file_put_contents($log, "[".date('Y-m-d H:i:s')."] $mensaje\n", FILE_APPEND);
}

try {
    $client = new Google_Client();
    $client->setApplicationName('Acceso Drive desde WordPress');
    $client->setAuthConfig('environment/service_account_cred.json');
    $client->setSubject('subgerencia@galogistic.com'); // Impersonación
    $client->setScopes([
        Google_Service_Drive::DRIVE
    ]);

    $driveService = new Google_Service_Drive($client);

    $sharedDriveId = '0APg0nAAp2LMpUk9PVA'; // ID de unidad compartida

    // Buscar o crear unidad compartida si no existe
    function buscarOCrearCarpeta($driveService, $folderName, $sharedDriveId, $parentId) {
        $query = "name = '$folderName' and mimeType = 'application/vnd.google-apps.folder' and trashed = false";
        
        if($parentId != $sharedDriveId) {
            $query .= "and '$parentId' in parents";
        }
        
        $optParams = [
            'q' => $query,
            'driveId' => $sharedDriveId,
            'corpora' => 'drive',
            'includeItemsFromAllDrives' => true,
            'supportsAllDrives' => true,
            'fields' => 'files(id, name)',
            
        ];
        

        
        $results = $driveService->files->listFiles($optParams);
        
        if (count($results->getFiles()) > 0) {
            return $results->getFiles()[0]->getId();
        }
    
        // Si no existe, crearla
        $folderMetadata = new Google_Service_Drive_DriveFile([
            'name' => $folderName,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parentId],
        ]);
        
        $folder = $driveService->files->create($folderMetadata, [
            'fields' => 'id',
            'supportsAllDrives' => true
        ]);
        
        return $folder->id;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
        $archivo = $_FILES['archivo'];
        $do = trim($_POST['do']);
        $rol = trim($_POST['rol']);
        
        if ($archivo['error'] === UPLOAD_ERR_OK) {
            $archivoTemporal = $archivo['tmp_name'];
            $nombreOriginal = $archivo['name'];
    
            try {
                $parentFolderId = buscarOCrearCarpeta($driveService, $do, $sharedDriveId, $sharedDriveId);
                $childFolderId = buscarOCrearCarpeta($driveService, $rol, $sharedDriveId, $parentFolderId);
    
                $fileMetadata = new Google_Service_Drive_DriveFile([
                    'name' => $nombreOriginal,
                    'parents' => [$childFolderId]
                ]);
    
                $content = file_get_contents($archivoTemporal);
    
                $file = $driveService->files->create($fileMetadata, [
                    'data' => $content,
                    'uploadType' => 'multipart',
                    'fields' => 'id',
                    'supportsAllDrives' => true
                ]);
    
                echo "✅ Archivo subido con éxito. ID: " . $file->id;
            } catch (Exception $e) {
                echo "Error al subir archivo: " . $e->getMessage();
            }
        } else {
            echo "Error al subir archivo: código " . $archivo['error'];
        }
    }

    // Mostrar archivos en Drive
    echo "<h2>📁 Archivos en Google Drive</h2><ul>";
    $results = $driveService->files->listFiles([
        'pageSize' => 200,
        'includeItemsFromAllDrives' => true,
        'supportsAllDrives' => true,
        'fields' => 'files(id, name)'
    ]);
    foreach ($results->getFiles() as $file) {
        $downloadUrl = "descargar.php?id={$file->getId()}";
        echo "<li>{$file->getName()} (ID: {$file->getId()}) <a href='$downloadUrl' target='_blank' download>{$file->getName()}</a></li>";
    }
    echo "</ul>";

} catch (Exception $e) {
    $errorMsg = "❌ Error: " . $e->getMessage();
    echo "<p>$errorMsg</p>";
    registrar_error($errorMsg);
}
?>
