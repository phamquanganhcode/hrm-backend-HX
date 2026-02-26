FROM webdevops/php-nginx:8.2-alpine

ENV WEB_DOCUMENT_ROOT=/app/public
ENV APP_ENV=production

WORKDIR /app

COPY . .

# Bước quan trọng: Tạo sẵn các thư mục và phân quyền ngay khi build
RUN mkdir -p storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/logs \
    && chown -R application:application /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

RUN composer install --no-interaction --optimize-autoloader --no-dev