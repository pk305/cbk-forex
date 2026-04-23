<?php

namespace Pk305\CbkForex\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property Carbon      $rate_date
 * @property string      $currency_label
 * @property string|null $currency_code
 * @property string|null $currency_name
 * @property float       $rate
 * @property Carbon      $created_at
 * @property Carbon      $updated_at
 */
class ExchangeRate extends Model
{
    protected $fillable = [
        'rate_date',
        'currency_label',
        'currency_code',
        'currency_name',
        'rate',
    ];

    protected $casts = [
        'rate_date' => 'date',
        'rate'      => 'float',
    ];

    public function getTable(): string
    {
        return config('cbk-forex.table_name', 'cbk_exchange_rates');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /** Filter by a specific date (defaults to today). */
    public function scopeForDate(Builder $query, Carbon|string|null $date = null): Builder
    {
        $date = $date ? Carbon::parse($date)->toDateString() : now()->toDateString();

        return $query->whereDate('rate_date', $date);
    }

    /** Filter by ISO currency code (case-insensitive). */
    public function scopeForCurrency(Builder $query, string $code): Builder
    {
        return $query->where('currency_code', strtoupper($code));
    }

    /** Filter by raw CBK currency label. */
    public function scopeForLabel(Builder $query, string $label): Builder
    {
        return $query->where('currency_label', strtoupper($label));
    }

    /** Return the most recent rates (latest date available). */
    public function scopeLatest(Builder $query): Builder
    {
        $latestDate = static::max('rate_date');

        return $query->whereDate('rate_date', $latestDate);
    }

    /** Return rates between two dates. */
    public function scopeBetweenDates(Builder $query, Carbon|string $from, Carbon|string $to): Builder
    {
        return $query
            ->whereDate('rate_date', '>=', Carbon::parse($from)->toDateString())
            ->whereDate('rate_date', '<=', Carbon::parse($to)->toDateString());
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Convenience: get a single rate for a currency on a given date. */
    public static function getRate(string $currencyCode, Carbon|string|null $date = null): float|null
    {
        return static::forCurrency($currencyCode)
            ->forDate($date)
            ->value('rate');
    }
}
