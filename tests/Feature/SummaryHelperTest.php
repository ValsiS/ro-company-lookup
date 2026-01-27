<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Tests\Feature;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Valsis\RoCompanyLookup\Facades\RoCompanyLookup;
use Valsis\RoCompanyLookup\Tests\TestCase;

class SummaryHelperTest extends TestCase
{
    #[Test]
    public function it_returns_compact_summary(): void
    {
        Http::fake([
            'webservicesp.anaf.ro/*' => Http::response($this->fixture('anaf_single.json'), 200),
        ]);

        $summary = RoCompanyLookup::summary('RO123456');

        $this->assertSame(123456, $summary['cui']);
        $this->assertSame('ACME SRL', $summary['name']);
        $this->assertSame('6201', $summary['caen']);
        $this->assertNull($summary['registration_date']);
        $this->assertTrue($summary['vat_payer']);
    }

    #[Test]
    public function it_returns_null_for_summary_or_null_when_not_found(): void
    {
        Http::fake([
            'webservicesp.anaf.ro/*' => Http::response($this->fixture('anaf_empty.json'), 200),
        ]);

        $summary = RoCompanyLookup::summaryOrNull('999999');

        $this->assertNull($summary);
    }

    #[Test]
    public function it_returns_batch_summaries(): void
    {
        Http::fake([
            'webservicesp.anaf.ro/*' => Http::response($this->fixture('anaf_batch.json'), 200),
        ]);

        $summaries = RoCompanyLookup::batchSummary(['RO123', 'RO456']);

        $this->assertSame(123, $summaries[0]['cui']);
        $this->assertSame(456, $summaries[1]['cui']);
    }

    #[Test]
    public function it_returns_summary_with_status(): void
    {
        Http::fake([
            'webservicesp.anaf.ro/*' => Http::response($this->fixture('anaf_single.json'), 200),
        ]);

        $summary = RoCompanyLookup::summaryOrResult('RO123456');

        $this->assertSame('ok', $summary['status']);
        $this->assertSame(123456, $summary['cui']);
    }

    #[Test]
    public function it_returns_batch_summaries_with_status(): void
    {
        Http::fake([
            'webservicesp.anaf.ro/*' => Http::response($this->fixture('anaf_batch.json'), 200),
        ]);

        $summaries = RoCompanyLookup::batchSummaryWithStatus(['RO123', 'RO456']);

        $this->assertSame('ok', $summaries[0]['status']);
        $this->assertSame('ok', $summaries[1]['status']);
    }

    #[Test]
    public function it_validates_cui_without_exceptions(): void
    {
        $this->assertTrue(RoCompanyLookup::isValidCui('RO123456'));
        $this->assertFalse(RoCompanyLookup::isValidCui('RO'));
    }

    #[Test]
    public function it_returns_safe_summary(): void
    {
        Http::fake([
            'webservicesp.anaf.ro/*' => Http::response($this->fixture('anaf_empty.json'), 200),
        ]);

        $summary = RoCompanyLookup::summarySafe('999999');

        $this->assertSame(['exists' => false], $summary);
    }

    #[Test]
    public function it_returns_batch_summary_map(): void
    {
        Http::fake([
            'webservicesp.anaf.ro/*' => Http::response($this->fixture('anaf_batch.json'), 200),
        ]);

        $summaries = RoCompanyLookup::batchSummaryMap(['RO123', 'RO456']);

        $this->assertSame(123, $summaries['RO123']['cui']);
        $this->assertSame(456, $summaries['RO456']['cui']);
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
