<?php

namespace App\Controllers;

use App\Models\PaymentModel;
use App\Models\BookingModel;
use App\Models\UserModel;

class Finance extends BaseController
{
    protected $paymentModel;
    protected $bookingModel;
    protected $userModel;
    protected $session;

    public function __construct()
    {
        $this->paymentModel = new PaymentModel();
        $this->bookingModel = new BookingModel();
        $this->userModel = new UserModel();
        $this->session = session();
    }

    /**
     * Customer Payments Management
     */
    public function payments()
    {

        // Get recorded customer payments from payments table
        $recordedPayments = $this->paymentModel
            ->select('payments.*, bookings.booking_reference, bookings.total_fee,
                     customers.first_name as customer_first_name, 
                     customers.last_name as customer_last_name,
                     processors.first_name as processor_first_name,
                     processors.last_name as processor_last_name,
                     processors.user_type as processor_type')
            ->join('bookings', 'bookings.id = payments.booking_id')
            ->join('users as customers', 'customers.id = payments.paid_by', 'left')
            ->join('users as processors', 'processors.id = payments.processed_by', 'left')
            ->where('payments.payment_type', 'customer_payment')
            ->findAll();

        // Get unrecorded payments (completed bookings with no customer_payment record)
        $db = \Config\Database::connect();
        $unrecordedPaymentsData = $db->table('bookings b')
            ->select('b.id, b.booking_reference, b.title, b.customer_id, b.total_fee, b.status, b.created_at,
                     c.first_name as customer_first_name, c.last_name as customer_last_name')
            ->join('users c', 'c.id = b.customer_id')
            ->join('payments p', 'p.booking_id = b.id AND p.payment_type = \'customer_payment\'', 'left')
            ->where('b.status', 'completed')
            ->where('p.id IS NULL', null, false)
            ->orderBy('b.created_at', 'DESC')
            ->get()
            ->getResultArray();

        // Combine both lists, formatting unrecorded as pending payments
        $payments = [];
        
        // Add recorded payments
        foreach ($recordedPayments as $payment) {
            $payments[] = array_merge($payment, ['is_unrecorded' => false]);
        }

        // Add unrecorded payments with pending status
        foreach ($unrecordedPaymentsData as $unrecorded) {
            $payments[] = array_merge($unrecorded, [
                'payment_reference' => null,
                'is_unrecorded' => true,
                'status' => 'pending',
                'payment_method' => 'pending_recording',
                'amount' => $unrecorded['total_fee'],
                'payment_date' => $unrecorded['created_at']
            ]);
        }

        // Sort all by date descending
        usort($payments, function($a, $b) {
            $dateA = strtotime($a['payment_date'] ?? $a['created_at']);
            $dateB = strtotime($b['payment_date'] ?? $b['created_at']);
            return $dateB - $dateA;
        });

        $data = [
            'role' => $this->session->get('user_role'),
            'user' => $this->getCurrentUser(),
            'payments' => $payments
        ];

        return view('dashboard/finance_payments', $data);
    }

    /**
     * Worker Payouts Management
     */
    public function payouts()
    {

        // Get recorded worker payouts from payments table
        $recordedPayouts = $this->paymentModel
            ->select('payments.*, bookings.booking_reference, bookings.title, bookings.worker_id,
                     workers.first_name as worker_first_name, 
                     workers.last_name as worker_last_name,
                     bookings.worker_earnings,
                     processors.first_name as processor_first_name,
                     processors.last_name as processor_last_name')
            ->join('bookings', 'bookings.id = payments.booking_id')
            ->join('users as workers', 'workers.id = payments.paid_to', 'left')
            ->join('users as processors', 'processors.id = payments.processed_by', 'left')
            ->where('payments.payment_type', 'worker_payout')
            ->findAll();

        // Get unrecorded payouts (completed bookings with no payout record)
        $db = \Config\Database::connect();
        $unrecordedPayoutsData = $db->table('bookings b')
            ->select('b.id, b.booking_reference, b.title, b.worker_id, b.worker_earnings, b.status, b.created_at,
                     w.first_name as worker_first_name, w.last_name as worker_last_name')
            ->join('users w', 'w.id = b.worker_id')
            ->join('payments p', 'p.booking_id = b.id AND p.payment_type = \'worker_payout\'', 'left')
            ->where('b.status', 'completed')
            ->where('p.id IS NULL', null, false)
            ->orderBy('b.created_at', 'DESC')
            ->get()
            ->getResultArray();

        // Combine both lists, formatting unrecorded as pending payouts
        $payouts = [];
        
        // Add recorded payouts
        foreach ($recordedPayouts as $payout) {
            $payouts[] = array_merge($payout, ['is_unrecorded' => false]);
        }

        // Add unrecorded payouts with pending status
        foreach ($unrecordedPayoutsData as $unrecorded) {
            $payouts[] = array_merge($unrecorded, [
                'payment_reference' => null,
                'is_unrecorded' => true,
                'status' => 'pending',
                'payment_method' => 'pending_recording',
                'amount' => $unrecorded['worker_earnings'],
                'payment_date' => $unrecorded['created_at']
            ]);
        }

        // Sort all by date descending
        usort($payouts, function($a, $b) {
            $dateA = strtotime($a['payment_date'] ?? $a['created_at']);
            $dateB = strtotime($b['payment_date'] ?? $b['created_at']);
            return $dateB - $dateA;
        });

        $data = [
            'role' => $this->session->get('user_role'),
            'user' => $this->getCurrentUser(),
            'payouts' => $payouts
        ];

        return view('dashboard/finance_payouts', $data);
    }

    /**
     * Financial Reports
     */
    public function reports()
    {

        // Get financial statistics
        $totalRevenue = $this->paymentModel->getTotalRevenue();
        $monthlyRevenue = $this->paymentModel->getMonthlyRevenue();

        // Get report analytics
        $db = db_connect();
        $dailyCollections = $db->table('payments')
            ->select('DATE(payment_date) AS report_date, SUM(amount) AS total_amount', false)
            ->where('payment_type', 'customer_payment')
            ->where('status', 'completed')
            ->where('payment_date IS NOT NULL', null, false)
            ->groupBy('DATE(payment_date)', false)
            ->orderBy('report_date', 'DESC')
            ->limit(7)
            ->get()
            ->getResultArray();

        $paymentMethods = $db->table('payments')
            ->select('payment_method, COUNT(*) AS payment_count', false)
            ->where('payment_type', 'customer_payment')
            ->where('status', 'completed')
            ->groupBy('payment_method')
            ->orderBy('payment_count', 'DESC')
            ->get()
            ->getResultArray();

        $paymentMethodTotal = 0;
        foreach ($paymentMethods as $method) {
            $paymentMethodTotal += (int) ($method['payment_count'] ?? 0);
        }
        
        // Get completed bookings
        $completedBookings = $this->bookingModel
            ->where('status', 'completed')
            ->countAllResults();

        // Get total commission earned
        $totalCommission = $this->bookingModel
            ->selectSum('commission_amount')
            ->where('status', 'completed')
            ->first();

        $data = [
            'role' => $this->session->get('user_role'),
            'user' => $this->getCurrentUser(),
            'stats' => [
                'total_revenue' => $totalRevenue,
                'monthly_revenue' => $monthlyRevenue['total_revenue'] ?? 0,
                'completed_bookings' => $completedBookings,
                'total_commission' => $totalCommission['commission_amount'] ?? 0
            ],
            'analytics' => [
                'daily_collections' => array_reverse($dailyCollections),
                'payment_methods' => $paymentMethods,
                'payment_method_total' => $paymentMethodTotal,
            ],
        ];

        return view('dashboard/finance_reports', $data);
    }

    /**
     * Record Customer Payment Form
     */
    public function recordPaymentForm($bookingId)
    {

        // Get booking details
        $booking = $this->bookingModel
            ->select('b.*, c.first_name as customer_first_name, c.last_name as customer_last_name')
            ->from('bookings b')
            ->join('users c', 'c.id = b.customer_id')
            ->where('b.id', $bookingId)
            ->first();

        if (!$booking) {
            return redirect()->to('/finance/payments')->with('error', 'Booking not found');
        }

        // Check if already has payment record
        $existingPayment = $this->paymentModel
            ->where('booking_id', $bookingId)
            ->where('payment_type', 'customer_payment')
            ->first();

        if ($existingPayment) {
            return redirect()->to('/finance/payments')->with('error', 'Payment already recorded for this booking');
        }

        $data = [
            'role' => $this->session->get('user_role'),
            'user' => $this->getCurrentUser(),
            'booking' => $booking
        ];

        return view('dashboard/finance_record_payment', $data);
    }

    /**
     * Store Customer Payment
     */
    public function storePayment($bookingId)
    {

        $booking = $this->bookingModel->find($bookingId);
        if (!$booking || $booking['status'] !== 'completed') {
            return redirect()->to('/finance/payments')->with('error', 'Invalid booking');
        }

        // Validate payment data
        if (!$this->validate([
            'payment_method' => 'required|in_list[cash,gcash,paymaya,bank_transfer,credit_card]',
            'amount' => 'required|numeric|greater_than[0]',
            'notes' => 'permit_empty|string|max_length[500]'
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $paymentData = [
            'booking_id' => $bookingId,
            'payment_type' => 'customer_payment',
            'payment_method' => $this->request->getPost('payment_method'),
            'amount' => (float) $this->request->getPost('amount'),
            'payment_date' => date('Y-m-d'),
            'status' => 'completed',
            'paid_by' => $booking['customer_id'],
            'notes' => $this->request->getPost('notes'),
            'processed_by' => $this->session->get('user_id'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($this->paymentModel->insert($paymentData)) {
            return redirect()->to('/finance/payments')->with('success', 'Payment recorded successfully');
        } else {
            return redirect()->back()->withInput()->with('error', 'Failed to record payment');
        }
    }

    /**
     * Record Worker Payout Form
     */
    public function recordPayoutForm($bookingId)
    {

        // Get booking details
        $booking = $this->bookingModel
            ->select('b.*, w.first_name as worker_first_name, w.last_name as worker_last_name')
            ->from('bookings b')
            ->join('users w', 'w.id = b.worker_id')
            ->where('b.id', $bookingId)
            ->first();

        if (!$booking) {
            return redirect()->to('/finance/payouts')->with('error', 'Booking not found');
        }

        // Check if already has payout record
        $existingPayout = $this->paymentModel
            ->where('booking_id', $bookingId)
            ->where('payment_type', 'worker_payout')
            ->first();

        if ($existingPayout) {
            return redirect()->to('/finance/payouts')->with('error', 'Payout already recorded for this booking');
        }

        $data = [
            'role' => $this->session->get('user_role'),
            'user' => $this->getCurrentUser(),
            'booking' => $booking
        ];

        return view('dashboard/finance_record_payout', $data);
    }

    /**
     * Store Worker Payout
     */
    public function storePayout($bookingId)
    {

        $booking = $this->bookingModel->find($bookingId);
        if (!$booking || $booking['status'] !== 'completed') {
            return redirect()->to('/finance/payouts')->with('error', 'Invalid booking');
        }

        // Validate payout data
        if (!$this->validate([
            'payment_method' => 'required|in_list[cash,gcash,paymaya,bank_transfer,credit_card]',
            'amount' => 'required|numeric|greater_than[0]|less_than_equal_to[' . $booking['worker_earnings'] . ']',
            'notes' => 'permit_empty|string|max_length[500]'
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $payoutNotes = $this->request->getPost('notes') ?? '';
        
        $payoutData = [
            'booking_id' => $bookingId,
            'payment_reference' => $this->paymentModel->generatePaymentReference('WORK'),
            'payment_type' => 'worker_payout',
            'payment_method' => $this->request->getPost('payment_method'),
            'amount' => (float) $this->request->getPost('amount'),
            'payment_date' => date('Y-m-d'),
            'status' => 'completed',
            'paid_to' => $booking['worker_id'],
            'notes' => $payoutNotes,
            'processed_by' => $this->session->get('user_id')
        ];

        if ($this->paymentModel->insert($payoutData, false)) {
            return redirect()->to('/finance/payouts')->with('success', 'Worker payout recorded successfully');
        } else {
            $errors = $this->paymentModel->errors();
            log_message('error', 'Payout insert failed: ' . json_encode($errors));
            return redirect()->back()->withInput()->with('error', 'Failed to record payout: ' . implode(', ', $errors));
        }
    }

    /**
     * Get current user data
     */
    private function getCurrentUser()
    {
        $userId = $this->session->get('user_id');
        return $this->userModel->find($userId);
    }
}
