<?php

namespace Nails\Invoice\FindReplace\Exclusion;

use Nails\FindReplace\Interfaces\Exclusion;

class Package implements Exclusion
{
    public function hidden(): array
    {
        return [
            NAILS_DB_PREFIX . 'invoice_payment',
            NAILS_DB_PREFIX . 'invoice_refund',
            NAILS_DB_PREFIX . 'invoice_source',
            NAILS_DB_PREFIX . 'invoice_email',
            NAILS_DB_PREFIX . 'invoice_invoice.callback_data',
            NAILS_DB_PREFIX . 'invoice_invoice.payment_data',
            NAILS_DB_PREFIX . 'invoice_invoice.payment_driver',
            NAILS_DB_PREFIX . 'invoice_invoice_item.callback_data',
        ];
    }

    public function readOnly(): array
    {
        return [
            NAILS_DB_PREFIX . 'invoice_invoice.currency',
            NAILS_DB_PREFIX . 'invoice_invoice_item.currency',
            NAILS_DB_PREFIX . 'invoice_customer.email',
            NAILS_DB_PREFIX . 'invoice_customer.billing_email',
            NAILS_DB_PREFIX . 'invoice_customer.telephone',
            NAILS_DB_PREFIX . 'invoice_customer.vat_number',
        ];
    }
}
