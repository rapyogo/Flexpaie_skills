<?php

namespace FlexPay\Laravel\Tests\Feature;

use FlexPay\Laravel\Services\FlexPayService;
use FlexPay\Laravel\Support\FlexPayStatus;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\TestCase;

class FlexPayServiceTest extends TestCase
{
    public function test_simulated_mode_generates_order_number(): void
    {
        $service = new FlexPayService('SIMULATED', 'fake-token');
        $this->assertTrue($service->isSimulated());

        $result = $service->initiateMobileMoney('0973604485', 'REF-TEST-1', 15.00, 'USD');

        $this->assertTrue($result['success']);
        $this->assertStringStartsWith('SIM-', $result['orderNumber']);

        $check = $service->checkTransaction($result['orderNumber']);
        $this->assertTrue($check['is_completed']);
        $this->assertEquals(FlexPayStatus::STATE_COMPLETED, $check['state']);
    }

    public function test_check_transaction_maintains_pending_when_status_is_two(): void
    {
        Http::fake([
            'https://backend.flexpay.cd/api/rest/v1/check/ORD-12345' => Http::response([
                'code' => '0',
                'message' => 'Transaction found',
                'transaction' => [
                    'orderNumber' => 'ORD-12345',
                    'status' => '2', // EN ATTENTE DE PIN !
                    'amount' => '10.00',
                    'channel' => 'airtel'
                ]
            ], 200)
        ]);

        $service = new FlexPayService('PROD_MERCHANT', 'fake-token');
        $check = $service->checkTransaction('ORD-12345');

        $this->assertTrue($check['is_pending']);
        $this->assertFalse($check['is_completed']);
        $this->assertFalse($check['is_failed']);
        $this->assertEquals(FlexPayStatus::STATE_PENDING, $check['state']);
    }
}
