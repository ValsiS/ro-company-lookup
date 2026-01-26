<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Mappers;

final class VatHistoryEntryNameMapper extends AbstractLanguageNameMapper
{
    protected array $ro = [
        'start_date' => 'data_inceput',
        'end_date' => 'data_sfarsit',
        'is_vat_payer' => 'statut_TVA',
    ];

    protected array $en = [
        'start_date' => 'start_date',
        'end_date' => 'end_date',
        'is_vat_payer' => 'is_vat_payer',
    ];
}
