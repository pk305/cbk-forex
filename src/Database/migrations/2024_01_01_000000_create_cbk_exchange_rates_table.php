<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
      public function up(): void
      {
            $table = config('cbk-forex.table_name', 'cbk_exchange_rates');

            Schema::create($table, function (Blueprint $table) {
                  $table->id();

                  $table->date('rate_date')
                        ->comment('The date the rate was published by CBK');

                  $table->string('currency_label', 50)
                        ->comment('Raw label from CBK e.g. "US DOLLAR"');

                  $table->string('currency_code', 10)
                        ->nullable()
                        ->comment('ISO currency code e.g. USD');

                  $table->string('currency_name', 100)
                        ->nullable()
                        ->comment('Human-readable currency name');

                  $table->decimal('rate', 12, 4)
                        ->comment('KES rate for 1 unit of the currency (or 100 units for JPY)');

                  $table->timestamps();

                  // Prevent duplicate entries for the same currency on the same date
                  $table->unique(['rate_date', 'currency_label'], 'cbk_forex_date_currency_unique');

                  $table->index('rate_date',      'cbk_forex_rate_date_index');
                  $table->index('currency_code',  'cbk_forex_currency_code_index');
            });
      }

      public function down(): void
      {
            $table = config('cbk-forex.table_name', 'cbk_exchange_rates');
            Schema::dropIfExists($table);
      }
};
