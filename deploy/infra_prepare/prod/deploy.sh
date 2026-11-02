#!/usr/bin/env bash
# /opt/otus-delivery/bin/deploy.sh SHA [blue|green]
# Если цвет не указан, берется противоположный текущему.

set -e

export DEPLOY_DIR=/opt/otus-delivery
cd "$DEPLOY_DIR/bin"

# переменные для Compose
export IMAGE_TAG=$1
export SLOT=${2:-}

# В active хранится строка, например: blue abcdef1234.
CURRENT_SLOT=''
if [ -f ../active ]; then
  read -r CURRENT_SLOT OLD_TAG < ../active
fi

# Автоматический выбор цвета для запуска из GitLab CI.
if [ -z "$SLOT" ]; then
  if [ "$CURRENT_SLOT" = blue ]; then SLOT=green; else SLOT=blue; fi
fi

# Общие БД и очереди
docker compose --env-file ../.env -f compose.infra.yml -p otus-delivery \
  up -d --no-recreate --wait postgres redis memcached rabbitmq elasticsearch

# готовые образы без сборки
docker compose --env-file ../.env -f compose.app.yml -p "otus-$SLOT" \
  pull --policy missing php web

# Запуск
# --wait ждёт запуска контейнеров и их healthcheck из Compose.
docker compose --env-file ../.env -f compose.app.yml -p "otus-$SLOT" up -d --wait

# в какой цвет отправлять новые запросы - otus-blue-web / otus-green-web
mkdir -p ../nginx
cat > ../nginx/default.conf <<EOF
server {
    listen 80;
    location / {
        proxy_pass http://otus-$SLOT-web:80;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
    }
}
EOF

# в первый раз запускаем gateway, потом только перечитываем конфиг
docker compose --env-file ../.env -f compose.infra.yml -p otus-delivery up -d --no-recreate gateway
docker compose --env-file ../.env -f compose.infra.yml -p otus-delivery exec -T gateway nginx -s reload

echo "$SLOT $IMAGE_TAG" > ../active
echo "Выкатили $IMAGE_TAG на $SLOT"
