# Despliegue piloto en Salad

Este documento deja el contenedor listo para el grupo que ya creaste:
`avatar-ai-pilot`, una réplica, RTX 3090 (24 GB), 8 vCPU, 16 GB RAM y 100 GB
de disco. Es un piloto con datos de prueba: Salad Community usa nodos de
terceros, así que no se debe activar todavía para datos clínicos reales.

## 1. Construir y publicar la imagen privada

Desde esta carpeta, cuando el Mac tenga al menos 15 GB libres y Docker Desktop
esté funcionando:

```bash
bash build-salad.sh
export GHCR_USER="TU_USUARIO_GITHUB"
export GHCR_TOKEN="TOKEN_CON_WRITE_PACKAGES"
bash publish-ghcr.sh
```

La primera instrucción produce `avatar-ai-pilot:pilot-v4-local` para `linux/amd64`, que
es la arquitectura de la RTX 3090. La segunda publica exclusivamente
`ghcr.io/tinqsoftware/avatar-ai-pilot:pilot-v4`; nunca hagas público ese
paquete porque contiene el clip de referencia institucional de Anita.

La imagen `pilot-v4` incluye el modelo Qwen completo. Por ello es más grande,
pero evita que Salad construya una copia incompleta en su caché al iniciar.

Para publicar, el usuario de GitHub debe pertenecer a `tinqsoftware` y tener
permiso de crear paquetes. El token de GitHub necesita `write:packages`; para
que Salad lea la imagen privada, crea un segundo token restringido a
`read:packages`. No reutilices el token de escritura dentro de Salad.

## 2. Completar la pantalla actual de Salad

En **Image Source > Edit** selecciona **GitHub Container Registry** como
registro privado y usa:

| Campo | Valor |
| --- | --- |
| Imagen | `ghcr.io/tinqsoftware/avatar-ai-pilot:pilot-v4` |
| Usuario | usuario técnico de GitHub con acceso de solo lectura |
| Contraseña | token de GitHub con `read:packages` |
| Réplicas | `1` |
| Command | déjalo vacío |

En **Environment Variables**, crea estas variables. Sustituye el secreto por
una cadena aleatoria larga que se guardará también en el `.env` del backend;
no lo pongas en Flutter ni en el repositorio.

```text
ANITA_VOICE_TOKEN=<SECRETO_LARGO_ALEATORIO>
ANITA_DEVICE=cuda:0
ANITA_QWEN_MODEL=Qwen/Qwen3-TTS-12Hz-0.6B-Base
ANITA_ALIGN_MODEL=small
ANITA_MAX_CONCURRENT=1
ANITA_REFERENCE_AUDIO=/app/voices/anita-reference.mp3
ANITA_REFERENCE_TEXT=Qué gusto acompañarte mientras llega tu cita de Medicina General.
```

No crees una variable adicional para WhisperX: la imagen usa automáticamente
`cuda` para CTranslate2 y conserva `cuda:0` sólo para Qwen/PyTorch.

En **Networking**, expón el puerto `8790` como HTTP. Copia la URL HTTPS que
Salad muestra al terminar: esa URL es solo para el backend Laravel, nunca para
la app Flutter.

En **Health Probes & Logging** configura una sonda HTTP de inicio y una de
vivacidad con `GET /health`, puerto `8790`. Da a la de inicio hasta 10 minutos:
Qwen ya está incluido en la imagen, pero WhisperX aún puede descargar sus
pesos en una réplica nueva. No habilites un servicio externo de logs para este
piloto, porque allí no deben viajar textos ni audio.

## 3. Conectar el backend Laravel (VPS Hostinger)

Solo cuando el estado de Salad sea **Running** y `GET /health` devuelva
`models_loaded: true`, coloca estas variables en el `.env` de Laravel del VPS:

```text
ASSISTANT_VOICEBOX_ENABLED=true
ASSISTANT_VOICEBOX_URL=https://URL-HTTPS-ENTREGADA-POR-SALAD
ASSISTANT_VOICEBOX_TOKEN=EL_MISMO_SECRETO_LARGO
ASSISTANT_VOICEBOX_VOICE=anita
ASSISTANT_VOICEBOX_TIMEOUT_SECONDS=30
```

Luego ejecuta en el VPS:

```bash
php artisan config:clear
php artisan config:cache
```

Prueba primero `GET /api/v1/asistente/estado` con una cuenta de prueba; debe
devolver `available: true` y `voice_available: true`. Después envía un saludo
y una consulta de prueba. El backend valida el token entre Laravel y Salad y
el teléfono solo recibe el MP3 temporal, la duración y los visemas.

## 4. Prueba obligatoria antes de TestFlight

1. Abre el avatar, escucha el saludo y comprueba la boca con A/E/I/O/U.
2. Haz dos preguntas consecutivas y cierra/reabre la llamada.
3. Confirma que la URL de Salad no aparece en Flutter ni en los logs de la app.
4. Apaga el grupo de Salad después de la prueba si no lo vas a usar: el cobro
   de GPU solo aplica mientras la réplica está ejecutándose.
