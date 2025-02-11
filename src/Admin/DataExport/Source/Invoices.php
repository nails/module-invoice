<?php

namespace Nails\Invoice\Admin\DataExport\Source;

use Nails\Admin\Interfaces\DataExport\Source;
use Nails\Factory;
use Nails\Invoice\Constants;

/**
 * Class Invoices
 *
 * @package Nails\Invoice\Admin\DataExport\Source
 */
class Invoices implements Source
{
    public function getLabel(): string
    {
        return 'Invoices';
    }

    // --------------------------------------------------------------------------

    public function getFileName(): string
    {
        return 'invoices';
    }

    // --------------------------------------------------------------------------

    public function getDescription(): string
    {
        return 'Export invoices';
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the source's extended description, HTML allowed, optional
     *
     * @return string
     */
    public function getDescriptionExtended(): string
    {
        return '';
    }

    // --------------------------------------------------------------------------

    public function getOptions(): array
    {
        $oInvoiceModel = Factory::model('Invoice', Constants::MODULE_SLUG);
        return [
            [
                'key'     => 'state',
                'label'   => 'State',
                'type'    => 'dropdown',
                'class'   => 'select2',
                'options' => [
                    ''                                    => 'All',
                    $oInvoiceModel::STATE_DRAFT           => 'Draft',
                    $oInvoiceModel::STATE_OPEN            => 'Open',
                    $oInvoiceModel::STATE_PAID_PARTIAL    => 'Partially Paid',
                    $oInvoiceModel::STATE_PAID_PROCESSING => 'Payments processing',
                    $oInvoiceModel::STATE_PAID            => 'Paid',
                    $oInvoiceModel::STATE_WRITTEN_OFF     => 'Written Off',
                ],
            ],
            [
                'key'   => 'date_start',
                'label' => 'Date Start',
                'info'  => 'This value is inclusive',
                'type'  => 'date',
            ],
            [
                'key'   => 'date_end',
                'label' => 'Date End',
                'info'  => 'This value is inclusive',
                'type'  => 'date',
            ],
            [
                'key'     => 'date_column',
                'label'   => 'Date Column',
                'type'    => 'dropdown',
                'class'   => 'select2',
                'options' => [
                    'dated'       => 'Raised — the date when the invoice was raised',
                    'due'         => 'Due — the date when payment is due by',
                    'paid'        => 'Paid — the date when payment was made',
                    'written_off' => 'Written Off — the date when the invoice was written off',
                    'created'     => 'Created — the date when the invoice was created',
                    'modified'    => 'Modified — the date when the invoice was last modified',
                ],
            ],
        ];
    }

    // --------------------------------------------------------------------------

    public function isEnabled(): bool
    {
        return true;
    }

    // --------------------------------------------------------------------------

    public function execute($aOptions = [])
    {
        $oDb               = Factory::service('Database');
        $oInvoiceModel     = Factory::model('Invoice', Constants::MODULE_SLUG);
        $oInvoiceItemModel = Factory::model('InvoiceItem', Constants::MODULE_SLUG);

        $sState      = getFromArray('state', $aOptions);
        $sDateStart  = getFromArray('date_start', $aOptions);
        $sDateEnd    = getFromArray('date_end', $aOptions);
        $sDateColumn = getFromArray('date_column', $aOptions) ?? 'dated';

        $sTableInvoice     = $oInvoiceModel->getTableName();
        $sTableInvoiceItem = $oInvoiceItemModel->getTableName();

        $sSqlInvoice = sprintf(
            'SELECT `i`.* FROM `%s` `i`',
            $sTableInvoice
        );

        $sSqlInvoiceItem = sprintf(
            'SELECT `ii`.* FROM `%s` `ii` LEFT JOIN `%s` `i` ON `i`.`id` = `ii`.`invoice_id`',
            $sTableInvoiceItem,
            $sTableInvoice
        );

        $aConditionals = [];

        if (!empty($sDateStart)) {
            $aConditionals[] = sprintf(
                '`i`.`%s` >= "%s"',
                $sDateColumn,
                $sDateStart
            );
        }

        if (!empty($sDateEnd)) {
            $aConditionals[] = sprintf(
                '`i`.`%s` <= "%s"',
                $sDateColumn,
                $sDateEnd
            );
        }

        if (!empty($sState)) {
            $aConditionals[] = sprintf(
                '`i`.`state` = "%s"',
                $sState
            );
        }

        if (!empty($aConditionals)) {
            $sSqlInvoice     .= ' WHERE ' . implode(' AND ', $aConditionals);
            $sSqlInvoiceItem .= ' WHERE ' . implode(' AND ', $aConditionals);
        }

        return [
            Factory::factory('DataExportSourceResponse', \Nails\Admin\Constants::MODULE_SLUG)
                ->setLabel('Table: ' . $sTableInvoice)
                ->setFileName('invoice')
                ->setFields(arrayExtractProperty($oDb->query('DESCRIBE ' . $sTableInvoice)->result(), 'Field'))
                ->setSource($oDb->query($sSqlInvoice)),

            Factory::factory('DataExportSourceResponse', \Nails\Admin\Constants::MODULE_SLUG)
                ->setLabel('Table: ' . $sTableInvoiceItem)
                ->setFileName('invoice_item')
                ->setFields(arrayExtractProperty($oDb->query('DESCRIBE ' . $sTableInvoiceItem)->result(), 'Field'))
                ->setSource($oDb->query($sSqlInvoiceItem)),
        ];
    }
}
