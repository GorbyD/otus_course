#!/usr/bin/env bash
# На CI-сервер - исходники, сборка и тесты
set -euo pipefail

cd "$(dirname "$0")/.."

: "${CI_COMMIT_SHA:?}" "${CI_REGISTRY_IMAGE:?}" "${CI_REGISTRY:?}"
docker build --provenance=false --sbom=false --target test --build-context app=www -t "$CI_REGISTRY_IMAGE/test:$CI_COMMIT_SHA" docker/php
docker run --rm "$CI_REGISTRY_IMAGE/test:$CI_COMMIT_SHA"

docker build --provenance=false --sbom=false --target prod --build-context app=www -t "$CI_REGISTRY_IMAGE/php:$CI_COMMIT_SHA" docker/php
docker build --provenance=false --sbom=false --build-context app=www -t "$CI_REGISTRY_IMAGE/web:$CI_COMMIT_SHA" docker/nginx-backend

printf '%s' "$CI_REGISTRY_PASSWORD" | docker login "$CI_REGISTRY" -u "$CI_REGISTRY_USER" --password-stdin
docker push "$CI_REGISTRY_IMAGE/php:$CI_COMMIT_SHA"
docker push "$CI_REGISTRY_IMAGE/web:$CI_COMMIT_SHA"
