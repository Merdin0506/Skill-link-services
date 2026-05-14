<?php

namespace App\Controllers\API;

use CodeIgniter\RESTful\ResourceController;
use App\Models\UserModel;
use App\Models\BookingModel;
use App\Models\PaymentModel;
use App\Models\ReviewModel;

class DashboardController extends ResourceController
{
    protected $userModel;
    protected $bookingModel;
    protected $paymentModel;
    protected $reviewModel;
    protected $session;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->bookingModel = new BookingModel();
        $this->paymentModel = new PaymentModel();
        $this->reviewModel = new ReviewModel();
        $this->session = session();
    }

    /**
     * Verify user role - used for role-based access control
     */
    private function checkUserRole($allowedRoles = [])
    {
        $user = $this->request->authUser ?? null;

        if (!$user || !isset($user['id'])) {
            return $this->failUnauthorized('User not authenticated');
        }

        if (!empty($allowedRoles) && !in_array($user['user_type'], $allowedRoles)) {
            return $this->failForbidden('User role not authorized for this action');
        }

        return $user;
    }

    /**
     * Get dashboard data - GET /api/dashboard/data
     */
    public function data()
    {
        $user = $this->checkUserRole();
        if ($user instanceof \CodeIgniter\HTTP\Response) {
            return $user;
        }

        $userId = $user['id'];
        $userRole = $user['user_type'];

        try {
            $dashboardData = $this->getDashboardData($userId, $userRole);
            return $this->respond([
                'status' => 'success',
                'data' => $dashboardData,
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['first_name'] . ' ' . $user['last_name'],
                    'role' => $user['user_type'],
                    'email' => $user['email'],
                ]
            ], 200);
        } catch (\Exception $e) {
            return $this->fail('Error fetching dashboard data: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get dashboard stats - GET /api/dashboard/stats
     */
    public function stats()
    {
        $user = $this->checkUserRole();
        if ($user instanceof \CodeIgniter\HTTP\Response) {
            return $user;
        }

        $userId = $user['id'];
        $userRole = $user['user_type'];

        $stats = match ($userRole) {
            'super_admin' => $this->getAdminStats(),
            'admin' => $this->getAdminStats(),
            'worker' => $this->getWorkerStats($userId),
            'customer' => $this->getCustomerStats($userId),
            'finance' => $this->getFinanceStats(),
            default => []
        };

        return $this->respond([
            'status' => 'success',
            'stats' => $stats
        ], 200);
    }

    /**
     * Get analytics - GET /api/dashboard/analytics
     */
    public function analytics()
    {
        $user = $this->checkUserRole();
        if ($user instanceof \CodeIgniter\HTTP\Response) {
            return $user;
        }

        $userId = $user['id'];
        $userRole = $user['user_type'];

        $analytics = match ($userRole) {
            'super_admin' => $this->getSystemAnalytics(),
            'admin' => $this->getSystemAnalytics(),
            'worker' => $this->getWorkerAnalytics($userId),
            'customer' => $this->getCustomerAnalytics($userId),
            'finance' => $this->getPaymentAnalytics(),
            default => []
        };

        return $this->respond([
            'status' => 'success',
            'analytics' => $analytics
        ], 200);
    }

    /**
     * Get recent bookings - GET /api/dashboard/bookings
     */
    public function bookings()
    {
        $user = $this->checkUserRole();
        if ($user instanceof \CodeIgniter\HTTP\Response) {
            return $user;
        }

        $userId = $user['id'];
        $userRole = $user['user_type'];
        $limit = $this->getPositiveIntParam('limit', 10);

        $bookings = match ($userRole) {
            'super_admin' => $this->bookingModel->limit($limit)->orderBy('created_at', 'DESC')->find(),
            'admin' => $this->bookingModel->limit($limit)->orderBy('created_at', 'DESC')->find(),
            'worker' => $this->bookingModel->where('worker_id', $userId)->limit($limit)->orderBy('created_at', 'DESC')->find(),
            'customer' => $this->bookingModel->where('customer_id', $userId)->limit($limit)->orderBy('created_at', 'DESC')->find(),
            'finance' => $this->getFinanceDashboardBookings($limit),
            default => []
        };

        return $this->respond([
            'status' => 'success',
            'bookings' => $bookings,
            'count' => count($bookings)
        ], 200);
    }

    // ========== Statistics Methods ==========

    private function getAdminStats()
    {
        return [
            'total_users' => $this->userModel->countAll(),
            'total_bookings' => $this->bookingModel->countAll(),
            'total_revenue' => $this->getTotalRevenue(),
            'active_workers' => $this->userModel->where('user_type', 'worker')->where('status', 'active')->countAllResults(),
            'pending_workers' => $this->userModel->where('user_type', 'worker')->where('status', 'pending')->countAllResults(),
            'pending_bookings' => $this->bookingModel->where('status', 'pending')->countAllResults(),
            'completed_bookings' => $this->bookingModel->where('status', 'completed')->countAllResults(),
        ];
    }

    private function getWorkerStats($userId)
    {
        return [
            'available_bookings' => $this->bookingModel->where('status', 'pending')->countAllResults(),
            'assigned_bookings' => $this->bookingModel->where('worker_id', $userId)->where('status', 'assigned')->countAllResults(),
            'in_progress_bookings' => $this->bookingModel->where('worker_id', $userId)->where('status', 'in_progress')->countAllResults(),
            'completed_jobs' => $this->bookingModel->where('worker_id', $userId)->where('status', 'completed')->countAllResults(),
            'total_earnings' => $this->getWorkerEarnings($userId),
            // Do not expose average rating to workers (Admin-only data)
        ];
    }

    private function getCustomerStats($userId)
    {
        return [
            'active_bookings' => $this->bookingModel->where('customer_id', $userId)->where('status', 'in_progress')->countAllResults(),
            'pending_bookings' => $this->bookingModel->where('customer_id', $userId)->where('status', 'pending')->countAllResults(),
            'completed_bookings' => $this->bookingModel->where('customer_id', $userId)->where('status', 'completed')->countAllResults(),
            'total_bookings' => $this->bookingModel->where('customer_id', $userId)->countAllResults(),
            'total_spent' => $this->getTotalUserSpent($userId),
            'average_rating_given' => $this->getAverageRatingGiven($userId),
        ];
    }

    private function getFinanceStats()
    {
        return [
            'total_payments' => $this->paymentModel->countAll(),
            'completed_payments' => $this->paymentModel->where('status', 'completed')->countAllResults(),
            'pending_payments' => $this->paymentModel->where('status', 'pending')->countAllResults(),
            'total_collected' => $this->getTotalRevenueCompleted(),
            'today_collections' => $this->getTodayCollections(),
        ];
    }

    private function getFinanceDashboardBookings(int $limit): array
    {
        $db = \Config\Database::connect();
        $paymentSubquery = $db->table('payments')
            ->select('MAX(id) AS payment_id, booking_id')
            ->where('payment_type', 'customer_payment')
            ->groupBy('booking_id')
            ->getCompiledSelect();

        return $db->table('bookings')
            ->select("
                bookings.*,
                latest_payment.payment_id,
                payments.payment_reference,
                payments.payment_method,
                payments.status AS payment_status,
                payments.amount,
                payments.transaction_id,
                payments.payment_date,
                payments.created_at AS transaction_created_at,
                customers.first_name AS customer_first_name,
                customers.last_name AS customer_last_name,
                workers.first_name AS worker_first_name,
                workers.last_name AS worker_last_name
            ")
            ->join("({$paymentSubquery}) AS latest_payment", 'latest_payment.booking_id = bookings.id', 'left')
            ->join('payments', 'payments.id = latest_payment.payment_id', 'left')
            ->join('users AS customers', 'customers.id = bookings.customer_id', 'left')
            ->join('users AS workers', 'workers.id = bookings.worker_id', 'left')
            ->orderBy('bookings.created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    // ========== Analytics Methods ==========

    private function getSystemAnalytics()
    {
        return [
            'bookings_by_status' => $this->getBookingsByStatus(),
            'bookings_by_priority' => $this->getBookingsByPriority(),
            'revenue_trend' => $this->getRevenueTrend(),
            'user_growth' => $this->getUserGrowth(),
        ];
    }

    private function getWorkerAnalytics($userId)
    {
        return [
            'earnings_trend' => $this->getWorkerEarningsTrend($userId),
            'job_completion_rate' => $this->getWorkerCompletionRate($userId),
        ];
    }

    private function getCustomerAnalytics($userId)
    {
        return [
            'spending_trend' => $this->getSpendingTrend($userId),
            'service_preferences' => $this->getServicePreferences($userId),
        ];
    }

    private function getPaymentAnalytics()
    {
        return [
            'daily_collections' => $this->getDailyCollections(),
            'payment_methods' => $this->getPaymentMethods(),
        ];
    }

    // ========== Helper Methods ==========

    private function getBookingsByStatus()
    {
        $statuses = ['pending', 'assigned', 'in_progress', 'completed', 'cancelled', 'rejected'];
        $data = [];
        foreach ($statuses as $status) {
            $data[$status] = $this->bookingModel->where('status', $status)->countAllResults();
        }
        return $data;
    }

    private function getBookingsByPriority()
    {
        $priorities = ['low', 'medium', 'high', 'urgent'];
        $data = [];
        foreach ($priorities as $priority) {
            $data[$priority] = $this->bookingModel->where('priority', $priority)->countAllResults();
        }
        return $data;
    }

    private function getRevenueTrend($days = 30)
    {
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $query = $this->bookingModel->selectSum('total_fee')
                ->where('DATE(completed_at)', $date)
                ->where('status', 'completed')
                ->first();
            $data[$date] = floatval($query['total_fee'] ?? 0);
        }
        return $data;
    }

    private function getUserGrowth($days = 30)
    {
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $count = $this->userModel->where('DATE(created_at)', $date)->countAllResults();
            $data[$date] = $count;
        }
        return $data;
    }

    private function getTotalRevenue()
    {
        $result = $this->bookingModel->selectSum('total_fee')
            ->where('status', 'completed')
            ->first();
        return floatval($result['total_fee'] ?? 0);
    }

    private function getTotalRevenueCompleted()
    {
        $result = $this->paymentModel->selectSum('amount')
            ->where('status', 'completed')
            ->first();
        return floatval($result['amount'] ?? 0);
    }

    private function getTodayCollections()
    {
        $today = date('Y-m-d');
        $result = $this->paymentModel->selectSum('amount')
            ->where('DATE(created_at)', $today)
            ->where('status', 'completed')
            ->first();
        return floatval($result['amount'] ?? 0);
    }

    private function getTotalUserSpent($userId)
    {
        $result = $this->bookingModel->selectSum('total_fee')
            ->where('customer_id', $userId)
            ->where('status', 'completed')
            ->first();
        return floatval($result['total_fee'] ?? 0);
    }

    private function getWorkerEarnings($userId)
    {
        $result = $this->bookingModel->selectSum('worker_earnings')
            ->where('worker_id', $userId)
            ->where('status', 'completed')
            ->first();
        return floatval($result['worker_earnings'] ?? 0);
    }

    private function getAverageRating($userId)
    {
        $result = $this->reviewModel->selectAvg('rating')
            ->where('worker_id', $userId)
            ->first();
        return round(floatval($result['rating'] ?? 0), 2);
    }

    private function getAverageRatingGiven($userId)
    {
        $result = $this->reviewModel->selectAvg('rating')
            ->where('customer_id', $userId)
            ->first();
        return round(floatval($result['rating'] ?? 0), 2);
    }

    private function getWorkerEarningsTrend($userId, $days = 30)
    {
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $query = $this->bookingModel->selectSum('worker_earnings')
                ->where('worker_id', $userId)
                ->where('DATE(completed_at)', $date)
                ->where('status', 'completed')
                ->first();
            $data[$date] = floatval($query['worker_earnings'] ?? 0);
        }
        return $data;
    }

    private function getWorkerCompletionRate($userId)
    {
        $completed = $this->bookingModel->where('worker_id', $userId)
            ->where('status', 'completed')->countAllResults();
        $total = $this->bookingModel->where('worker_id', $userId)->countAllResults();
        return $total > 0 ? round(($completed / $total) * 100, 2) : 0;
    }

    private function getSpendingTrend($userId, $days = 30)
    {
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $query = $this->bookingModel->selectSum('total_fee')
                ->where('customer_id', $userId)
                ->where('DATE(created_at)', $date)
                ->first();
            $data[$date] = floatval($query['total_fee'] ?? 0);
        }
        return $data;
    }

    private function getServicePreferences($userId)
    {
        return $this->bookingModel->selectCount('service_id', 'count')
            ->select('service_id')
            ->where('customer_id', $userId)
            ->orderBy('COUNT(*)', 'DESC')
            ->groupBy('service_id')
            ->limit(5)
            ->find();
    }

    private function getDailyCollections($days = 7)
    {
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $query = $this->paymentModel->selectSum('amount')
                ->where('DATE(created_at)', $date)
                ->where('status', 'completed')
                ->first();
            $data[$date] = floatval($query['amount'] ?? 0);
        }
        return $data;
    }

    private function getPaymentMethods()
    {
        return $this->paymentModel->selectCount('payment_method', 'count')
            ->select('payment_method')
            ->groupBy('payment_method')
            ->find();
    }

    /**
     * Dashboard data aggregation method
     */
    private function getDashboardData($userId, $userRole)
    {
        $data = [
            'stats' => match ($userRole) {
                'super_admin' => $this->getAdminStats(),
                'admin' => $this->getAdminStats(),
                'worker' => $this->getWorkerStats($userId),
                'customer' => $this->getCustomerStats($userId),
                'finance' => $this->getFinanceStats(),
                default => []
            },
            'analytics' => match ($userRole) {
                'super_admin' => $this->getSystemAnalytics(),
                'admin' => $this->getSystemAnalytics(),
                'worker' => $this->getWorkerAnalytics($userId),
                'customer' => $this->getCustomerAnalytics($userId),
                'finance' => $this->getPaymentAnalytics(),
                default => []
            },
            'recentBookings' => match ($userRole) {
                'super_admin' => $this->bookingModel->limit(10)->orderBy('created_at', 'DESC')->find(),
                'admin' => $this->bookingModel->limit(10)->orderBy('created_at', 'DESC')->find(),
                'worker' => $this->bookingModel->where('worker_id', $userId)->limit(10)->orderBy('created_at', 'DESC')->find(),
                'customer' => $this->bookingModel->where('customer_id', $userId)->limit(10)->orderBy('created_at', 'DESC')->find(),
                'finance' => $this->getFinanceDashboardBookings(10),
                default => [],
            },
        ];

        // Include pending worker applications for admin dashboards
        if ($userRole === 'admin' || $userRole === 'super_admin') {
            $workers = $this->userModel->where('user_type', 'worker')->where('status', 'pending')->orderBy('created_at', 'DESC')->limit(10)->findAll();
            $skillLabels = UserModel::WORKER_SKILL_OPTIONS;
            $data['pendingWorkerApplications'] = array_map(function(array $worker) use ($skillLabels) {
                $skills = UserModel::normalizeWorkerSkills($worker['skills'] ?? []);
                return [
                    'id' => $worker['id'] ?? null,
                    'full_name' => trim(($worker['first_name'] ?? '') . ' ' . ($worker['last_name'] ?? '')),
                    'email' => $worker['email'] ?? null,
                    'skills' => array_map(fn($s) => $skillLabels[$s] ?? $s, $skills),
                    'created_at' => $worker['created_at'] ?? null,
                    'status' => $worker['status'] ?? 'pending'
                ];
            }, $workers);
        }

        return $data;
    }

    private function getPositiveIntParam(string $name, int $default): int
    {
        $value = (int) $this->request->getVar($name);

        return $value > 0 ? $value : $default;
    }
}
