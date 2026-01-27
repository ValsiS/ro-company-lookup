<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Console;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Console\Command;
use Valsis\RoCompanyLookup\RoCompanyLookupManager;

class DemoCompanyCommand extends Command
{
    protected $signature = 'ro-company-lookup:demo {cui} {--date=} {--json}';

    protected $description = 'Demo: output a compact company summary from ANAF.';

    public function handle(RoCompanyLookupManager $manager): int
    {
        $cuiArgument = $this->argument('cui');
        if (! is_string($cuiArgument) || $cuiArgument === '') {
            $this->error('Invalid CUI argument.');

            return self::FAILURE;
        }

        $dateOption = $this->option('date');
        $asJson = (bool) $this->option('json');

        $date = null;
        if (is_string($dateOption) && $dateOption !== '') {
            try {
                $date = new DateTimeImmutable($dateOption, new DateTimeZone('Europe/Bucharest'));
            } catch (\Throwable $exception) {
                $this->error('Invalid date format. Expected YYYY-MM-DD.');

                return self::FAILURE;
            }
        }

        $result = $manager->tryLookup($cuiArgument, $date);

        if (! $result->isOk()) {
            $payload = [
                'exists' => false,
                'status' => $result->status,
                'message' => $result->message,
                'error' => $result->error,
                'code' => $result->error_code,
            ];

            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}');

            return self::FAILURE;
        }

        $company = $result->data;

        $payload = [
            'exists' => true,
            'cui' => $company?->company->cui,
            'name' => $company?->company->name,
            'caen' => $company?->caen?->code,
            'registration_date' => $company?->registrationDate()?->format('Y-m-d'),
            'vat_payer' => $company?->isVatPayer(),
        ];

        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}');

        return self::SUCCESS;
    }
}
