<?php

namespace App\Models;

use CodeIgniter\Model;

class ReviewModel extends Model
{
    protected $table = 'reviews';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'booking_id',
        'customer_id',
        'worker_id',
        'rating',
        'comment',
        'service_quality',
        'timeliness',
        'professionalism',
        'would_recommend',
        'status'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'booking_id' => 'required|integer',
        'customer_id' => 'required|integer',
        'worker_id' => 'required|integer',
        'rating' => 'required|integer|greater_than_equal_to[1]|less_than_equal_to[5]',
        'service_quality' => 'required|integer|greater_than_equal_to[1]|less_than_equal_to[5]',
        'timeliness' => 'required|integer|greater_than_equal_to[1]|less_than_equal_to[5]',
        'professionalism' => 'required|integer|greater_than_equal_to[1]|less_than_equal_to[5]',
        'status' => 'required|in_list[published,hidden,flagged]'
    ];

    public function createReview($data)
    {
        // Check if review already exists for this booking
        $existing = $this->where('booking_id', $data['booking_id'])->first();
        if ($existing) {
            return false;
        }

        return $this->insert($data);
    }

    public function getReviewWithDetails($reviewId)
    {
        return $this->select('reviews.*, 
                             bookings.booking_reference,
                             customers.first_name as customer_first_name,
                             customers.last_name as customer_last_name,
                             workers.first_name as worker_first_name,
                             workers.last_name as worker_last_name,
                             services.name as service_name')
                    ->join('bookings', 'bookings.id = reviews.booking_id')
                    ->join('users as customers', 'customers.id = reviews.customer_id')
                    ->join('users as workers', 'workers.id = reviews.worker_id')
                    ->join('services', 'services.id = bookings.service_id')
                    ->where('reviews.id', $reviewId)
                    ->first();
    }

    public function getWorkerReviews($workerId, $status = 'published')
    {
        return $this->select('reviews.*, 
                             customers.first_name as customer_first_name,
                             customers.last_name as customer_last_name,
                             bookings.booking_reference,
                             services.name as service_name')
                    ->join('users as customers', 'customers.id = reviews.customer_id')
                    ->join('bookings', 'bookings.id = reviews.booking_id')
                    ->join('services', 'services.id = bookings.service_id')
                    ->where('reviews.worker_id', $workerId)
                    ->where('reviews.status', $status)
                    ->orderBy('reviews.created_at', 'DESC')
                    ->findAll();
    }

    public function getWorkerAverageRating($workerId)
    {
        $result = $this->select('AVG(rating) as average_rating, COUNT(*) as total_reviews')
                    ->where('worker_id', $workerId)
                    ->where('status', 'published')
                    ->first();
        
        return [
            'average_rating' => $result ? round($result['average_rating'], 2) : 0,
            'total_reviews' => $result ? $result['total_reviews'] : 0
        ];
    }

    public function getWorkerDetailedRatings($workerId)
    {
        $result = $this->select('AVG(rating) as overall_rating,
                             AVG(service_quality) as service_quality,
                             AVG(timeliness) as timeliness,
                             AVG(professionalism) as professionalism,
                             COUNT(*) as total_reviews')
                    ->where('worker_id', $workerId)
                    ->where('status', 'published')
                    ->first();
        
        return [
            'overall_rating' => $result ? round($result['overall_rating'], 2) : 0,
            'service_quality' => $result ? round($result['service_quality'], 2) : 0,
            'timeliness' => $result ? round($result['timeliness'], 2) : 0,
            'professionalism' => $result ? round($result['professionalism'], 2) : 0,
            'total_reviews' => $result ? $result['total_reviews'] : 0
        ];
    }

    public function getServiceReviews($serviceId, $status = 'published')
    {
        return $this->select('reviews.*, 
                             customers.first_name as customer_first_name,
                             customers.last_name as customer_last_name,
                             workers.first_name as worker_first_name,
                             workers.last_name as worker_last_name')
                    ->join('bookings', 'bookings.id = reviews.booking_id')
                    ->join('users as customers', 'customers.id = reviews.customer_id')
                    ->join('users as workers', 'workers.id = reviews.worker_id')
                    ->where('bookings.service_id', $serviceId)
                    ->where('reviews.status', $status)
                    ->orderBy('reviews.created_at', 'DESC')
                    ->findAll();
    }

    public function getTopWorkers($limit = 10, $minReviews = 5)
    {
        return $this->select('workers.id, workers.first_name, workers.last_name,
                             AVG(reviews.rating) as average_rating,
                             COUNT(reviews.id) as total_reviews')
                    ->join('users as workers', 'workers.id = reviews.worker_id')
                    ->where('reviews.status', 'published')
                    ->groupBy('workers.id')
                    ->having('total_reviews >=', $minReviews)
                    ->orderBy('average_rating', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    public function getRecentReviews($limit = 10)
    {
        return $this->select('reviews.*, 
                             customers.first_name as customer_first_name,
                             customers.last_name as customer_last_name,
                             workers.first_name as worker_first_name,
                             workers.last_name as worker_last_name,
                             services.name as service_name')
                    ->join('users as customers', 'customers.id = reviews.customer_id')
                    ->join('users as workers', 'workers.id = reviews.worker_id')
                    ->join('bookings', 'bookings.id = reviews.booking_id')
                    ->join('services', 'services.id = bookings.service_id')
                    ->where('reviews.status', 'published')
                    ->orderBy('reviews.created_at', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    public function getFlaggedReviews()
    {
        return $this->select('reviews.*, 
                             customers.first_name as customer_first_name,
                             customers.last_name as customer_last_name,
                             workers.first_name as worker_first_name,
                             workers.last_name as worker_last_name')
                    ->join('users as customers', 'customers.id = reviews.customer_id')
                    ->join('users as workers', 'workers.id = reviews.worker_id')
                    ->where('reviews.status', 'flagged')
                    ->orderBy('reviews.created_at', 'DESC')
                    ->findAll();
    }

    public function updateReviewStatus($reviewId, $status)
    {
        return $this->update($reviewId, ['status' => $status]);
    }

    public function getRatingDistribution($workerId = null)
    {
        $query = $this->select('rating, COUNT(*) as count')
                    ->where('status', 'published');
        
        if ($workerId) {
            $query->where('worker_id', $workerId);
        }
        
        $results = $query->groupBy('rating')->findAll();
        
        $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($results as $result) {
            $distribution[$result['rating']] = (int)$result['count'];
        }
        
        return $distribution;
    }

    public function canReview($customerId, $bookingId)
    {
        $bookingModel = new BookingModel();
        $booking = $bookingModel->find($bookingId);
        
        if (!$booking || $booking['customer_id'] != $customerId) {
            return false;
        }
        
        if ($booking['status'] != 'completed') {
            return false;
        }
        
        $existing = $this->where('booking_id', $bookingId)->first();
        if ($existing) {
            return false;
        }
        
        return true;
    }
}
