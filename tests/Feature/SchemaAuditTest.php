<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Valsis\RoCompanyLookup\Facades\RoCompanyLookup;
use Valsis\RoCompanyLookup\Tests\TestCase;

final class SchemaAuditTest extends TestCase
{
    public function test_schema_audit_does_not_report_unknown_keys_for_known_fixtures(): void
    {
        config()->set('ro-company-lookup.schema_audit.enabled', true);
        config()->set('ro-company-lookup.schema_audit.fail_on_unknown', true);

        Http::fake([
            'webservicesp.anaf.ro/*' => Http::response($this->fixture('anaf_single.json'), 200),
        ]);

        $data = RoCompanyLookup::lookup('RO123456');

        $this->assertSame(123456, $data->company->cui);
    }

    /**
     * @return array<string, mixed>
     */
    protected function fixture(string $name): array
    {
        $path = __DIR__.'/../Fixtures/'.$name;

        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }
}
