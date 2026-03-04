<?php

namespace App\Models;

use CodeIgniter\Model;

class ServiceRecordModel extends Model
{
    protected $table = 'service_records';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'booking_id',
        'customer_id',
        'provider_id',
        'service_id',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'address_text',
        'labor_fee',
        'platform_fee',
        'total_amount',
        'payment_status',
        'payment_ref',
        'customer_note',
        'provider_note',
        'admin_note'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}