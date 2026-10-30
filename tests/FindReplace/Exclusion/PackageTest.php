<?php

namespace Tests\FindReplace\Exclusion;

use Nails\Invoice\FindReplace\Exclusion\Package;
use PHPUnit\Framework\TestCase;

class PackageTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!defined('NAILS_DB_PREFIX')) {
            define('NAILS_DB_PREFIX', 'nails_');
        }
    }

    public function testHidden(): void
    {
        self::assertSame(
            [
                NAILS_DB_PREFIX . 'invoice_payment',
                NAILS_DB_PREFIX . 'invoice_refund',
                NAILS_DB_PREFIX . 'invoice_source',
                NAILS_DB_PREFIX . 'invoice_email',
                NAILS_DB_PREFIX . 'invoice_invoice.callback_data',
                NAILS_DB_PREFIX . 'invoice_invoice.payment_data',
                NAILS_DB_PREFIX . 'invoice_invoice.payment_driver',
                NAILS_DB_PREFIX . 'invoice_invoice_item.callback_data',
            ],
            (new Package())->hidden()
        );
    }

    public function testReadOnly(): void
    {
        self::assertSame(
            [
                NAILS_DB_PREFIX . 'invoice_invoice.currency',
                NAILS_DB_PREFIX . 'invoice_invoice_item.currency',
                NAILS_DB_PREFIX . 'invoice_customer.email',
                NAILS_DB_PREFIX . 'invoice_customer.billing_email',
                NAILS_DB_PREFIX . 'invoice_customer.telephone',
                NAILS_DB_PREFIX . 'invoice_customer.vat_number',
            ],
            (new Package())->readOnly()
        );
    }
}
