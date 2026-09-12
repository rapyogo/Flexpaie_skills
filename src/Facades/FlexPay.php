<?php

namespace FlexPay\Laravel\Facades;

use FlexPay\Laravel\Services\FlexPayService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array initiateMobileMoney(string $phone, string $reference, float $amount, string $currency = 'USD', ?string $callbackUrl = null)
 * @method static array checkTransaction(string $orderNumber)
 * @method static bool isSimulated()
 *
 * @see \FlexPay\Laravel\Services\FlexPayService
 */
class FlexPay extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FlexPayService::class;
    }
}
