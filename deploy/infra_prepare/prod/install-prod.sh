#!/usr/bin/env bash
set -euo pipefail
[[ $EUID -eq 0 ]] || { echo 'Нужен sudo' >&2; exit 1; }
cd "$(dirname "$0")"

id deploy >/dev/null 2>&1 || useradd --create-home --shell /bin/bash deploy
if id -nG deploy | tr ' ' '\n' | grep -Eq '^(docker|sudo)$'; then
  echo 'Удалите deploy из групп docker/sudo перед установкой ограниченного sudo.' >&2
  exit 1
fi


install -d -m 700 -o root -g root /opt/otus-delivery /opt/otus-delivery/bin
install -m 755 -o root -g root deploy.sh /opt/otus-delivery/bin/
install -m 644 -o root -g root compose.app.yml compose.infra.yml /opt/otus-delivery/bin/
if [[ ! -f /opt/otus-delivery/.env ]]; then
  install -m 600 -o root -g root production.env.example /opt/otus-delivery/.env
fi
rule=$(mktemp)
trap 'rm -f -- "$rule"' EXIT
printf '%s\n' 'deploy ALL=(root) NOPASSWD: /opt/otus-delivery/bin/deploy.sh *' > "$rule"
visudo -cf "$rule"
install -m 440 -o root -g root "$rule" /etc/sudoers.d/otus-delivery
echo 'Установлено. Заполните /opt/otus-delivery/.env и выполните sudo docker login в registry.'
