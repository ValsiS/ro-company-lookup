<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Mappers;

final class VatStatusEntryNameMapper extends AbstractLanguageNameMapper
{
    protected array $ro = [
        'code' => 'cod',
        'label' => 'label',
        'vat_start_date' => 'data_inceput_tva',
        'vat_cancel_date' => 'data_anulare_tva',
        'vat_cancel_operation_date' => 'data_operare_anulare_tva',
        'queried_at' => 'data_interogare',
    ];

    protected array $en = [
        'code' => 'code',
        'label' => 'label',
        'vat_start_date' => 'vat_start_date',
        'vat_cancel_date' => 'vat_cancellation_date',
        'vat_cancel_operation_date' => 'vat_cancellation_operation_date',
        'queried_at' => 'queried_at',
    ];
}
