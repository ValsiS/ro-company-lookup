<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Mappers;

final class VatCollectionNameMapper extends AbstractLanguageNameMapper
{
    protected array $ro = [
        'enabled' => 'activ',
        'start_date' => 'data_inceput',
        'end_date' => 'data_sfarsit',
        'published_at' => 'data_publicare',
        'updated_at' => 'data_actualizare',
        'act_type' => 'tip_act',
    ];

    protected array $en = [
        'enabled' => 'enabled',
        'start_date' => 'start_date',
        'end_date' => 'end_date',
        'published_at' => 'published_at',
        'updated_at' => 'updated_at',
        'act_type' => 'act_type',
    ];
}
