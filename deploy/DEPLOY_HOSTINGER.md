# 🚀 Guía de Despliegue en Hostinger (Nginx + Laravel)

Para que el MVP funcione en la nube y la app móvil pueda conectarse a la API, debes subir el backend a tu servidor en Hostinger.

## 1. Preparar la Base de Datos en Hostinger
1. Entra a tu panel de Hostinger y ve a **Bases de Datos -> Gestión de Bases de Datos**.
2. Crea una nueva base de datos (ej. `u123456789_miconsulta`) y anota el usuario y contraseña.
3. Entra a **phpMyAdmin** e importa el archivo `/database/miconsulta_db.mysql` que creamos en la Fase 0.

## 2. Subir Archivos del Backend
1. Comprime toda la carpeta `/backend` en un archivo `backend.zip` (Excluye la carpeta `vendor` y `.env` para que pese menos).
2. Sube el `.zip` al `File Manager` de Hostinger, dentro de `/domains/tu-dominio.com/public_html` (o la ruta que prefieras).
3. Descomprime el archivo.
4. Entra por SSH a tu servidor Hostinger y navega a la carpeta descomprimida.
5. Ejecuta: `composer install --optimize-autoloader --no-dev`.

## 3. Configurar Entorno (`.env`)
1. En la raíz de la carpeta `/backend` en Hostinger, crea un archivo `.env` basado en `.env.example`.
2. Actualiza los valores de la base de datos con los que creaste en el paso 1:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.tu-dominio.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=u123456789_miconsulta
DB_USERNAME=u123456789_usuario
DB_PASSWORD=tu_contraseña_fuerte
```
3. Genera la llave de la app: `php artisan key:generate`.

## 4. Configurar Nginx y Permisos
1. El archivo `nginx.conf` adjunto en esta carpeta te sirve de guía para apuntar tu dominio a la carpeta `/backend/public`.
2. Otorga permisos de escritura a las carpetas vitales de Laravel:
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## 5. Optimización Final
Una vez todo esté configurado y Nginx reiniciado, ejecuta:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 6. Actualizar Flutter
En tu proyecto Flutter (`/miconsulta_app`), busca todos los lugares donde configuramos la URL local (ej. `http://127.0.0.1:8000/api/v1`) y cámbialo por tu nueva URL en la nube (ej. `https://api.tu-dominio.com/api/v1`).
Luego, podrás compilar tu APK para Android con `flutter build apk --release`.
