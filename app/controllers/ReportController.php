<?php
// app/controllers/ReportController.php

require_once __DIR__ . '/../models/ReportModel.php';

/**
 * Controller xử lý các yêu cầu liên quan đến báo cáo và thống kê
 * Chịu trách nhiệm điều phối dữ liệu từ Model sang View cho Dashboard và Report
 */
class ReportController
{
    private $reportModel;

    /**
     * Hàm khởi tạo: Kiểm tra quyền truy cập cơ bản và khởi tạo Model
     */
    public function __construct()
    {
        // Kiểm tra nếu người dùng chưa đăng nhập thì đẩy về trang login
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=login");
            exit;
        }
        $this->reportModel = new ReportModel();
    }

    /**
     * Chức năng: Xử lý dữ liệu cho trang Dashboard chính
     * Phân quyền: 
     * - Staff: Chỉ xem được các con số thống kê cơ bản.
     * - Manager: Xem được thêm xu hướng tháng, sản phẩm bán chạy và heatmap.
     */
    public function index()
    {
        $role = strtoupper($_SESSION['role'] ?? '');

        // Dữ liệu tối thiểu cho cả Staff và Manager (KPIs tổng quát)
        $data = [
            'stats' => $this->reportModel->getGeneralStats(),
        ];

        // Nếu là Manager, lấy thêm dữ liệu phân tích chuyên sâu cho Dashboard
        if ($role === 'MANAGER') {
            // Lấy top 4 sản phẩm bán chạy (Top Selling)
            $data['top_selling']   = $this->reportModel->getTopSelling(4);
            // Lấy dữ liệu hoạt động biến thể (Heatmap)
            $data['heatmap']       = $this->reportModel->getHeatmapData();
            // Lấy xu hướng nhập xuất theo tháng (Monthly Trend)
            $data['monthly_trend'] = $this->reportModel->getMonthlyTrend();
        }

        return $data;
    }

    /**
     * Chức năng: Xử lý dữ liệu cho trang "Báo Cáo Chi Tiết"
     * Phân quyền: Chỉ dành cho MANAGER.
     * Dữ liệu bao gồm: Biểu đồ ngày, biểu đồ sản phẩm, biểu đồ nhân viên và bảng kê tồn kho chi tiết.
     */
    public function statistics()
    {
        // Bảo vệ route: Chỉ Manager mới được truy cập vào trang thống kê chi tiết
        if (strtoupper($_SESSION['role'] ?? '') !== 'MANAGER') {
            header("Location: index.php?page=dashboard");
            exit;
        }

        // Lấy tham số lọc từ URL (nếu có)
        $start_date = $_GET['start_date'] ?? null;
        $end_date = $_GET['end_date'] ?? null;

        // Tập hợp toàn bộ dữ liệu cần thiết cho trang View Report
        return [
            // Thống kê tổng hợp (có thể lọc theo ngày)
            'all_stats'     => $this->reportModel->getGeneralStats($start_date, $end_date),
            
            // Top 5 ngày có lưu lượng giao dịch lớn nhất (vẽ biểu đồ cột đôi)
            'top_5_days'    => $this->reportModel->getTop5BusiestDays(),
            
            // Top 5 sản phẩm có biến động nhập/xuất cao nhất (vẽ biểu đồ cột ngang)
            'product_flow'  => $this->reportModel->getTop5ProductFlow(),
            
            // Chi tiết hoạt động của từng biến thể (Size/Màu) - hiển thị dạng bảng
            'variant_flow'  => $this->reportModel->getVariantFlowDetail(),
            
            // Bảng kê tồn kho chi tiết từng mẫu giày và biến thể hiện tại
            'inventory'     => $this->reportModel->getDetailedInventory(),
            
            // Tỉ lệ phân bổ hàng trong kho theo thương hiệu (vẽ biểu đồ tròn)
            'brand_dist'    => $this->reportModel->getBrandDistribution(),
            
            // Hiệu suất làm việc của Top 3 nhân viên (vẽ biểu đồ cột)
            'staff_perf'    => $this->reportModel->getStaffPerformance(),
            
            // Lưu lại giá trị filter để hiển thị trên form tìm kiếm
            'filter'        => ['start' => $start_date, 'end' => $end_date]
        ];
    }

    /**
     * Chức năng: Xử lý yêu cầu lọc dữ liệu theo ngày
     * Nhận ngày bắt đầu/kết thúc và chuyển hướng về trang báo cáo kèm tham số URL
     */
    public function filterByDate($start, $end)
    {
        header("Location: index.php?page=report&start_date=$start&end_date=$end");
        exit;
    }
}