# import os
# import re
# import uvicorn
# from fastapi import FastAPI, Query
# from langchain_ollama import ChatOllama
# from langchain_community.utilities import SQLDatabase
# from langchain_core.prompts import ChatPromptTemplate
# from langchain_core.output_parsers import StrOutputParser
# from fastapi.middleware.cors import CORSMiddleware
# from sqlalchemy import text

# app = FastAPI()
# app.add_middleware(
#     CORSMiddleware,
#     allow_origins=["*"], # Cho phép tất cả các nguồn gọi tới
#     allow_methods=["*"],
#     allow_headers=["*"],
# )

# # 1. KẾT NỐI DATABASE
# # Thay đổi thông tin nếu đổi user/pass database
# DB_URL = "postgresql://ai_user:12345@localhost:5432/shoe_warehouse_ai"
# db = SQLDatabase.from_uri(DB_URL)

# # 2. KHỞI TẠO BỘ NÃO AI (QWEN 2.5 CODER OFFLINE)
# # Đảm bảo bồ đã chạy: ollama pull qwen2.5-coder:7b
# llm = ChatOllama(model="qwen2.5-coder:7b-instruct-q4_K_M", temperature=0)

# def clean_sql(text: str) -> str:
#     """Dọn dẹp mã SQL: xóa markdown, xóa nháy ngược và ký tự thừa"""
#     text = re.sub(r"```sql|```", "", text)
#     text = text.replace("`", "")
#     return text.strip()

# # 3. MÔ TẢ CHI TIẾT CẤU TRÚC VÀ NGỮ NGHĨA CSDL (CỰC KỲ QUAN TRỌNG)
# DB_METADATA_DESC = """
# Hệ thống Quản lý Kho Giày (Smart Warehouse) có cấu trúc PostgreSQL chi tiết như sau:

# 1. Bảng 'categories' (Danh mục thương hiệu):
#    - category_id (int, PK): Mã danh mục.
#    - category_name (varchar): Tên thương hiệu (Nike, Adidas, Jordan, Vans, Converse, Puma...).
#    - is_deleted (bool): Trạng thái xóa mềm (luôn lọc is_deleted = false).
#    - status (bool): true là đang kinh doanh.

# 2. Bảng 'products' (Thông tin mẫu giày):
#    - product_id (int, PK): Mã sản phẩm.
#    - product_name (varchar): Tên mẫu giày (VD: Nike Air Force 1).
#    - category_id (int, FK): Liên kết với bảng categories.
#    - is_deleted (bool): Luôn lọc p.is_deleted = false.
#    - image_embedding (vector(512)): Dùng cho tìm kiếm ảnh, không dùng cho SQL thông thường.

# 3. Bảng 'product_variants' (Biến thể chi tiết):
#    - variant_id (int, PK): Mã biến thể.
#    - product_id (int, FK): Liên kết bảng products.
#    - size (varchar): Kích cỡ (38, 40, 42...).
#    - color (varchar): Màu sắc (Black, White, Red...).
#    - stock (int): Số lượng tồn kho hiện tại (Dùng SUM(stock) để trả lời về số lượng trong kho).
#    - sku (varchar): Mã định danh (VD: NI-NAF1-BLA-42).
#    - is_deleted (bool): Luôn lọc pv.is_deleted = false.

# 4. Bảng 'shelves' (Kệ kho):
#    - shelf_id (int, PK): Mã kệ.
#    - location_code (varchar): Vị trí kệ (VD: A-01).

# 5. Bảng 'inventory' (Vị trí hàng thực tế trên kệ):
#    - variant_id, shelf_id, quantity (số lượng trên kệ cụ thể).

# 6. Bảng 'transactions' (Lịch sử Nhập/Xuất):
#    - transaction_type: 'IMPORT' (Nhập), 'EXPORT' (Xuất/Bán).
#    - quantity: Số lượng giao dịch.
#    - variant_id: khóa ngoại liên kết đến bảng product_variants
#    - user_id: khóa ngoại liên kết đến bảng users
#    - created_at: thời gian thực hiện
#    - lưu lịch sử nhập kho, xuất kho của sản phẩm 

# 7. Bảng 'users' (Nhân viên):
#    - role: 'MANAGER' hoặc 'STAFF'.
#    - status (boolean): lưu trạng thái của nhận viên. True là tài khoản này đang hoạt động. False là tài khoản này đang tạm ngưng.

# 8. LỌC TỔNG (SUM, COUNT): Nếu câu hỏi có từ "dưới X đôi", "hơn X đôi", KHÔNG ĐƯỢC dùng SUM trong WHERE. Phải dùng GROUP BY (theo tên sản phẩm) và dùng HAVING SUM(...) < X.

# 9. LUÔN GROUP BY: Khi sử dụng SUM() để tính tổng cho từng mẫu giày, phải GROUP BY product_name hoặc product_id.

# 10. VIỆT HÓA TÊN CỘT: Khi dùng SELECT lấy nhiều cột, HÃY SỬ DỤNG ALIAS BẰNG TIẾNG VIỆT (không dấu) cho dễ đọc. 
#             Ví dụ: SELECT pv.size AS Kich_Co, pv.color AS Mau_Sac, pv.stock AS Ton_Kho ...
# 11. Hãy nhớ cấu hình DB: products (product_id) -> product_variants (product_id, variant_id) -> transactions (variant_id). Luôn phải JOIN qua bảng trung gian product_variants.

# """

# @app.get("/ask")
# async def ask_ai(question: str = Query(...)):
#     try:
#         # Lấy thông tin Schema thực tế từ DB
#         schema_info = db.get_table_info()

#       #   # BƯỚC 2: SIÊU PROMPT TEXT-TO-SQL (ÉP AI DÙNG ALIAS ĐỂ TRÁNH LỖI AMBIGUOUS)
#       #   sql_prompt = ChatPromptTemplate.from_template(f"""
#       #   BẠN LÀ CHUYÊN GIA TRUY VẤN POSTGRESQL CHO HỆ THỐNG KHO GIÀY.
#       #   Dựa trên cấu trúc Schema bên dưới:
#       #   {{schema}}
        
#       #   MÔ TẢ CHI TIẾT NGỮ NGHĨA:
#       #   {DB_METADATA_DESC}

#       #   QUY TẮC BẮT BUỘC KHI VIẾT SQL:
#       #   1. LUÔN SỬ DỤNG ALIAS khi JOIN (VD: products p, product_variants pv, categories c).
#       #   2. NGHIÊM CẤM LỖI AmbiguousColumn: Tuyệt đối KHÔNG viết trống không tên cột nếu nó có ở nhiều bảng (như 'is_deleted', 'product_id'). BẮT BUỘC phải gắn Alias ở trước (VD: p.is_deleted, pv.product_id).
#       #   3. QUY TẮC XÓA MỀM (SOFT DELETE): Khi truy vấn lấy dữ liệu từ bảng nào, BẮT BUỘC phải có điều kiện lọc "alias.is_deleted = false" tương ứng cho bảng đó trong mệnh đề WHERE (VD: Nếu dùng bảng products thì phải có p.is_deleted = false; nếu dùng cả products và categories thì phải có p.is_deleted = false AND c.is_deleted = false).
#       #   4. TÌM KIẾM VĂN BẢN: Sử dụng ILIKE và dấu % (VD: p.product_name ILIKE '%nike%').
#       #   5. PHÂN BIỆT RÕ 2 LOẠI KIỂM TRA TỒN KHO:
#       #      - LOẠI 1 (Tính tổng): Nếu khách hỏi "Tổng số lượng giày Nike là bao nhiêu?" -> BẮT BUỘC dùng SUM(pv.stock) kết hợp GROUP BY.
#       #      - LOẠI 2 (Cảnh báo hàng sắp hết/Lọc chi tiết): Nếu khách hỏi tìm giày "dưới X đôi", "ít hơn X", "trên X đôi" -> TUYỆT ĐỐI KHÔNG DÙNG SUM() VÀ GROUP BY. Bắt buộc dùng mệnh đề WHERE trực tiếp: "pv.stock < X" hoặc "pv.stock > X" để lấy ra chính xác biến thể đang thỏa mãn.
#       #   6. CHỈ TRẢ VỀ SQL DUY NHẤT, KHÔNG GIẢI THÍCH, KHÔNG DÙNG NHÁY NGƯỢC.

#       #   7. TỐI ƯU HÓA CỘT TRẢ VỀ (MINIMAL SELECT): 
#       #      - Phân tích NGỮ NGHĨA câu hỏi để tự suy luận ra những cột CẦN THIẾT NHẤT. Tuyệt đối KHÔNG SELECT thừa dữ liệu không được yêu cầu.
#       #      - Nếu câu hỏi mang tính chất "tổng quát" (VD: liệt kê mẫu, đếm số lượng, tìm cái nào sắp hết): Chỉ SELECT cột định danh chính (VD: tên) và cột giá trị gộp (VD: SUM).
#       #      - Nếu câu hỏi có chứa các từ khóa yêu cầu "chi tiết", "thuộc tính", "phân loại" (VD: size nào, màu gì, mã bao nhiêu): Mới SELECT thêm các cột chi tiết tương ứng.
         
         
#       #   CÂU HỎI: "{{input}}"
#       #   SQL Query:
#       #   """)

#       # BƯỚC 2: SIÊU PROMPT TEXT-TO-SQL (PHIÊN BẢN CHỐNG LỖI TOÀN DIỆN)
#         sql_prompt = ChatPromptTemplate.from_template(f"""
#         BẠN LÀ CHUYÊN GIA TRUY VẤN POSTGRESQL CHO HỆ THỐNG KHO GIÀY.
#         Nhiệm vụ của bạn là dịch câu hỏi của người dùng thành một câu lệnh SQL duy nhất, chính xác và không có lỗi.

#         THÔNG TIN CƠ SỞ DỮ LIỆU (SCHEMA):
#         {{schema}}
        
#         MÔ TẢ NGỮ NGHĨA VÀ LIÊN KẾT BẢNG (SCHEMA METADATA):
#         {DB_METADATA_DESC}

#         ====================================================================
#         BỘ QUY TẮC SQL TỐI THƯỢNG (BẮT BUỘC TUÂN THỦ 100% ĐỂ TRÁNH LỖI):
#         ====================================================================

#         1. QUY TẮC BẢNG TRUNG GIAN (LIÊN KẾT RÀNG BUỘC) - [CHỐNG LỖI JOIN]:
#            - Bảng `products` (Sản phẩm gốc) KHÔNG BAO GIỜ liên kết trực tiếp với `transactions` (Giao dịch) hoặc `inventory` (Tồn kho thực tế).
#            - MỌI LIÊN KẾT từ `products` đến `transactions` hoặc `inventory` BẮT BUỘC phải đi qua bảng trung gian `product_variants`.
#            - Cú pháp JOIN chuẩn mực (Không bao giờ làm khác):
#              FROM products p
#              JOIN product_variants pv ON p.product_id = pv.product_id
#              [Tùy chọn: JOIN transactions t ON pv.variant_id = t.variant_id]
#              [Tùy chọn: JOIN inventory i ON pv.variant_id = i.variant_id]

#         2. QUY TẮC AN TOÀN DỮ LIỆU (ALIAS) - [CHỐNG LỖI AMBIGUOUS COLUMN]:
#            - TẤT CẢ các cột xuất hiện trong SELECT, WHERE, GROUP BY, ORDER BY, HAVING đều PHẢI có Alias đi kèm (VD: p.product_name, pv.stock, t.created_at). 
#            - NGHIÊM CẤM gọi tên cột trống không (VD: KHÔNG dùng `is_deleted`, PHẢI dùng `p.is_deleted` hoặc `pv.is_deleted`).

#         3. QUY TẮC XÓA MỀM (SOFT DELETE) - [CHỐNG LỖI DỮ LIỆU RÁC]:
#            - Mọi bảng có cột `is_deleted` khi xuất hiện trong phần `FROM` hoặc `JOIN` đều BẮT BUỘC phải kèm điều kiện lọc: `[alias].is_deleted = false` trong mệnh đề WHERE.
#            - Ví dụ: `WHERE p.is_deleted = false AND pv.is_deleted = false`.

#         4. QUY TẮC XỬ LÝ TỒN KHO VÀ SỐ LƯỢNG - [CHỐNG LỖI LOGIC TOÁN HỌC]:
#            - Cột `stock` trong bảng `product_variants` là số lượng tồn kho HIỆN TẠI.
#            - [TÍNH TỔNG]: Để biết "Tổng số lượng giày A là bao nhiêu?" -> BẮT BUỘC dùng SUM(pv.stock) kết hợp GROUP BY p.product_name.
#            - [LỌC ĐIỀU KIỆN]: Để tìm "Giày nào còn dưới 5 đôi?" -> KHÔNG DÙNG SUM. BẮT BUỘC dùng mệnh đề WHERE: `pv.stock < 5`.

#         5. QUY TẮC PHÂN LOẠI THỜI GIAN VÀ TRẠNG THÁI - [CHỐNG LỖI NGỮ NGHĨA]:
#            - "Mới thêm/Nhập gần đây": Lọc `t.transaction_type = 'IMPORT'`, kết hợp `ORDER BY t.created_at DESC LIMIT X`.
#            - "Bán chạy/Xuất nhiều": Lọc `t.transaction_type = 'EXPORT'`, sử dụng `SUM(t.quantity)` để tính tổng bán, kết hợp `ORDER BY SUM(t.quantity) DESC LIMIT X`.
#            - Bắt buộc áp dụng Quy tắc 1 để JOIN bảng `transactions` trước khi dùng điều kiện này.

#         6. QUY TẮC TÌM KIẾM VĂN BẢN - [CHỐNG LỖI CHỮ HOA/THƯỜNG]:
#            - Mọi tìm kiếm chuỗi (Tên giày, Hãng, Màu sắc) PHẢI dùng toán tử `ILIKE` thay vì `=` để không phân biệt hoa thường.
#            - Ví dụ: `p.product_name ILIKE '%nike%'`.

#         7. QUY TẮC ĐẶT TÊN CỘT ĐẦU RA (VIỆT HÓA ALIAS) - [GIAO DIỆN THÂN THIỆN]:
#            - Khi SELECT, hãy sử dụng `AS` để đặt tên tiếng Việt (không dấu, dùng gạch dưới) cho cột để giao diện hiển thị đẹp.
#            - Ví dụ: `SELECT p.product_name AS Ten_Giay, pv.size AS Kich_Co, SUM(t.quantity) AS Tong_Da_Ban`.

#         8. QUY TẮC TỐI ƯU ĐẦU RA (MINIMAL SELECT) - [CHỐNG QUÁ TẢI DATA]:
#            - Chỉ SELECT những cột thực sự cần thiết để trả lời câu hỏi. KHÔNG sử dụng `SELECT *`.
#            - Nếu hỏi "tổng số", chỉ trả về Tên và Cột Tổng.
#            - Chỉ trả về DUY NHẤT 1 câu lệnh SQL thuần túy. KHÔNG có markdown (```sql), KHÔNG có lời giải thích.

#         ====================================================================
        
#         CÂU HỎI TỪ NGƯỜI DÙNG: "{{input}}"
        
#         KẾT QUẢ SQL QUERY:
#         """)
        
#         # Chuỗi xử lý tạo SQL
#         sql_chain = sql_prompt | llm | StrOutputParser()
#         raw_output = sql_chain.invoke({"schema": schema_info, "input": question})
        
#         # Làm sạch chuỗi SQL
#         sql_query = clean_sql(raw_output)
#         sql_query = sql_query.replace("%%", "%") # Khử lỗi dấu % của langchain
        
#         print(f"--- 🛠 SQL THỰC THI: {sql_query}")

#         # Thực thi lấy dữ liệu thật
#         result_data = db.run(sql_query)
#       # print(f"--- 🛠 SQL THỰC THI: {sql_query}")

#         # ====================================================
#         # ĐOẠN SỬA MỚI: LẤY ĐỘNG TÊN CỘT VÀ DỮ LIỆU TỪ DATABASE
#         # ====================================================
#         cleaned_items = []
        
#         # Kết nối thẳng vào Engine của SQLAlchemy để lấy cả Header (Tên cột)
#         with db._engine.connect() as conn:
#             result = conn.execute(text(sql_query))
#             columns = result.keys()  # Lấy tên cột (VD: 'fullname', 'phone', 'size'...)
#             rows = result.fetchall() # Lấy danh sách các dòng dữ liệu
            
#             for row in rows:
#                 if len(columns) == 1:
#                     # Nếu truy vấn chỉ có 1 cột (VD: chỉ lấy tên giày)
#                     cleaned_items.append(f"• {row[0]}")
#                 else:
#                     # Nếu có nhiều cột, tự động ghép động: Tên cột: Giá trị
#                     # zip(columns, row) sẽ bắt cặp: ('size', '42'), ('color', 'Black')...
#                     row_details = " | ".join([f"{col}: <b>{val}</b>" for col, val in zip(columns, row)])
#                     cleaned_items.append(f"• {row_details}")

#         # Lọc trùng lặp nhưng vẫn giữ đúng thứ tự
#         unique_items = list(dict.fromkeys(cleaned_items))
        
#         # Nối bằng thẻ <br> để xuống dòng trên giao diện Web
#         clean_data = "<br>" + "<br>".join(unique_items)
        
#         print(f"--- ✨ DỮ LIỆU ĐÃ LÀM SẠCH:\n{clean_data}")

#         # ====================================================
#         # BƯỚC 4 MỚI: CHẶN LOGIC BẰNG PYTHON & PROMPT TỐI GIẢN
#         # ====================================================
#         if not clean_data or clean_data == "[]" or clean_data.strip() == "":
#             return {"status": "success", "answer": "Dạ, hiện tại mình kiểm tra thì hệ thống không có dữ liệu khớp với yêu cầu của bạn nhé!"}
        

#         # 2. ĐƯA CHO AI PROMPT ÉP KHUÔN (KHÔNG CHO TỰ NGHĨ)
#         answer_prompt = ChatPromptTemplate.from_template("""
#         THÔNG TIN TỪ KHO: {result}
#         CÂU HỎI CỦA KHÁCH: "{input}"

#         LUẬT TỐI THƯỢNG (BẮT BUỘC TUÂN THỦ):
#         1. [THÔNG TIN TỪ KHO] ở trên ĐÃ ĐƯỢC LỌC CHÍNH XÁC 100% cho câu hỏi của khách. Bạn KHÔNG CẦN tìm con số để chứng minh.
#         2. Bạn BẮT BUỘC phải bê nguyên [THÔNG TIN TỪ KHO] vào câu trả lời của mình.
#         3. Hãy trả lời theo ĐÚNG MẪU SAU (chỉ việc thay thế chữ):
        
#         "Dạ, theo hệ thống ghi nhận, thông tin bạn cần tìm bao gồm: {result}.  Bạn có cần mình hỗ trợ gì thêm không ạ?"

#         4. TUYỆT ĐỐI KHÔNG ĐƯỢC xin lỗi. TUYỆT ĐỐI KHÔNG ĐƯỢC bảo là không biết hay không tìm thấy số lượng.
#         """)
        
#         # Bồ chú ý dòng này giữ nguyên nhé, biến truyền vào là 'result'
#         answer_chain = answer_prompt | llm | StrOutputParser()
#         final_answer = answer_chain.invoke({"result": clean_data, "input": question})

#         final_answer_beauty = final_answer.replace(" Bạn có cần", "<br><br>Bạn có cần")
        
#         return {"status": "success", "answer": final_answer_beauty}
#     except Exception as e:
#         print(f"[ERROR]: {str(e)}")
#         return {"status": "error", "message": "Dạ, mình không tìm thấy dữ liệu liên quan. Bạn vui lòng hỏi rõ hơn tên sản phẩm hoặc thương hiệu nhé!"}

# if __name__ == "__main__":
#     print("--- 🚀 SMART WAREHOUSE OFFLINE AI IS READY ---")
#     uvicorn.run(app, host="127.0.0.1", port=8000)



import os
import re
import uvicorn
from fastapi import FastAPI, Query
from langchain_ollama import ChatOllama
from langchain_community.utilities import SQLDatabase
from langchain_core.prompts import ChatPromptTemplate
from langchain_core.output_parsers import StrOutputParser
from fastapi.middleware.cors import CORSMiddleware
from sqlalchemy import text

app = FastAPI()
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

# 1. KẾT NỐI DATABASE
DB_URL = "postgresql://ai_user:12345@localhost:5432/shoe_warehouse_ai"
db = SQLDatabase.from_uri(DB_URL)

# 2. KHỞI TẠO BỘ NÃO AI (Bản Instruct nén Q4_K_M)
llm = ChatOllama(model="qwen2.5-coder:7b-instruct-q4_K_M", temperature=0)

def clean_sql(text: str) -> str:
    """Dọn dẹp mã SQL: xóa markdown, xóa nháy ngược và ký tự thừa"""
    text = re.sub(r"```sql|```", "", text)
    text = text.replace("`", "")
    return text.strip()

# 3. MÔ TẢ CHI TIẾT CẤU TRÚC VÀ NGỮ NGHĨA CSDL 
DB_METADATA_DESC = """
Hệ thống Quản lý Kho Giày (Smart Warehouse) có cấu trúc PostgreSQL chi tiết như sau:

1. Bảng 'categories' (Danh mục thương hiệu):
   - category_id (int, PK): Mã danh mục.
   - category_name (varchar): Tên thương hiệu.
   - is_deleted (bool): Trạng thái xóa mềm.

2. Bảng 'products' (Thông tin mẫu giày):
   - product_id (int, PK): Mã sản phẩm.
   - product_name (varchar): Tên mẫu giày (VD: Nike Air Force 1).
   - category_id (int, FK): Liên kết với bảng categories.
   - is_deleted (bool): Luôn lọc p.is_deleted = false.

3. Bảng 'product_variants' (Biến thể chi tiết):
   - variant_id (int, PK): Mã biến thể.
   - product_id (int, FK): Liên kết bảng products.
   - size (varchar): Kích cỡ.
   - color (varchar): Màu sắc.
   - stock (int): Số lượng tồn kho hiện tại (Dùng SUM(stock)).
   - sku (varchar): Mã định danh.
   - is_deleted (bool): Luôn lọc pv.is_deleted = false.

4. Bảng 'transactions' (Lịch sử Nhập/Xuất):
   - transaction_type: 'IMPORT' (Nhập), 'EXPORT' (Xuất/Bán).
   - quantity: Số lượng giao dịch.
   - variant_id: khóa ngoại liên kết đến bảng product_variants
   - created_at: thời gian thực hiện
"""

@app.get("/ask")
async def ask_ai(question: str = Query(...)):
    try:
        # Lấy thông tin Schema thực tế từ DB
        schema_info = db.get_table_info()

        # BƯỚC 1: SIÊU PROMPT TEXT-TO-SQL (PHIÊN BẢN CHỐNG LỖI TOÀN DIỆN 2.0)
        sql_prompt = ChatPromptTemplate.from_template(f"""
        BẠN LÀ CHUYÊN GIA TRUY VẤN POSTGRESQL CHO HỆ THỐNG KHO GIÀY.
        Nhiệm vụ của bạn là dịch câu hỏi của người dùng thành một câu lệnh SQL duy nhất, chính xác và không có lỗi.

        THÔNG TIN CƠ SỞ DỮ LIỆU (SCHEMA):
        {{schema}}
        
        MÔ TẢ NGỮ NGHĨA VÀ LIÊN KẾT BẢNG (SCHEMA METADATA):
        {DB_METADATA_DESC}

        ====================================================================
        BỘ QUY TẮC SQL TỐI THƯỢNG (BẮT BUỘC TUÂN THỦ 100% ĐỂ TRÁNH LỖI):
        ====================================================================

        1. QUY TẮC BẢNG TRUNG GIAN (LIÊN KẾT RÀNG BUỘC):
           - Bảng `products` KHÔNG BAO GIỜ liên kết trực tiếp với `transactions`.
           - MỌI LIÊN KẾT từ `products` đến `transactions` BẮT BUỘC phải đi qua bảng `product_variants`.
           - Cú pháp JOIN chuẩn mực (Không bao giờ làm khác):
             FROM products p
             JOIN product_variants pv ON p.product_id = pv.product_id
             [Tùy chọn: JOIN transactions t ON pv.variant_id = t.variant_id]

        2. QUY TẮC ALIAS & TÊN CỘT (CHỐNG LỖI CÚ PHÁP):
           - TẤT CẢ các cột xuất hiện trong câu SQL đều PHẢI có Alias bảng đi kèm (VD: p.product_name, pv.stock). 
           - [QUAN TRỌNG NHẤT]: Khi đặt tên tiếng Việt cho cột bằng AS (Alias Output), TUYỆT ĐỐI KHÔNG SỬ DỤNG KHOẢNG TRẮNG. BẮT BUỘC dùng dấu gạch dưới `_`. 
             Ví dụ ĐÚNG: `AS Thoi_Gian_Nhap`, `AS Ten_Giay`. 
             Ví dụ SAI: `AS Thoi Gian Nhap` (Lỗi Syntax).

        3. QUY TẮC XÓA MỀM (SOFT DELETE):
           - Mọi bảng có cột `is_deleted` khi xuất hiện trong câu lệnh đều BẮT BUỘC phải lọc: `[alias].is_deleted = false` trong mệnh đề WHERE.

        4. QUY TẮC XỬ LÝ TỒN KHO VÀ SỐ LƯỢNG:
           - Cột `stock` là số lượng tồn kho HIỆN TẠI.
           - [TÍNH TỔNG]: BẮT BUỘC dùng SUM(pv.stock) kết hợp GROUP BY p.product_name.
           - [LỌC ĐIỀU KIỆN]: Để tìm "Giày nào còn dưới 5 đôi?" -> BẮT BUỘC dùng mệnh đề WHERE: `pv.stock < 5` (Không dùng SUM).

        5. QUY TẮC PHÂN LOẠI THỜI GIAN VÀ TRẠNG THÁI:
           - "Mới thêm/Nhập gần đây": Lọc `t.transaction_type = 'IMPORT'`, kết hợp `ORDER BY t.created_at DESC LIMIT X`.
           - "Bán chạy/Xuất nhiều": Lọc `t.transaction_type = 'EXPORT'`, kết hợp `ORDER BY SUM(t.quantity) DESC LIMIT X`.

        6. QUY TẮC TÌM KIẾM VĂN BẢN:
           - Mọi tìm kiếm chuỗi (Tên giày, Hãng) PHẢI dùng toán tử `ILIKE` thay vì `=`.

        7. TỐI ƯU ĐẦU RA (MINIMAL SELECT):
           - Chỉ trả về DUY NHẤT 1 câu lệnh SQL thuần túy. KHÔNG có markdown (```sql), KHÔNG có lời giải thích.

        ====================================================================
        
        CÂU HỎI TỪ NGƯỜI DÙNG: "{{input}}"
        
        KẾT QUẢ SQL QUERY:
        """)
        
        # Chuỗi xử lý tạo SQL
        sql_chain = sql_prompt | llm | StrOutputParser()
        raw_output = sql_chain.invoke({"schema": schema_info, "input": question})
        
        # Làm sạch chuỗi SQL
        sql_query = clean_sql(raw_output)
        sql_query = sql_query.replace("%%", "%") 
        
        print(f"--- 🛠 SQL THỰC THI: {sql_query}")

        # BƯỚC 2: THỰC THI LẤY DỮ LIỆU
        cleaned_items = []
        
        with db._engine.connect() as conn:
            result = conn.execute(text(sql_query))
            columns = result.keys()  
            rows = result.fetchall() 
            
            for row in rows:
                if len(columns) == 1:
                    cleaned_items.append(f"• {row[0]}")
                else:
                    row_details = " | ".join([f"{col}: <b>{val}</b>" for col, val in zip(columns, row)])
                    cleaned_items.append(f"• {row_details}")

        unique_items = list(dict.fromkeys(cleaned_items))
        clean_data = "<br>" + "<br>".join(unique_items)
        
        print(f"--- ✨ DỮ LIỆU ĐÃ LÀM SẠCH:\n{clean_data}")

        # BƯỚC 3: TRẢ LỜI NGAY LẬP TỨC (KHÔNG GỌI LLM LẦN 2)
        if not clean_data or clean_data == "[]" or clean_data.strip() == "":
            return {"status": "success", "answer": "Dạ, hiện tại mình kiểm tra thì hệ thống không có dữ liệu khớp với yêu cầu của bạn nhé!"}
        
        final_answer = f"Dạ, theo hệ thống ghi nhận, thông tin bạn cần tìm bao gồm: {clean_data} <br><br>Bạn có cần mình hỗ trợ gì thêm không ạ?"
        
        return {"status": "success", "answer": final_answer}

    except Exception as e:
        print(f"[ERROR]: {str(e)}")
        return {"status": "error", "message": "Dạ, mình không tìm thấy dữ liệu liên quan. Bạn vui lòng hỏi rõ hơn tên sản phẩm hoặc thương hiệu nhé!"}

if __name__ == "__main__":
    print("--- 🚀 SMART WAREHOUSE OFFLINE AI IS READY ---")
    uvicorn.run(app, host="127.0.0.1", port=8000)