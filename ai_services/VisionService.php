<?php
// app/ai_services/VisionService.php

class VisionService
{
    /**
     * Chạy Python ngầm định để chuyển đổi Ảnh thành Vector AI 512 chiều
     */
    public function generateVector($imagePath)
    {
        // Đường dẫn tuyệt đối của Python (Đảm bảo dùng đúng path bồ đã test ở CMD)
        $pythonPath = "python";
        $pythonScript = __DIR__ . '/generate_vector.py';

        // Chuyển đường dẫn ảnh sang đường dẫn tuyệt đối để Python không bị lạc đường
        $fullImagePath = realpath($imagePath);

        if (!$fullImagePath || !file_exists($fullImagePath)) {
            return ["status" => "error", "message" => "Không tìm thấy ảnh tại: " . $imagePath];
        }

        // Lệnh thực thi: python script.py path_anh 2>&1 (để bắt cả lỗi nếu có)
        $command = "python " . escapeshellarg($pythonScript) . " " . escapeshellarg($fullImagePath) . " 2>&1";
        $output = shell_exec($command);

        // Chạy lệnh
        $output = shell_exec($command);

        if ($output === null || empty(trim($output))) {
            return ["status" => "error", "message" => "Lỗi thực thi: Không nhận được phản hồi từ AI service."];
        }

        // --- BỘ LỌC THẦN THÁNH: REGEX ---
        // Tìm đoạn nằm trong dấu ngoặc nhọn { ... } ở cuối chuỗi output
        // giúp loại bỏ các dòng Warning, Loading weights của Python
        if (preg_match('/\{.*\}/s', $output, $matches)) {
            $cleanJson = $matches[0];
            $result = json_decode($cleanJson, true);

            if (json_last_error() === JSON_ERROR_NONE && isset($result['status'])) {
                if ($result['status'] === 'success') {
                    return [
                        "status" => "success",
                        "vector" => $result['vector'] // Trả về mảng 512 số
                    ];
                } else {
                    return ["status" => "error", "message" => "AI Lỗi: " . ($result['message'] ?? 'Không rõ lý do')];
                }
            }
        }

        // Nếu không lọc được JSON hoặc JSON lỗi, trả về toàn bộ output để debug
        return ["status" => "error", "message" => "AI trả về dữ liệu rác: " . substr($output, 0, 200) . "..."];
    }
}