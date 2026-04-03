Dưới đây là nội dung file `README.md` đã được viết lại một cách chuyên nghiệp. Mình đã bổ sung chi tiết phần **Hướng dẫn cài đặt** và đặc biệt là **Danh sách tài khoản test** dựa trên file Seeder vừa tạo để bất kỳ ai clone code về cũng có thể đăng nhập thử ngay lập tức.

Bạn copy toàn bộ nội dung dưới đây và dán đè vào file `README.md` của thư mục Backend nhé:

```markdown
# 🏢 HẢI XỒM HR - Backend API

Đây là hệ thống Backend cung cấp API cho dự án Quản lý Nhân sự (HRM) của chuỗi nhà hàng Hải Xồm. Hệ thống được xây dựng bằng Laravel, hỗ trợ phân quyền chi tiết (Admin, Quản lý chi nhánh, Kế toán, Nhân viên) và giới hạn luồng dữ liệu theo từng cơ sở làm việc.

---

## 🚀 1. Yêu cầu hệ thống (Prerequisites)
- **PHP**: ^8.2
- **Composer**: ^2.x
- **MySQL**: ^8.0
- **Node.js & NPM** (Để build assets nếu cần)

---

## 🛠 2. Hướng dẫn cài đặt (Installation)

Thực hiện lần lượt các bước sau để chạy dự án trên máy cá nhân:

**Bước 1: Clone source code về máy**
```bash
git clone <url-repo-cua-ban>
cd hrm-backend-hx
```

**Bước 2: Cài đặt các thư viện PHP**
```bash
composer install
```

**Bước 3: Thiết lập môi trường (.env)**
Copy file `.env.example` thành `.env` và cấu hình lại kết nối cơ sở dữ liệu:
```bash
cp .env.example .env
```
Mở file `.env`, cập nhật thông tin Database của bạn:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=haixom_hr  # Tên database bạn đã tạo trong MySQL
DB_USERNAME=root       # Username MySQL
DB_PASSWORD=           # Mật khẩu MySQL
```

**Bước 4: Tạo App Key**
```bash
php artisan key:generate
```

**Bước 5: Chạy Migration & Seed Dữ liệu (QUAN TRỌNG)**
Bước này sẽ tự động tạo các bảng và đọc file `public/db_employees.json` để tạo 44 nhân viên mẫu chia đều cho 2 Chi nhánh.
```bash
php artisan migrate:fresh --seed
```

**Bước 6: Chạy server**
```bash
php artisan serve
```
Backend sẽ khởi chạy tại: `http://127.0.0.1:8000`

---

## 🔑 3. Danh sách Tài khoản Đăng nhập (Test Accounts)

Sau khi chạy lệnh `php artisan migrate:fresh --seed`, hệ thống đã tự động tạo sẵn các tài khoản với đầy đủ các phân quyền khác nhau. 

Tất cả các tài khoản đều có chung mật khẩu mặc định là: **`123`**

Dưới đây là danh sách các tài khoản gợi ý để bạn có thể test nghiệm thu các chức năng (Username viết thường, không có dấu cách):

| Vai trò (Role) | Chức danh mẫu | Tên đăng nhập (Username) | Mật khẩu | Quyền hạn mô phỏng |
| :--- | :--- | :--- | :--- | :--- |
| **Admin (C3)** | Quản lý chung (QLT) | `emp_m01` | `123` | Xem & sửa được toàn bộ dữ liệu của tất cả chi nhánh. Điều chuyển được nhân sự. |
| **Manager (C2)** | Kế toán trưởng | `emp_ql02` | `123` | **Chỉ xem và xếp ca được cho nhân sự thuộc Chi nhánh 1**. |
| **Manager (C2)** | Giám sát | `emp_ql03` | `123` | **Chỉ xem và xếp ca được cho nhân sự thuộc Chi nhánh 2**. |
| **Kế toán (C1)** | Quỹ | `emp_ql05` | `123` | Truy cập phân hệ tính lương, khấu trừ, phụ cấp. |
| **Nhân viên (C0)** | Nhân viên Order | `emp_t01` | `123` | Chỉ truy cập App nhân viên: Xem lịch cá nhân, bảng lương, xin nghỉ. |
| **Nhân viên (C0)** | Bếp chính | `emp_b01` | `123` | Chỉ truy cập App nhân viên. |
| **Nhân viên (C0)** | Bảo vệ | `emp_bv04` | `123` | Chỉ truy cập App nhân viên. |

*(Ghi chú: Username thực chất chính là mã nhân viên - ID trong file `db_employees.json` được viết thường. Bạn có thể mở file đó ra để lấy thêm tài khoản test nếu muốn).*

---

## 📂 4. Cấu trúc Phân quyền (Role Based Access)
Hệ thống nhận diện quyền dựa trên trường `role` của bảng `employees` (hoặc `accounts`):
* `C3`: Ban Giám Đốc / Admin.
* `C2`: Quản lý cấp cơ sở (Bị giới hạn tầm nhìn bởi `branch_id`).
* `C1`: Kế toán / Thu ngân.
* `C0`: Nhân viên tiêu chuẩn (Bàn, Bếp, Tạp vụ, Bảo vệ,...).

---
*Dự án được xây dựng và phát triển cho Hệ thống Nhà hàng Hải Xồm.*
```
