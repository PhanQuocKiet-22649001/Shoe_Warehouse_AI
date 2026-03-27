<?php
// app/ai_services/VisionService.php

class VisionService
{
    private $apiKeys = [];
    private $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=";

    public function __construct()
    {
        // 1. Khai báo đường dẫn tới file .env (Tính từ thư mục hiện tại lùi ra ngoài thư mục gốc)
        // Nếu file .env ở ngoài cùng thư mục Shoe_Warehouse, bồ có thể cần chỉnh lại '../../.env' cho khớp.
        $envPath = __DIR__ . '/../.env'; 

        // 2. Đọc file .env bằng PHP thuần
        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                // Bỏ qua các dòng comment có dấu #
                if (strpos(trim($line), '#') === 0) continue;
                
                // Tách tên biến và giá trị
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                // Xóa khoảng trắng và dấu ngoặc kép ở giá trị
                $value = trim($value, " \t\n\r\0\x0B\"'"); 

                if ($name === 'GEMINI_API_KEYS') {
                    // Cắt chuỗi thành mảng các keys bằng dấu phẩy
                    $this->apiKeys = array_map('trim', explode(',', $value));
                }
            }
        }

        // Báo lỗi ngay nếu chưa cấu hình Key
        if (empty($this->apiKeys)) {
            die(json_encode(["status" => "error", "message" => "Hệ thống thiếu API Key. Vui lòng kiểm tra file .env"]));
        }
    }

    public function analyzeProductImage($imagePath)
    {
        $randomKey = $this->apiKeys[array_rand($this->apiKeys)];
        $fullUrl = $this->apiUrl . $randomKey;

        if (!file_exists($imagePath)) {
            return ["status" => "error", "message" => "Không tìm thấy ảnh tại: " . $imagePath];
        }

        $imageData = base64_encode(file_get_contents($imagePath));

        // Nhận diện đuôi ảnh động (Hỗ trợ PNG, WEBP, JPG)
        $mimeType = mime_content_type($imagePath);
        if (!$mimeType) $mimeType = "image/jpeg";

        // Prompt ép AI nhả đúng cấu trúc (Đã sửa lại "name" thành "model" cho khớp logic PHP)
        $prompt = "Bạn là một API trích xuất dữ liệu ảnh giày tự động. Nhiệm vụ của bạn là phân tích ảnh và CHỈ xuất ra dữ liệu định dạng JSON nguyên thủy.
TUYỆT ĐỐI KHÔNG sử dụng thẻ markdown (không có ```json).
TUYỆT ĐỐI KHÔNG thêm lời chào, phần giải thích, hay bất kỳ ký tự nào ngoài cấu trúc JSON.

Cấu trúc JSON bắt buộc:
{
    \"status\": \"success\",
    \"brand\": \"Tên hãng (Ví dụ: Nike, Puma, Converse, MLB)\",
    \"model\": \"Tên đôi giày\",
    \"color\": \"Màu sắc chủ đạo bằng TIẾNG ANH (Ví dụ: Black, White, Red, Blue)\"
}";
        $data = [
            "contents" => [[
                "parts" => [
                    ["text" => $prompt],
                    ["inline_data" => ["mime_type" => $mimeType, "data" => $imageData]]
                ]
            ]],
            "generationConfig" => [
                "temperature" => 0.1,
                "maxOutputTokens" => 800
            ]
        ];

        $ch = curl_init($fullUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 90);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return ["status" => "error", "message" => "Lỗi cURL: " . $err];
        }

        $result = json_decode($response, true);

        // NẾU MÃ TRẢ VỀ LÀ 200 (THÀNH CÔNG)
        if ($httpCode === 200) {
            if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                $rawText = $result['candidates'][0]['content']['parts'][0]['text'];

                // DÙNG REGEX "MÓC" JSON
                if (preg_match('/\{[\s\S]*\}/', $rawText, $matches)) {
                    $cleanJson = $matches[0];
                    $finalData = json_decode($cleanJson, true);

                    if (json_last_error() === JSON_ERROR_NONE && $finalData) {
                        return [
                            "status" => "success",
                            "brand" => $finalData['brand'] ?? 'Unknown',
                            "model" => $finalData['model'] ?? 'Unknown',
                            "color" => $finalData['color'] ?? 'Unknown'
                        ];
                    }
                }

                return ["status" => "error", "message" => "AI trả về nội dung không đọc được: " . $rawText];
            } else {
                $finishReason = $result['candidates'][0]['finishReason'] ?? 'Không xác định';
                return ["status" => "error", "message" => "Google từ chối phân tích ảnh này. (Lý do: $finishReason)"];
            }
        }

        $msg = $result['error']['message'] ?? "Lỗi không xác định (Code: $httpCode)";
        return ["status" => "error", "message" => "Google báo lỗi: " . $msg];
    }
}