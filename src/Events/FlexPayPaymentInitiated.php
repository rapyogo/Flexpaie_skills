<?php

namespace FlexPay\Laravel\Events;

use FlexPay\Laravel\Models\FlexPayPayment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FlexPayPaymentInitiated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public FlexPayPayment $payment)
    {
    }
}
