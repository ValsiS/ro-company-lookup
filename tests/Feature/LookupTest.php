<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Tests\Feature;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Valsis\RoCompanyLookup\Contracts\RoCompanyLookupDriver;
use Valsis\RoCompanyLookup\Drivers\DriverResponse;
use Valsis\RoCompanyLookup\Exceptions\LookupFailedException;
use Valsis\RoCompanyLookup\Facades\RoCompanyLookup;
use Valsis\RoCompanyLookup\RoCompanyLookupManager;
use Valsis\RoCompanyLookup\Tests\TestCase;

class LookupTest extends TestCase
{
    #[Test]
    public function it_looks_up_a_single_company(): void
    {
        Http::fake([
            'webservicesp.anaf.ro/*' => Http::response($this->fixture('anaf_single.json'), 200),
        ]);

        $date = new DateTimeImmutable('2024-01-10', new DateTimeZone('Europe/Bucharest'));
        $result = RoCompanyLookup::lookup('RO123456', $date);

        $this->assertSame(123456, $result->company->cui);
        $this->assertSame('ACME SRL', $result->company->name);
        $this->assertSame('J40/123/2010', $result->company->registration_number);
        $this->assertSame('6201', $result->caen?->code);
        $this->assertSame('0211234567', $result->contact->phones[0] ?? null);
        $this->assertSame(2, $result->vat->current?->code);
        $this->assertSame('Bucuresti', $result->address->fiscal_domicile?->county);
        $this->assertSame('2024-01-10', $result->meta->queried_for_date);
        $this->assertSame('anaf', $result->meta->source);
        $this->assertFalse($result->meta->cache_hit);
        $this->assertFalse($result->meta->is_stale);

        $metaArray = $result->meta->toArray();
        $this->assertArrayNotHasKey('raw', $metaArray);
    }

    #[Test]
    public function it_batches_lookups(): void
    {
        Http::fake([
            'webservicesp.anaf.ro/*' => Http::response($this->fixture('anaf_batch.json'), 200),
        ]);

        $results = RoCompanyLookup::batch([123, 456])->get();

        $this->assertCount(2, $results);
        $this->assertSame('ACME SRL', $results[0]->company->name);
        $this->assertSame('BETA SRL', $results[1]->company->name);
    }

    #[Test]
    public function it_uses_cache_for_repeat_lookups(): void
    {
        Carbon::setTestNow(Carbon::parse('2024-01-01 10:00:00', 'Europe/Bucharest'));

        Http::fake([
            'webservicesp.anaf.ro/*' => Http::response($this->fixture('anaf_single.json'), 200),
        ]);

        $date = new DateTimeImmutable('2024-01-10', new DateTimeZone('Europe/Bucharest'));

        RoCompanyLookup::lookup('RO123456', $date);
        RoCompanyLookup::lookup('RO123456', $date);

        Http::assertSentCount(1);
    }

    #[Test]
    public function it_returns_stale_cache_when_remote_fails(): void
    {
        config()->set('ro-company-lookup.cache_ttl_seconds', 60);
        config()->set('ro-company-lookup.stale_ttl_seconds', 3600);

        Carbon::setTestNow(Carbon::parse('2024-01-01 10:00:00', 'Europe/Bucharest'));
        Http::fake([
            'webservicesp.anaf.ro/*' => Http::response($this->fixture('anaf_single.json'), 200),
        ]);

        RoCompanyLookup::lookup('RO123456', new DateTimeImmutable('2024-01-10', new DateTimeZone('Europe/Bucharest')));

        Carbon::setTestNow(Carbon::parse('2024-01-01 10:02:00', 'Europe/Bucharest'));
        $manager = app(RoCompanyLookupManager::class);
        $manager->extend('anaf', function () {
            return new class implements RoCompanyLookupDriver
            {
                public function lookup(int $cui, DateTimeInterface $date): DriverResponse
                {
                    throw new LookupFailedException('Service unavailable.');
                }

                public function batch(array $cuis, DateTimeInterface $date): array
                {
                    throw new LookupFailedException('Service unavailable.');
                }
            };
        });
        $manager->forgetDrivers();

        $result = RoCompanyLookup::lookup('RO123456', new DateTimeImmutable('2024-01-10', new DateTimeZone('Europe/Bucharest')));

        $this->assertTrue($result->meta->is_stale);
        $this->assertTrue($result->meta->cache_hit);
        $this->assertSame('ACME SRL', $result->company->name);
    }

    #[Test]
    public function it_retries_on_server_error(): void
    {
        config()->set('ro-company-lookup.anaf.retries', 1);
        config()->set('ro-company-lookup.anaf.backoff_ms', 0);

        Http::fakeSequence('webservicesp.anaf.ro/*')
            ->push(['error' => 'down'], 500)
            ->push($this->fixture('anaf_single.json'), 200);

        $date = new DateTimeImmutable('2024-01-10', new DateTimeZone('Europe/Bucharest'));

        $result = RoCompanyLookup::lookup('RO123456', $date);

        $this->assertSame('ACME SRL', $result->company->name);
        Http::assertSentCount(2);
    }

    #[Test]
    public function the_command_outputs_json(): void
    {
        Http::fake([
            'webservicesp.anaf.ro/*' => Http::response($this->fixture('anaf_single.json'), 200),
        ]);

        Artisan::call('ro-company-lookup:check', [
            'cui' => '123456',
            '--date' => '2024-01-10',
            '--raw' => true,
        ]);

        $output = Artisan::output();
        $payload = json_decode($output, true);

        $this->assertIsArray($payload);
        $this->assertSame(123456, $payload['firma']['cui'] ?? null);
        $this->assertArrayHasKey('raw', $payload['meta']);
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
