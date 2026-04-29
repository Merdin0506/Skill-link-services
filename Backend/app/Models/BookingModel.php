<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingModel extends Model
{
    protected $table = 'bookings';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'customer_id',
        'worker_id',
        'service_id',
        'booking_reference',
        'title',
        'description',
        'location_address',
        'latitude',
        'longitude',
        'scheduled_date',
        'scheduled_time',
        'estimated_duration',
        'status',
        'priority',
        'labor_fee',
        'materials_fee',
        'total_fee',
        'commission_amount',
        'worker_earnings',
        'notes',
        'assigned_at',
        'started_at',
        'completed_at'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'customer_id' => 'required|integer',
        'service_id' => 'required|integer',
        'booking_reference' => 'required|max_length[50]',
        'title' => 'required|min_length[3]|max_length[255]',
        'location_address' => 'required|min_length[5]',
        'scheduled_date' => 'required|valid_date[Y-m-d]',
        'scheduled_time' => 'required|valid_date[H:i]',
        'status' => 'required|in_list[pending,assigned,in_progress,completed,cancelled,rejected]',
        'priority' => 'required|in_list[low,medium,high,urgent]',
        'labor_fee' => 'required|numeric|greater_than_equal_to[0]',
        'materials_fee' => 'numeric|greater_than_equal_to[0]',
        'total_fee' => 'required|numeric|greater_than_equal_to[0]'
    ];

    public function generateBookingReference()
    {
        do {
            $reference = 'SKL' . date('Y') . strtoupper(substr(uniqid(), -6));
            $exists = $this->where('booking_reference', $reference)->first();
        } while ($exists);
        
        return $reference;
    }

    public function createBooking($data)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $data['booking_reference'] = $this->generateBookingReference();
            $data['total_fee'] = $data['labor_fee'] + ($data['materials_fee'] ?? 0);

            $bookingId = $this->insert($data);
            if (!$bookingId) {
                throw new \Exception('Failed to insert booking record');
            }

            if (!$this->syncServiceRecordFromBooking((int) $bookingId)) {
                throw new \Exception('Failed to sync service record');
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Transaction failed');
            }

            return $bookingId;
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'createBooking Transaction failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getBookingWithDetails($bookingId)
    {
        return $this->select('bookings.*, 
                             customers.first_name as customer_first_name,
                             customers.last_name as customer_last_name,
                             customers.phone as customer_phone,
                             workers.first_name as worker_first_name,
                             workers.last_name as worker_last_name,
                             workers.phone as worker_phone,
                             services.name as service_name,
                             services.category as service_category')
                    ->join('users as customers', 'customers.id = bookings.customer_id')
                    ->join('users as workers', 'workers.id = bookings.worker_id', 'left')
                    ->join('services', 'services.id = bookings.service_id')
                    ->where('bookings.id', $bookingId)
                    ->first();
    }

    public function getCustomerBookings($customerId, $status = null)
    {
        $query = $this->select('bookings.*, services.name as service_name, services.category')
                      ->join('services', 'services.id = bookings.service_id')
                      ->where('bookings.customer_id', $customerId);
        
        if ($status) {
            $query->where('bookings.status', $status);
        }
        
        return $query->orderBy('bookings.created_at', 'DESC')->findAll();
    }

    public function getWorkerBookings($workerId, $status = null)
    {
        $query = $this->select('bookings.*, 
                             customers.first_name as customer_first_name,
                             customers.last_name as customer_last_name,
                             customers.phone as customer_phone,
                             services.name as service_name')
                      ->join('users as customers', 'customers.id = bookings.customer_id')
                      ->join('services', 'services.id = bookings.service_id')
                      ->where('bookings.worker_id', $workerId);
        
        if ($status) {
            $query->where('bookings.status', $status);
        }
        
        return $query->orderBy('bookings.scheduled_date', 'ASC')->findAll();
    }

    public function getPendingBookings()
    {
        return $this->select('bookings.*, 
                             customers.first_name as customer_first_name,
                             customers.last_name as customer_last_name,
                             customers.phone as customer_phone,
                             services.name as service_name,
                             services.category')
                    ->join('users as customers', 'customers.id = bookings.customer_id')
                    ->join('services', 'services.id = bookings.service_id')
                    ->where('bookings.status', 'pending')
                    ->orderBy('bookings.priority', 'DESC')
                    ->orderBy('bookings.created_at', 'ASC')
                    ->findAll();
    }

    public function assignWorker($bookingId, $workerId, $assignedBy)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $userModel = new UserModel();
            $worker = $userModel->find($workerId);
            $booking = $this->find($bookingId);
            
            if (!$worker || !$booking) {
                throw new \Exception('Worker or booking not found');
            }

            $commissionAmount = $booking['labor_fee'] * ($worker['commission_rate'] / 100);
            $workerEarnings = $booking['labor_fee'] - $commissionAmount;

            $updated = $this->update($bookingId, [
                'worker_id' => $workerId,
                'status' => 'assigned',
                'assigned_at' => date('Y-m-d H:i:s'),
                'commission_amount' => $commissionAmount,
                'worker_earnings' => $workerEarnings
            ]);

            if (!$updated) {
                throw new \Exception('Failed to update booking status');
            }

            if (!$this->syncServiceRecordFromBooking((int) $bookingId)) {
                throw new \Exception('Failed to sync service record');
            }

            $db->transComplete();
            return (bool) $db->transStatus();
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'assignWorker Transaction failed: ' . $e->getMessage());
            return false;
        }
    }

    public function startBooking($bookingId)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $updated = $this->update($bookingId, [
                'status' => 'in_progress',
                'started_at' => date('Y-m-d H:i:s')
            ]);

            if (!$updated) {
                throw new \Exception('Failed to update booking status to in_progress');
            }

            if (!$this->syncServiceRecordFromBooking((int) $bookingId)) {
                throw new \Exception('Failed to sync service record');
            }

            $db->transComplete();
            return (bool) $db->transStatus();
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'startBooking Transaction failed: ' . $e->getMessage());
            return false;
        }
    }

    public function completeBooking($bookingId)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $updated = $this->update($bookingId, [
                'status' => 'completed',
                'completed_at' => date('Y-m-d H:i:s')
            ]);

            if (!$updated) {
                throw new \Exception('Failed to update booking status to completed');
            }

            if (!$this->syncServiceRecordFromBooking((int) $bookingId)) {
                throw new \Exception('Failed to sync service record');
            }

            $db->transComplete();
            return (bool) $db->transStatus();
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'completeBooking Transaction failed: ' . $e->getMessage());
            return false;
        }
    }

    public function cancelBooking($bookingId, $reason = null)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $data = ['status' => 'cancelled'];
            if ($reason) {
                $data['notes'] = $reason;
            }
            $updated = $this->update($bookingId, $data);

            if (!$updated) {
                throw new \Exception('Failed to update booking status to cancelled');
            }

            if (!$this->syncServiceRecordFromBooking((int) $bookingId)) {
                throw new \Exception('Failed to sync service record');
            }

            $db->transComplete();
            return (bool) $db->transStatus();
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'cancelBooking Transaction failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Keep service_records in sync with booking lifecycle.
     */
    public function syncServiceRecordFromBooking(int $bookingId): bool
    {
        $booking = $this->find($bookingId);
        if (!$booking) {
            return false;
        }

        $db = db_connect();
        $recordModel = new ServiceRecordModel();

        $statusMap = [
            'pending' => 'pending',
            'assigned' => 'scheduled',
            'in_progress' => 'in_progress',
            'completed' => 'completed',
            'cancelled' => 'cancelled',
            'rejected' => 'cancelled',
        ];

        $recordStatus = $statusMap[$booking['status'] ?? 'pending'] ?? 'pending';

        $scheduledAt = null;
        if (!empty($booking['scheduled_date']) && !empty($booking['scheduled_time'])) {
            $scheduledAt = $booking['scheduled_date'] . ' ' . $booking['scheduled_time'];
        }

        $payment = $db->table('payments')
            ->select('payment_reference, status')
            ->where('booking_id', $bookingId)
            ->where('payment_type', 'customer_payment')
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();

        $paymentStatus = 'unpaid';
        if (!empty($payment)) {
            if (($payment['status'] ?? '') === 'completed') {
                $paymentStatus = 'paid';
            } elseif (($payment['status'] ?? '') === 'refunded') {
                $paymentStatus = 'refunded';
            } else {
                $paymentStatus = 'partial';
            }
        }

        $recordData = [
            'booking_id' => $bookingId,
            'customer_id' => (int) ($booking['customer_id'] ?? 0),
            'provider_id' => !empty($booking['worker_id']) ? (int) $booking['worker_id'] : null,
            'service_id' => (int) ($booking['service_id'] ?? 0),
            'status' => $recordStatus,
            'scheduled_at' => $scheduledAt,
            'started_at' => $booking['started_at'] ?? null,
            'completed_at' => $booking['completed_at'] ?? null,
            'address_text' => $booking['location_address'] ?? null,
            'labor_fee' => (float) ($booking['labor_fee'] ?? 0),
            'platform_fee' => (float) ($booking['commission_amount'] ?? 0),
            'total_amount' => (float) ($booking['total_fee'] ?? 0),
            'payment_status' => $paymentStatus,
            'payment_ref' => $payment['payment_reference'] ?? null,
            'customer_note' => $booking['description'] ?? null,
            'provider_note' => $booking['notes'] ?? null,
            'admin_note' => 'Auto-synced from booking lifecycle',
        ];

        $existing = $recordModel->where('booking_id', $bookingId)->first();
        if ($existing) {
            return (bool) $recordModel->update((int) $existing['id'], $recordData);
        }

        return (bool) $recordModel->insert($recordData, false);
    }

    public function getAvailableWorkers($serviceCategory)
    {
        $userModel = new UserModel();
        return $userModel->getWorkersBySkill($serviceCategory);
    }

    public function getTodayBookings()
    {
        return $this->where('scheduled_date', date('Y-m-d'))
                    ->whereIn('status', ['pending', 'assigned', 'in_progress'])
                    ->countAllResults();
    }

    public function getWeeklyBookings()
    {
        $startOfWeek = date('Y-m-d', strtotime('monday this week'));
        $endOfWeek = date('Y-m-d', strtotime('sunday this week'));
        
        return $this->where('scheduled_date >=', $startOfWeek)
                    ->where('scheduled_date <=', $endOfWeek)
                    ->countAllResults();
    }

    public function getMonthlyBookings()
    {
        return $this->where('MONTH(scheduled_date)', date('m'))
                    ->where('YEAR(scheduled_date)', date('Y'))
                    ->countAllResults();
    }
}
