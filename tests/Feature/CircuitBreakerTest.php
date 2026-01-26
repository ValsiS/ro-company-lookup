<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Tests\Feature;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Valsis\RoCompanyLookup\Contracts\RoCompanyLookupDriver;
use Valsis\RoCompanyLookup\Drivers\DriverResponse;
use Valsis\RoCompanyLookup\Exceptions\CircuitOpenException;
use Valsis\RoCompanyLookup\Exceptions\LookupFailedException;
use Valsis\RoCompanyLookup\Facades\RoCompanyLookup;
use Valsis\RoCompanyLookup\RoCompanyLookupManager;
use Valsis\RoCompanyLookup\Tests\TestCase;

class CircuitBreakerTest extends TestCase
{
    #[Test]
    public function it_uses_stale_cache_when_circuit_is_open(): void
    {
        config()->set('ro-company-lookup.circuit_breaker.enabled', true);
        config()->set('ro-company-lookup.circuit_breaker.failure_threshold', 1);
        config()->set('ro-company-lookup.circuit_breaker.cooldown_seconds', 3600);
        config()->set('ro-company-lookup.cache_ttl_seconds', 1);
        config()->set('ro-company-lookup.stale_ttl_seconds', 3600);

        Carbon::setTestNow(Carbon::parse('2024-01-01 10:00:00', 'Europe/Bucharest'));
        Http::fake([
            'webservicesp.anaf.ro/*' => Http::response($this->fixture('anaf_single.json'), 200),
        ]);

        RoCompanyLookup::lookup('RO123456');

        Carbon::setTestNow(Carbon::parse('2024-01-01 10:02:00', 'Europe/Bucharest'));

        $manager = app(RoCompanyLookupManager::class);
        $driver = new class implements RoCompanyLookupDriver
        {
            public int $calls = 0;

            public function lookup(int $cui, DateTimeInterface $date): DriverResponse
            {
                $this->calls++;
                throw new LookupFailedException('Service unavailable.', 500);
            }

            public function batch(array $cuis, DateTimeInterface $date): array
            {
                $this->calls++;
                throw new LookupFailedException('Service unavailable.', 500);
            }
        };

        $manager->extend('anaf', fn () => $driver);
        $manager->forgetDrivers();

        $result = RoCompanyLookup::lookup('RO123456');
        $this->assertTrue($result->meta->is_stale);

        $result = RoCompanyLookup::lookup('RO123456');
        $this->assertTrue($result->meta->is_stale);
        $this->assertSame(1, $driver->calls);
    }

    #[Test]
    public function it_throws_when_circuit_is_open_and_no_stale(): void
    {
        config()->set('ro-company-lookup.circuit_breaker.enabled', true);
        config()->set('ro-company-lookup.circuit_breaker.failure_threshold', 1);
        config()->set('ro-company-lookup.circuit_breaker.cooldown_seconds', 3600);
        config()->set('ro-company-lookup.cache_ttl_seconds', 0);
        config()->set('ro-company-lookup.stale_ttl_seconds', 0);

        $manager = app(RoCompanyLookupManager::class);
        $manager->extend('anaf', function () {
            return new class implements RoCompanyLookupDriver
            {
                public function lookup(int $cui, DateTimeInterface $date): DriverResponse
                {
                    throw new LookupFailedException('Service unavailable.', 500);
                }

                public function batch(array $cuis, DateTimeInterface $date): array
                {
                    throw new LookupFailedException('Service unavailable.', 500);
                }
            };
        });
        $manager->forgetDrivers();

        try {
            RoCompanyLookup::lookup('RO999999');
            $this->fail('Expected LookupFailedException');
        } catch (LookupFailedException $exception) {
            $this->assertSame(500, $exception->getCode());
        }

        $this->expectException(CircuitOpenException::class);
        RoCompanyLookup::lookup('RO999999');
    }

    /**
     * @return array<string, mixed>
     */
    protected function fixture(string $name): array
    {
        $contents = file_get_contents(__DIR__.'/../Fixtures/'.$name);

        return json_decode($contents ?: '[]', true) ?? [];
    }
}
