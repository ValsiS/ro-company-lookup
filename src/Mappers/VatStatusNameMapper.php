<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Mappers;

final class VatStatusNameMapper extends AbstractLanguageNameMapper
{
    protected array $ro = [
        'current' => 'curent',
        'history' => 'istoric',
    ];

    protected array $en = [
        'current' => 'current',
        'history' => 'history',
    ];
}
