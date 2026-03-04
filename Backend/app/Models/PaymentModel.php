<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentModel extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'booking_id',
        'payment_reference',
        'amount',
        'payment_method',
        'payment_type',
        'status',
        'transaction_id',
        'paid_by',
        'paid_to',
        'payment_date',
        'processed_by',
        'notes'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'booking_id' => 'required|integer',
        'payment_reference' => 'required|max_length[100]|is_unique[payments.payment_reference,id,{id}]',
        'amount' => 'required|numeric|greater_than[0]',
        'payment_method' => 'required|in_list[cash,gcash,paymaya,bank_transfer,credit_card]',
        'payment_type' => 'required|in_list[customer_payment,worker_payout]',
        'status' => 'required|in_list[pending,processing,completed,failed,refunded]'
    ];

    public function generatePaymentReference($type = 'PAY')
    {
        do {
            $reference = $type . date('Ymd') . strtoupper(substr(uniqid(), -6));
            $exists = $this->where('payment_reference', $reference)->first();
        } while ($exists);
        
        return $reference;
    }

    public function createCustomerPayment($bookingId, $paymentMethod, $processedBy = null)
    {
        $bookingModel = new BookingModel();
        $booking = $bookingModel->find($bookingId);
        
        if (!$booking) {
            return false;
        }

        return $this->insert([
            'booking_id' => $bookingId,
            'payment_reference' => $this->generatePaymentReference('CUST'),
            'amount' => $booking['total_fee'],
            'payment_method' => $paymentMethod,
            'payment_type' => 'customer_payment',
            'status' => 'pending',
            'paid_by' => $booking['customer_id'],
            'processed_by' => $processedBy
        ]);
    }

    public function createWorkerPayout($bookingId, $processedBy = null)
    {
        $bookingModel = new BookingModel();
        $booking = $bookingModel->find($bookingId);
        
        if (!$booking || !$booking['worker_id']) {
            return false;
        }

        return $this->insert([
            'booking_id' => $bookingId,
            'payment_reference' => $this->generatePaymentReference('WORK'),
            'amount' => $booking['worker_earnings'],
            'payment_method' => 'gcash', // Default payout method
            'payment_type' => 'worker_payout',
            'status' => 'pending',
            'paid_to' => $booking['worker_id'],
            'processed_by' => $processedBy
        ]);
    }

    public function processPayment($paymentId, $status, $transactionId = null, $processedBy = null)
    {
        $data = ['status' => $status];
        
        if ($status === 'completed') {
            $data['payment_date'] = date('Y-m-d H:i:s');
        }
        
        if ($transactionId) {
            $data['transaction_id'] = $transactionId;
        }
        
        if ($processedBy) {
            $data['processed_by'] = $processedBy;
        }
        
        return $this->update($paymentId, $data);
    }

    public function getPaymentWithDetails($paymentId)
    {
        return $this->select('payments.*, 
                             bookings.booking_reference,
                             bookings.title as booking_title,
                             paid_by_user.first_name as paid_by_first_name,
                             paid_by_user.last_name as paid_by_last_name,
                             paid_to_user.first_name as paid_to_first_name,
                             paid_to_user.last_name as paid_to_last_name,
                             processed_by_user.first_name as processed_by_first_name,
                             processed_by_user.last_name as processed_by_last_name')
                    ->join('bookings', 'bookings.id = payments.booking_id')
                    ->join('users as paid_by_user', 'paid_by_user.id = payments.paid_by', 'left')
                    ->join('users as paid_to_user', 'paid_to_user.id = payments.paid_to', 'left')
                    ->join('users as processed_by_user', 'processed_by_user.id = payments.processed_by', 'left')
                    ->where('payments.id', $paymentId)
                    ->first();
    }

    public function getPendingPayments()
    {
        return $this->select('payments.*, bookings.booking_reference, bookings.title')
                    ->join('bookings', 'bookings.id = payments.booking_id')
                    ->where('payments.status', 'pending')
                    ->orderBy('payments.created_at', 'ASC')
                    ->findAll();
    }

    public function getCustomerPayments($customerId)
    {
        return $this->select('payments.*, bookings.booking_reference, bookings.title')
                    ->join('bookings', 'bookings.id = payments.booking_id')
                    ->where('payments.paid_by', $customerId)
                    ->where('payments.payment_type', 'customer_payment')
                    ->orderBy('payments.created_at', 'DESC')
                    ->findAll();
    }

    public function getWorkerPayouts($workerId)
    {
        return $this->select('payments.*, bookings.booking_reference, bookings.title')
                    ->join('bookings', 'bookings.id = payments.booking_id')
                    ->where('payments.paid_to', $workerId)
                    ->where('payments.payment_type', 'worker_payout')
                    ->orderBy('payments.created_at', 'DESC')
                    ->findAll();
    }

    public function getWorkerEarnings($workerId, $startDate = null, $endDate = null)
    {
        $query = $this->select('SUM(amount) as total_earnings')
                    ->where('paid_to', $workerId)
                    ->where('payment_type', 'worker_payout')
                    ->where('status', 'completed');
        
        if ($startDate) {
            $query->where('payment_date >=', $startDate);
        }
        
        if ($endDate) {
            $query->where('payment_date <=', $endDate);
        }
        
        $result = $query->first();
        return $result ? $result['total_earnings'] : 0;
    }

    public function getTotalRevenue($startDate = null, $endDate = null)
    {
        $query = $this->select('SUM(amount) as total_revenue')
                    ->where('payment_type', 'customer_payment')
                    ->where('status', 'completed');
        
        if ($startDate) {
            $query->where('payment_date >=', $startDate);
        }
        
        if ($endDate) {
            $query->where('payment_date <=', $endDate);
        }
        
        $result = $query->first();
        return $result ? $result['total_revenue'] : 0;
    }

    public function getTodayPayments()
    {
        return $this->select('COUNT(*) as count, SUM(amount) as total')
                    ->where('payment_date', date('Y-m-d'))
                    ->where('status', 'completed')
                    ->first();
    }

    public function getMonthlyRevenue($year = null, $month = null)
    {
        $year = $year ?: date('Y');
        $month = $month ?: date('m');
        
        return $this->select('SUM(amount) as total_revenue')
                    ->where('payment_type', 'customer_payment')
                    ->where('status', 'completed')
                    ->where('YEAR(payment_date)', $year)
                    ->where('MONTH(payment_date)', $month)
                    ->first();
    }

    public function getPaymentMethods()
    {
        return [
            'cash' => 'Cash',
            'gcash' => 'GCash',
            'paymaya' => 'PayMaya',
            'bank_transfer' => 'Bank Transfer',
            'credit_card' => 'Credit Card'
        ];
    }
}
