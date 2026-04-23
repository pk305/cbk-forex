# CBK Forex — Laravel Package

A Laravel package that fetches **Central Bank of Kenya (CBK) foreign exchange rates** daily and stores them in your database, with a clean API for querying rates by date, currency, or history.

---

## Features

- ✅ Fetches live rates from the [CBK official data endpoint](https://www.centralbank.go.ke/forex-exchange-rates/)
- ✅ Stores rates with ISO currency codes and human-readable names
- ✅ **Daily auto-scheduling** via Laravel's task scheduler — no cron setup needed
- ✅ Artisan command for manual fetches and CI pipelines
- ✅ `upsert` logic — re-running never creates duplicates
- ✅ Eloquent model with helpful query scopes
- ✅ Optional API endpoints (REST)
- ✅ Optional pruning of old records
- ✅ Configurable via `.env` or published config file

---

## Requirements

| Dependency | Version       |
| ---------- | ------------- |
| PHP        | ^8.1          |
| Laravel    | 10, 11, or 12 |

---

## Installation

```bash
composer require pk305/cbk-forex
```

### Publish the config (optional)

```bash
php artisan vendor:publish --tag=cbk-forex-config
```

### Run the migration

```bash
php artisan migrate
```

---

## Scheduling (Daily Auto-Fetch)

The package **automatically registers** a daily scheduled command. All you need is a working Laravel scheduler. If you haven't set it up yet, add this single cron entry to your server:

```cron
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

By default the fetch runs at **14:00 (2 PM) EAT** — after CBK typically publishes rates. Change the time in your `.env`:

```env
CBK_FOREX_FETCH_TIME=14:00
```

---

## Manual Fetch (Artisan)

```bash
# Fetch and store latest rates
php artisan cbk-forex:fetch

# Fetch + prune old records
php artisan cbk-forex:fetch --prune
```

---

## Environment Variables

```env
# CBK data endpoint (rarely needs changing)
CBK_FOREX_URL=https://www.centralbank.go.ke/wp-admin/admin-ajax.php?action=get_wdtable&table_id=193

# HTTP timeouts (seconds)
CBK_FOREX_TIMEOUT=30
CBK_FOREX_CONNECT_TIMEOUT=10

# SSL verification (set to false only for local dev)
CBK_FOREX_VERIFY_SSL=true

# Daily fetch time (HH:MM, 24hr)
CBK_FOREX_FETCH_TIME=14:00

# Database table name
CBK_FOREX_TABLE=cbk_exchange_rates

# Pruning (optional)
CBK_FOREX_PRUNE=false
CBK_FOREX_KEEP_DAYS=365
```

---

## Usage

### Using the Facade

```php
use Pk305\CbkForex\Facades\CbkForex;

// Manually trigger a fetch
$result = CbkForex::fetchAndStore();
// => ['fetched' => 21, 'saved' => 21, 'skipped' => 0, 'date' => '2026-04-23']

// Get the latest available rates (keyed by ISO code)
$rates = CbkForex::latestRates();
$usdRate = $rates['USD']->rate; // e.g. 129.21

// Get rates for a specific date
$rates = CbkForex::ratesForDate('2026-04-22');

// Get 30-day history for a currency
$history = CbkForex::historyForCurrency('USD', 30);
```

### Using the Eloquent Model Directly

```php
use Pk305\CbkForex\Models\ExchangeRate;

// Today's rates
ExchangeRate::forDate()->get();

// Specific date
ExchangeRate::forDate('2026-04-22')->get();

// A single currency
ExchangeRate::forCurrency('USD')->forDate()->first();

// Quick rate lookup
$rate = ExchangeRate::getRate('EUR'); // float or null

// Latest rates (most recent date in DB)
ExchangeRate::latest()->get();

// Date range
ExchangeRate::betweenDates('2026-04-01', '2026-04-23')
    ->forCurrency('GBP')
    ->orderBy('rate_date')
    ->get();
```

---

## REST API (Optional)

If you want to expose rates over HTTP, register these routes in your `routes/api.php`:

```php
use Pk305\CbkForex\Http\Controllers\ExchangeRateController;

Route::prefix('cbk-forex')->group(function () {
    Route::get('/rates',              [ExchangeRateController::class, 'latest']);
    Route::get('/rates/{date}',       [ExchangeRateController::class, 'forDate']);
    Route::get('/rates/currency/{code}', [ExchangeRateController::class, 'history']);
});
```

### Example Responses

**`GET /api/cbk-forex/rates`**

```json
{
  "data": [
    {
      "id": 1,
      "rate_date": "2026-04-23",
      "currency_label": "US DOLLAR",
      "currency_code": "USD",
      "currency_name": "US Dollar",
      "rate": 129.21
    }
  ],
  "meta": { "date": "2026-04-23", "count": 21 }
}
```

**`GET /api/cbk-forex/rates/currency/USD?days=7`**

```json
{
  "data": [ ... ],
  "meta": { "currency_code": "USD", "days": 7, "count": 7 }
}
```

---

## Supported Currencies

| CBK Label        | ISO Code | Name                   |
| ---------------- | -------- | ---------------------- |
| US DOLLAR        | USD      | US Dollar              |
| STG POUND        | GBP      | Sterling Pound         |
| EURO             | EUR      | Euro                   |
| S FRANC          | CHF      | Swiss Franc            |
| AUSTRALIAN $     | AUD      | Australian Dollar      |
| CAN $            | CAD      | Canadian Dollar        |
| HONGKONG DOLLAR  | HKD      | Hong Kong Dollar       |
| SINGAPORE DOLLAR | SGD      | Singapore Dollar       |
| CHINESE YUAN     | CNY      | Chinese Yuan           |
| JPY (100)        | JPY      | Japanese Yen (per 100) |
| IND RUPEE        | INR      | Indian Rupee           |
| DAN KRONER       | DKK      | Danish Krone           |
| NOR KRONER       | NOK      | Norwegian Krone        |
| SW KRONER        | SEK      | Swedish Krona          |
| SA RAND          | ZAR      | South African Rand     |
| SAUDI RIYAL      | SAR      | Saudi Riyal            |
| AE DIRHAM        | AED      | UAE Dirham             |
| KES / RWF        | RWF      | Rwandan Franc          |
| KES / BIF        | BIF      | Burundian Franc        |
| KES / TSHS       | TZS      | Tanzanian Shilling     |
| KES / USHS       | UGX      | Ugandan Shilling       |

---

## Testing

```bash
composer test
```

---

## License

MIT
