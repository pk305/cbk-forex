<?php

namespace Pk305\CbkForex\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Pk305\CbkForex\CbkForexServiceProvider;
use Pk305\CbkForex\Models\ExchangeRate;
use Pk305\CbkForex\Services\CbkForexService;
use Orchestra\Testbench\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class CbkForexServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [CbkForexServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    private function mockServiceWithResponse(array $data): CbkForexService
    {
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode($data)),
        ]);

        $service = new CbkForexService();

        $reflection = new \ReflectionClass($service);
        $prop = $reflection->getProperty('client');
        $prop->setAccessible(true);
        $prop->setValue($service, new Client(['handler' => HandlerStack::create($mockHandler)]));

        return $service;
    }

    private function sampleResponse(): array
    {
        return [
            'draw'            => 1,
            'recordsTotal'    => '2',
            'recordsFiltered' => '2',
            'data'            => [
                ['23/04/2026', 'US DOLLAR', '129.2100'],
                ['23/04/2026', 'EURO',      '151.5100'],
            ],
        ];
    }

    /** @test */
    public function it_fetches_and_stores_exchange_rates(): void
    {
        $service = $this->mockServiceWithResponse($this->sampleResponse());
        $result  = $service->fetchAndStore();

        $this->assertEquals(2, $result['fetched']);
        $this->assertDatabaseCount('cbk_exchange_rates', 2);
    }

    /** @test */
    public function it_correctly_maps_currency_codes(): void
    {
        $service = $this->mockServiceWithResponse($this->sampleResponse());
        $service->fetchAndStore();

        $usd = ExchangeRate::forCurrency('USD')->first();
        $this->assertNotNull($usd);
        $this->assertEquals(129.21, $usd->rate);
        $this->assertEquals('US Dollar', $usd->currency_name);

        $eur = ExchangeRate::forCurrency('EUR')->first();
        $this->assertNotNull($eur);
        $this->assertEquals(151.51, $eur->rate);
    }

    /** @test */
    public function it_does_not_duplicate_records_on_re_fetch(): void
    {
        $service = $this->mockServiceWithResponse($this->sampleResponse());
        $service->fetchAndStore();

        $service2 = $this->mockServiceWithResponse($this->sampleResponse());
        $service2->fetchAndStore();

        $this->assertDatabaseCount('cbk_exchange_rates', 2);
    }

    /** @test */
    public function it_returns_latest_rates(): void
    {
        $service = $this->mockServiceWithResponse($this->sampleResponse());
        $service->fetchAndStore();

        $latest = $service->latestRates();

        $this->assertCount(2, $latest);
        $this->assertTrue($latest->has('USD'));
        $this->assertTrue($latest->has('EUR'));
    }

    /** @test */
    public function it_throws_on_malformed_response(): void
    {
        $this->expectException(\RuntimeException::class);

        $mockHandler = new MockHandler([
            new Response(200, [], '{"invalid": true}'),
        ]);

        $service = new CbkForexService();
        $reflection = new \ReflectionClass($service);
        $prop = $reflection->getProperty('client');
        $prop->setAccessible(true);
        $prop->setValue($service, new Client(['handler' => HandlerStack::create($mockHandler)]));

        $service->fetchAndStore();
    }

    /** @test */
    public function exchange_rate_model_scope_for_date_works(): void
    {
        $service = $this->mockServiceWithResponse($this->sampleResponse());
        $service->fetchAndStore();

        $rates = ExchangeRate::forDate('2026-04-23')->get();
        $this->assertCount(2, $rates);

        $empty = ExchangeRate::forDate('2000-01-01')->get();
        $this->assertCount(0, $empty);
    }
}
