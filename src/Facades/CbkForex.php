<?php

namespace Pk305\CbkForex\Facades;

use Illuminate\Support\Facades\Facade;
use Pk305\CbkForex\Services\CbkForexService;

/**
 * @method static array fetchAndStore()
 * @method static \Illuminate\Support\Collection latestRates()
 * @method static \Illuminate\Support\Collection ratesForDate(\Carbon\Carbon|string $date)
 * @method static \Illuminate\Support\Collection historyForCurrency(string $code, int $days = 30)
 * @method static int pruneOldRecords()
 *
 * @see \Pk305\CbkForex\Services\CbkForexService
 */
class CbkForex extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CbkForexService::class;
    }
}
