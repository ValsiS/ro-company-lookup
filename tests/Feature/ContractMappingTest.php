<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Tests\Feature;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Valsis\RoCompanyLookup\Facades\RoCompanyLookup;
use Valsis\RoCompanyLookup\Tests\TestCase;

class ContractMappingTest extends TestCase
{
    #[Test]
    public function it_maps_alternate_keys_and_optional_fields(): void
    {
        Http::fake([
            'webservicesp.anaf.ro/*' => Http::response($this->fixture('anaf_alt_keys.json'), 200),
        ]);

        $result = RoCompanyLookup::lookup('RO789012', new DateTimeImmutable('2024-05-10', new DateTimeZone('Europe/Bucharest')));

        $this->assertSame(789012, $result->company->cui);
        $this->assertSame('ALT SRL', $result->company->name_mfinante);
        $this->assertSame('J40/777/2020', $result->company->registration_number);
        $this->assertSame('6202', $result->caen->principal_mfinante?->code);
        $this->assertSame('Cluj', $result->address->anaf?->county);
        $this->assertNotNull($result->vat->current);
        $this->assertSame(1, $result->vat->current->code);
    }

    #[Test]
    public function it_handles_minimal_payloads(): void
    {
        Http::fake([
            'webservicesp.anaf.ro/*' => Http::response($this->fixture('anaf_minimal.json'), 200),
        ]);

        $result = RoCompanyLookup::lookup('RO555555');

        $this->assertSame(555555, $result->company->cui);
        $this->assertNull($result->company->name_mfinante);
        $this->assertSame('Str Minimal 5', $result->address->anaf?->formatted);
    }

    #[Test]
    public function it_handles_notfound_payloads(): void
    {
        Http::fake([
            'webservicesp.anaf.ro/*' => Http::response($this->fixture('anaf_empty.json'), 200),
        ]);

        $result = RoCompanyLookup::lookup('999999');

        $this->assertSame(999999, $result->company->cui);
        $this->assertNull($result->company->name_mfinante);
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
