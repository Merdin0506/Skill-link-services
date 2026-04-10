<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Models\PaymentModel;
use App\Models\BookingModel;
use CodeIgniter\API\ResponseTrait;

class PaymentsController extends BaseController
{
    use ResponseTrait;

    protected $paymentModel;
    protected $bookingModel;

    public function __construct()
    {
        $this->paymentModel = new PaymentModel();
        $this->bookingModel = new BookingModel();
    }

    public function index()
    {
        $status = $this->request->getVar('status');
        $paymentType = $this->request->getVar('payment_type');
        $userId = $this->request->getVar('user_id');
        $userType = $this->request->getVar('user_type');
        $limit = $this->getPositiveIntParam('limit', 50);

        if ($status === 'pending') {
            $payments = $this->paymentModel->getPendingPayments();
        } elseif ($userType && $userId) {
            if ($userType === 'customer') {
                $payments = $this->paymentModel->getCustomerPayments($userId);
            } elseif ($userType === 'worker') {
                $payments = $this->paymentModel->getWorkerPayouts($userId);
            } else {
                return $this->fail('Invalid user type');
            }
        } else {
            $payments = $this->paymentModel
                ->when($status, function($query, $status) {
                    return $query->where('status', $status);
                })
                ->when($paymentType, function($query, $paymentType) {
                    return $query->where('payment_type', $paymentType);
                })
                ->limit($limit)
                ->findAll();
        }

        return $this->respond([
            'status' => 'success',
            'data' => $payments
        ]);
    }

    public function show($id = null)
    {
        $payment = $this->paymentModel->getPaymentWithDetails($id);

        if (!$payment) {
            return $this->failNotFound('Payment not found');
        }

        return $this->respond([
            'status' => 'success',
            'data' => $payment
        ]);
    }

    public function createCustomerPayment()
    {
        $rules = [
            'booking_id' => 'required|integer',
            'payment_method' => 'required|in_list[cash,gcash,paymaya,bank_transfer,credit_card]',
            'processed_by' => 'integer'
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $bookingId = $this->request->getVar('booking_id');
        $booking = $this->bookingModel->find($bookingId);

        if (!$booking) {
            return $this->fail('Booking not found');
        }

        if ($booking['status'] !== 'completed') {
            return $this->fail('Payment can only be processed for completed bookings');
        }

        // Check if payment already exists
        $existingPayment = $this->paymentModel
            ->where('booking_id', $bookingId)
            ->where('payment_type', 'customer_payment')
            ->first();

        if ($existingPayment) {
            return $this->fail('Payment already exists for this booking');
        }

        try {
            $paymentId = $this->paymentModel->createCustomerPayment(
                $bookingId,
                $this->request->getVar('payment_method'),
                $this->request->getVar('processed_by')
            );

            if ($paymentId) {
                $payment = $this->paymentModel->getPaymentWithDetails($paymentId);
                return $this->respondCreated([
                    'status' => 'success',
                    'message' => 'Customer payment created successfully',
                    'data' => $payment
                ]);
            } else {
                return $this->fail('Failed to create customer payment');
            }
        } catch (\Exception $e) {
            return $this->fail('Failed to create customer payment: ' . $e->getMessage());
        }
    }

    public function createWorkerPayout()
    {
        $rules = [
            'booking_id' => 'required|integer',
            'processed_by' => 'integer'
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $bookingId = $this->request->getVar('booking_id');
        $booking = $this->bookingModel->find($bookingId);

        if (!$booking) {
            return $this->fail('Booking not found');
        }

        if ($booking['status'] !== 'completed') {
            return $this->fail('Payout can only be processed for completed bookings');
        }

        if (!$booking['worker_id']) {
            return $this->fail('No worker assigned to this booking');
        }

        // Check if customer payment is completed
        $customerPayment = $this->paymentModel
            ->where('booking_id', $bookingId)
            ->where('payment_type', 'customer_payment')
            ->where('status', 'completed')
            ->first();

        if (!$customerPayment) {
            return $this->fail('Customer payment must be completed before worker payout');
        }

        // Check if payout already exists
        $existingPayout = $this->paymentModel
            ->where('booking_id', $bookingId)
            ->where('payment_type', 'worker_payout')
            ->first();

        if ($existingPayout) {
            return $this->fail('Payout already exists for this booking');
        }

        try {
            $payoutId = $this->paymentModel->createWorkerPayout(
                $bookingId,
                $this->request->getVar('processed_by')
            );

            if ($payoutId) {
                $payout = $this->paymentModel->getPaymentWithDetails($payoutId);
                return $this->respondCreated([
                    'status' => 'success',
                    'message' => 'Worker payout created successfully',
                    'data' => $payout
                ]);
            } else {
                return $this->fail('Failed to create worker payout');
            }
        } catch (\Exception $e) {
            return $this->fail('Failed to create worker payout: ' . $e->getMessage());
        }
    }

    public function processPayment($id = null)
    {
        $payment = $this->paymentModel->find($id);

        if (!$payment) {
            return $this->failNotFound('Payment not found');
        }

        if ($payment['status'] !== 'pending') {
            return $this->fail('Payment cannot be processed');
        }

        $rules = [
            'status' => 'required|in_list[completed,failed]',
            'transaction_id' => 'max_length[255]',
            'processed_by' => 'integer'
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        try {
            $success = $this->paymentModel->processPayment(
                $id,
                $this->request->getVar('status'),
                $this->request->getVar('transaction_id'),
                $this->request->getVar('processed_by')
            );

            if ($success) {
                $updatedPayment = $this->paymentModel->getPaymentWithDetails($id);
                return $this->respond([
                    'status' => 'success',
                    'message' => 'Payment processed successfully',
                    'data' => $updatedPayment
                ]);
            } else {
                return $this->fail('Failed to process payment');
            }
        } catch (\Exception $e) {
            return $this->fail('Failed to process payment: ' . $e->getMessage());
        }
    }

    public function paymentMethods()
    {
        $methods = $this->paymentModel->getPaymentMethods();

        return $this->respond([
            'status' => 'success',
            'data' => $methods
        ]);
    }

    public function statistics()
    {
        $stats = [
            'total_revenue' => $this->paymentModel->getTotalRevenue(),
            'today_payments' => $this->paymentModel->getTodayPayments(),
            'monthly_revenue' => $this->paymentModel->getMonthlyRevenue(),
            'pending_payments' => $this->paymentModel->where('status', 'pending')->countAllResults(),
            'completed_payments' => $this->paymentModel->where('status', 'completed')->countAllResults(),
            'customer_payments_total' => $this->paymentModel
                ->where('payment_type', 'customer_payment')
                ->where('status', 'completed')
                ->selectSum('amount')
                ->get()
                ->getRow()->amount ?? 0,
            'worker_payouts_total' => $this->paymentModel
                ->where('payment_type', 'worker_payout')
                ->where('status', 'completed')
                ->selectSum('amount')
                ->get()
                ->getRow()->amount ?? 0
        ];

        return $this->respond([
            'status' => 'success',
            'data' => $stats
        ]);
    }

    public function workerEarnings($workerId = null)
    {
        if (!$workerId) {
            return $this->fail('Worker ID is required');
        }

        $startDate = $this->request->getVar('start_date');
        $endDate = $this->request->getVar('end_date');

        $earnings = $this->paymentModel->getWorkerEarnings($workerId, $startDate, $endDate);
        $payouts = $this->paymentModel->getWorkerPayouts($workerId);

        return $this->respond([
            'status' => 'success',
            'data' => [
                'total_earnings' => $earnings,
                'payouts' => $payouts
            ]
        ]);
    }

    public function revenueReport()
    {
        $startDate = $this->request->getVar('start_date');
        $endDate = $this->request->getVar('end_date');
        $groupBy = $this->request->getVar('group_by') ?? 'day'; // day, week, month

        $query = $this->paymentModel
            ->select('DATE(payment_date) as date, SUM(amount) as revenue, COUNT(*) as count')
            ->where('payment_type', 'customer_payment')
            ->where('status', 'completed')
            ->groupBy('DATE(payment_date)')
            ->orderBy('date', 'ASC');

        if ($startDate) {
            $query->where('payment_date >=', $startDate);
        }

        if ($endDate) {
            $query->where('payment_date <=', $endDate);
        }

        $revenueData = $query->findAll();

        return $this->respond([
            'status' => 'success',
            'data' => $revenueData
        ]);
    }
}
