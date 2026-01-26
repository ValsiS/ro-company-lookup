<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Mappers;

final class LegalNameMapper extends AbstractLanguageNameMapper
{
    protected array $ro = [
        'current' => 'curenta',
        'history' => 'istoric',
    ];

    protected array $en = [
        'current' => 'current',
        'history' => 'history',
    ];
}
