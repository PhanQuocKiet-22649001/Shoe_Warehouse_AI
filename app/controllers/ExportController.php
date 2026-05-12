<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../models/ExportModel.php';

class ExportController
{
    private $model;

    public function __construct()
    {
        $this->model = new ExportModel();
        date_default_timezone_set('Asia/Ho_Chi_Minh');
    }

    /**
     * Chức năng: Kêu gọi thiết bị Manager F5 màu của Card Giao Việc.
     * Tác dụng: Push realtime status.
     */
    private function triggerManagerSync($ticket_id, $status, $completed_at = null)
    {
        try {
            $options = ['cluster' => 'ap1', 'useTLS' => true];
            $pusher = new Pusher\Pusher('24a79cb74cfa666e1831', '4cb0f10dc4e59d30d062', '2150978', $options);
            $payload = ['ticket_id' => $ticket_id, 'status' => $status];
            if ($completed_at) {
                $payload['completed_at'] = $completed_at;
            }
            $pusher->trigger('warehouse-channel', 'ticket-status-changed', $payload);
        } catch (Exception $e) {}
    }

    /** Chức năng: Đổ mảng Xuất kho cho Javascript */
    public function getMyExportsAjax()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        $staff_id = $_SESSION['user_id'];
        $data = $this->model->getMyExports($staff_id);
        echo json_encode($data);
        exit;
    }

    /** Chức năng: Update + số lượng hoàn tất thủ công */
    public function updateExportProgressAjax()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        $detail_id = $_POST['detail_id'];
        $picked_qty = (int)$_POST['picked_qty'];

        if ($this->model->updateExportProgress($detail_id, $picked_qty)) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Lỗi cập nhật CSDL']);
        }
        exit;
    }

    /** Chức năng: Dò map Layout Kệ theo mã Giày */
    public function getLocationsAjax()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        $variant_id = $_GET['variant_id'];
        $data = $this->model->getLocationsFromJSON($variant_id);
        echo json_encode($data);
        exit;
    }

    /** Chức năng: Dứt điểm quá trình thu hồi phiếu. Hoàn tất trừ mảng trên layout. */
    public function completeExportAjax()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        $ticket_id = $_REQUEST['ticket_id'] ?? 0;

        if ($ticket_id > 0) {
            if ($this->model->completeExportTicket($ticket_id)) {
                $completed_at = date('d/m/Y H:i');
                $this->triggerManagerSync($ticket_id, 'COMPLETED', $completed_at);
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Lỗi trừ kho thực tế.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Mã phiếu không hợp lệ.']);
        }
        exit;
    }
}