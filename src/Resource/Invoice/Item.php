<?php

/**
 * This class represents objects dispensed by the InvoiceItem model
 *
 * @package  Nails\Invoice\Resource\Invoice
 * @category resource
 */

namespace Nails\Invoice\Resource\Invoice;

use Nails\Common\Exception\FactoryException;
use Nails\Common\Model\Base;
use Nails\Common\Resource\Entity;
use Nails\Currency\Exception\CurrencyException;
use Nails\Currency\Service\Currency;
use Nails\Factory;
use Nails\Invoice\Constants;
use Nails\Invoice\Resource\Invoice;
use Nails\Invoice\Resource\Tax;
use stdClass;

class Item extends Entity
{
    /**
     * @var int
     */
    public $invoice_id;

    /**
     * the invoice (expandable field)
     *
     * @var Invoice
     */
    public $invoice;

    /**
     * the item's label
     *
     * @var string
     */
    public $label;

    /**
     * The item's body
     *
     * @var string
     */
    public $body;

    /**
     * The item's order
     *
     * @var int
     */
    public $order;

    /**
     * The item's currency
     *
     * @var \Nails\Currency\Resource\Currency
     */
    public $currency;

    /**
     * the item's totals
     *
     * @var Invoice\Item\Totals
     */
    public $totals;

    /**
     * The item's unit cost
     *
     * @var Invoice\Item\UnitCost
     */
    public $unit_cost;

    /**
     * The item's quantity
     *
     * @var int
     */
    public $quantity;

    /**
     * The item's unit
     *
     * @var Invoice\Item\Unit
     */
    public $unit;

    /**
     * The item's tax ID
     *
     * @var int
     */
    public $tax_id;

    /**
     * the item's tax object (expandable field)
     *
     * @var Tax
     */
    public $tax;

    /**
     * The item's callback data
     *
     * @var Invoice\Item\Data\Callback
     */
    public $callback_data;

    // --------------------------------------------------------------------------

    /**
     * Item constructor.
     *
     * @throws FactoryException
     * @throws CurrencyException
     */
    public function __construct(self|stdClass|array $resource = [], ?Base $model = null)
    {
        parent::__construct($resource, $model);

        // --------------------------------------------------------------------------

        //  Currency
        /** @var Currency $oCurrencyService */
        $oCurrencyService = Factory::service('Currency', \Nails\Currency\Constants::MODULE_SLUG);
        $this->currency   = $oCurrencyService->getByIsoCode($entity->currency);

        // --------------------------------------------------------------------------

        //  Totals
        $this->totals = Factory::resource(
            'InvoiceItemTotals',
            Constants::MODULE_SLUG,
            (object) [
                'currency' => $this->currency,
                'sub'      => (int) $entity->sub_total,
                'tax'      => (int) $entity->tax_total,
                'grand'    => (int) $entity->grand_total,
            ]
        );

        unset($this->sub_total);
        unset($this->tax_total);
        unset($this->grand_total);

        // --------------------------------------------------------------------------

        //  Unit cost
        $this->unit_cost = Factory::resource(
            'InvoiceItemUnitCost',
            Constants::MODULE_SLUG,
            (object) [
                'currency' => $this->currency,
                'raw'      => (int) $entity->unit_cost,
            ]
        );

        // --------------------------------------------------------------------------

        //  Unit
        /** @var \Nails\Invoice\Model\Invoice\Item $oModel */
        $oModel = Factory::model('InvoiceItem', Constants::MODULE_SLUG);
        $aUnits = $oModel->getUnits();

        $this->unit = Factory::resource(
            'InvoiceItemUnit',
            Constants::MODULE_SLUG,
            (object) [
                'id'    => $entity->unit,
                'label' => $aUnits[$entity->unit],
            ]
        );

        // --------------------------------------------------------------------------

        //  Data blobs
        $this->callback_data = Factory::resource(
            'InvoiceItemDataCallback',
            Constants::MODULE_SLUG,
            json_decode((string) $this->callback_data) ?: (object) []
        );
    }
}
