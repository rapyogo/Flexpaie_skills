<?php

namespace FlexPay\Laravel\Tests\Unit;

use FlexPay\Laravel\Support\OperatorDetector;
use PHPUnit\Framework\TestCase;

class OperatorDetectorTest extends TestCase
{
    public function test_detects_airtel_numbers(): void
    {
        $this->assertEquals(OperatorDetector::AIRTEL, OperatorDetector::detect('0973604485'));
        $this->assertEquals(OperatorDetector::AIRTEL, OperatorDetector::detect('+24398123456'));
        $this->assertEquals(OperatorDetector::AIRTEL, OperatorDetector::detect('243990001111'));
    }

    public function test_detects_vodacom_numbers(): void
    {
        $this->assertEquals(OperatorDetector::VODACOM, OperatorDetector::detect('0812345678'));
        $this->assertEquals(OperatorDetector::VODACOM, OperatorDetector::detect('+243820000000'));
        $this->assertEquals(OperatorDetector::VODACOM, OperatorDetector::detect('0839999999'));
    }

    public function test_detects_orange_numbers(): void
    {
        $this->assertEquals(OperatorDetector::ORANGE, OperatorDetector::detect('0841234567'));
        $this->assertEquals(OperatorDetector::ORANGE, OperatorDetector::detect('0851234567'));
        $this->assertEquals(OperatorDetector::ORANGE, OperatorDetector::detect('0891234567'));
        $this->assertEquals(OperatorDetector::ORANGE, OperatorDetector::detect('0801234567'));
    }

    public function test_detects_africell_numbers(): void
    {
        $this->assertEquals(OperatorDetector::AFRICELL, OperatorDetector::detect('0901234567'));
        $this->assertEquals(OperatorDetector::AFRICELL, OperatorDetector::detect('0911234567'));
    }

    public function test_normalizes_various_phone_formats(): void
    {
        $this->assertEquals('243973604485', OperatorDetector::normalize('0973604485'));
        $this->assertEquals('243973604485', OperatorDetector::normalize('+243 973 604 485'));
        $this->assertEquals('243973604485', OperatorDetector::normalize('00243973604485'));
        $this->assertEquals('243973604485', OperatorDetector::normalize('243973604485'));
    }

    public function test_validates_drc_phone_numbers(): void
    {
        $this->assertTrue(OperatorDetector::isValidDrcPhone('0812345678'));
        $this->assertTrue(OperatorDetector::isValidDrcPhone('+243973604485'));
        $this->assertFalse(OperatorDetector::isValidDrcPhone('0712345678')); // Invalide en RDC
        $this->assertFalse(OperatorDetector::isValidDrcPhone('12345'));
    }
}
