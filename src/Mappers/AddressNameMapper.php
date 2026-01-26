<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Mappers;

final class AddressNameMapper extends AbstractLanguageNameMapper
{
    protected array $ro = [
        'formatted' => 'formatat',
        'raw' => 'neprelucrat',
        'raw_mf' => 'neprelucrat_mf',
        'raw_recom' => 'neprelucrat_recom',
        'country' => 'tara',
        'county' => 'judet',
        'city' => 'localitate',
        'sub_locality' => 'sub_localitate',
        'sector' => 'sector',
        'street' => 'strada',
        'street_type' => 'tip_strada',
        'number' => 'numar',
        'building' => 'bloc',
        'entrance' => 'scara',
        'floor' => 'etaj',
        'apartment' => 'apartament',
        'postal_code' => 'cod_postal',
        'siruta_code' => 'cod_siruta',
        'source' => 'sursa',
        'expiration' => 'expirare',
    ];

    protected array $en = [
        'formatted' => 'formatted_address',
        'raw' => 'raw_address',
        'raw_mf' => 'raw_ministry_of_finance',
        'raw_recom' => 'raw_recom_address',
        'country' => 'country',
        'county' => 'county',
        'city' => 'city',
        'sub_locality' => 'sub_locality',
        'sector' => 'sector',
        'street' => 'street',
        'street_type' => 'street_type',
        'number' => 'number',
        'building' => 'building',
        'entrance' => 'entrance',
        'floor' => 'floor',
        'apartment' => 'apartment',
        'postal_code' => 'postal_code',
        'siruta_code' => 'siruta_code',
        'source' => 'source',
        'expiration' => 'expiry',
    ];
}
