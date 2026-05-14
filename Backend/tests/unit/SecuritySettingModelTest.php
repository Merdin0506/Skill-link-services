<?php

use App\Models\SecuritySettingModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class SecuritySettingModelTest extends CIUnitTestCase
{
    public function testSanitizeSettingsClampsAndNormalizesValues(): void
    {
        $settings = SecuritySettingModel::sanitizeSettings([
            'brute_force_threshold' => 0,
            'block_duration_minutes' => 999999,
            'sync_poll_seconds' => 1,
            'auto_block_enabled' => 'false',
            'notify_on_failed_login' => 'true',
            'notify_on_blocked_ip' => 0,
            'notify_on_suspicious_activity' => 1,
        ]);

        $this->assertSame(1, $settings['brute_force_threshold']);
        $this->assertSame(10080, $settings['block_duration_minutes']);
        $this->assertSame(10, $settings['sync_poll_seconds']);
        $this->assertFalse($settings['auto_block_enabled']);
        $this->assertTrue($settings['notify_on_failed_login']);
        $this->assertFalse($settings['notify_on_blocked_ip']);
        $this->assertTrue($settings['notify_on_suspicious_activity']);
    }

    public function testDefaultsExposeExpectedBaselinePolicy(): void
    {
        $this->assertSame(5, SecuritySettingModel::DEFAULTS['brute_force_threshold']);
        $this->assertSame(30, SecuritySettingModel::DEFAULTS['block_duration_minutes']);
        $this->assertSame(20, SecuritySettingModel::DEFAULTS['sync_poll_seconds']);
        $this->assertTrue(SecuritySettingModel::DEFAULTS['auto_block_enabled']);
    }
}
