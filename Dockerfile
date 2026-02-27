FROM webdevops/php-nginx:8.2-alpine

# Cấu hình Web Root cho Laravel
ENV WEB_DOCUMENT_ROOT=/app/public
ENV APP_ENV=production

WORKDIR /app

# Copy mã nguồn
COPY . .

# TẠO THƯ MỤC VÀ PHÂN QUYỀN TRƯỚC (Đây là bước fix lỗi image_6222a0.png)
RUN mkdir -p storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/logs \
    && chown -R application:application /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# Cài đặt thư viện
RUN composer install --no-interaction --optimize-autoloader --no-dev