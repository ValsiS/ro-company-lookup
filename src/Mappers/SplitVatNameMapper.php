<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Mappers;

final class SplitVatNameMapper extends AbstractLanguageNameMapper
{
    protected array $ro = [
        'enabled' => 'activ',
        'start_date' => 'data_inceput',
        'cancelled_at' => 'data_anulare',
    ];

    protected array $en = [
        'enabled' => 'enabled',
        'start_date' => 'start_date',
        'cancelled_at' => 'cancelled_at',
    ];
}
