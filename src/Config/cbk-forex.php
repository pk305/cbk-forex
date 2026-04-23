<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CBK Forex Data Source URL
    |--------------------------------------------------------------------------
    |
    | The URL used to fetch the foreign exchange data from the Central Bank
    | of Kenya. This should not normally need to be changed.
    |
    */
    'url' => env(
        'CBK_FOREX_URL',
        'https://www.centralbank.go.ke/wp-admin/admin-ajax.php?action=get_wdtable&table_id=193'
    ),

    /*
    |--------------------------------------------------------------------------
    | HTTP Request Options
    |--------------------------------------------------------------------------
    |
    | Options passed to the Guzzle HTTP client when making requests to the
    | CBK endpoint. Adjust timeout values as needed.
    |
    */
    'http' => [
        'timeout'         => env('CBK_FOREX_TIMEOUT', 30),
        'connect_timeout' => env('CBK_FOREX_CONNECT_TIMEOUT', 10),
        'verify'          => env('CBK_FOREX_VERIFY_SSL', true),
        'headers'         => [
            'User-Agent' => 'Mozilla/5.0 (compatible; CbkForexBot/1.0)',
            'Accept'     => 'application/json, text/plain, */*',
            'Referer'    => 'https://www.centralbank.go.ke/forex-exchange-rates/',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fetch Parameters
    |--------------------------------------------------------------------------
    |
    | Parameters sent in the POST body to the CBK data endpoint.
    | `length` controls how many records are fetched per request.
    | Set to a high number to capture all available records.
    |
    */
    'fetch' => [
        'draw'   => 1,
        'start'  => 0,
        'length' => 100,  // fetches today's ~21 currencies + a buffer
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Table Name
    |--------------------------------------------------------------------------
    |
    | The name of the database table where exchange rates will be stored.
    |
    */
    'table_name' => env('CBK_FOREX_TABLE', 'cbk_exchange_rates'),

    /*
    |--------------------------------------------------------------------------
    | Scheduled Fetch Time
    |--------------------------------------------------------------------------
    |
    | The time of day (24hr format HH:MM) at which the daily scheduled
    | command will run. CBK typically publishes rates around midday EAT.
    |
    */
    'fetch_time' => env('CBK_FOREX_FETCH_TIME', '14:00'),

    /*
    |--------------------------------------------------------------------------
    | Pruning
    |--------------------------------------------------------------------------
    |
    | Optionally prune old records. Set `prune` to true and configure
    | `keep_days` to retain only the most recent N days of data.
    | Set `prune` to false to keep all historical data.
    |
    */
    'prune' => [
        'enabled'   => env('CBK_FOREX_PRUNE', false),
        'keep_days' => env('CBK_FOREX_KEEP_DAYS', 365),
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Name Normalisation Map
    |--------------------------------------------------------------------------
    |
    | Maps the raw currency labels returned by CBK to clean ISO codes and
    | human-readable names. Add more entries here as CBK adds currencies.
    |
    */
    'currency_map' => [
        'US DOLLAR'        => ['code' => 'USD', 'name' => 'US Dollar'],
        'STG POUND'        => ['code' => 'GBP', 'name' => 'Sterling Pound'],
        'EURO'             => ['code' => 'EUR', 'name' => 'Euro'],
        'S FRANC'          => ['code' => 'CHF', 'name' => 'Swiss Franc'],
        'AUSTRALIAN $'     => ['code' => 'AUD', 'name' => 'Australian Dollar'],
        'CAN $'            => ['code' => 'CAD', 'name' => 'Canadian Dollar'],
        'HONGKONG DOLLAR'  => ['code' => 'HKD', 'name' => 'Hong Kong Dollar'],
        'SINGAPORE DOLLAR' => ['code' => 'SGD', 'name' => 'Singapore Dollar'],
        'CHINESE YUAN'     => ['code' => 'CNY', 'name' => 'Chinese Yuan'],
        'JPY (100)'        => ['code' => 'JPY', 'name' => 'Japanese Yen (per 100)'],
        'IND RUPEE'        => ['code' => 'INR', 'name' => 'Indian Rupee'],
        'DAN KRONER'       => ['code' => 'DKK', 'name' => 'Danish Krone'],
        'NOR KRONER'       => ['code' => 'NOK', 'name' => 'Norwegian Krone'],
        'SW KRONER'        => ['code' => 'SEK', 'name' => 'Swedish Krona'],
        'SA RAND'          => ['code' => 'ZAR', 'name' => 'South African Rand'],
        'SAUDI RIYAL'      => ['code' => 'SAR', 'name' => 'Saudi Riyal'],
        'AE DIRHAM'        => ['code' => 'AED', 'name' => 'UAE Dirham'],
        'KES / RWF'        => ['code' => 'RWF', 'name' => 'Rwandan Franc'],
        'KES / BIF'        => ['code' => 'BIF', 'name' => 'Burundian Franc'],
        'KES / TSHS'       => ['code' => 'TZS', 'name' => 'Tanzanian Shilling'],
        'KES / USHS'       => ['code' => 'UGX', 'name' => 'Ugandan Shilling'],
    ],

];
