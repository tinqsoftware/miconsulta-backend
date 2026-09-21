#!/usr/bin/env bash
set -euo pipefail

# Publica exclusivamente en el registro privado de GitHub Container Registry.
# No registra el token: Docker lo recibe por stdin. Puede recibir GHCR_TOKEN
# explícitamente o tomar la sesión activa de GitHub CLI, con write:packages.
image_tag="${1:-ghcr.io/tinqsoftware/avatar-ai-pilot:pilot-v4}"

: "${GHCR_USER:?Define GHCR_USER con tu usuario de GitHub}"
if [[ -z "${GHCR_TOKEN:-}" ]]; then
  GHCR_TOKEN="$(gh auth token)"
fi

printf '%s' "$GHCR_TOKEN" | docker login ghcr.io --username "$GHCR_USER" --password-stdin
docker tag avatar-ai-pilot:pilot-v4-local "$image_tag"
docker push "$image_tag"
