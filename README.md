# 👟 Hệ Thống Quản Lý Kho Giày Thông Minh (Smart Warehouse)
**Đồ án Khóa luận tốt nghiệp - Đại học Công nghiệp TP.HCM (IUH)**

Hệ thống quản lý kho giày tích hợp trí tuệ nhân tạo (AI) chạy **Offline 100%**, hỗ trợ nhận diện sản phẩm qua hình ảnh và trợ lý ảo Chatbot truy vấn dữ liệu tồn kho thông minh bằng ngôn ngữ tự nhiên.

---

## 🛠 1. Công nghệ & Thư viện sử dụng
* **Frontend:** Bootstrap 5, CSS3, JavaScript.
* **Backend:** PHP (Kiến trúc MVC).
* **Database:** PostgreSQL + Extension `pgvector`.
* **AI Engine:** Python (Chạy Local API).
* **AI Frameworks:** * **LangChain:** Quản lý luồng truy vấn dữ liệu (Text-to-SQL).
    * **Ollama:** Môi trường chạy mô hình AI Offline.
    * **Qwen2.5-Coder:7b:** Mô hình ngôn ngữ lớn chuyên biệt cho lập trình và SQL.
    * **CLIP (ViT-B-32):** Nhận diện hình ảnh không gian Vector.

---

## 📂 2. Cấu trúc thư mục dự án
```text
D:\Application\xampp\htdocs\Shoe_Warehouse
│   .env                # Cấu hình biến môi trường
│   .gitignore          # Các file loại trừ khi git
│   README.md           # Hướng dẫn dự án
│   testapi.php         # File test API
│   testhash.php        # File test mã hóa mật khẩu
│
├───ai_services         # Dịch vụ AI (Python)
│   │   ai_bridge.py        # API Chatbot (Cổng 8000)
│   │   api_vector.py       # API Quét ảnh (Cổng 5000)
│   │   generate_vector.py  # Script khởi tạo/Tải model lần đầu
│   │   VisionService.php   # Cầu nối gọi Python từ PHP
│   └───models/             # Lưu trữ bộ não AI (CLIP Model)
│
├───app                 # Source code PHP chính (MVC)
│   ├───controllers/    # Auth, Category, Product, Report, User Controllers
│   ├───models/         # Category, Product, Report, User Models
│   └───views/          # layouts (footer, header, sidebar...) & pages
│
├───config              # database.php (Cấu hình kết nối)
├───csdl                # backup.sql (File sao lưu dữ liệu)
└───public              # Thư mục thực thi chính
    │   index.php
    └───assets/         # Tài nguyên hệ thống
        ├───css/        # Các file định dạng giao diện (.css)
        ├───img_logo/   # Logo các thương hiệu giày
        ├───img_product/# Hình ảnh sản phẩm trong kho
        ├───img_temp/   # Ảnh tạm thời khi xử lý AI


🚀 3. Các bước cài đặt và chạy dự án
🔹 Bước 1: Cài đặt thư viện Python
- Mở Terminal dự án và cài đặt các thư viện cần thiết để chạy LangChain và Model CLIP:
python -m pip install -U torch Pillow ftfy regex tqdm langchain langchain-community langchain-ollama langgraph sqlalchemy psycopg2-binary pgvector fastapi uvicorn flask sentence-transformers python-dotenv

pip install vanna google-generativeai fastapi uvicorn python-dotenv psycopg2-binary pandaspython 

- Cài đặt thư viện CLIP trực tiếp từ source OpenAI:
python -m pip install git+https://github.com/openai/CLIP.git


- Đối với Quét ảnh:
Chạy file generate_vector.py để AI tự động tải bộ não CLIP về thư mục models (Chỉ chạy 1 lần duy nhất):
cd ai_services
python generate_vector.py


🔹 Bước 3: Cấu hình Database
Mở PostgreSQL, tạo database tên là shoe_warehouse_ai.

Import file csdl/backup.sql vào database vừa tạo.

Kích hoạt extension vector: CREATE EXTENSION IF NOT EXISTS vector;

Cấu hình User/Password database trong config/database.php.


4. Cách vận hành khi Demo (Bắt buộc)
Để hệ thống hoạt động đầy đủ tính năng, bồ cần mở cửa sổ Terminal để chạy các dịch vụ sau:

- Dịch vụ Chatbot (LangChain + Ollama):
cd ai_services
python ai_bridge.py

- Phần mềm Ollama: Đảm bảo phần mềm Ollama đã được bật






ollama pull qwen2.5-coder:7b-instruct-q4_K_M

## ĐÃ LÀM (17/19)
1. login
2. logout
3. thêm danh mục sản phẩm
4. xóa danh mục sản phẩm (xóa mềm)
5. cập nhật trạng thái danh mục (active/inactive)
6. nhập kho (Quét AI)
7. cập nhật trạng thái sản phẩm/biến thể (active/inactive)
8. Thêm user
9. xóa mềm user
10. cập nhật trạng thái user (active/inactive)
11. xem thống kê báo cáo
12. tìm kiếm (tên, size, màu sắc)
13. xem thông tin cá nhân
14. cập nhật thông tin cá nhân
15. chatbot AI
16. xuất kho
17. xem lịch sử xuất nhập kho

## CHƯA LÀM
1. Quản lí vị trí kệ (heatmap)
2. Dự đoán xu hướng tiêu thụ (AI)


## Đang làm 
1. quản lí vị trí kệ
- load được vị trí sản phẩm ra giao diện
- thêm được sản phẩm vào kệ đã chọn nhưng chưa xong hoàn chỉnh
- xuất kho chưa tự trừ sản phẩm trên kệ
- thuật toán gợi ý vị trí nhập kho vào kệ chưa hoàn chỉnh

