<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Mappers;

final class CaenEntryNameMapper extends AbstractLanguageNameMapper
{
    protected array $ro = [
        'code' => 'cod',
        'label' => 'label',
        'version' => 'versiune',
    ];

    protected array $en = [
        'code' => 'code',
        'label' => 'label',
        'version' => 'version',
    ];
}
