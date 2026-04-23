<?php

namespace Pk305\CbkForex\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Pk305\CbkForex\Models\ExchangeRate;

class CbkForexService
{
    protected Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout'         => config('cbk-forex.http.timeout', 30),
            'connect_timeout' => config('cbk-forex.http.connect_timeout', 10),
            'verify'          => config('cbk-forex.http.verify', true),
            'headers'         => config('cbk-forex.http.headers', []),
        ]);
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Fetch today's rates from CBK and upsert them into the database.
     *
     * @return array{fetched: int, saved: int, skipped: int, date: string}
     * @throws \RuntimeException
     */
    public function fetchAndStore(): array
    {
        $raw = $this->fetchFromCbk();

        $parsed = $this->parseRecords($raw);

        return $this->upsertRecords($parsed);
    }

    /**
     * Return the latest stored rates as a keyed Collection (currency_code => model).
     */
    public function latestRates(): Collection
    {
        return ExchangeRate::latest()
            ->get()
            ->keyBy('currency_code');
    }

    /**
     * Return rates for a specific date.
     */
    public function ratesForDate(Carbon|string $date): Collection
    {
        return ExchangeRate::forDate($date)->get();
    }

    /**
     * Return historical rates for a single currency.
     */
    public function historyForCurrency(string $code, int $days = 30): Collection
    {
        return ExchangeRate::forCurrency($code)
            ->betweenDates(now()->subDays($days), now())
            ->orderBy('rate_date')
            ->get();
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    /**
     * Hit the CBK AJAX endpoint and return the raw decoded JSON array.
     *
     * @throws \RuntimeException on HTTP failure or malformed response.
     */
    protected function fetchFromCbk(): array
    {
        $url    = config('cbk-forex.url');
        $params = config('cbk-forex.fetch');

        try {
            $response = $this->client->post($url, [
                'form_params' => array_merge($params, [
                    'columns[0][data]'       => 0,
                    'columns[0][searchable]' => 'true',
                    'columns[0][orderable]'  => 'true',
                    'columns[1][data]'       => 1,
                    'columns[1][searchable]' => 'true',
                    'columns[1][orderable]'  => 'true',
                    'columns[2][data]'       => 2,
                    'columns[2][searchable]' => 'true',
                    'columns[2][orderable]'  => 'true',
                    'order[0][column]'       => 0,
                    'order[0][dir]'          => 'desc',
                    'search[value]'          => '',
                    'search[regex]'          => 'false',
                ]),
            ]);
        } catch (GuzzleException $e) {
            Log::error('[CbkForex] HTTP request failed', ['error' => $e->getMessage()]);
            throw new \RuntimeException('CBK Forex: HTTP request failed — ' . $e->getMessage(), 0, $e);
        }

        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        if (!is_array($data) || !isset($data['data'])) {
            Log::error('[CbkForex] Unexpected response format', ['body' => substr($body, 0, 500)]);
            throw new \RuntimeException('CBK Forex: Unexpected response format from CBK endpoint.');
        }

        return $data['data'];
    }

    /**
     * Parse the raw 2D array rows into structured associative arrays.
     *
     * Each raw row: [ "23/04/2026", "US DOLLAR", "129.2100" ]
     *
     * @param  array<int, array<int, string>> $rows
     * @return array<int, array{rate_date: string, currency_label: string, currency_code: string|null, currency_name: string|null, rate: float}>
     */
    protected function parseRecords(array $rows): array
    {
        $currencyMap = config('cbk-forex.currency_map', []);
        $parsed      = [];

        foreach ($rows as $row) {
            if (count($row) < 3) {
                continue;
            }

            [$rawDate, $rawLabel, $rawRate] = $row;

            // Normalise label: trim + uppercase, and fix KES\/ prefix from JSON
            $label = strtoupper(trim(str_replace('\\/', '/', $rawLabel)));

            // Parse date from DD/MM/YYYY format
            try {
                $date = Carbon::createFromFormat('d/m/Y', trim($rawDate))->toDateString();
            } catch (\Exception $e) {
                Log::warning('[CbkForex] Could not parse date', ['raw' => $rawDate]);
                continue;
            }

            $rate = (float) str_replace(',', '', trim($rawRate));

            if ($rate <= 0) {
                continue;
            }

            // Try exact match first, then try with KES/ prefix variations
            $meta = $currencyMap[$label]
                ?? $currencyMap[str_replace('KES / ', 'KES / ', $label)]
                ?? null;

            $parsed[] = [
                'rate_date'       => $date,
                'currency_label'  => $label,
                'currency_code'   => $meta['code'] ?? null,
                'currency_name'   => $meta['name'] ?? null,
                'rate'            => $rate,
            ];
        }

        return $parsed;
    }

    /**
     * Upsert parsed records into the database.
     *
     * @param  array<int, array> $records
     * @return array{fetched: int, saved: int, skipped: int, date: string}
     */
    protected function upsertRecords(array $records): array
    {
        $saved   = 0;
        $skipped = 0;
        $date    = $records[0]['rate_date'] ?? now()->toDateString();

        foreach ($records as $record) {
            try {
                $result = ExchangeRate::updateOrCreate(
                    [
                        'rate_date'      => $record['rate_date'],
                        'currency_label' => $record['currency_label'],
                    ],
                    [
                        'currency_code' => $record['currency_code'],
                        'currency_name' => $record['currency_name'],
                        'rate'          => $record['rate'],
                    ]
                );

                $result->wasRecentlyCreated || $result->wasChanged() ? $saved++ : $skipped++;
            } catch (\Exception $e) {
                Log::error('[CbkForex] Failed to upsert record', [
                    'record' => $record,
                    'error'  => $e->getMessage(),
                ]);
                $skipped++;
            }
        }

        Log::info('[CbkForex] Sync complete', [
            'fetched' => count($records),
            'saved'   => $saved,
            'skipped' => $skipped,
            'date'    => $date,
        ]);

        return [
            'fetched' => count($records),
            'saved'   => $saved,
            'skipped' => $skipped,
            'date'    => $date,
        ];
    }

    /**
     * Delete records older than the configured keep_days threshold.
     */
    public function pruneOldRecords(): int
    {
        $keepDays = config('cbk-forex.prune.keep_days', 365);
        $cutoff   = now()->subDays($keepDays)->toDateString();

        return ExchangeRate::whereDate('rate_date', '<', $cutoff)->delete();
    }
}
