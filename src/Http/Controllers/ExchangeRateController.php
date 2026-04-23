<?php

namespace Pk305\CbkForex\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Pk305\CbkForex\Models\ExchangeRate;
use Pk305\CbkForex\Services\CbkForexService;

class ExchangeRateController extends Controller
{
    public function __construct(protected CbkForexService $service)
    {
    }

    /**
     * GET /cbk-forex/rates
     * Returns the most recent set of exchange rates.
     */
    public function latest(): JsonResponse
    {
        $rates = $this->service->latestRates()->values();

        return response()->json([
            'data' => $rates,
            'meta' => [
                'date'  => $rates->first()?->rate_date?->toDateString(),
                'count' => $rates->count(),
            ],
        ]);
    }

    /**
     * GET /cbk-forex/rates/{date}
     * Returns exchange rates for a specific date (YYYY-MM-DD).
     */
    public function forDate(string $date): JsonResponse
    {
        $rates = $this->service->ratesForDate($date);

        if ($rates->isEmpty()) {
            return response()->json(['message' => "No rates found for date: {$date}"], 404);
        }

        return response()->json([
            'data' => $rates,
            'meta' => ['date' => $date, 'count' => $rates->count()],
        ]);
    }

    /**
     * GET /cbk-forex/rates/currency/{code}?days=30
     * Returns historical rates for a given ISO currency code.
     */
    public function history(Request $request, string $code): JsonResponse
    {
        $days  = (int) $request->get('days', 30);
        $rates = $this->service->historyForCurrency(strtoupper($code), $days);

        if ($rates->isEmpty()) {
            return response()->json(['message' => "No history found for currency: {$code}"], 404);
        }

        return response()->json([
            'data' => $rates,
            'meta' => ['currency_code' => strtoupper($code), 'days' => $days, 'count' => $rates->count()],
        ]);
    }
}
