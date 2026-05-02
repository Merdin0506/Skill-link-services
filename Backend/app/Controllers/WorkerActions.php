<?php

namespace App\Controllers;

use App\Models\BookingModel;
use App\Models\ServiceRecordModel;
use App\Models\UserModel;

class WorkerActions extends BaseController
{
    protected $bookingModel;
    protected $recordModel;
    protected $userModel;
    protected $session;

    public function __construct()
    {
        $this->bookingModel = new BookingModel();
        $this->recordModel = new ServiceRecordModel();
        $this->userModel = new UserModel();
        $this->session = session();
    }

    /**
     * Worker accepts a pending job
     */
    public function acceptJob($bookingId = null)
    {
        // Debug logging: record incoming accept attempts
        log_message('debug', 'acceptJob called for booking: ' . var_export($bookingId, true));

        $workerId = (int) $this->session->get('user_id');
        $booking = $this->bookingModel->find($bookingId);

        if (!$booking) {
            log_message('debug', 'acceptJob: booking not found for id ' . var_export($bookingId, true));
            return redirect()->back()->with('error', 'Booking not found');
        }

        // Check if booking is still pending
        if ($booking['status'] !== 'pending') {
            return redirect()->back()->with('error', 'This job is no longer available');
        }

        // Get worker details for commission calculation
        $worker = $this->userModel->find($workerId);
        if (!$worker || $worker['status'] !== 'active') {
            return redirect()->back()->with('error', 'Worker account not active');
        }

        try {
            // Use the assignWorker method which calculates earnings
            log_message('debug', "acceptJob: attempting assignWorker booking={$bookingId} worker={$workerId}");
            $success = $this->bookingModel->assignWorker($bookingId, $workerId, $workerId);
            log_message('debug', 'acceptJob: assignWorker result: ' . var_export($success, true));
            
            if ($success) {
                log_message('debug', 'acceptJob: success redirecting to my-jobs for booking ' . $bookingId);
                return redirect()->to('/worker/my-jobs')->with('success', 'Job accepted successfully! Reference: ' . $booking['booking_reference']);
            } else {
                log_message('debug', 'acceptJob: assignWorker returned false for booking ' . $bookingId);
                return redirect()->back()->with('error', 'Failed to accept job. Please try again.');
            }
        } catch (\Exception $e) {
            log_message('error', 'Job acceptance error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while accepting the job');
        }
    }

    /**
     * Worker starts working on assigned job
     */
    public function startJob($bookingId = null)
    {

        $workerId = (int) $this->session->get('user_id');
        $booking = $this->bookingModel->find($bookingId);

        if (!$booking) {
            return redirect()->back()->with('error', 'Booking not found');
        }

        // Verify this booking is assigned to this worker
        if ($booking['worker_id'] != $workerId) {
            return redirect()->back()->with('error', 'This is not your assigned job');
        }

        // Check if booking is in assigned status
        if ($booking['status'] !== 'assigned') {
            return redirect()->back()->with('error', 'Job cannot be started at this time');
        }

        try {
            $success = $this->bookingModel->startBooking($bookingId);
            
            if ($success) {
                return redirect()->back()->with('success', 'Job started successfully!');
            } else {
                return redirect()->back()->with('error', 'Failed to start job');
            }
        } catch (\Exception $e) {
            log_message('error', 'Job start error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred');
        }
    }

    /**
     * Show complete job form (with payment collection)
     */
    public function completeJobForm($bookingId = null)
    {

        $workerId = (int) $this->session->get('user_id');
        $booking = $this->bookingModel
            ->select('b.*, c.first_name as customer_first_name, c.last_name as customer_last_name')
            ->from('bookings b')
            ->join('users c', 'c.id = b.customer_id')
            ->where('b.id', $bookingId)
            ->first();

        if (!$booking) {
            return redirect()->back()->with('error', 'Booking not found');
        }

        // Verify this booking is assigned to this worker
        if ($booking['worker_id'] != $workerId) {
            return redirect()->back()->with('error', 'This is not your assigned job');
        }

        // Check if booking is in progress
        if ($booking['status'] !== 'in_progress') {
            return redirect()->back()->with('error', 'Job must be in progress to complete');
        }

        $data = [
            'role' => $this->session->get('user_role'),
            'booking' => $booking
        ];

        return view('dashboard/worker_complete_job_form', $data);
    }

    /**
     * Worker completes the job and records payment
     */
    public function completeJob($bookingId = null)
    {

        $workerId = (int) $this->session->get('user_id');
        $booking = $this->bookingModel->find($bookingId);

        if (!$booking) {
            return redirect()->back()->with('error', 'Booking not found');
        }

        // Verify this booking is assigned to this worker
        if ($booking['worker_id'] != $workerId) {
            return redirect()->back()->with('error', 'This is not your assigned job');
        }

        // Check if booking is in progress
        if ($booking['status'] !== 'in_progress') {
            return redirect()->back()->with('error', 'Job must be started before completing');
        }

        // Validate payment data
        if (!$this->validate([
            'amount_collected' => 'required|numeric|greater_than[0]|less_than_equal_to[' . $booking['total_fee'] . ']',
            'payment_method' => 'required|in_list[cash,gcash,paymaya,bank_transfer]',
            'payment_notes' => 'permit_empty|string|max_length[500]'
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $db = \Config\Database::connect();
            $db->transStart();

            // Complete the booking
            $success = $this->bookingModel->completeBooking($bookingId);
            
            if (!$success) {
                $db->transRollback();
                return redirect()->back()->with('error', 'Failed to complete job');
            }

            // Record customer payment
            $paymentModel = new \App\Models\PaymentModel();
            
            $paymentNotes = $this->request->getPost('payment_notes') ?? '';
            $notes = trim($paymentNotes) ? $paymentNotes . ' (Collected by worker on-site)' : 'Collected by worker on-site';
            
            $paymentData = [
                'booking_id' => $bookingId,
                'payment_reference' => $paymentModel->generatePaymentReference('CUST'),
                'payment_type' => 'customer_payment',
                'payment_method' => $this->request->getPost('payment_method'),
                'amount' => (float) $this->request->getPost('amount_collected'),
                'payment_date' => date('Y-m-d'),
                'status' => 'completed',
                'paid_by' => $booking['customer_id'],
                'notes' => $notes,
                'processed_by' => $workerId
            ];

            if (!$paymentModel->insert($paymentData, false)) {
                $db->transRollback();
                $errors = $paymentModel->errors();
                log_message('error', 'Payment insert failed: ' . json_encode($errors));
                return redirect()->back()->with('error', 'Failed to record payment: ' . implode(', ', $errors));
            }

            // Ensure service_records is synchronized with completed booking data.
            $updatedBooking = $this->bookingModel->find($bookingId);
            $existingRecord = $this->recordModel->where('booking_id', $bookingId)->first();

            $recordData = [
                'booking_id' => $bookingId,
                'customer_id' => (int) ($updatedBooking['customer_id'] ?? $booking['customer_id']),
                'provider_id' => (int) ($updatedBooking['worker_id'] ?? $workerId),
                'service_id' => (int) ($updatedBooking['service_id'] ?? $booking['service_id']),
                'status' => 'completed',
                'scheduled_at' => trim(($updatedBooking['scheduled_date'] ?? '') . ' ' . ($updatedBooking['scheduled_time'] ?? '')) ?: null,
                'started_at' => $updatedBooking['started_at'] ?? null,
                'completed_at' => $updatedBooking['completed_at'] ?? date('Y-m-d H:i:s'),
                'address_text' => $updatedBooking['location_address'] ?? null,
                'labor_fee' => (float) ($updatedBooking['labor_fee'] ?? $booking['labor_fee'] ?? 0),
                'platform_fee' => (float) ($updatedBooking['commission_amount'] ?? 0),
                'total_amount' => (float) ($updatedBooking['total_fee'] ?? $booking['total_fee'] ?? 0),
                'payment_status' => 'paid',
                'payment_ref' => $paymentData['payment_reference'],
                'customer_note' => $updatedBooking['description'] ?? null,
                'provider_note' => $notes,
                'admin_note' => 'Auto-generated from completed booking',
            ];

            if ($existingRecord) {
                if (!$this->recordModel->update((int) $existingRecord['id'], $recordData)) {
                    $db->transRollback();
                    $errors = $this->recordModel->errors();
                    log_message('error', 'Service record update failed: ' . json_encode($errors));
                    return redirect()->back()->with('error', 'Failed to sync service record: ' . implode(', ', $errors));
                }
            } else {
                if (!$this->recordModel->insert($recordData, false)) {
                    $db->transRollback();
                    $errors = $this->recordModel->errors();
                    log_message('error', 'Service record insert failed: ' . json_encode($errors));
                    return redirect()->back()->with('error', 'Failed to create service record: ' . implode(', ', $errors));
                }
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return redirect()->back()->with('error', 'Transaction failed');
            }

            $earnings = (float) ($booking['worker_earnings'] ?? 0);
            return redirect()->to('/worker/my-jobs')->with('success', 'Job completed and payment recorded! You earned ₱' . number_format($earnings, 2));
        } catch (\Exception $e) {
            log_message('error', 'Job completion error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred');
        }
    }

    /**
     * Admin assigns worker to a booking
     */
    public function adminAssign()
    {

        $bookingId = $this->request->getPost('booking_id');
        $workerId = $this->request->getPost('worker_id');

        if (!$bookingId || !$workerId) {
            return redirect()->back()->with('error', 'Invalid data provided');
        }

        $booking = $this->bookingModel->find($bookingId);
        if (!$booking) {
            return redirect()->back()->with('error', 'Booking not found');
        }

        $worker = $this->userModel->find($workerId);
        if (!$worker || $worker['user_type'] !== 'worker') {
            return redirect()->back()->with('error', 'Invalid worker selected');
        }

        try {
            $adminId = (int) $this->session->get('user_id');
            $success = $this->bookingModel->assignWorker($bookingId, $workerId, $adminId);
            
            if ($success) {
                return redirect()->back()->with('success', 'Worker assigned successfully to booking ' . $booking['booking_reference']);
            } else {
                return redirect()->back()->with('error', 'Failed to assign worker');
            }
        } catch (\Exception $e) {
            log_message('error', 'Admin assignment error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred');
        }
    }
}
