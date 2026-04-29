<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Models\BookingModel;
use App\Models\PaymentModel;
use App\Models\ServiceModel;
use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;

class BookingsController extends BaseController
{
    use ResponseTrait;

    protected $bookingModel;
    protected $paymentModel;
    protected $serviceModel;
    protected $userModel;

    public function __construct()
    {
        $this->bookingModel = new BookingModel();
        $this->paymentModel = new PaymentModel();
        $this->serviceModel = new ServiceModel();
        $this->userModel = new UserModel();
    }

    public function index()
    {
        $status = $this->request->getVar('status');
        $userType = $this->request->getVar('user_type');
        $userId = $this->request->getVar('user_id');
        $limit = $this->getPositiveIntParam('limit', 50);

        if ($userType && $userId) {
            if ($userType === 'customer') {
                $bookings = $this->bookingModel->getCustomerBookings($userId, $status);
            } elseif ($userType === 'worker') {
                $bookings = $this->bookingModel->getWorkerBookings($userId, $status);
            } else {
                return $this->fail('Invalid user type');
            }
        } elseif ($status === 'pending') {
            $bookings = $this->bookingModel->getPendingBookings();
        } else {
            $bookings = $this->bookingModel
                ->when($status, function($query, $status) {
                    return $query->where('status', $status);
                })
                ->limit($limit)
                ->findAll();
        }

        return $this->respond([
            'status' => 'success',
            'data' => $bookings
        ]);
    }

    public function show($id = null)
    {
        $booking = $this->bookingModel->getBookingWithDetails($id);

        if (!$booking) {
            return $this->failNotFound('Booking not found');
        }

        return $this->respond([
            'status' => 'success',
            'data' => $booking
        ]);
    }

    public function availableJobs()
    {
        $limit = $this->getPositiveIntParam('limit', 50);
        $bookings = array_slice($this->bookingModel->getPendingBookings(), 0, $limit);

        return $this->respond([
            'status' => 'success',
            'data' => $bookings
        ]);
    }

    public function store()
    {
        $rules = [
            'customer_id' => 'required|integer',
            'service_id' => 'required|integer',
            'title' => 'required|min_length[3]|max_length[255]',
            'description' => 'max_length[1000]',
            'location_address' => 'required|min_length[5]',
            'scheduled_date' => 'required|valid_date[Y-m-d]',
            'scheduled_time' => 'required|valid_date[H:i]',
            'labor_fee' => 'required|numeric|greater_than[0]',
            'materials_fee' => 'numeric|greater_than_equal_to[0]',
            'priority' => 'required|in_list[low,medium,high,urgent]'
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        // Validate customer exists
        $customer = $this->userModel->find($this->request->getVar('customer_id'));
        if (!$customer || $customer['user_type'] !== 'customer') {
            return $this->fail('Invalid customer');
        }

        // Validate service exists
        $service = $this->serviceModel->find($this->request->getVar('service_id'));
        if (!$service) {
            return $this->fail('Invalid service');
        }

        $data = [
            'customer_id' => $this->request->getVar('customer_id'),
            'service_id' => $this->request->getVar('service_id'),
            'title' => $this->request->getVar('title'),
            'description' => $this->request->getVar('description'),
            'location_address' => $this->request->getVar('location_address'),
            'latitude' => $this->request->getVar('latitude'),
            'longitude' => $this->request->getVar('longitude'),
            'scheduled_date' => $this->request->getVar('scheduled_date'),
            'scheduled_time' => $this->request->getVar('scheduled_time'),
            'labor_fee' => $this->request->getVar('labor_fee'),
            'materials_fee' => $this->request->getVar('materials_fee') ?? 0,
            'priority' => $this->request->getVar('priority'),
            'status' => 'pending',
            'notes' => $this->request->getVar('notes')
        ];

        try {
            $bookingId = $this->bookingModel->createBooking($data);
            $booking = $this->bookingModel->getBookingWithDetails($bookingId);

            return $this->respondCreated([
                'status' => 'success',
                'message' => 'Booking created successfully',
                'data' => $booking
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to create booking: ' . $e->getMessage());
        }
    }

    public function assignWorker()
    {
        $rules = [
            'booking_id' => 'required|integer',
            'worker_id' => 'required|integer',
            'assigned_by' => 'required|integer'
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $bookingId = $this->request->getVar('booking_id');
        $workerId = $this->request->getVar('worker_id');

        // Validate booking exists and is pending
        $booking = $this->bookingModel->find($bookingId);
        if (!$booking || $booking['status'] !== 'pending') {
            return $this->fail('Invalid booking or booking cannot be assigned');
        }

        // Validate worker exists and is active
        $worker = $this->userModel->find($workerId);
        if (!$worker || $worker['user_type'] !== 'worker' || $worker['status'] !== 'active') {
            return $this->fail('Invalid worker');
        }

        try {
            $success = $this->bookingModel->assignWorker(
                $bookingId, 
                $workerId, 
                $this->request->getVar('assigned_by')
            );

            if ($success) {
                $updatedBooking = $this->bookingModel->getBookingWithDetails($bookingId);
                return $this->respond([
                    'status' => 'success',
                    'message' => 'Worker assigned successfully',
                    'data' => $updatedBooking
                ]);
            } else {
                return $this->fail('Failed to assign worker');
            }
        } catch (\Exception $e) {
            return $this->fail('Failed to assign worker: ' . $e->getMessage());
        }
    }

    public function acceptBooking($id = null)
    {
        $userId = (int) ($this->request->authUserId ?? 0);
        $userRole = (string) ($this->request->authUserRole ?? '');

        if ($userId <= 0) {
            return $this->failUnauthorized('Authenticated user not found');
        }

        if (!in_array($userRole, ['worker', 'admin', 'super_admin'], true)) {
            return $this->failForbidden('You do not have permission to accept this booking');
        }

        $booking = $this->bookingModel->find($id);

        if (!$booking) {
            return $this->failNotFound('Booking not found');
        }

        if (($booking['status'] ?? '') !== 'pending') {
            return $this->fail('This job is no longer available');
        }

        $worker = $this->userModel->find($userId);
        if (!$worker || ($worker['status'] ?? '') !== 'active') {
            return $this->fail('Worker account not active');
        }

        if (($worker['user_type'] ?? '') !== 'worker' && !in_array($userRole, ['admin', 'super_admin'], true)) {
            return $this->fail('Only worker accounts can be assigned to jobs');
        }

        try {
            $success = $this->bookingModel->assignWorker((int) $id, $userId, $userId);

            if (!$success) {
                return $this->fail('Failed to accept job');
            }

            $updatedBooking = $this->bookingModel->getBookingWithDetails($id);

            return $this->respond([
                'status' => 'success',
                'message' => 'Job accepted successfully',
                'data' => $updatedBooking
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to accept job: ' . $e->getMessage());
        }
    }

    public function startBooking($id = null)
    {
        $userId = (int) ($this->request->authUserId ?? 0);
        $booking = $this->bookingModel->find($id);

        if (!$booking) {
            return $this->failNotFound('Booking not found');
        }

        if ((int) ($booking['worker_id'] ?? 0) !== $userId) {
            return $this->failForbidden('This is not your assigned job');
        }

        if ($booking['status'] !== 'assigned') {
            return $this->fail('Booking cannot be started');
        }

        try {
            $success = $this->bookingModel->startBooking($id);
            
            if ($success) {
                $updatedBooking = $this->bookingModel->getBookingWithDetails($id);
                return $this->respond([
                    'status' => 'success',
                    'message' => 'Booking started successfully',
                    'data' => $updatedBooking
                ]);
            } else {
                return $this->fail('Failed to start booking');
            }
        } catch (\Exception $e) {
            return $this->fail('Failed to start booking: ' . $e->getMessage());
        }
    }

    public function completeBooking($id = null)
    {
        $userId = (int) ($this->request->authUserId ?? 0);
        $booking = $this->bookingModel->find($id);

        if (!$booking) {
            return $this->failNotFound('Booking not found');
        }

        if ((int) ($booking['worker_id'] ?? 0) !== $userId) {
            return $this->failForbidden('This is not your assigned job');
        }

        if ($booking['status'] !== 'in_progress') {
            return $this->fail('Booking cannot be completed');
        }

        try {
            $success = $this->bookingModel->completeBooking($id);
            
            if ($success) {
                $updatedBooking = $this->bookingModel->getBookingWithDetails($id);
                return $this->respond([
                    'status' => 'success',
                    'message' => 'Booking completed successfully',
                    'data' => $updatedBooking
                ]);
            } else {
                return $this->fail('Failed to complete booking');
            }
        } catch (\Exception $e) {
            return $this->fail('Failed to complete booking: ' . $e->getMessage());
        }
    }

    public function completeBookingWithPayment($id = null)
    {
        $userId = (int) ($this->request->authUserId ?? 0);
        $booking = $this->bookingModel->find($id);

        if (!$booking) {
            return $this->failNotFound('Booking not found');
        }

        if ((int) ($booking['worker_id'] ?? 0) !== $userId) {
            return $this->failForbidden('This is not your assigned job');
        }

        if (($booking['status'] ?? '') !== 'in_progress') {
            return $this->fail('Job must be started before completing');
        }

        $existingPayment = $this->paymentModel
            ->where('booking_id', (int) $id)
            ->where('payment_type', 'customer_payment')
            ->first();

        if ($existingPayment) {
            return $this->fail('Customer payment already exists for this booking');
        }

        $rules = [
            'amount_collected' => 'required|numeric|greater_than[0]|less_than_equal_to[' . $booking['total_fee'] . ']',
            'payment_method' => 'required|in_list[cash,gcash,paymaya,bank_transfer]',
            'payment_notes' => 'permit_empty|string|max_length[500]'
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $db = db_connect();
        $db->transStart();

        try {
            $completed = $this->bookingModel->completeBooking((int) $id);

            if (!$completed) {
                $db->transRollback();
                return $this->fail('Failed to complete job');
            }

            $paymentNotes = trim((string) ($this->request->getVar('payment_notes') ?? ''));
            $notes = $paymentNotes !== ''
                ? $paymentNotes . ' (Collected by worker on-site)'
                : 'Collected by worker on-site';

            $paymentData = [
                'booking_id' => (int) $id,
                'payment_reference' => $this->paymentModel->generatePaymentReference('CUST'),
                'payment_type' => 'customer_payment',
                'payment_method' => $this->request->getVar('payment_method'),
                'amount' => (float) $this->request->getVar('amount_collected'),
                'payment_date' => date('Y-m-d H:i:s'),
                'status' => 'completed',
                'paid_by' => (int) ($booking['customer_id'] ?? 0),
                'notes' => $notes,
                'processed_by' => $userId
            ];

            if (!$this->paymentModel->insert($paymentData, false)) {
                $db->transRollback();
                return $this->fail('Failed to record payment: ' . implode(', ', $this->paymentModel->errors()));
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->fail('Transaction failed');
            }

            $updatedBooking = $this->bookingModel->getBookingWithDetails($id);
            $payment = $this->paymentModel->where('booking_id', (int) $id)
                ->where('payment_reference', $paymentData['payment_reference'])
                ->first();

            return $this->respond([
                'status' => 'success',
                'message' => 'Job completed and payment recorded successfully',
                'data' => [
                    'booking' => $updatedBooking,
                    'payment' => $payment,
                ]
            ]);
        } catch (\Exception $e) {
            $db->transRollback();
            return $this->fail('Failed to complete booking: ' . $e->getMessage());
        }
    }

    public function cancelBooking($id = null)
    {
        $booking = $this->bookingModel->find($id);

        if (!$booking) {
            return $this->failNotFound('Booking not found');
        }

        if (!in_array($booking['status'], ['pending', 'assigned'])) {
            return $this->fail('Booking cannot be cancelled');
        }

        $reason = $this->request->getVar('reason');

        try {
            $success = $this->bookingModel->cancelBooking($id, $reason);
            
            if ($success) {
                $updatedBooking = $this->bookingModel->getBookingWithDetails($id);
                return $this->respond([
                    'status' => 'success',
                    'message' => 'Booking cancelled successfully',
                    'data' => $updatedBooking
                ]);
            } else {
                return $this->fail('Failed to cancel booking');
            }
        } catch (\Exception $e) {
            return $this->fail('Failed to cancel booking: ' . $e->getMessage());
        }
    }

    public function availableWorkers($serviceId = null)
    {
        if (!$serviceId) {
            return $this->fail('Service ID is required');
        }

        $service = $this->serviceModel->find($serviceId);
        if (!$service) {
            return $this->failNotFound('Service not found');
        }

        $workers = $this->bookingModel->getAvailableWorkers($service['category']);

        return $this->respond([
            'status' => 'success',
            'data' => $workers
        ]);
    }

    public function statistics()
    {
        $stats = [
            'today_bookings' => $this->bookingModel->getTodayBookings(),
            'weekly_bookings' => $this->bookingModel->getWeeklyBookings(),
            'monthly_bookings' => $this->bookingModel->getMonthlyBookings(),
            'pending_bookings' => $this->bookingModel->where('status', 'pending')->countAllResults(),
            'assigned_bookings' => $this->bookingModel->where('status', 'assigned')->countAllResults(),
            'in_progress_bookings' => $this->bookingModel->where('status', 'in_progress')->countAllResults(),
            'completed_bookings' => $this->bookingModel->where('status', 'completed')->countAllResults()
        ];

        return $this->respond([
            'status' => 'success',
            'data' => $stats
        ]);
    }
}
