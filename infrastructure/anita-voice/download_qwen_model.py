"""Incluye en la imagen la copia completa y verificable de Qwen3-TTS."""
from pathlib import Path

from huggingface_hub import snapshot_download


MODEL_ID = "Qwen/Qwen3-TTS-12Hz-0.6B-Base"
# El identificador de snapshot que escribe Transformers para un subcomponente
# no es una revisión del repositorio Qwen. Descargamos la revisión publicada
# del modelo completo y validamos los archivos imprescindibles abajo.
MODEL_REVISION = "main"
MODEL_DIRECTORY = Path("/opt/anita-models/qwen3-tts")
REQUIRED_FILES = (
    "config.json",
    "speech_tokenizer/config.json",
    "speech_tokenizer/preprocessor_config.json",
)


def main() -> None:
    snapshot_download(
        repo_id=MODEL_ID,
        revision=MODEL_REVISION,
        local_dir=MODEL_DIRECTORY,
    )
    missing = [name for name in REQUIRED_FILES if not (MODEL_DIRECTORY / name).is_file()]
    if missing:
        raise RuntimeError(f"Snapshot Qwen incompleto; faltan: {', '.join(missing)}")


if __name__ == "__main__":
    main()
