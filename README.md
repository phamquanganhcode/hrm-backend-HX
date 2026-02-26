🚀 Công nghệ sử dụng
Framework: Laravel 11

Database: MySQL 8.0 (Dockerized)

Authentication: Laravel Sanctum (Token-based)

Architecture: Service & Repository Pattern

Infrastructure: Docker & Docker Compose

🛠 Hướng dẫn cài đặt nhanh (Setup)
Sau khi clone dự án về máy, hãy thực hiện lần lượt các bước sau:

1. Cài đặt các thư viện PHP

composer install

2. Cấu hình môi trường (.env)
   
Tạo file .env từ file mẫu:

cp .env.example .env

Mở file .env vừa tạo và cập nhật các thông số kết nối Database Docker:

DB_CONNECTION=mysql

DB_HOST=127.0.0.1

DB_PORT=3306

DB_DATABASE=hrm_demo

DB_USERNAME=root

DB_PASSWORD=root_secret

3. Tạo chìa khóa ứng dụng và Token

php artisan key:generate

php artisan install:api

4. Khởi động hạ tầng Docker
   
Đảm bảo Docker Desktop đã bật, sau đó chạy:

docker-compose up -d

5. Khởi tạo Database và Dữ liệu mẫu (Seed)
   
Lệnh này sẽ tạo cấu trúc bảng và bơm sẵn các tài khoản thử nghiệm:

php artisan migrate:fresh --seed

🔐 Tài khoản thử nghiệm (Test Accounts)
Bạn có thể sử dụng các tài khoản sau để đăng nhập trên giao diện:
Nhân viên (Employee)	nhanvien	123
