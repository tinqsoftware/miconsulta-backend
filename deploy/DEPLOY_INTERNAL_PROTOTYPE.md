# Despliegue interno del prototipo

Este despliegue crea exactamente dos contenedores: `app` (Laravel, Nginx y el
worker de colas) y `database` (MySQL 8). Los celulares acceden solamente por
HTTPS al servicio `app`; MySQL nunca se expone.

## Datos que infraestructura debe entregar

- Una IP privada accesible desde el Wi-Fi institucional y desde la VPN de los
  dispositivos de prueba. Para el prototipo actual es `10.0.89.241`.
- Un certificado TLS confiable para esa IP, con la IP incluida como SAN:
  archivos `tls.crt` y `tls.key`. En un iPhone de prueba debe instalarse la CA
  interna y habilitarse su confianza total antes de abrir la app.
- La URL interna y el token de la API de Telecertificación. En el entorno
  actual se documentó como `http://host.docker.internal:8080` desde el
  contenedor, pero debe verificarse con infraestructura antes de usarlo.
- Opcionalmente, un dump actualizado de la base de datos. No usar un dump
  semilla si se requiere mostrar información vigente. Sin dump, las
  migraciones del repositorio crean el esquema mínimo reproducible y luego se
  pueden crear pacientes ficticios con `prototype:patient`.

## Preparación segura

```bash
cd miconsulta-backend/deploy
cp .env.prototype.example .env.prototype
chmod 600 .env.prototype
```

Editar `.env.prototype` con contraseñas fuertes. Generar la llave de Laravel
en una máquina segura y copiar el resultado, sin comillas, en `APP_KEY`:

```bash
docker run --rm -it php:8.4-cli php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

El archivo `.env.prototype`, los certificados TLS y los documentos montados se
excluyen explícitamente de la imagen Docker. Deben mantenerse solo en el host
del servidor y montarse en tiempo de ejecución.

El archivo `.env.prototype` no se sube a Git.

## Publicar Flutter Web

En la máquina que tiene el proyecto Flutter, generar el build con la IP
interna de esta API y copiar el resultado al directorio `deploy/web` del
servidor. El mismo Nginx servirá la web y la API bajo el mismo origen:

```bash
cd miconsulta_app
flutter build web --release --dart-define=API_BASE_URL=https://10.0.89.241/api/v1
rsync -av --delete build/web/ cenate@SERVIDOR:/home/cenate/miconsulta-backend/deploy/web/
```

Para Android y TestFlight, usar exactamente el mismo `--dart-define` al
compilar. No apuntar las builds de prueba al dominio público.

## Primer arranque

Por defecto, el Compose monta `deploy/certificates` y `deploy/certs`. Si se
usan rutas distintas en el host, definirlas en `.env.prototype` antes del
arranque:

```env
CERTIFICATES_HOST_PATH=/ruta/host/certificados
TLS_HOST_PATH=/ruta/host/tls
```

Luego levantar el servicio:

```bash
docker compose --env-file .env.prototype -f docker-compose.prototype.yml up -d --build
docker compose --env-file .env.prototype -f docker-compose.prototype.yml exec app php artisan migrate --force
docker compose --env-file .env.prototype -f docker-compose.prototype.yml exec app php artisan optimize
docker compose --env-file .env.prototype -f docker-compose.prototype.yml ps
```

Para cargar un dump actualizado, usar el script versionado. Exige una
confirmación explícita, crea un backup SQL del servidor y solo después importa
el archivo y ejecuta las migraciones. Durante la importación la app queda
temporalmente detenida. Si falla la carga o una migración, el script restaura
el backup y vuelve a iniciar la app. No borrar el volumen MySQL:

```bash
cd deploy
chmod +x import-real-database.sh
MI_CONSULTA_CONFIRM_IMPORT=YES \
  ./import-real-database.sh /ruta/segura/miconsulta-production.sql
```

## Verificación

```bash
curl --cacert /ruta/ca-interna.crt https://10.0.89.241/up
docker compose --env-file .env.prototype -f docker-compose.prototype.yml logs --tail=100 app
```

Con una CA autofirmada para IP, también se puede verificar desde el servidor:

```bash
curl --insecure https://127.0.0.1/up
```

## Actualizar el backend sin tocar la base de datos

Después de recibir cambios desde Git, el administrador puede ejecutar este
único comando en el servidor. No recrea el contenedor `database`, no borra el
volumen MySQL ni reemplaza `deploy/web`:

```bash
cd /home/cenate/miconsulta-backend
bash deploy/update-prototype.sh
```

Desde un celular conectado al Wi-Fi/VPN, la app autenticada debe abrir:

```text
GET /api/v1/certificados/discapacidad
Authorization: Bearer <token>
```

El endpoint no acepta una ruta ni un DNI enviado por el teléfono: usa el DNI
del usuario autenticado, consulta Telecertificación por ese DNI y devuelve
solo el PDF activo y vigente de ese paciente.

## Preparar un paciente de demostración

Para cada DNI que tenga un certificado activo en Telecertificación, crear una
cuenta de prueba en Mi Consulta. Este comando solo se ejecuta dentro del
contenedor y no expone el DNI ni la contraseña mediante una API:

```bash
docker compose --env-file .env.prototype -f docker-compose.prototype.yml \
  exec app php artisan prototype:patient 00000003 \
  --password='CAMBIAR_POR_CLAVE_TEMPORAL' \
  --nombres='Paciente' \
  --apellido-paterno='Demostración'
```

El DNI de inicio de sesión debe coincidir exactamente con `dni_paciente` en
Telecertificación. Reemplazar `00000003` por los DNI proporcionados para la
demostración. No usar datos personales reales como datos semilla.
