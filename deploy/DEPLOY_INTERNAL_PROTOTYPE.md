# Despliegue interno del prototipo

Este despliegue crea exactamente dos contenedores: `app` (Laravel, Nginx y el
worker de colas) y `database` (MySQL 8). Los celulares acceden solamente por
HTTPS al servicio `app`; MySQL nunca se expone.

## Datos que infraestructura debe entregar

- Un DNS privado para la API, por ejemplo `api.miconsulta.interno`, accesible
  desde el Wi-Fi institucional y desde la VPN de los dispositivos de prueba.
- Certificado TLS confiable para ese DNS: archivos `tls.crt` y `tls.key`.
- La URL interna y el token de la API de Telecertificación. En el entorno
  actual se documentó como `http://host.docker.internal:8080` desde el
  contenedor, pero debe verificarse con infraestructura antes de usarlo.
- Un dump actualizado de la base de datos, guardado como
  `deploy/database/miconsulta-production.sql`. No usar el dump semilla si se
  requiere mostrar información vigente.

## Preparación segura

```bash
cd miconsulta-backend/deploy
cp .env.prototype.example .env.prototype
chmod 600 .env.prototype
```

Editar `.env.prototype` con contraseñas fuertes. Generar la llave de Laravel
en una máquina segura y copiar el resultado, sin comillas, en `APP_KEY`:

```bash
docker run --rm -it php:8.2-cli php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

Crear la carpeta `deploy/database/` y copiar allí el dump actualizado. Este
archivo y `.env.prototype` no se suben a Git.

## Publicar Flutter Web

En la máquina que tiene el proyecto Flutter, generar el build con el DNS
interno de esta API y copiar el resultado al directorio `deploy/web` del
servidor. El mismo Nginx servirá la web y la API bajo el mismo origen:

```bash
cd miconsulta_app
flutter build web --release --dart-define=API_BASE_URL=https://api.miconsulta.interno/api/v1
rsync -av --delete build/web/ cenate@SERVIDOR:/home/cenate/miconsulta-backend/deploy/web/
```

## Primer arranque

Desde `deploy/` ajustar las dos rutas de host de `docker-compose.prototype.yml`:

```yaml
/ruta/host/certificados:/mnt/certificates:ro
/ruta/host/tls:/etc/nginx/tls:ro
```

Luego levantar el servicio:

```bash
docker compose --env-file .env.prototype -f docker-compose.prototype.yml up -d --build
docker compose --env-file .env.prototype -f docker-compose.prototype.yml exec app php artisan migrate --force
docker compose --env-file .env.prototype -f docker-compose.prototype.yml exec app php artisan optimize
docker compose --env-file .env.prototype -f docker-compose.prototype.yml ps
```

La importación del dump ocurre solamente al crear por primera vez el volumen
`mysql_data`. Para reemplazar datos durante pruebas, primero se toma un backup,
se detiene el despliegue y se importa explícitamente el nuevo dump; no se debe
borrar un volumen con información que se necesite conservar.

## Verificación

```bash
curl --cacert /ruta/ca-interna.crt https://api.miconsulta.interno/up
docker compose --env-file .env.prototype -f docker-compose.prototype.yml logs --tail=100 app
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
