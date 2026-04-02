D:.
│   README.md
│
├───ai_services
├───app
│   ├───controllers
│   ├───models
│   └───views
│       ├───layouts
│       │       sidebar.php
│       │
│       └───pages
│               dashboard.php
│
├───config
└───public
    │   index.php
    │
    ├───assets
    │   └───css
    │           dashboard.css
    │           footer.css
    │           header.css
    │           main.css
    │           sidebar.css
    │
    └───uploads

- xong
1. Đăng nhập (Login)
2. Đăng xuất (Logout)
3. Xem thông tin cá nhân
4. Cập nhật thông tin cá nhân
5. Thêm nhân viên
6. Sửa trạng thái nhân viên (Active/Inactive)
7. Xóa mềm nhân viên
8. Tải danh mục sản phẩm (Load categories)
9. Sửa trạng thái danh mục (Active/Inactive)
10. Xóa mềm danh mục
11. Tải danh sách sản phẩm (Load products)
12. Thêm sản phẩm mới
13. Tìm kiếm sản phẩm (theo Tên, Size, Màu sắc)
14. Xem chi tiết biến thể sản phẩm
15. Sửa trạng thái sản phẩm (Active/Inactive)
16. Xóa mềm sản phẩm
17. demo AI quét ảnh (chưa xong)



# 📦 Hệ Thống Quản Lý Kho Giày Thông Minh (Smart Warehouse)

Dự án tích hợp AI để nhận diện và đối soát hình ảnh sản phẩm.

## 🛠 1. Yêu cầu hệ thống
- XAMPP (PHP 7.4 trở lên, MySQL)
- Python 3.8+ (Đã thêm vào biến môi trường PATH)

## 🚀 2. Cài đặt môi trường AI (Bắt buộc)
Mở CMD tại thư mục dự án và chạy các lệnh sau để cài đặt cho hệ thống:

1. Cài đặt thư viện nền:
   python -m pip install torch Pillow ftfy regex tqdm

2. Cài đặt Model CLIP từ OpenAI:
   python -m pip install git+https://github.com/openai/CLIP.git

## 💻 3. Cách sử dụng
1. Import cơ sở dữ liệu từ thư mục `/csdl` vào phpMyAdmin.
2. Cấu hình kết nối database trong `config/database.php`.
3. Bật Apache và MySQL trên XAMPP.
4. Truy cập `localhost/Shoe_Warehouse/public`.
5. **Tính năng AI:** Khi bạn tải ảnh lên để quét, hệ thống sẽ tự động gọi Python để xử lý. 
   *(Lưu ý: Trong lần chạy đầu tiên, hệ thống sẽ tự động tải Model AI khoảng 350MB, vui lòng đảm bảo có kết nối mạng).*