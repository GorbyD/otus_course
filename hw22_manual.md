# hw22: инструкция по запуску

1. Подготовить `docker-compose.override.yml` (по образцу `docker-compose.override.example.yml`), чтобы добавить Mailpit для получения письма. В проде письмо убдет уходить по SMTP из DSN.

2. Подготвоить `.env` (по образцу `.env.example`)
3. Установить зависимости:
```bash
docker exec -it project-php-fpm1-1 /bin/sh -c "cd /var/www/html && composer update"
```
4. На странице `http://localhost/statement` заполнить и отправить форму
5. Посмотреть консоль:
```bash
docker compose logs -f statement-worker
```
6. Письмо с выпиской: http://localhost:8025
7. Очередь в Rabbit: http://localhost:15672
