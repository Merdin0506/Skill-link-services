<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Models\ReviewModel;
use App\Models\BookingModel;
use CodeIgniter\API\ResponseTrait;

class ReviewsController extends BaseController
{
    use ResponseTrait;

    protected $reviewModel;
    protected $bookingModel;

    public function __construct()
    {
        $this->reviewModel = new ReviewModel();
        $this->bookingModel = new BookingModel();
    }

    public function index()
    {
        $workerId = $this->request->getVar('worker_id');
        $serviceId = $this->request->getVar('service_id');
        $status = $this->request->getVar('status') ?? 'published';
        $limit = $this->getPositiveIntParam('limit', 50);

        if ($workerId) {
            $reviews = $this->reviewModel->getWorkerReviews($workerId, $status);
        } elseif ($serviceId) {
            $reviews = $this->reviewModel->getServiceReviews($serviceId, $status);
        } elseif ($status === 'flagged') {
            $reviews = $this->reviewModel->getFlaggedReviews();
        } else {
            $reviews = $this->reviewModel->getRecentReviews($limit);
        }

        return $this->respond([
            'status' => 'success',
            'data' => $reviews
        ]);
    }

    public function show($id = null)
    {
        $review = $this->reviewModel->getReviewWithDetails($id);

        if (!$review) {
            return $this->failNotFound('Review not found');
        }

        return $this->respond([
            'status' => 'success',
            'data' => $review
        ]);
    }

    public function store()
    {
        $rules = [
            'booking_id' => 'required|integer',
            'customer_id' => 'required|integer',
            'worker_id' => 'required|integer',
            'rating' => 'required|integer|greater_than_equal_to[1]|less_than_equal_to[5]',
            'comment' => 'max_length[1000]',
            'service_quality' => 'required|integer|greater_than_equal_to[1]|less_than_equal_to[5]',
            'timeliness' => 'required|integer|greater_than_equal_to[1]|less_than_equal_to[5]',
            'professionalism' => 'required|integer|greater_than_equal_to[1]|less_than_equal_to[5]',
            'would_recommend' => 'required|in_list[0,1]'
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $bookingId = $this->request->getVar('booking_id');
        $customerId = $this->request->getVar('customer_id');

        // Check if customer can review this booking
        if (!$this->reviewModel->canReview($customerId, $bookingId)) {
            return $this->fail('You cannot review this booking');
        }

        $data = [
            'booking_id' => $bookingId,
            'customer_id' => $customerId,
            'worker_id' => $this->request->getVar('worker_id'),
            'rating' => $this->request->getVar('rating'),
            'comment' => $this->request->getVar('comment'),
            'service_quality' => $this->request->getVar('service_quality'),
            'timeliness' => $this->request->getVar('timeliness'),
            'professionalism' => $this->request->getVar('professionalism'),
            'would_recommend' => $this->request->getVar('would_recommend'),
            'status' => 'published'
        ];

        try {
            $reviewId = $this->reviewModel->createReview($data);

            if ($reviewId) {
                $review = $this->reviewModel->getReviewWithDetails($reviewId);
                return $this->respondCreated([
                    'status' => 'success',
                    'message' => 'Review created successfully',
                    'data' => $review
                ]);
            } else {
                return $this->fail('Failed to create review');
            }
        } catch (\Exception $e) {
            return $this->fail('Failed to create review: ' . $e->getMessage());
        }
    }

    public function workerRating($workerId = null)
    {
        if (!$workerId) {
            return $this->fail('Worker ID is required');
        }

        $rating = $this->reviewModel->getWorkerAverageRating($workerId);
        $detailedRating = $this->reviewModel->getWorkerDetailedRatings($workerId);
        $distribution = $this->reviewModel->getRatingDistribution($workerId);

        return $this->respond([
            'status' => 'success',
            'data' => [
                'average_rating' => $rating,
                'detailed_ratings' => $detailedRating,
                'rating_distribution' => $distribution
            ]
        ]);
    }

    public function topWorkers()
    {
        $limit = $this->getPositiveIntParam('limit', 10);
        $minReviews = $this->request->getVar('min_reviews') ?? 5;

        $workers = $this->reviewModel->getTopWorkers($limit, $minReviews);

        return $this->respond([
            'status' => 'success',
            'data' => $workers
        ]);
    }

    public function updateStatus($id = null)
    {
        $review = $this->reviewModel->find($id);

        if (!$review) {
            return $this->failNotFound('Review not found');
        }

        $rules = [
            'status' => 'required|in_list[published,hidden,flagged]'
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        try {
            $success = $this->reviewModel->updateReviewStatus($id, $this->request->getVar('status'));

            if ($success) {
                $updatedReview = $this->reviewModel->getReviewWithDetails($id);
                return $this->respond([
                    'status' => 'success',
                    'message' => 'Review status updated successfully',
                    'data' => $updatedReview
                ]);
            } else {
                return $this->fail('Failed to update review status');
            }
        } catch (\Exception $e) {
            return $this->fail('Failed to update review status: ' . $e->getMessage());
        }
    }

    public function canReview()
    {
        $bookingId = $this->request->getVar('booking_id');
        $customerId = $this->request->getVar('customer_id');

        if (!$bookingId || !$customerId) {
            return $this->fail('Booking ID and Customer ID are required');
        }

        $canReview = $this->reviewModel->canReview($customerId, $bookingId);

        return $this->respond([
            'status' => 'success',
            'data' => [
                'can_review' => $canReview
            ]
        ]);
    }

    public function statistics()
    {
        $totalReviews = $this->reviewModel->where('status', 'published')->countAllResults();
        $averageRating = $this->reviewModel
            ->select('AVG(rating) as average')
            ->where('status', 'published')
            ->first()['average'] ?? 0;
        
        $flaggedReviews = $this->reviewModel->where('status', 'flagged')->countAllResults();
        $ratingDistribution = $this->reviewModel->getRatingDistribution();

        return $this->respond([
            'status' => 'success',
            'data' => [
                'total_reviews' => $totalReviews,
                'average_rating' => round($averageRating, 2),
                'flagged_reviews' => $flaggedReviews,
                'rating_distribution' => $ratingDistribution
            ]
        ]);
    }

    public function recentReviews()
    {
        $limit = $this->getPositiveIntParam('limit', 10);
        $reviews = $this->reviewModel->getRecentReviews($limit);

        return $this->respond([
            'status' => 'success',
            'data' => $reviews
        ]);
    }

    public function flaggedReviews()
    {
        $reviews = $this->reviewModel->getFlaggedReviews();

        return $this->respond([
            'status' => 'success',
            'data' => $reviews
        ]);
    }
}
