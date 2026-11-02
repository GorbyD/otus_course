# Установка гитлаб локлаьно (Допустим gitlab уже настроен и существует)
- deploy/infra_prepare/compose.gitlab.yml поднять через docker compose
- получить пароль sudo docker compose exec gitlab cat /etc/gitlab/initial_root_password 
- сменить пароль
- создать пустой проект study/otus-course
- git remote add gitlab http://{server}/{repo}, git push gitlab --all
- Чтобы не было проблемы с https - на runner сервере nano /etc/docker/daemon.json - добавить {"insecure-registries": ["{gitlabIP}:5050"]}, sudo systemctl restart docker
- в /srv/otus-ci/runner/config.toml добавить volumes = ["/var/run/docker.sock:/var/run/docker.sock"]

# Создать runner в gitlab
- в репозитории проекта Settings → CI/CD → Runners → Create project runner
- тег docker-ci
- зарегистрировать runner 
gitlab-runner register  --url {url}  --token {token}
- проверить статус в gitlab
	
# WSL, эмуляция prod сервера
- на прод сервер кладем файлы из папки deploy/infra_prepare/prod
- sudo bash install-prod.sh
- sudo nano /opt/otus-delivery/.env
- IMAGE_PREFIX на production должен совпадать со значением CI_REGISTRY_IMAGE проекта GitLab. Это адрес образов (registry_external_url)
- создать в Gitlab для проекта deploy token с read_registry (Settings → Repository → Deploy tokens)
- sudo docker login {IMAGE_PREFIX} (без пути к репо) с токеном
- создать пару ключей, поместить публичный на Prod

## Доступ к WSL по SSH
Внутри WSL ({wslIP})
```
hostname -I
```

На хосте
```powershell
netsh interface portproxy add v4tov4 `
  listenaddress=0.0.0.0 `
  listenport=2222 `
  connectaddress={wslIP} `
  connectport=22
  
New-NetFirewallRule `
  -DisplayName "WSL SSH" `
  -Direction Inbound `
  -Protocol TCP `
  -LocalPort 2222 `
  -Action Allow
```

# Переменные Gitlab для SSH
- DEPLOY_HOST, DEPLOY_PORT, SSH_PRIVATE_KEY
