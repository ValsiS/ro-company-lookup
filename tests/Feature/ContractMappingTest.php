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

    #[Test]
    public function it_maps_company_profile_from_v9_payload(): void
    {
        Http::fake([
            'webservicesp.anaf.ro/*' => Http::response($this->fixture('anaf_v9_single.json'), 200),
        ]);

        $result = RoCompanyLookup::lookup('RO46632129');

        $this->assertSame(46632129, $result->company->cui);
        $this->assertSame('TERRADOT S.R.L.', $result->company->name_mfinante);
        $this->assertNotNull($result->company->profile);
        $this->assertSame('INREGISTRAT din data 10.08.2022', $result->company->profile->registration_status);
        $this->assertSame('2022-08-11', $result->company->profile->registration_date?->format('Y-m-d'));
    }

    #[Test]
    public function it_maps_status_sections_from_v9_payload(): void
    {
        Http::fake([
            'webservicesp.anaf.ro/*' => Http::response($this->fixture('anaf_v9_statuses.json'), 200),
        ]);

        $result = RoCompanyLookup::lookup('RO555111');

        $this->assertNotNull($result->vat_collection);
        $this->assertTrue($result->vat_collection->enabled);
        $this->assertSame('2024-02-01', $result->vat_collection->start_date?->format('Y-m-d'));

        $this->assertNotNull($result->inactive_status);
        $this->assertFalse($result->inactive_status->is_inactive);
        $this->assertSame('2023-06-01', $result->inactive_status->inactivated_at?->format('Y-m-d'));

        $this->assertNotNull($result->split_vat);
        $this->assertFalse($result->split_vat->enabled);
        $this->assertSame('2019-02-01', $result->split_vat->cancelled_at?->format('Y-m-d'));
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
