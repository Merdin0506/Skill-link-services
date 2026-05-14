<?php

use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class UserModelMatchingTest extends CIUnitTestCase
{
    public function testCalculateDistanceKmReturnsZeroForSamePoint(): void
    {
        $this->assertSame(0.0, UserModel::calculateDistanceKm(6.1164, 125.1716, 6.1164, 125.1716));
    }

    public function testGetWorkersForBookingFiltersOutWorkersOutsideCoverage(): void
    {
        $model = $this->getMockBuilder(UserModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getWorkersByServiceCategory'])
            ->getMock();

        $model->method('getWorkersByServiceCategory')
            ->with('plumber')
            ->willReturn([
                [
                    'id' => 10,
                    'first_name' => 'Near',
                    'last_name' => 'Worker',
                    'work_latitude' => 6.1164,
                    'work_longitude' => 125.1716,
                    'service_radius_km' => 10,
                ],
                [
                    'id' => 11,
                    'first_name' => 'Far',
                    'last_name' => 'Worker',
                    'work_latitude' => 6.5000,
                    'work_longitude' => 125.5000,
                    'service_radius_km' => 5,
                ],
            ]);

        $booking = [
            'latitude' => 6.1170,
            'longitude' => 125.1720,
            'location_address' => 'General Santos City',
        ];

        $workers = $model->getWorkersForBooking($booking, 'plumber');

        $this->assertCount(1, $workers);
        $this->assertSame(10, $workers[0]['id']);
        $this->assertNotNull($workers[0]['distance_km']);
        $this->assertTrue($workers[0]['within_coverage']);
    }

    public function testGetWorkersForBookingFallsBackToCityMatchWithoutCoordinates(): void
    {
        $model = $this->getMockBuilder(UserModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getWorkersByServiceCategory'])
            ->getMock();

        $model->method('getWorkersByServiceCategory')
            ->with('general')
            ->willReturn([
                [
                    'id' => 20,
                    'first_name' => 'City',
                    'last_name' => 'Match',
                    'service_city' => 'General Santos',
                    'service_radius_km' => 20,
                    'work_latitude' => null,
                    'work_longitude' => null,
                ],
                [
                    'id' => 21,
                    'first_name' => 'Other',
                    'last_name' => 'Town',
                    'service_city' => 'Davao',
                    'service_radius_km' => 20,
                    'work_latitude' => null,
                    'work_longitude' => null,
                ],
            ]);

        $workers = $model->getWorkersForBooking([
            'location_address' => 'Purok 4, General Santos City',
        ], 'general');

        $this->assertSame(20, $workers[0]['id']);
        $this->assertSame(1, $workers[0]['location_confidence']);
    }
}
