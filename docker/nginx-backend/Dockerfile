FROM nginx:1.27-alpine

# Шаблон конфига: ${PHP_FPM_HOST} подставляется из окружения при старте контейнера.
COPY templates/ /etc/nginx/templates/

# Статика и точка входа. PHP-файлы nginx не исполняет, а передаёт в php-fpm
# с тем же путём, поэтому код в образе php-fpm лежит по тому же адресу.
# Код приходит как именованный контекст "app" (см. .gitlab-ci.yml):
#   docker build --build-context app=www docker/nginx-backend
COPY --from=app public/ /var/www/html/public/
