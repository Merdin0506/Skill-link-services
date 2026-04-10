<?php

use App\Libraries\AccessControl;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class AccessControlPolicyTest extends CIUnitTestCase
{
    private AccessControl $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new AccessControl();
    }

    public function testSuperAdminHasAllCrudActions(): void
    {
        $this->assertTrue($this->policy->isAllowed('super_admin', 'users', 'read'));
        $this->assertTrue($this->policy->isAllowed('super_admin', 'users', 'write'));
        $this->assertTrue($this->policy->isAllowed('super_admin', 'users', 'update'));
        $this->assertTrue($this->policy->isAllowed('super_admin', 'users', 'delete'));
    }

    public function testFinanceCanManagePaymentsButCannotDeletePayments(): void
    {
        $this->assertTrue($this->policy->isAllowed('finance', 'payments', 'read'));
        $this->assertTrue($this->policy->isAllowed('finance', 'payments', 'write'));
        $this->assertTrue($this->policy->isAllowed('finance', 'payments', 'update'));
        $this->assertFalse($this->policy->isAllowed('finance', 'payments', 'delete'));
    }

    public function testCustomerCannotAccessSensitiveUserResource(): void
    {
        $this->assertFalse($this->policy->isAllowed('customer', 'users', 'read'));
        $this->assertFalse($this->policy->isAllowed('customer', 'users', 'write'));
        $this->assertFalse($this->policy->isAllowed('customer', 'users', 'update'));
        $this->assertFalse($this->policy->isAllowed('customer', 'users', 'delete'));
    }

    public function testWorkerCanUpdateBookingsButCannotDeleteBookings(): void
    {
        $this->assertTrue($this->policy->isAllowed('worker', 'bookings', 'read'));
        $this->assertTrue($this->policy->isAllowed('worker', 'bookings', 'update'));
        $this->assertFalse($this->policy->isAllowed('worker', 'bookings', 'delete'));
    }

    public function testCustomerCanCreateAndUpdateReviewsButCannotDelete(): void
    {
        $this->assertTrue($this->policy->isAllowed('customer', 'reviews', 'read'));
        $this->assertTrue($this->policy->isAllowed('customer', 'reviews', 'write'));
        $this->assertTrue($this->policy->isAllowed('customer', 'reviews', 'update'));
        $this->assertFalse($this->policy->isAllowed('customer', 'reviews', 'delete'));
    }

    public function testResourceAliasNormalizationWorks(): void
    {
        $this->assertSame('users', $this->policy->normalizeResource('user'));
        $this->assertSame('payments', $this->policy->normalizeResource('payment'));
    }

    public function testMethodActionMappingSupportsCrudLevels(): void
    {
        $this->assertSame('read', $this->policy->mapMethodToAction('GET'));
        $this->assertSame('write', $this->policy->mapMethodToAction('POST'));
        $this->assertSame('update', $this->policy->mapMethodToAction('PUT'));
        $this->assertSame('delete', $this->policy->mapMethodToAction('DELETE'));
    }
}
