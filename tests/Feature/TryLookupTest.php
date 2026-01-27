<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Tests\Feature;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Valsis\RoCompanyLookup\Facades\RoCompanyLookup;
use Valsis\RoCompanyLookup\Tests\TestCase;

class TryLookupTest extends TestCase
{
    #[Test]
    public function it_returns_invalid_status_for_bad_cui(): void
    {
        $result = RoCompanyLookup::tryLookup('RO');

        $this->assertSame('invalid', $result->status);
        $this->assertNull($result->data);
    }

    #[Test]
    public function it_returns_not_found_status_when_company_missing(): void
    {
        Http::fake([
            'webservicesp.anaf.ro/*' => Http::response($this->fixture('anaf_empty.json'), 200),
        ]);

        $result = RoCompanyLookup::tryLookup('999999');

        $this->assertSame('not_found', $result->status);
        $this->assertNotNull($result->data);
        $this->assertSame(999999, $result->data->company->cui);
    }

    #[Test]
    public function it_returns_ok_status_when_company_found(): void
    {
        Http::fake([
            'webservicesp.anaf.ro/*' => Http::response($this->fixture('anaf_single.json'), 200),
        ]);

        $result = RoCompanyLookup::tryLookup('123456');

        $this->assertSame('ok', $result->status);
        $this->assertNotNull($result->data);
        $this->assertSame('ACME SRL', $result->data->company->name);
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
