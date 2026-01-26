<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Console;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Console\Command;
use Valsis\RoCompanyLookup\Exceptions\LookupFailedException;
use Valsis\RoCompanyLookup\RoCompanyLookupManager;

class CheckCompanyCommand extends Command
{
    protected $signature = 'ro-company-lookup:check {cui} {--date=} {--raw}';

    protected $description = 'Check Romanian company data by CUI via ANAF.';

    public function handle(RoCompanyLookupManager $manager): int
    {
        $cui = $this->argument('cui');
        $dateOption = $this->option('date');
        $includeRaw = (bool) $this->option('raw');

        $date = null;
        if ($dateOption) {
            try {
                $date = new DateTimeImmutable($dateOption, new DateTimeZone('Europe/Bucharest'));
            } catch (\Throwable $exception) {
                $this->error('Invalid date format. Expected YYYY-MM-DD.');

                return self::FAILURE;
            }
        }

        try {
            $result = $manager->lookup($cui, $date, $includeRaw);
        } catch (LookupFailedException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $json = json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $this->line($json ?: '{}');

        return self::SUCCESS;
    }
}
