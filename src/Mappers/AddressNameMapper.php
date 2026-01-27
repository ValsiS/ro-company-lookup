<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Mappers;

final class AddressNameMapper extends AbstractLanguageNameMapper
{
    protected array $ro = [
        'formatted' => 'formatat',
        'raw' => 'neprelucrat',
        'country' => 'tara',
        'county' => 'judet',
        'county_code' => 'cod_judet',
        'county_auto_code' => 'cod_judet_auto',
        'city' => 'localitate',
        'locality_code' => 'cod_localitate',
        'street' => 'strada',
        'number' => 'numar',
        'postal_code' => 'cod_postal',
        'details' => 'detalii',
    ];

    protected array $en = [
        'formatted' => 'formatted_address',
        'raw' => 'raw_address',
        'country' => 'country',
        'county' => 'county',
        'county_code' => 'county_code',
        'county_auto_code' => 'county_auto_code',
        'city' => 'city',
        'locality_code' => 'locality_code',
        'street' => 'street',
        'number' => 'number',
        'postal_code' => 'postal_code',
        'details' => 'details',
    ];
}
