FROM nvidia/cuda:12.4.1-cudnn-runtime-ubuntu22.04

ENV DEBIAN_FRONTEND=noninteractive \
    PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    HF_HOME=/opt/models \
    TRANSFORMERS_CACHE=/opt/models

RUN for attempt in 1 2 3 4 5; do \
        rm -rf /var/lib/apt/lists/*; \
        if apt-get update \
            && apt-get install -y --no-install-recommends python3 python3-pip ffmpeg ca-certificates libsndfile1; then \
            break; \
        fi; \
        if [ "$attempt" = 5 ]; then exit 1; fi; \
        sleep 15; \
    done \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY requirements.txt .
RUN python3 -m pip install --no-cache-dir --upgrade pip \
    && python3 -m pip install --no-cache-dir --index-url https://download.pytorch.org/whl/cu124 torch==2.6.0 torchaudio==2.6.0 \
    && python3 -m pip install --no-cache-dir -r requirements.txt

COPY download_qwen_model.py .
# El modelo de Qwen se empaqueta fuera de HF_HOME. Asi, cada nodo de Salad
# recibe una copia completa y no intenta reconstruirla desde un caché efímero.
RUN python3 download_qwen_model.py \
    && test -f /opt/anita-models/qwen3-tts/speech_tokenizer/preprocessor_config.json

COPY app.py .
COPY voices/anita-reference.mp3 /app/voices/anita-reference.mp3
EXPOSE 8790
CMD ["python3", "-m", "uvicorn", "app:app", "--host", "::", "--port", "8790", "--no-access-log"]
