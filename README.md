# bitacoraGA

## Instrucciones para configurar integraciones
### Integración con GA Logistic
La página de login que se integra en la página de ga-logistic.com se incluye en este repo, pero debe ser copiado en el sistema de archivos de ga-logistic.com. Este se creó como un plugin de WP, por lo que si se requiere subir un cambio, hay que:

1. Copiar la carpeta de cls-integration (ga/cls-integration en este repo, pero excluir ga) y su contenido dentro del sistema de archivos de la página de GA, dentro de wp-content/wp-plugins.
2. Hay que verificar que en el panel de adminsitrador de Wordpress se liste el plugin cls-integration (Si no se lista es porque hay un error en el contenido de la carpeta)
3. Si está listado el plugin, debe activarse e inmediatamente los cambios hechos se deben ver reflejados.

### Integración con Google Workspace
Con el fin de cargar automáticamente los archivos subidos en las bitácoras de GA en el drive del grupo de trabajo se creó una integración con Workspace. Para poder probarla de manera local, es necesario descargar los archivos del la librería "Google APIs Client Library for PHP" compilados (Normalmente se compilan con composer, pero en este caso hay que conseguir el compilado de la librería), actualmente se puede hacer con este link:

- https://github.com/abcastillo1/api-google-drive/tree/master/api-drive

Se debe subir la carpeta compilada en wp-content/google-drive.

Una vez subida la carpeta, se debe incluir el JSON con las credenciales del service account de google, el archivo se llama "service_account_cred.json", y debe incluirse en environment a nivel root de este repo.