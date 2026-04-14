<?php
class WarehouseAIService {
    private $apiUrl = "http://localhost:8000/ask";

    public function askAI($question) {
        $data = json_encode(["query" => $question]);

        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data)
        ]);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            return "Lỗi kết nối AI Server: " . curl_error($ch);
        }
        curl_close($ch);

        $result = json_decode($response, true);
        return $result['answer'] ?? "AI không thể trả lời câu hỏi này.";
    }
}