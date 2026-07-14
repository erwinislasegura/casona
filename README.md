# casona
## PWA Fiesta Ochentera

La aplicación incluye configuración PWA instalable para Android, Chrome, Edge, escritorio y Safari/iOS dentro de las capacidades de cada navegador.

### Configurar la PWA

- El manifest está en `public/manifest.webmanifest` y define `name`, `short_name`, `start_url`, `scope`, `display: standalone`, orientación adaptable, colores de tema y accesos directos a `/admin/scanner` y `/admin/reservas?status=pending`.
- El Service Worker está en `public/service-worker.js`. Regístralo desde las vistas con `/assets/js/service-worker-register.js`.
- Las páginas deben incluir `viewport-fit=cover`, `theme-color`, `apple-mobile-web-app-capable`, `apple-mobile-web-app-title`, link al manifest y Apple Touch Icon.

### Íconos de la PWA

La PWA no agrega binarios nuevos para evitar errores de revisión y mantener el repositorio liviano. El manifest reutiliza el logo existente de Ciclón Producciones (`assets/logo-ciclon.jpeg`) como ícono principal, maskable y Apple Touch Icon. Si más adelante se requieren tamaños físicos específicos, deben generarse fuera del flujo de código y reemplazarse de forma controlada por assets aprobados.

### Probar el Service Worker

1. Servir el sitio por HTTPS o `localhost`.
2. Abrir DevTools > Application > Service Workers.
3. Confirmar que `/service-worker.js` queda activo.
4. Activar modo offline y recargar una página pública para ver `public/offline.html`.
5. Verificar que rutas sensibles de administración y métodos POST/PUT/PATCH/DELETE no se cachean.

### Limpiar caché

- En Chrome/Edge: DevTools > Application > Storage > Clear site data.
- También puedes desregistrar el Service Worker desde DevTools > Application > Service Workers > Unregister.
- En iOS, elimina el acceso de la pantalla de inicio y limpia datos del sitio desde Safari.

### Actualizar versión

- Cambia `PWA_VERSION` en `public/service-worker.js` cuando publiques nuevos recursos estáticos.
- El script `public/assets/js/app-update.js` muestra “Hay una nueva versión disponible” y permite activar el nuevo Service Worker con recarga controlada.
- El Service Worker elimina cachés antiguos durante `activate`.

### Requisito de HTTPS

La instalación PWA, el Service Worker, cámara, wake lock y varias APIs de dispositivos requieren HTTPS en producción. `localhost` funciona para desarrollo.

### Instalar en Android

1. Abrir el sitio en Chrome o Edge.
2. Esperar el botón “Instalar aplicación” o usar el menú del navegador.
3. Confirmar instalación. La app abrirá en modo standalone.

### Instalar en iPhone/iPad

Safari no expone `beforeinstallprompt`. Abre el menú Compartir y selecciona “Agregar a pantalla de inicio”. La interfaz muestra esta instrucción solo en iOS/iPadOS.

### Instalar en computador

Abre el sitio en Chrome o Edge y usa el ícono de instalación de la barra de direcciones o el botón “Instalar aplicación” cuando esté disponible.

### Solución de problemas de cámara

- Verifica HTTPS y permisos de cámara del navegador.
- Usa la cámara trasera por defecto cuando el dispositivo la exponga.
- Si la linterna no aparece, el navegador o el hardware no soportan `torch`.
- Si la PWA está offline, el escáner debe bloquear validaciones y mostrar “Sin conexión. No es posible validar esta entrada”.

### Solución de problemas de caché

- Incrementa `PWA_VERSION` después de cambios en recursos estáticos.
- Usa “Actualizar ahora” en el aviso de nueva versión.
- Limpia datos del sitio si el navegador conserva una versión antigua.
- No cachees endpoints privados, tokens, PDFs, comprobantes, sesiones ni respuestas administrativas.

## Panel administrativo Bootstrap + MySQL

El login administrativo está construido con Bootstrap 5.3 y una capa visual compacta propia en `public/assets/css/login.css`. Las vistas de acceso, recuperación y reinicio de contraseña están en `app/Views/auth/`.

### Base de datos

El esquema MySQL está en `database/schema.sql` e incluye:

- `admin_users`: usuarios administrativos, roles, estado activo, bloqueo temporal e historial de último acceso.
- `admin_login_attempts`: auditoría de accesos exitosos y fallidos por correo e IP.
- `admin_remember_tokens`: tokens persistentes seguros para “Recordarme”, guardados como hash.
- `admin_password_resets`: tokens de recuperación de contraseña, también hasheados.
- `admin_sessions`: sesiones administrativas revocables con expiración.
- `reservas` y `entradas`: tablas base para solicitudes y validación QR.

Para crear la base:

```bash
mysql -u USER -p < database/schema.sql
```

Configura la conexión PDO usando variables de entorno equivalentes a:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fiesta_ochentera
DB_USERNAME=fiesta_user
DB_PASSWORD=secret
```

`app/Models/AdminAuthRepository.php` contiene operaciones PDO mínimas para buscar usuarios activos, registrar intentos, marcar login exitoso, aplicar bloqueo temporal y almacenar tokens “Recordarme” con hash.

### Flujo de acceso administrador

- El footer público muestra “Acceso administrador” y abre `/admin/login`.
- El formulario de login envía `redirect_to=/admin` para que, después de autenticar, el controlador redirija al panel.
- `app/Controllers/AdminAuthController.php` valida credenciales con MySQL, regenera la sesión, registra el intento y devuelve una ruta segura de redirección.
- `app/Views/admin/dashboard.php` es la vista compacta Bootstrap del panel inicial con accesos a solicitudes, escáner, entradas y configuración.

### Rutas PHP incluidas

Para que el login cargue sin depender de reglas externas de servidor, se agregaron entradas PHP directas:

- `/admin/login/` carga `app/Views/auth/login.php` y procesa POST de autenticación.
- `/admin/` valida sesión y carga `app/Views/admin/dashboard.php`.
- `/admin/forgot-password/` y `/admin/reset-password/` cargan sus vistas compactas.

La conexión MySQL quedó separada en `config/database.php`; las vistas no crean conexiones directas. El controlador de login usa esa fábrica solo al recibir POST, así la pantalla de acceso puede cargar aunque la base de datos esté temporalmente fuera de servicio.

### Corrección para `/admin/login` en Apache/XAMPP

Se incluyeron reglas `.htaccess` y wrappers en `public/admin/` para cubrir ambos despliegues comunes:

- Si el `DocumentRoot` apunta a la raíz del proyecto, `/admin/login` se resuelve con `admin/login/index.php`.
- Si el `DocumentRoot` apunta a `public/`, `/admin/login` se resuelve con `public/admin/login/index.php`, que carga la misma ruta real.
- Los assets visuales existentes se exponen en `public/assets/` como enlaces simbólicos a `assets/`, sin agregar binarios nuevos.

En Apache debe estar habilitado `mod_rewrite` y, si se usa `.htaccess`, `AllowOverride All` para este directorio.

### CSS/JS cuando el proyecto vive en `/casona`

Las vistas calculan automáticamente el prefijo del proyecto (`/casona`) desde `SCRIPT_NAME` y generan URLs como `/casona/assets/css/login.css`. Para que funcionen tanto con DocumentRoot en la raíz como en `public/`, se agregaron enlaces simbólicos:

- `assets/css` → `public/assets/css`
- `assets/js` → `public/assets/js`
- `manifest.webmanifest`, `service-worker.js` y `offline.html` → sus archivos en `public/`

Así `/casona/admin/login/` carga la vista y también sus CSS/JS.

### Usuario inicial de administración

Se agregó `database/seed_admin.sql` para crear un usuario inicial de pruebas:

- Email: `admin@fiesta80s.cl`
- Password inicial: `Admin12345!`

Importar después del esquema:

```bash
mysql -u USER -p < database/schema.sql
mysql -u USER -p < database/seed_admin.sql
```

Cambia esta contraseña inmediatamente después del primer ingreso.

Si el usuario ya existía antes, vuelve a ejecutar `database/seed_admin.sql`: el seed ahora actualiza también `password_hash`, limpia `failed_login_count` y elimina `locked_until` para evitar que un usuario previo quede con contraseña antigua o bloqueado.
