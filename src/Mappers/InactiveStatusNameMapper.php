<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Mappers;

final class InactiveStatusNameMapper extends AbstractLanguageNameMapper
{
    protected array $ro = [
        'is_inactive' => 'este_inactiv',
        'inactivated_at' => 'data_inactivare',
        'reactivated_at' => 'data_reactivare',
        'published_at' => 'data_publicare',
        'removed_at' => 'data_radiere',
    ];

    protected array $en = [
        'is_inactive' => 'is_inactive',
        'inactivated_at' => 'inactivated_at',
        'reactivated_at' => 'reactivated_at',
        'published_at' => 'published_at',
        'removed_at' => 'removed_at',
    ];
}
