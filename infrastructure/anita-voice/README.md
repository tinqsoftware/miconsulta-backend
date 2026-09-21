# Servicio privado de voz de Anita

Este contenedor reemplaza el adaptador incompleto que dependía de un Voicebox
externo. Qwen3-TTS crea el audio en español a partir de la referencia de voz
institucional autorizada de CENATE. WhisperX alinea **ese mismo audio final** y
devuelve tiempos de palabras; Laravel conserva el mapeo CENATE A/E/I/O/U/F-V
para controlar Rive.

El único perfil del piloto es `anita`. No hay un endpoint para que el teléfono
suba una voz ni seleccione una referencia. Para cada futuro avatar se desplegará
un perfil aislado, con su propio audio institucional autorizado y token.

## Construcción y prueba local

1. Coloca el audio institucional de referencia en
   `voices/anita-reference.mp3`. Ese directorio está ignorado por Git y la
   imagen debe publicarse únicamente en un registro privado.
2. Copia `.env.example` como `.env` y cambia `ANITA_VOICE_TOKEN` por un secreto
   largo y aleatorio.
3. En este Mac, construye con `bash build-salad.sh`. El script fuerza
   `linux/amd64`, que es la arquitectura de la RTX 3090 de Salad, y comprueba
   la arquitectura al finalizar. La primera ejecución descarga los pesos de
   Qwen y de alineación, por lo que el contenedor puede tardar varios minutos
   en estar sano.
4. Configura en Laravel `ASSISTANT_VOICEBOX_URL` con el endpoint HTTPS privado
   del contenedor, `ASSISTANT_VOICEBOX_TOKEN` con el mismo secreto y
   `ASSISTANT_VOICEBOX_VOICE=anita`.

El audio temporal se escribe solo en `/dev/shm`, que Docker monta como memoria,
y se elimina inmediatamente después de alinearlo. No se habilitan Swagger,
logs de cuerpo de solicitud ni puertos públicos. No uses Salad Community para
datos clínicos reales: este piloto debe trabajar exclusivamente con datos de
prueba hasta recibir la aprobación de seguridad correspondiente.
