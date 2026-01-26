<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Mappers;

final class LegalHistoryEntryNameMapper extends AbstractLanguageNameMapper
{
    protected array $ro = [
        'updated_at' => 'data_actualizare',
        'name' => 'denumire',
        'organization' => 'organizare',
    ];

    protected array $en = [
        'updated_at' => 'updated_at',
        'name' => 'name',
        'organization' => 'organization',
    ];
}
