"""Servicio privado de voz de Anita.

Genera audio con Qwen3-TTS y obtiene marcas de tiempo de las palabras desde
ese audio final. No persiste textos, audio ni transcripciones: los únicos WAV
temporales se crean en /dev/shm y se eliminan inmediatamente.
"""
from __future__ import annotations

import asyncio
import base64
import os
import subprocess
import tempfile
from contextlib import asynccontextmanager
from pathlib import Path

import io
import soundfile as sf
import torch
import whisperx
from fastapi import FastAPI, Header, HTTPException
from pydantic import BaseModel, Field
from qwen_tts import Qwen3TTSModel

ADAPTER_TOKEN = os.environ["ANITA_VOICE_TOKEN"]
DEVICE = os.environ.get("ANITA_DEVICE", "cuda:0")
REMOTE_QWEN_MODEL = os.environ.get("ANITA_QWEN_MODEL", "Qwen/Qwen3-TTS-12Hz-0.6B-Base")
BUNDLED_QWEN_MODEL = Path(
    os.environ.get("ANITA_QWEN_BUNDLED_MODEL", "/opt/anita-models/qwen3-tts")
)
REFERENCE_AUDIO = Path(os.environ.get("ANITA_REFERENCE_AUDIO", "/app/voices/anita-reference.mp3"))
REFERENCE_TEXT = os.environ.get(
    "ANITA_REFERENCE_TEXT",
    "Qué gusto acompañarte mientras llega tu cita de Medicina General.",
)
ALIGN_MODEL = os.environ.get("ANITA_ALIGN_MODEL", "small")
MAX_CONCURRENT = int(os.environ.get("ANITA_MAX_CONCURRENT", "1"))
semaphore = asyncio.Semaphore(MAX_CONCURRENT)
tts_model: Qwen3TTSModel | None = None
transcriber = None
align_model = None
align_metadata = None


def qwen_model_source() -> str:
    """Prefiere la copia completa incluida en la imagen de producción.

    La variable ANITA_QWEN_MODEL se conserva como alternativa para desarrollo,
    pero Salad no debe reconstruir el modelo desde su caché efímero.
    """
    if not BUNDLED_QWEN_MODEL.is_dir():
        return REMOTE_QWEN_MODEL

    required_file = BUNDLED_QWEN_MODEL / "speech_tokenizer" / "preprocessor_config.json"
    if not required_file.is_file():
        raise RuntimeError(
            "El modelo Qwen empaquetado está incompleto: falta "
            f"{required_file}"
        )
    return str(BUNDLED_QWEN_MODEL)


class SpeechRequest(BaseModel):
    input: str = Field(min_length=1, max_length=750)
    # Solo se admite un perfil para este piloto; los futuros avatares tendrán
    # perfiles aislados, nunca una URL de audio entregada por el móvil.
    voice: str = Field(default="anita", pattern=r"^anita$")
    response_format: str = Field(default="mp3", pattern=r"^mp3$")


@asynccontextmanager
async def lifespan(_: FastAPI):
    global tts_model, transcriber, align_model, align_metadata
    if not torch.cuda.is_available():
        raise RuntimeError("Se requiere una GPU CUDA para Anita")
    if not REFERENCE_AUDIO.is_file():
        raise RuntimeError("No se encontró el audio institucional de referencia")

    # SDPA evita compilar flash-attn en nodos efímeros; se puede sustituir por
    # flash_attention_2 después de medirlo en la misma GPU de producción.
    tts_model = Qwen3TTSModel.from_pretrained(
        qwen_model_source(),
        device_map=DEVICE,
        dtype=torch.bfloat16,
        attn_implementation="sdpa",
    )
    transcriber = whisperx.load_model(ALIGN_MODEL, DEVICE, language="es")
    align_model, align_metadata = whisperx.load_align_model(language_code="es", device=DEVICE)
    yield


app = FastAPI(docs_url=None, redoc_url=None, lifespan=lifespan)


def authorize(authorization: str | None) -> None:
    if authorization != f"Bearer {ADAPTER_TOKEN}":
        raise HTTPException(status_code=401, detail="No autorizado")


def transcode_to_mp3(wav: bytes) -> bytes:
    completed = subprocess.run(
        [
            "ffmpeg", "-hide_banner", "-loglevel", "error", "-i", "pipe:0",
            "-codec:a", "libmp3lame", "-b:a", "64k", "-f", "mp3", "pipe:1",
        ],
        input=wav, stdout=subprocess.PIPE, stderr=subprocess.PIPE,
        check=True,
    )
    return completed.stdout


def synthesize_wav(text: str) -> bytes:
    if tts_model is None:
        raise RuntimeError("El modelo de voz no está listo")
    wavs, sample_rate = tts_model.generate_voice_clone(
        text=text,
        language="Spanish",
        ref_audio=str(REFERENCE_AUDIO),
        ref_text=REFERENCE_TEXT,
    )
    buffer = io.BytesIO()
    sf.write(buffer, wavs[0], sample_rate, format="WAV")
    return buffer.getvalue()


def align_words(wav: bytes) -> tuple[list[dict[str, int | str]], int]:
    path: Path | None = None
    try:
        with tempfile.NamedTemporaryFile(dir="/dev/shm", suffix=".wav", delete=False) as temp:
            temp.write(wav)
            path = Path(temp.name)
        audio = whisperx.load_audio(str(path))
        result = transcriber.transcribe(audio, batch_size=4, language="es")
        aligned = whisperx.align(
            result["segments"], align_model, align_metadata, audio, DEVICE,
            return_char_alignments=False,
        )
        words = [
            {
                "text": str(word["word"]).strip(),
                "start_ms": round(float(word["start"]) * 1000),
                "end_ms": round(float(word["end"]) * 1000),
            }
            for segment in aligned["segments"]
            for word in segment.get("words", [])
            if word.get("word") and word.get("start") is not None and word.get("end") is not None
        ]
        info = sf.info(io.BytesIO(wav))
        duration = max(1, round((info.frames / info.samplerate) * 1000))
        if not words:
            raise RuntimeError("No se pudieron alinear palabras en español")
        return words, duration
    finally:
        if path:
            path.unlink(missing_ok=True)


@app.get("/health")
async def health():
    return {
        "status": "ok",
        "models_loaded": tts_model is not None and transcriber is not None,
        "voice": "anita",
    }


@app.post("/v1/anita/speech")
async def speech(request: SpeechRequest, authorization: str | None = Header(default=None)):
    authorize(authorization)
    async with semaphore:
        try:
            wav = await asyncio.to_thread(synthesize_wav, request.input.strip())
            words, duration_ms = await asyncio.to_thread(align_words, wav)
            mp3 = await asyncio.to_thread(transcode_to_mp3, wav)
        except Exception as error:
            # No registramos texto, audio ni la excepción; Laravel solo recibe
            # una indisponibilidad temporal y conserva la respuesta en texto.
            raise HTTPException(status_code=503, detail="Voz no disponible") from error
        return {
            "audio_base64": base64.b64encode(mp3).decode("ascii"),
            "duration_ms": duration_ms,
            "words": words,
        }
