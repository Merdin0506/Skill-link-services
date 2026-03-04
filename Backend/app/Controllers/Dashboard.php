<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\BookingModel;
use App\Models\PaymentModel;
use App\Models\ReviewModel;

class Dashboard extends BaseController
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
     * Display dashboard based on user role
     */
    public function index()
    {
        // Check if user is logged in
        if (!$this->session->has('user_id')) {
            return redirect()->to('/auth/login');
        }

        $userId = $this->session->get('user_id');
        $userRole = $this->session->get('user_role');

        // For demo purposes, we'll create a mock user if session exists
        $user = [
            'id' => $userId,
            'first_name' => explode(' ', $this->session->get('user_name') ?? 'Demo User')[0],
            'last_name' => explode(' ', $this->session->get('user_name') ?? 'User')[1] ?? 'User',
            'email' => $this->session->get('email'),
            'user_type' => $userRole,
        ];

        // Try to get real user from database
        $dbUser = $this->userModel->find($userId);
        if ($dbUser) {
            $user = $dbUser;
        }

        $data = $this->getDashboardData($userId, $userRole);
        $data['user'] = $user;
        $data['role'] = $userRole;

        return view('dashboard/index', $data);
    }

    public function users()
    {
        if (!$this->session->has('user_id')) {
            return redirect()->to('/auth/login');
        }

        if ($this->session->get('user_role') !== 'admin') {
            return redirect()->to('/dashboard');
        }

        $data = [
            'role' => $this->session->get('user_role'),
            'users' => $this->userModel->orderBy('created_at', 'DESC')->findAll(),
            'user' => $this->getCurrentUser(),
        ];

        return view('dashboard/admin_users', $data);
    }

    public function bookings()
    {
        if (!$this->session->has('user_id')) {
            return redirect()->to('/auth/login');
        }

        if ($this->session->get('user_role') !== 'admin') {
            return redirect()->to('/dashboard');
        }

        $bookings = $this->bookingModel
            ->select('bookings.*, customers.first_name AS customer_first_name, customers.last_name AS customer_last_name, workers.first_name AS worker_first_name, workers.last_name AS worker_last_name, services.name AS service_name')
            ->join('users AS customers', 'customers.id = bookings.customer_id')
            ->join('users AS workers', 'workers.id = bookings.worker_id', 'left')
            ->join('services', 'services.id = bookings.service_id', 'left')
            ->orderBy('bookings.created_at', 'DESC')
            ->findAll();

        $data = [
            'role' => $this->session->get('user_role'),
            'bookings' => $bookings,
            'user' => $this->getCurrentUser(),
        ];

        return view('dashboard/admin_bookings', $data);
    }

    public function payments()
    {
        if (!$this->session->has('user_id')) {
            return redirect()->to('/auth/login');
        }

        if ($this->session->get('user_role') !== 'admin') {
            return redirect()->to('/dashboard');
        }

        $payments = $this->paymentModel
            ->select('payments.*, bookings.booking_reference, bookings.title AS booking_title')
            ->join('bookings', 'bookings.id = payments.booking_id', 'left')
            ->orderBy('payments.created_at', 'DESC')
            ->findAll();

        $data = [
            'role' => $this->session->get('user_role'),
            'payments' => $payments,
            'user' => $this->getCurrentUser(),
        ];

        return view('dashboard/admin_payments', $data);
    }

    public function availableJobs()
    {
        if (!$this->session->has('user_id')) {
            return redirect()->to('/auth/login');
        }

        if ($this->session->get('user_role') !== 'worker') {
            return redirect()->to('/dashboard');
        }

        $jobs = $this->bookingModel
            ->select('bookings.*, customers.first_name AS customer_first_name, customers.last_name AS customer_last_name, services.name AS service_name, services.category AS service_category')
            ->join('users AS customers', 'customers.id = bookings.customer_id')
            ->join('services', 'services.id = bookings.service_id', 'left')
            ->where('bookings.status', 'pending')
            ->orderBy('bookings.created_at', 'DESC')
            ->findAll();

        $data = [
            'role' => $this->session->get('user_role'),
            'jobs' => $jobs,
            'user' => $this->getCurrentUser(),
        ];

        return view('dashboard/worker_available_jobs', $data);
    }

    public function myJobs()
    {
        if (!$this->session->has('user_id')) {
            return redirect()->to('/auth/login');
        }

        if ($this->session->get('user_role') !== 'worker') {
            return redirect()->to('/dashboard');
        }

        $workerId = (int) $this->session->get('user_id');
        $jobs = $this->bookingModel
            ->select('bookings.*, customers.first_name AS customer_first_name, customers.last_name AS customer_last_name, services.name AS service_name')
            ->join('users AS customers', 'customers.id = bookings.customer_id')
            ->join('services', 'services.id = bookings.service_id', 'left')
            ->where('bookings.worker_id', $workerId)
            ->orderBy('bookings.created_at', 'DESC')
            ->findAll();

        $data = [
            'role' => $this->session->get('user_role'),
            'jobs' => $jobs,
            'user' => $this->getCurrentUser(),
        ];

        return view('dashboard/worker_my_jobs', $data);
    }

    public function earnings()
    {
        if (!$this->session->has('user_id')) {
            return redirect()->to('/auth/login');
        }

        if ($this->session->get('user_role') !== 'worker') {
            return redirect()->to('/dashboard');
        }

        $workerId = (int) $this->session->get('user_id');

        $total = $this->bookingModel
            ->selectSum('worker_earnings', 'total_earnings')
            ->where('worker_id', $workerId)
            ->where('status', 'completed')
            ->first();

        $recentPayouts = $this->paymentModel
            ->where('paid_to', $workerId)
            ->where('payment_type', 'worker_payout')
            ->orderBy('created_at', 'DESC')
            ->findAll(20);

        $data = [
            'role' => $this->session->get('user_role'),
            'user' => $this->getCurrentUser(),
            'totalEarnings' => (float) ($total['total_earnings'] ?? 0),
            'recentPayouts' => $recentPayouts,
        ];

        return view('dashboard/worker_earnings', $data);
    }

    public function myBookings()
    {
        if (!$this->session->has('user_id')) {
            return redirect()->to('/auth/login');
        }

        $customerId = (int) $this->session->get('user_id');
        $bookings = $this->bookingModel
            ->select('bookings.*, workers.first_name AS worker_first_name, workers.last_name AS worker_last_name, services.name AS service_name, services.category AS service_category')
            ->join('users AS workers', 'workers.id = bookings.worker_id', 'left')
            ->join('services', 'services.id = bookings.service_id', 'left')
            ->where('bookings.customer_id', $customerId)
            ->orderBy('bookings.created_at', 'DESC')
            ->findAll();

        $data = [
            'role' => $this->session->get('user_role'),
            'bookings' => $bookings,
            'user' => $this->getCurrentUser(),
        ];

        return view('dashboard/customer_bookings', $data);
    }

    public function services()
    {
        if (!$this->session->has('user_id')) {
            return redirect()->to('/auth/login');
        }

        // Load ServiceModel
        $serviceModel = new \App\Models\ServiceModel();
        
        $services = $serviceModel
            ->where('status', 'active')
            ->orderBy('name', 'ASC')
            ->findAll();

        $data = [
            'role' => $this->session->get('user_role'),
            'services' => $services,
            'user' => $this->getCurrentUser(),
        ];

        return view('dashboard/customer_services', $data);
    }

    public function myPayments()
    {
        if (!$this->session->has('user_id')) {
            return redirect()->to('/auth/login');
        }

        $customerId = (int) $this->session->get('user_id');
        $payments = $this->paymentModel
            ->select('payments.*, bookings.booking_reference, bookings.title AS booking_title, services.name AS service_name')
            ->join('bookings', 'bookings.id = payments.booking_id')
            ->join('services', 'services.id = bookings.service_id', 'left')
            ->where('bookings.customer_id', $customerId)
            ->orderBy('payments.created_at', 'DESC')
            ->findAll();

        $data = [
            'role' => $this->session->get('user_role'),
            'payments' => $payments,
            'user' => $this->getCurrentUser(),
        ];

        return view('dashboard/customer_payments', $data);
    }

    public function profile()
    {
        if (!$this->session->has('user_id')) {
            return redirect()->to('/auth/login');
        }

        $data = [
            'role' => $this->session->get('user_role'),
            'user' => $this->getCurrentUser(),
        ];

        return view('dashboard/profile', $data);
    }

    public function settings()
    {
        if (!$this->session->has('user_id')) {
            return redirect()->to('/auth/login');
        }

        $data = [
            'role' => $this->session->get('user_role'),
            'user' => $this->getCurrentUser(),
            'baseUrl' => base_url(),
            'environment' => ENVIRONMENT,
        ];

        return view('dashboard/settings', $data);
    }

    /**
     * Get dashboard data based on user role
     */
    private function getDashboardData($userId, $userRole)
    {
        $data = [
            'stats' => [],
            'recentBookings' => [],
            'analytics' => []
        ];

        switch ($userRole) {
            case 'admin':
                $data['stats'] = $this->getAdminStats();
                $data['recentBookings'] = $this->getAllRecentBookings(10);
                $data['analytics'] = $this->getSystemAnalytics();
                break;

            case 'owner':
                $data['stats'] = $this->getOwnerStats($userId);
                $data['recentBookings'] = $this->getOwnerBookings($userId, 10);
                $data['analytics'] = $this->getOwnerAnalytics($userId);
                break;

            case 'worker':
                $data['stats'] = $this->getWorkerStats($userId);
                $data['recentBookings'] = $this->getWorkerBookings($userId, 10);
                $data['analytics'] = $this->getWorkerAnalytics($userId);
                break;

            case 'customer':
                $data['stats'] = $this->getCustomerStats($userId);
                $data['recentBookings'] = $this->getCustomerBookings($userId, 10);
                $data['analytics'] = $this->getCustomerAnalytics($userId);
                break;

            case 'cashier':
                $data['stats'] = $this->getCashierStats();
                $data['recentBookings'] = $this->getAllRecentBookings(10);
                $data['analytics'] = $this->getPaymentAnalytics();
                break;
        }

        return $data;
    }

    private function getCurrentUser(): array
    {
        $userId = $this->session->get('user_id');
        $userRole = $this->session->get('user_role');

        $user = [
            'id' => $userId,
            'first_name' => explode(' ', $this->session->get('user_name') ?? 'Demo User')[0],
            'last_name' => explode(' ', $this->session->get('user_name') ?? 'User')[1] ?? 'User',
            'email' => $this->session->get('email'),
            'user_type' => $userRole,
        ];

        $dbUser = $this->userModel->find($userId);
        if ($dbUser) {
            $user = $dbUser;
        }

        return $user;
    }

    /**
     * Admin Dashboard Stats
     */
    private function getAdminStats()
    {
        return [
            'total_users' => $this->userModel->countAll(),
            'total_bookings' => $this->bookingModel->countAll(),
            'total_revenue' => $this->getTotalRevenue(),
            'active_workers' => $this->userModel->where('user_type', 'worker')->where('status', 'active')->countAllResults(),
            'pending_bookings' => $this->bookingModel->where('status', 'pending')->countAllResults(),
            'completed_bookings' => $this->bookingModel->where('status', 'completed')->countAllResults(),
        ];
    }

    /**
     * Owner Dashboard Stats
     */
    private function getOwnerStats($userId)
    {
        return [
            'total_services' => $this->getServiceCount($userId),
            'active_bookings' => $this->bookingModel->where('customer_id', $userId)->where('status', 'in_progress')->countAllResults(),
            'completed_jobs' => $this->bookingModel->where('customer_id', $userId)->where('status', 'completed')->countAllResults(),
            'total_bookings' => $this->bookingModel->where('customer_id', $userId)->countAllResults(),
            'average_rating' => $this->getAverageRating($userId),
            'total_spent' => $this->getTotalUserSpent($userId),
        ];
    }

    /**
     * Worker Dashboard Stats
     */
    private function getWorkerStats($userId)
    {
        return [
            'available_bookings' => $this->bookingModel->where('status', 'pending')->countAllResults(),
            'assigned_bookings' => $this->bookingModel->where('worker_id', $userId)->where('status', 'assigned')->countAllResults(),
            'in_progress_bookings' => $this->bookingModel->where('worker_id', $userId)->where('status', 'in_progress')->countAllResults(),
            'completed_jobs' => $this->bookingModel->where('worker_id', $userId)->where('status', 'completed')->countAllResults(),
            'total_earnings' => $this->getWorkerEarnings($userId),
            'average_rating' => $this->getAverageRating($userId),
        ];
    }

    /**
     * Customer Dashboard Stats
     */
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

    /**
     * Cashier Dashboard Stats
     */
    private function getCashierStats()
    {
        return [
            'total_payments' => $this->paymentModel->countAll(),
            'completed_payments' => $this->paymentModel->where('status', 'completed')->countAllResults(),
            'pending_payments' => $this->paymentModel->where('status', 'pending')->countAllResults(),
            'total_collected' => $this->getTotalRevenueCompleted(),
            'today_collections' => $this->getTodayCollections(),
        ];
    }

    /**
     * Get recent bookings
     */
    private function getCustomerBookings($userId, $limit = 10)
    {
        return $this->bookingModel->where('customer_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->find();
    }

    /**
     * Get owner bookings
     */
    private function getOwnerBookings($userId, $limit = 10)
    {
        return $this->bookingModel->where('customer_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->find();
    }

    /**
     * Get worker bookings
     */
    private function getWorkerBookings($userId, $limit = 10)
    {
        return $this->bookingModel->where('worker_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->find();
    }

    /**
     * Get all recent bookings
     */
    private function getAllRecentBookings($limit = 10)
    {
        return $this->bookingModel->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->find();
    }

    /**
     * Get system analytics for admin
     */
    private function getSystemAnalytics()
    {
        return [
            'bookings_by_status' => $this->getBookingsByStatus(),
            'bookings_by_priority' => $this->getBookingsByPriority(),
            'revenue_trend' => $this->getRevenueTrend(),
            'user_growth' => $this->getUserGrowth(),
        ];
    }

    /**
     * Get owner analytics
     */
    private function getOwnerAnalytics($userId)
    {
        return [
            'booking_timeline' => $this->getBookingTimeline($userId),
            'service_performance' => $this->getOwnerServicePerformance($userId),
        ];
    }

    /**
     * Get worker analytics
     */
    private function getWorkerAnalytics($userId)
    {
        return [
            'earnings_trend' => $this->getWorkerEarningsTrend($userId),
            'job_completion_rate' => $this->getWorkerCompletionRate($userId),
        ];
    }

    /**
     * Get customer analytics
     */
    private function getCustomerAnalytics($userId)
    {
        return [
            'spending_trend' => $this->getSpendingTrend($userId),
            'service_preferences' => $this->getServicePreferences($userId),
        ];
    }

    /**
     * Get payment analytics
     */
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
            $data[$date] = $query['total_fee'] ?? 0;
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
        return $result['total_fee'] ?? 0;
    }

    private function getTotalRevenueCompleted()
    {
        $result = $this->paymentModel->selectSum('amount')
            ->where('status', 'completed')
            ->first();
        return $result['amount'] ?? 0;
    }

    private function getTodayCollections()
    {
        $today = date('Y-m-d');
        $result = $this->paymentModel->selectSum('amount')
            ->where('DATE(created_at)', $today)
            ->where('status', 'completed')
            ->first();
        return $result['amount'] ?? 0;
    }

    private function getTotalUserSpent($userId)
    {
        $result = $this->bookingModel->selectSum('total_fee')
            ->where('customer_id', $userId)
            ->where('status', 'completed')
            ->first();
        return $result['total_fee'] ?? 0;
    }

    private function getWorkerEarnings($userId)
    {
        $result = $this->bookingModel->selectSum('worker_earnings')
            ->where('worker_id', $userId)
            ->where('status', 'completed')
            ->first();
        return $result['worker_earnings'] ?? 0;
    }

    private function getAverageRating($userId)
    {
        $result = $this->reviewModel->selectAvg('rating')
            ->where('worker_id', $userId)
            ->first();
        return $result['rating'] ?? 0;
    }

    private function getAverageRatingGiven($userId)
    {
        $result = $this->reviewModel->selectAvg('rating')
            ->where('customer_id', $userId)
            ->first();
        return $result['rating'] ?? 0;
    }

    private function getServiceCount($userId)
    {
        // This would need ServiceModel which we saw in workspace
        return 0;
    }

    private function getBookingTimeline($userId)
    {
        return $this->bookingModel->where('customer_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->select('id, title, status, created_at, completed_at')
            ->find();
    }

    private function getOwnerServicePerformance($userId)
    {
        return $this->bookingModel->selectSum('total_fee')
            ->selectCount('id')
            ->where('customer_id', $userId)
            ->where('status', 'completed')
            ->groupBy('service_id')
            ->limit(5)
            ->find();
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
            $data[$date] = $query['worker_earnings'] ?? 0;
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
            $data[$date] = $query['total_fee'] ?? 0;
        }
        return $data;
    }

    private function getServicePreferences($userId)
    {
        return $this->bookingModel->selectCount('service_id')
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
            $data[$date] = $query['amount'] ?? 0;
        }
        return $data;
    }

    private function getPaymentMethods()
    {
        return $this->paymentModel->selectCount('payment_method')
            ->groupBy('payment_method')
            ->find();
    }
}
