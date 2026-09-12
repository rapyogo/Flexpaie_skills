<?php

namespace FlexPay\Laravel\Tests\Unit;

use FlexPay\Laravel\Support\FlexPayStatus;
use PHPUnit\Framework\TestCase;

class FlexPayStatusTest extends TestCase
{
    public function test_recognizes_completed_status(): void
    {
        $this->assertTrue(FlexPayStatus::isCompleted('0'));
        $this->assertTrue(FlexPayStatus::isCompleted(0));
        $this->assertTrue(FlexPayStatus::isCompleted('completed'));
        $this->assertFalse(FlexPayStatus::isCompleted('2'));
        $this->assertFalse(FlexPayStatus::isCompleted('1'));
    }

    public function test_recognizes_critical_pending_status_two(): void
    {
        // POINT CRITIQUE : status "2" = attente de PIN, ne doit JAMAIS être considéré comme failed
        $this->assertTrue(FlexPayStatus::isPending('2'));
        $this->assertTrue(FlexPayStatus::isPending(2));
        $this->assertTrue(FlexPayStatus::isPending('pending'));
        $this->assertFalse(FlexPayStatus::isPending('0'));
        $this->assertFalse(FlexPayStatus::isPending('1'));
    }

    public function test_recognizes_failed_status(): void
    {
        $this->assertTrue(FlexPayStatus::isFailed('1'));
        $this->assertTrue(FlexPayStatus::isFailed('failed'));
        $this->assertTrue(FlexPayStatus::isFailed('declined'));
        $this->assertFalse(FlexPayStatus::isFailed('2'));
        $this->assertFalse(FlexPayStatus::isFailed('0'));
    }

    public function test_normalizes_codes_to_state_strings(): void
    {
        $this->assertEquals(FlexPayStatus::STATE_COMPLETED, FlexPayStatus::normalizeState('0'));
        $this->assertEquals(FlexPayStatus::STATE_PENDING, FlexPayStatus::normalizeState('2'));
        $this->assertEquals(FlexPayStatus::STATE_FAILED, FlexPayStatus::normalizeState('1'));
    }
}
