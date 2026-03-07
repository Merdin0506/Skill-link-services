<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\BookingModel;
use App\Models\PaymentModel;
use App\Models\ReviewModel;
use App\Models\ServiceRecordModel;

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
        
        // Load dashboard helper
        helper('dashboard');
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
            ->select('bookings.*, customers.first_name AS customer_first_name, customers.last_name AS customer_last_name, workers.first_name AS worker_first_name, workers.last_name AS worker_last_name, services.name AS service_name, services.category AS service_category')
            ->join('users AS customers', 'customers.id = bookings.customer_id')
            ->join('users AS workers', 'workers.id = bookings.worker_id', 'left')
            ->join('services', 'services.id = bookings.service_id', 'left')
            ->orderBy('bookings.created_at', 'DESC')
            ->findAll();

        // Organize workers by service category for filtering
        $workersByCategory = [];
        $allWorkers = $this->userModel
            ->where('user_type', 'worker')
            ->where('status', 'active')
            ->orderBy('first_name', 'ASC')
            ->findAll();

        // Get unique service categories from bookings
        $categories = array_unique(array_column($bookings, 'service_category'));
        
        foreach ($categories as $category) {
            if ($category) {
                $workersByCategory[$category] = $this->userModel->getWorkersByServiceCategory($category);
            }
        }

        $data = [
            'role' => $this->session->get('user_role'),
            'bookings' => $bookings,
            'workers' => $allWorkers,
            'workersByCategory' => $workersByCategory,
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

    public function records()
    {
        if (!$this->session->has('user_id')) {
            return redirect()->to('/auth/login');
        }

        if ($this->session->get('user_role') !== 'admin') {
            return redirect()->to('/dashboard');
        }

        $recordModel = new ServiceRecordModel();

        // Get filter params
        $status  = $this->request->getGet('status');
        $payment = $this->request->getGet('payment_status');
        $q       = $this->request->getGet('q');
        $show_deleted = $this->request->getGet('show_deleted');

        $builder = $recordModel
            ->select('service_records.*, customers.first_name AS customer_first_name, customers.last_name AS customer_last_name, providers.first_name AS provider_first_name, providers.last_name AS provider_last_name, services.name AS service_name')
            ->join('users AS customers', 'customers.id = service_records.customer_id')
            ->join('users AS providers', 'providers.id = service_records.provider_id', 'left')
            ->join('services', 'services.id = service_records.service_id');

        if ($show_deleted) {
            $builder->onlyDeleted();
        }

        if ($status) {
            $builder->where('service_records.status', $status);
        }
        if ($payment) {
            $builder->where('service_records.payment_status', $payment);
        }
        if ($q) {
            $builder->groupStart()
                ->like('service_records.payment_ref', $q)
                ->orLike('service_records.address_text', $q)
                ->orLike('customers.first_name', $q)
                ->orLike('customers.last_name', $q)
                ->orLike('services.name', $q)
            ->groupEnd();
        }

        $records = $builder->orderBy('service_records.created_at', 'DESC')->findAll();

        // Get status counts for summary widget
        $statusCounts = [
            'pending' => 0,
            'scheduled' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'cancelled' => 0
        ];
        
        $countBuilder = $recordModel->select('status, COUNT(*) as count');
        if (!$show_deleted) {
            $countBuilder->where('deleted_at IS NULL');
        } else {
            $countBuilder->onlyDeleted();
        }
        $counts = $countBuilder->groupBy('status')->findAll();
        
        foreach ($counts as $count) {
            if (isset($statusCounts[$count['status']])) {
                $statusCounts[$count['status']] = (int)$count['count'];
            }
        }

        $data = [
            'role'          => $this->session->get('user_role'),
            'records'       => $records,
            'user'          => $this->getCurrentUser(),
            'filters'       => ['status' => $status, 'payment_status' => $payment, 'q' => $q, 'show_deleted' => $show_deleted],
            'statusCounts'  => $statusCounts,
        ];

        return view('dashboard/admin_records', $data);
    }

    public function recordCreate()
    {
        if (!$this->session->has('user_id')) {
            return redirect()->to('/auth/login');
        }
        if ($this->session->get('user_role') !== 'admin') {
            return redirect()->to('/dashboard');
        }

        // Strict mode: service records must originate from actual bookings.
        return redirect()->to('/admin/records')->with('error', 'Manual record creation is disabled. Records are generated from completed bookings.');

        $serviceModel = new \App\Models\ServiceModel();

        $data = [
            'role'      => $this->session->get('user_role'),
            'user'      => $this->getCurrentUser(),
            'customers' => $this->userModel->where('user_type', 'customer')->orderBy('first_name')->findAll(),
            'workers'   => $this->userModel->whereIn('user_type', ['worker'])->orderBy('first_name')->findAll(),
            'services'  => $serviceModel->where('status', 'active')->orderBy('name')->findAll(),
            'record'    => null,
        ];

        return view('dashboard/admin_record_form', $data);
    }

    public function recordStore()
    {
        if (!$this->session->has('user_id')) {
            return redirect()->to('/auth/login');
        }
        if ($this->session->get('user_role') !== 'admin') {
            return redirect()->to('/dashboard');
        }

        // Strict mode: prevent manual insertion to avoid out-of-sync records.
        return redirect()->to('/admin/records')->with('error', 'Manual record creation is disabled. Complete a booking to generate a record.');

        $rules = [
            'customer_id'    => 'required|integer',
            'service_id'     => 'required|integer',
            'status'         => 'required|in_list[pending,scheduled,in_progress,completed,cancelled]',
            'labor_fee'      => 'permit_empty|numeric|greater_than_equal_to[0]',
            'platform_fee'   => 'permit_empty|numeric|greater_than_equal_to[0]',
            'payment_status' => 'permit_empty|in_list[unpaid,partial,paid,refunded]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $laborFee    = (float) ($this->request->getPost('labor_fee') ?? 0);
        $platformFee = (float) ($this->request->getPost('platform_fee') ?? 0);

        $recordData = [
            'customer_id'    => $this->request->getPost('customer_id'),
            'provider_id'    => $this->request->getPost('provider_id') ?: null,
            'service_id'     => $this->request->getPost('service_id'),
            'booking_id'     => $this->request->getPost('booking_id') ?: null,
            'status'         => $this->request->getPost('status') ?? 'pending',
            'scheduled_at'   => $this->request->getPost('scheduled_at') ?: null,
            'address_text'   => $this->request->getPost('address_text'),
            'labor_fee'      => $laborFee,
            'platform_fee'   => $platformFee,
            'total_amount'   => $laborFee + $platformFee,
            'payment_status' => $this->request->getPost('payment_status') ?? 'unpaid',
            'payment_ref'    => $this->request->getPost('payment_ref'),
            'customer_note'  => $this->request->getPost('customer_note'),
            'provider_note'  => $this->request->getPost('provider_note'),
            'admin_note'     => $this->request->getPost('admin_note'),
        ];

        $recordModel = new ServiceRecordModel();

        if ($recordModel->insert($recordData) === false) {
            return redirect()->back()->withInput()->with('errors', $recordModel->errors());
        }

        return redirect()->to('/admin/records')->with('success', 'Record created successfully.');
    }

    public function recordEdit($id = null)
    {
        if (!$this->session->has('user_id')) {
            return redirect()->to('/auth/login');
        }
        if ($this->session->get('user_role') !== 'admin') {
            return redirect()->to('/dashboard');
        }

        $recordModel  = new ServiceRecordModel();
        $serviceModel = new \App\Models\ServiceModel();

        $record = $recordModel->find((int) $id);
        if (!$record) {
            return redirect()->to('/admin/records')->with('error', 'Record not found.');
        }

        $data = [
            'role'      => $this->session->get('user_role'),
            'user'      => $this->getCurrentUser(),
            'customers' => $this->userModel->where('user_type', 'customer')->orderBy('first_name')->findAll(),
            'workers'   => $this->userModel->whereIn('user_type', ['worker'])->orderBy('first_name')->findAll(),
            'services'  => $serviceModel->where('status', 'active')->orderBy('name')->findAll(),
            'record'    => $record,
        ];

        return view('dashboard/admin_record_form', $data);
    }

    public function recordUpdate($id = null)
    {
        if (!$this->session->has('user_id')) {
            return redirect()->to('/auth/login');
        }
        if ($this->session->get('user_role') !== 'admin') {
            return redirect()->to('/dashboard');
        }

        $recordModel = new ServiceRecordModel();
        $record = $recordModel->find((int) $id);
        if (!$record) {
            return redirect()->to('/admin/records')->with('error', 'Record not found.');
        }

        $rules = [
            'customer_id'    => 'required|integer',
            'service_id'     => 'required|integer',
            'status'         => 'required|in_list[pending,scheduled,in_progress,completed,cancelled]',
            'labor_fee'      => 'permit_empty|numeric|greater_than_equal_to[0]',
            'platform_fee'   => 'permit_empty|numeric|greater_than_equal_to[0]',
            'payment_status' => 'permit_empty|in_list[unpaid,partial,paid,refunded]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $laborFee    = (float) ($this->request->getPost('labor_fee') ?? 0);
        $platformFee = (float) ($this->request->getPost('platform_fee') ?? 0);

        $recordData = [
            'customer_id'    => $this->request->getPost('customer_id'),
            'provider_id'    => $this->request->getPost('provider_id') ?: null,
            'service_id'     => $this->request->getPost('service_id'),
            'booking_id'     => $this->request->getPost('booking_id') ?: null,
            'status'         => $this->request->getPost('status'),
            'scheduled_at'   => $this->request->getPost('scheduled_at') ?: null,
            'address_text'   => $this->request->getPost('address_text'),
            'labor_fee'      => $laborFee,
            'platform_fee'   => $platformFee,
            'total_amount'   => $laborFee + $platformFee,
            'payment_status' => $this->request->getPost('payment_status') ?? 'unpaid',
            'payment_ref'    => $this->request->getPost('payment_ref'),
            'customer_note'  => $this->request->getPost('customer_note'),
            'provider_note'  => $this->request->getPost('provider_note'),
            'admin_note'     => $this->request->getPost('admin_note'),
        ];

        // Strict mode: booking-linked records should not have core source fields rewritten manually.
        if (!empty($record['booking_id'])) {
            $recordData = [
                'status'         => $this->request->getPost('status'),
                'payment_status' => $this->request->getPost('payment_status') ?? ($record['payment_status'] ?? 'unpaid'),
                'payment_ref'    => $this->request->getPost('payment_ref'),
                'customer_note'  => $this->request->getPost('customer_note'),
                'provider_note'  => $this->request->getPost('provider_note'),
                'admin_note'     => $this->request->getPost('admin_note'),
            ];
        }

        if ($recordModel->update((int) $id, $recordData) === false) {
            return redirect()->back()->withInput()->with('errors', $recordModel->errors());
        }

        return redirect()->to('/admin/records')->with('success', 'Record updated successfully.');
    }

    public function recordDelete($id = null)
    {
        if (!$this->session->has('user_id')) {
            return redirect()->to('/auth/login');
        }
        if ($this->session->get('user_role') !== 'admin') {
            return redirect()->to('/dashboard');
        }

        $recordModel = new ServiceRecordModel();
        $record = $recordModel->find((int) $id);
        if (!$record) {
            return redirect()->to('/admin/records')->with('error', 'Record not found.');
        }

        // Soft delete — sets deleted_at
        $recordModel->delete((int) $id);

        return redirect()->to('/admin/records')->with('success', 'Record deleted (archived) successfully.');
    }

    public function recordRestore($id = null)
    {
        if (!$this->session->has('user_id')) {
            return redirect()->to('/auth/login');
        }
        if ($this->session->get('user_role') !== 'admin') {
            return redirect()->to('/dashboard');
        }

        $recordModel = new ServiceRecordModel();

        // Find including soft-deleted
        $record = $recordModel->onlyDeleted()->find((int) $id);
        if (!$record) {
            return redirect()->to('/admin/records?show_deleted=1')->with('error', 'Deleted record not found.');
        }

        // Restore by clearing deleted_at
        $recordModel->update((int) $id, ['deleted_at' => null]);

        return redirect()->to('/admin/records')->with('success', 'Record restored successfully.');
    }

    public function availableJobs()
    {
        if (!$this->session->has('user_id')) {
            return redirect()->to('/auth/login');
        }

        if ($this->session->get('user_role') !== 'worker') {
            return redirect()->to('/dashboard');
        }

        $workerId = (int) $this->session->get('user_id');
        
        // Get worker's skills
        $worker = $this->userModel->find($workerId);
        $workerSkills = json_decode($worker['skills'] ?? '[]', true);
        if (!is_array($workerSkills)) {
            $workerSkills = [];
        }

        // Get all pending jobs
        $allJobs = $this->bookingModel
            ->select('bookings.*, customers.first_name AS customer_first_name, customers.last_name AS customer_last_name, services.name AS service_name, services.category AS service_category')
            ->join('users AS customers', 'customers.id = bookings.customer_id')
            ->join('services', 'services.id = bookings.service_id', 'left')
            ->where('bookings.status', 'pending')
            ->orderBy('bookings.created_at', 'DESC')
            ->findAll();

        // Skill mapping - same as in UserModel
        $skillMap = [
            'electrician' => ['electrical', 'electrician', 'wiring'],
            'plumber' => ['plumbing', 'plumber', 'pipe'],
            'mechanic' => ['automotive', 'mechanic', 'engine'],
            'technician' => ['technician', 'technical', 'repair'],
            'general' => [] // general jobs available to all workers
        ];

        // Filter jobs based on worker's skills
        $matchingJobs = [];
        foreach ($allJobs as $job) {
            $serviceCategory = $job['service_category'] ?? '';
            
            // General category jobs are available to all workers
            if ($serviceCategory === 'general' || !isset($skillMap[$serviceCategory])) {
                $matchingJobs[] = $job;
                continue;
            }

            // Check if worker has matching skills for this job
            $requiredKeywords = $skillMap[$serviceCategory];
            $hasMatchingSkill = false;

            foreach ($workerSkills as $skill) {
                $skillLower = strtolower($skill);
                foreach ($requiredKeywords as $keyword) {
                    if (stripos($skillLower, $keyword) !== false) {
                        $hasMatchingSkill = true;
                        break 2;
                    }
                }
            }

            if ($hasMatchingSkill) {
                $matchingJobs[] = $job;
            }
        }

        $data = [
            'role' => $this->session->get('user_role'),
            'jobs' => $matchingJobs,
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

    public function workerJobDetails($bookingId = null)
    {
        if (!$this->session->has('user_id')) {
            return redirect()->to('/auth/login');
        }

        if ($this->session->get('user_role') !== 'worker') {
            return redirect()->to('/dashboard')->with('error', 'Unauthorized access');
        }

        $workerId = (int) $this->session->get('user_id');

        $booking = $this->bookingModel
            ->select('bookings.*, customers.first_name AS customer_first_name, customers.last_name AS customer_last_name, customers.email AS customer_email, services.name AS service_name, services.category AS service_category, services.description AS service_description')
            ->join('users AS customers', 'customers.id = bookings.customer_id', 'left')
            ->join('services', 'services.id = bookings.service_id', 'left')
            ->where('bookings.id', (int) $bookingId)
            ->first();

        if (!$booking) {
            return redirect()->to('/worker/available-jobs')->with('error', 'Job not found');
        }

        // Workers can view any pending job, but non-pending jobs only if assigned to them.
        if (($booking['status'] ?? '') !== 'pending' && (int) ($booking['worker_id'] ?? 0) !== $workerId) {
            return redirect()->to('/worker/my-jobs')->with('error', 'You are not allowed to view this job');
        }

        $customerReviews = $this->reviewModel
            ->select('reviews.*, services.name AS service_name, bookings.booking_reference')
            ->join('bookings', 'bookings.id = reviews.booking_id', 'left')
            ->join('services', 'services.id = bookings.service_id', 'left')
            ->where('reviews.booking_id', (int) ($booking['id'] ?? 0))
            ->where('reviews.status', 'published')
            ->orderBy('reviews.created_at', 'DESC')
            ->limit(1)
            ->findAll();

        $serviceReviews = $this->reviewModel
            ->select('reviews.*, customers.first_name AS customer_first_name, customers.last_name AS customer_last_name')
            ->join('bookings', 'bookings.id = reviews.booking_id', 'left')
            ->join('users AS customers', 'customers.id = reviews.customer_id', 'left')
            ->where('bookings.service_id', (int) ($booking['service_id'] ?? 0))
            ->where('reviews.status', 'published')
            ->orderBy('reviews.created_at', 'DESC')
            ->limit(5)
            ->findAll();

        $data = [
            'role' => $this->session->get('user_role'),
            'user' => $this->getCurrentUser(),
            'booking' => $booking,
            'customerReviews' => $customerReviews,
            'serviceReviews' => $serviceReviews,
        ];

        return view('dashboard/worker_job_details', $data);
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

        if ($this->session->get('user_role') !== 'customer') {
            return redirect()->to('/dashboard')->with('error', 'Unauthorized access');
        }

        $customerId = (int) $this->session->get('user_id');
        $bookings = $this->bookingModel
            ->select('bookings.*, workers.first_name AS worker_first_name, workers.last_name AS worker_last_name, services.name AS service_name, services.category AS service_category, reviews.id AS review_id')
            ->join('users AS workers', 'workers.id = bookings.worker_id', 'left')
            ->join('services', 'services.id = bookings.service_id', 'left')
            ->join('reviews', 'reviews.booking_id = bookings.id AND reviews.customer_id = ' . $customerId, 'left')
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

    public function serviceDetails($serviceId = null)
    {
        if (!$this->session->has('user_id')) {
            return redirect()->to('/auth/login');
        }

        if ($this->session->get('user_role') !== 'customer') {
            return redirect()->to('/dashboard')->with('error', 'Unauthorized access');
        }

        $serviceModel = new \App\Models\ServiceModel();
        $service = $serviceModel
            ->where('id', (int) $serviceId)
            ->where('status', 'active')
            ->first();

        if (!$service) {
            return redirect()->to('/customer/services')->with('error', 'Service not found');
        }

        $reviews = $this->reviewModel->getServiceReviews((int) $serviceId, 'published');
        $totalReviews = count($reviews);
        $averageRating = 0;

        if ($totalReviews > 0) {
            $totalRating = array_sum(array_map(static fn ($review) => (int) ($review['rating'] ?? 0), $reviews));
            $averageRating = round($totalRating / $totalReviews, 1);
        }

        // Count total bookings for this service
        $bookingModel = new \App\Models\BookingModel();
        $totalBookings = $bookingModel->where('service_id', (int) $serviceId)->countAllResults();

        $data = [
            'role' => $this->session->get('user_role'),
            'user' => $this->getCurrentUser(),
            'service' => $service,
            'reviews' => $reviews,
            'reviewStats' => [
                'total_reviews' => $totalReviews,
                'average_rating' => $averageRating,
                'total_bookings' => $totalBookings,
            ],
        ];

        return view('dashboard/customer_service_details', $data);
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

    public function createReview($bookingId = null)
    {
        if (!$this->session->has('user_id')) {
            return redirect()->to('/auth/login');
        }

        if ($this->session->get('user_role') !== 'customer') {
            return redirect()->to('/dashboard')->with('error', 'Unauthorized access');
        }

        $customerId = (int) $this->session->get('user_id');

        $booking = $this->bookingModel
            ->select('bookings.*, services.name AS service_name, workers.first_name AS worker_first_name, workers.last_name AS worker_last_name, reviews.id AS review_id')
            ->join('services', 'services.id = bookings.service_id', 'left')
            ->join('users AS workers', 'workers.id = bookings.worker_id', 'left')
            ->join('reviews', 'reviews.booking_id = bookings.id AND reviews.customer_id = ' . $customerId, 'left')
            ->where('bookings.id', $bookingId)
            ->where('bookings.customer_id', $customerId)
            ->first();

        if (!$booking) {
            return redirect()->to('/customer/bookings')->with('error', 'Booking not found');
        }

        if (($booking['status'] ?? '') !== 'completed') {
            return redirect()->to('/customer/bookings')->with('error', 'You can only rate completed bookings');
        }

        if (!empty($booking['review_id'])) {
            return redirect()->to('/customer/bookings')->with('info', 'You already rated this booking');
        }

        $data = [
            'role' => $this->session->get('user_role'),
            'user' => $this->getCurrentUser(),
            'booking' => $booking,
        ];

        return view('dashboard/customer_review_form', $data);
    }

    public function storeReview($bookingId = null)
    {
        if (!$this->session->has('user_id')) {
            return redirect()->to('/auth/login');
        }

        if ($this->session->get('user_role') !== 'customer') {
            return redirect()->to('/dashboard')->with('error', 'Unauthorized access');
        }

        $customerId = (int) $this->session->get('user_id');

        $booking = $this->bookingModel
            ->where('id', $bookingId)
            ->where('customer_id', $customerId)
            ->first();

        if (!$booking) {
            return redirect()->to('/customer/bookings')->with('error', 'Booking not found');
        }

        if (($booking['status'] ?? '') !== 'completed') {
            return redirect()->to('/customer/bookings')->with('error', 'You can only rate completed bookings');
        }

        $rules = [
            'rating' => 'required|integer|greater_than_equal_to[1]|less_than_equal_to[5]',
            'service_quality' => 'required|integer|greater_than_equal_to[1]|less_than_equal_to[5]',
            'timeliness' => 'required|integer|greater_than_equal_to[1]|less_than_equal_to[5]',
            'professionalism' => 'required|integer|greater_than_equal_to[1]|less_than_equal_to[5]',
            'would_recommend' => 'required|in_list[0,1]',
            'comment' => 'permit_empty|max_length[1000]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $reviewData = [
            'booking_id' => (int) $bookingId,
            'customer_id' => $customerId,
            'worker_id' => (int) ($booking['worker_id'] ?? 0),
            'rating' => (int) $this->request->getPost('rating'),
            'comment' => (string) $this->request->getPost('comment'),
            'service_quality' => (int) $this->request->getPost('service_quality'),
            'timeliness' => (int) $this->request->getPost('timeliness'),
            'professionalism' => (int) $this->request->getPost('professionalism'),
            'would_recommend' => (int) $this->request->getPost('would_recommend'),
            'status' => 'published',
        ];

        try {
            $created = $this->reviewModel->createReview($reviewData);

            if (!$created) {
                return redirect()->to('/customer/bookings')->with('error', 'Review already exists or could not be created');
            }

            return redirect()->to('/customer/bookings')->with('success', 'Rating submitted successfully');
        } catch (\Exception $e) {
            log_message('error', 'Review submission error: ' . $e->getMessage());
            return redirect()->to('/customer/bookings')->with('error', 'Failed to submit rating');
        }
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

            case 'finance':
                $data['stats'] = $this->getFinanceStats();
                $data['recentBookings'] = $this->getRecentTransactions(10);
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
     * Finance Dashboard Stats
     */
    private function getFinanceStats()
    {
        $db = db_connect();

        $completedBookingsCount = (int) $db->table('bookings')
            ->where('status', 'completed')
            ->countAllResults();

        $completedBookingsRevenue = $db->table('bookings')
            ->selectSum('total_fee')
            ->where('status', 'completed')
            ->get()
            ->getRowArray();

        $bookingsWithoutCompletedPaymentCount = (int) $db->table('bookings b')
            ->join("payments p", "p.booking_id = b.id AND p.payment_type = 'customer_payment' AND p.status = 'completed'", 'left')
            ->where('b.status', 'completed')
            ->where('p.id IS NULL', null, false)
            ->countAllResults();

        $recordedPendingPayouts = $db->table('payments')
            ->selectSum('amount')
            ->where('payment_type', 'worker_payout')
            ->where('status', 'pending')
            ->get()
            ->getRowArray();

        $unrecordedPendingPayouts = $db->table('bookings b')
            ->selectSum('b.worker_earnings')
            ->join("payments p", "p.booking_id = b.id AND p.payment_type = 'worker_payout'", 'left')
            ->where('b.status', 'completed')
            ->where('p.id IS NULL', null, false)
            ->get()
            ->getRowArray();

        return [
            'total_payments' => $completedBookingsCount,
            'completed_payments' => $completedBookingsCount - $bookingsWithoutCompletedPaymentCount,
            'pending_payments' => $bookingsWithoutCompletedPaymentCount,
            'total_collected' => $completedBookingsRevenue['total_fee'] ?? 0,
            'pending_payouts' => (float) ($recordedPendingPayouts['amount'] ?? 0) + (float) ($unrecordedPendingPayouts['worker_earnings'] ?? 0),
        ];
    }

    /**
     * Get recent bookings
     */
    private function getCustomerBookings($userId, $limit = 10)
    {
        return $this->bookingModel
            ->select('bookings.*, workers.first_name AS worker_first_name, workers.last_name AS worker_last_name, services.name AS service_name, services.category AS service_category')
            ->join('users AS workers', 'workers.id = bookings.worker_id', 'left')
            ->join('services', 'services.id = bookings.service_id', 'left')
            ->where('bookings.customer_id', $userId)
            ->orderBy('bookings.created_at', 'DESC')
            ->limit($limit)
            ->find();
    }

    /**
     * Get owner bookings
     */
    private function getOwnerBookings($userId, $limit = 10)
    {
        return $this->bookingModel
            ->select('bookings.*, workers.first_name AS worker_first_name, workers.last_name AS worker_last_name, services.name AS service_name, services.category AS service_category')
            ->join('users AS workers', 'workers.id = bookings.worker_id', 'left')
            ->join('services', 'services.id = bookings.service_id', 'left')
            ->where('bookings.customer_id', $userId)
            ->orderBy('bookings.created_at', 'DESC')
            ->limit($limit)
            ->find();
    }

    /**
     * Get worker bookings
     */
    private function getWorkerBookings($userId, $limit = 10)
    {
        return $this->bookingModel
            ->select('bookings.*, customers.first_name AS customer_first_name, customers.last_name AS customer_last_name, services.name AS service_name, services.category AS service_category')
            ->join('users AS customers', 'customers.id = bookings.customer_id', 'left')
            ->join('services', 'services.id = bookings.service_id', 'left')
            ->where('bookings.worker_id', $userId)
            ->orderBy('bookings.created_at', 'DESC')
            ->limit($limit)
            ->find();
    }

    /**
     * Get all recent bookings
     */
    private function getAllRecentBookings($limit = 10)
    {
        return $this->bookingModel
            ->select('bookings.*, customers.first_name AS customer_first_name, customers.last_name AS customer_last_name, workers.first_name AS worker_first_name, workers.last_name AS worker_last_name, services.name AS service_name')
            ->join('users AS customers', 'customers.id = bookings.customer_id', 'left')
            ->join('users AS workers', 'workers.id = bookings.worker_id', 'left')
            ->join('services', 'services.id = bookings.service_id', 'left')
            ->orderBy('bookings.created_at', 'DESC')
            ->limit($limit)
            ->find();
    }

    /**
     * Get recent transactions (payments) for finance dashboard
     */
    private function getRecentTransactions($limit = 10)
    {
        return $this->bookingModel
            ->select("bookings.id AS booking_id,
                     bookings.booking_reference,
                     bookings.title,
                     bookings.total_fee,
                     bookings.status AS booking_status,
                     customer_payments.id AS payment_id,
                     customer_payments.payment_reference,
                     customer_payments.amount,
                     customer_payments.payment_method,
                     customer_payments.status AS payment_status,
                     COALESCE(customer_payments.created_at, bookings.created_at) AS transaction_created_at,
                     customers.first_name AS customer_first_name,
                     customers.last_name AS customer_last_name,
                     workers.first_name AS worker_first_name,
                     workers.last_name AS worker_last_name", false)
            ->join("payments AS customer_payments", "customer_payments.booking_id = bookings.id AND customer_payments.payment_type = 'customer_payment'", 'left')
            ->join('users AS customers', 'customers.id = bookings.customer_id', 'left')
            ->join('users AS workers', 'workers.id = bookings.worker_id', 'left')
            ->whereIn('bookings.status', ['completed', 'in_progress', 'assigned', 'pending'])
            ->orderBy('transaction_created_at', 'DESC', false)
            ->limit($limit)
            ->findAll();
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
            ->where('payment_type', 'customer_payment')
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
        $db = db_connect();
        return $db->table('bookings')
            ->select('services.name as service_name, COUNT(bookings.id) as count')
            ->join('services', 'services.id = bookings.service_id', 'left')
            ->where('bookings.customer_id', $userId)
            ->groupBy('bookings.service_id')
            ->orderBy('count', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();
    }

    private function getDailyCollections($days = 7)
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

    private function getPaymentMethods()
    {
        return $this->paymentModel
            ->select('payment_method, COUNT(*) as count')
            ->where('status', 'completed')
            ->groupBy('payment_method')
            ->findAll();
    }
}
