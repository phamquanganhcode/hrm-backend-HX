# Dùng image gộp sẵn cả Nginx và PHP 8.2 cực nhẹ
FROM webdevops/php-nginx:8.2-alpine

# Trỏ máy chủ web thẳng vào thư mục public của Laravel
ENV WEB_DOCUMENT_ROOT=/app/public
ENV APP_ENV=production

WORKDIR /app

# Copy toàn bộ code vào
COPY . .

# Cài đặt thư viện (bỏ qua các thư viện test để web nhẹ hơn)
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Cấp quyền ghi file cho Laravel
RUN chown -R application:application /app/storage /app/bootstrap/cache