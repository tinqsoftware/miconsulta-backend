#!/usr/bin/env bash
set -euo pipefail

# Salad con RTX 3090 ejecuta Linux x86_64. En un Mac Apple Silicon no se debe
# usar el valor por defecto de Docker (arm64), pues Salad no podría iniciar la
# imagen resultante.
image_tag="${1:-avatar-ai-pilot:pilot-v4-local}"

script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
docker build --platform linux/amd64 --tag "$image_tag" "$script_dir"
docker image inspect "$image_tag" --format '{{.Os}}/{{.Architecture}}'
