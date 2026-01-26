<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Mappers;

final class ContactNameMapper extends AbstractLanguageNameMapper
{
    protected array $ro = [
        'phones' => 'telefon',
        'emails' => 'email',
    ];

    protected array $en = [
        'phones' => 'phone_numbers',
        'emails' => 'emails',
    ];
}
