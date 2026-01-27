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

        $summaries = RoCompanyLookup::batchSummary(['RO123456', 'RO789012']);

        $this->assertSame(123456, $summaries[0]['cui']);
        $this->assertSame(789012, $summaries[1]['cui']);
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
