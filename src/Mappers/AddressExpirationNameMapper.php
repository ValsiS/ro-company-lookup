<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Mappers;

final class AddressExpirationNameMapper extends AbstractLanguageNameMapper
{
    protected array $ro = [
        'updated_at' => 'data_actualizare',
        'expires_at' => 'data_expirare',
        'is_expired' => 'este_expirat',
    ];

    protected array $en = [
        'updated_at' => 'updated_at',
        'expires_at' => 'expires_at',
        'is_expired' => 'is_expired',
    ];
}
