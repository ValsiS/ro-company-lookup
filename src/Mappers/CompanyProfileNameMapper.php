<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Mappers;

final class CompanyProfileNameMapper extends AbstractLanguageNameMapper
{
    protected array $ro = [
        'registration_date' => 'data_inregistrare',
        'registration_status' => 'stare_inregistrare',
        'fiscal_office' => 'organ_fiscal',
        'ownership_form' => 'forma_de_proprietate',
        'e_invoice_status' => 'status_ro_e_factura',
        'e_invoice_registration_date' => 'data_inreg_ro_e_factura',
        'iban' => 'iban',
    ];

    protected array $en = [
        'registration_date' => 'registration_date',
        'registration_status' => 'registration_status',
        'fiscal_office' => 'fiscal_office',
        'ownership_form' => 'ownership_form',
        'e_invoice_status' => 'e_invoice_status',
        'e_invoice_registration_date' => 'e_invoice_registration_date',
        'iban' => 'iban',
    ];
}
