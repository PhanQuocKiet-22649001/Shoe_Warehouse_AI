import os
import re
import uvicorn
from fastapi import FastAPI, Query
from langchain_ollama import ChatOllama
from langchain_community.utilities import SQLDatabase
from langchain_core.prompts import ChatPromptTemplate
from langchain_core.output_parsers import StrOutputParser

app = FastAPI()

# 1. KẾT NỐI DATABASE
# Thay đổi thông tin nếu bồ đổi user/pass database
DB_URL = "postgresql://ai_user:12345@localhost:5432/shoe_warehouse_ai"
db = SQLDatabase.from_uri(DB_URL)

# 2. KHỞI TẠO BỘ NÃO AI (QWEN 2.5 CODER OFFLINE)
# Đảm bảo bồ đã chạy: ollama pull qwen2.5-coder:7b
llm = ChatOllama(model="qwen2.5-coder:7b", temperature=0)

def clean_sql(text: str) -> str:
    """Dọn dẹp mã SQL: xóa markdown, xóa nháy ngược và ký tự thừa"""
    text = re.sub(r"```sql|```", "", text)
    text = text.replace("`", "")
    return text.strip()

# 3. MÔ TẢ CHI TIẾT CẤU TRÚC VÀ NGỮ NGHĨA CSDL (CỰC KỲ QUAN TRỌNG)
DB_METADATA_DESC = """
Hệ thống Quản lý Kho Giày (Smart Warehouse) có cấu trúc PostgreSQL chi tiết như sau:

1. Bảng 'categories' (Danh mục thương hiệu):
   - category_id (int, PK): Mã danh mục.
   - category_name (varchar): Tên thương hiệu (Nike, Adidas, Jordan, Vans, Converse, Puma...).
   - is_deleted (bool): Trạng thái xóa mềm (luôn lọc is_deleted = false).
   - status (bool): true là đang kinh doanh.

2. Bảng 'products' (Thông tin mẫu giày):
   - product_id (int, PK): Mã sản phẩm.
   - product_name (varchar): Tên mẫu giày (VD: Nike Air Force 1).
   - category_id (int, FK): Liên kết với bảng categories.
   - is_deleted (bool): Luôn lọc p.is_deleted = false.
   - image_embedding (vector(512)): Dùng cho tìm kiếm ảnh, không dùng cho SQL thông thường.

3. Bảng 'product_variants' (Biến thể chi tiết):
   - variant_id (int, PK): Mã biến thể.
   - product_id (int, FK): Liên kết bảng products.
   - size (varchar): Kích cỡ (38, 40, 42...).
   - color (varchar): Màu sắc (Black, White, Red...).
   - stock (int): Số lượng tồn kho hiện tại (Dùng SUM(stock) để trả lời về số lượng trong kho).
   - sku (varchar): Mã định danh (VD: NI-NAF1-BLA-42).
   - is_deleted (bool): Luôn lọc pv.is_deleted = false.

4. Bảng 'shelves' (Kệ kho):
   - shelf_id (int, PK): Mã kệ.
   - location_code (varchar): Vị trí kệ (VD: A-01).

5. Bảng 'inventory' (Vị trí hàng thực tế trên kệ):
   - variant_id, shelf_id, quantity (số lượng trên kệ cụ thể).

6. Bảng 'transactions' (Lịch sử Nhập/Xuất):
   - transaction_type: 'IMPORT' (Nhập), 'EXPORT' (Xuất/Bán).
   - quantity: Số lượng giao dịch.

7. Bảng 'users' (Nhân viên):
   - role: 'MANAGER' hoặc 'STAFF'.
"""

@app.get("/ask")
async def ask_ai(question: str = Query(...)):
    try:
        # Lấy thông tin Schema thực tế từ DB
        schema_info = db.get_table_info()

        # BƯỚC 2: SIÊU PROMPT TEXT-TO-SQL (ÉP AI DÙNG ALIAS ĐỂ TRÁNH LỖI AMBIGUOUS)
        sql_prompt = ChatPromptTemplate.from_template(f"""
        BẠN LÀ CHUYÊN GIA TRUY VẤN POSTGRESQL CHO HỆ THỐNG KHO GIÀY.
        Dựa trên cấu trúc Schema bên dưới:
        {{schema}}
        
        MÔ TẢ CHI TIẾT NGỮ NGHĨA:
        {DB_METADATA_DESC}

        QUY TẮC BẮT BUỘC KHI VIẾT SQL:
        1. LUÔN SỬ DỤNG ALIAS khi JOIN (VD: products p, product_variants pv, categories c).
        2. TRÁNH LỖI AmbiguousColumn: Các cột chung như 'is_deleted', 'product_id', 'variant_id' PHẢI đi kèm Alias (VD: p.is_deleted, pv.product_id).
        3. LUÔN LỌC 'is_deleted = false' cho tất cả các bảng tham gia truy vấn.
        4. TÌM KIẾM VĂN BẢN: Sử dụng ILIKE và dấu % (VD: p.product_name ILIKE '%nike%').
        5. TỒN KHO: Câu hỏi về "số lượng còn lại", "trong kho có bao nhiêu" -> SUM(pv.stock) từ bảng 'product_variants' pv.
        6. CHỈ TRẢ VỀ SQL DUY NHẤT, KHÔNG GIẢI THÍCH, KHÔNG DÙNG NHÁY NGƯỢC.

        CÂU HỎI: "{{input}}"
        SQL Query:
        """)
        
        # Chuỗi xử lý tạo SQL
        sql_chain = sql_prompt | llm | StrOutputParser()
        raw_output = sql_chain.invoke({"schema": schema_info, "input": question})
        
        # Làm sạch chuỗi SQL
        sql_query = clean_sql(raw_output)
        sql_query = sql_query.replace("%%", "%") # Khử lỗi dấu % của langchain
        
        print(f"--- 🛠 SQL THỰC THI: {sql_query}")

        # Thực thi lấy dữ liệu thật
        result_data = db.run(sql_query)
        print(f"--- 📊 DỮ LIỆU LẤY ĐƯỢC: {result_data}")

        # BƯỚC 4: AI TỔNG HỢP CÂU TRẢ LỜI
        answer_prompt = ChatPromptTemplate.from_template("""
        BẠN LÀ TRỢ LÝ KHO THÔNG MINH. 
        Dựa trên kết quả truy vấn từ Database: {result}
        Hãy trả lời câu hỏi của người dùng: "{input}" một cách tự nhiên và chính xác.

        Yêu cầu:
        - Trả lời bằng tiếng Việt lịch sự.
        - Nếu kết quả là [], báo là hiện không có thông tin này trong hệ thống.
        - Nếu kết quả có số, hãy nói rõ con số đó lấy từ hệ thống quản lý kho.
        """)
        
        answer_chain = answer_prompt | llm | StrOutputParser()
        final_answer = answer_chain.invoke({"result": result_data, "input": question})

        return {"status": "success", "answer": final_answer}

    except Exception as e:
        print(f"[ERROR]: {str(e)}")
        return {"status": "error", "message": "Dạ, em không tìm thấy dữ liệu liên quan. Bồ vui lòng hỏi rõ hơn tên sản phẩm hoặc thương hiệu nhé!"}

if __name__ == "__main__":
    print("--- 🚀 SMART WAREHOUSE OFFLINE AI IS READY ---")
    uvicorn.run(app, host="127.0.0.1", port=8000)