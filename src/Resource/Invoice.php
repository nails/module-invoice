<?php

/**
 * This class represents objects dispensed by the Invoice model
 *
 * @package  Nails\Invoice\Resource
 * @category resource
 */

namespace Nails\Invoice\Resource;

use Nails\Address\Resource\Address;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Common\Helper\Model\Expand;
use Nails\Common\Resource\Date;
use Nails\Common\Resource\DateTime;
use Nails\Common\Resource\Entity;
use Nails\Common\Resource\ExpandableField;
use Nails\Currency\Exception\CurrencyException;
use Nails\Currency\Resource\Currency;
use Nails\Factory;
use Nails\Invoice\Constants;
use Nails\Invoice\Factory\ChargeRequest;
use Nails\Invoice\Resource\Invoice\Data;
use Nails\Invoice\Resource\Invoice\Item;
use Nails\Invoice\Resource\Invoice\State;
use Nails\Invoice\Resource\Invoice\Totals;
use Nails\Invoice\Resource\Invoice\Urls;

/**
 * Class Invoice
 *
 * @package Nails\Invoice\Resource
 */
class Invoice extends Entity
{
    /**
     * The invoice's reference
     *
     * @var string
     */
    public $ref;

    /**
     * The invoice's token
     *
     * @var string
     */
    public $token;

    /**
     * The invoice's customer ID
     *
     * @var int
     */
    public $customer_id;

    /**
     * The customer (expandable field)
     *
     * @var null|Customer
     */
    public $customer;

    /**
     * The invoice's state
     *
     * @var State
     */
    public $state;

    /**
     * The invoice's date
     *
     * @var Date
     */
    public $dated;

    /**
     * The invoice's terms, in days
     *
     * @var int
     */
    public $terms;

    /**
     * The invoice's due date
     *
     * @var Date
     */
    public $due;

    /**
     * The invoice's paid date
     *
     * @var DateTime
     */
    public $paid;

    /**
     * The invoice's email
     *
     * @var string
     */
    public $email;

    /**
     * The invoice's currency
     *
     * @var Currency
     */
    public $currency;

    /**
     * Any additional text
     *
     * @var string
     */
    public $additional_text;

    /**
     * Any callback data
     *
     * @var Data\Callback
     */
    public $callback_data;

    /**
     * Any payment data
     *
     * @var Data\Payment
     */
    public $payment_data;

    /**
     * The payemnt driver
     *
     * @var string|null
     */
    public $payment_driver;

    /**
     * The ID of the billing address associated with the invoice
     *
     * @var int|null
     */
    public $billing_address_id;

    /**
     * The billing address associated with the invoice
     *
     * @var Address|null
     */
    public $billing_address;

    /**
     * The ID of the delivery address associated with the invoice
     *
     * @var int|null
     */
    public $delivery_address_id;

    /**
     * The delivery address associated with the invoice
     *
     * @var Address|null
     */
    public $delivery_address;

    /**
     * Whether the invoice is scheduled
     *
     * @var bool
     */
    public $is_scheduled = false;

    /**
     * Whether the invoice is due
     *
     * @var bool
     */
    public $is_due = false;

    /**
     * Whether the invoice is overdue
     *
     * @var bool
     */
    public $is_overdue = false;

    /**
     * Whether the invoice has processing payments
     *
     * @var bool
     */
    public $has_processing_payments;

    /**
     * The invoice totals
     *
     * @var Totals
     */
    public $totals;

    /**
     * The invoice URLs
     *
     * @var Urls
     */
    public $urls;

    /**
     * The invoice items (expandable field)
     *
     * @var ExpandableField
     */
    public $items;

    /**
     * The invoice payments (expandable field)
     *
     * @var ExpandableField
     */
    public $payments;

    /**
     * The invoice refunds (expandable field)
     *
     * @var ExpandableField
     */
    public $refunds;

    // --------------------------------------------------------------------------

    /**
     * Invoice constructor.
     *
     * @param array $mObj
     *
     * @throws FactoryException
     * @throws CurrencyException
     */
    public function __construct($mObj = [])
    {
        parent::__construct($mObj);

        // --------------------------------------------------------------------------

        /** @var \Nails\Invoice\Model\Invoice $oModel */
        $oModel  = Factory::model('Invoice', Constants::MODULE_SLUG);
        $aStates = $oModel->getStates();

        $this->state = Factory::resource(
            'InvoiceState',
            Constants::MODULE_SLUG,
            (object) [
                'id'    => $mObj->state,
                'label' => $aStates[$mObj->state],
            ]
        );

        // --------------------------------------------------------------------------

        //  Dates and DateTimes
        $this->dated = Factory::resource('Date', null, (object) ['raw' => $mObj->dated]);
        $this->due   = Factory::resource('Date', null, (object) ['raw' => $mObj->due]);
        $this->paid  = Factory::resource('DateTime', null, (object) ['raw' => $mObj->paid]);

        // --------------------------------------------------------------------------

        if ($this->state->id == $oModel::STATE_OPEN) {

            /** @var \DateTime $oNow */
            $oNow = Factory::factory('DateTime');
            /** @var Date $oNow */
            $oNow = Factory::resource('Date', null, (object) ['raw' => $oNow->format('Y-m-d')]);

            $this->is_scheduled = $this->dated->isFuture();
            $this->is_due       = $oNow == $this->dated || $this->dated->isPast();
            $this->is_overdue   = $this->due->isPast();
        }

        $this->has_processing_payments = $mObj->processing_payments > 0;
        unset($this->processing_payments);

        // --------------------------------------------------------------------------

        //  Currency
        /** @var \Nails\Currency\Service\Currency $oCurrencyService */
        $oCurrencyService = Factory::service('Currency', \Nails\Currency\Constants::MODULE_SLUG);
        $this->currency   = $oCurrencyService->getByIsoCode($mObj->currency);

        // --------------------------------------------------------------------------

        //  Totals
        $this->totals = Factory::resource(
            'InvoiceTotals',
            Constants::MODULE_SLUG,
            (object) [
                'currency'   => $this->currency,
                'sub'        => (int) $mObj->sub_total,
                'tax'        => (int) $mObj->tax_total,
                'grand'      => (int) $mObj->grand_total,
                'paid'       => (int) $mObj->paid_total,
                'processing' => (int) $mObj->processing_total,
            ]
        );

        unset($this->sub_total);
        unset($this->tax_total);
        unset($this->grand_total);
        unset($this->paid_total);
        unset($this->processing_total);

        // --------------------------------------------------------------------------

        //  URLs
        $this->urls = Factory::resource(
            'InvoiceUrls',
            Constants::MODULE_SLUG,
            (object) [
                'payment'  => siteUrl('invoice/invoice/' . $this->ref . '/' . $this->token . '/pay'),
                'download' => siteUrl('invoice/invoice/' . $this->ref . '/' . $this->token . '/download'),
                'view'     => siteUrl('invoice/invoice/' . $this->ref . '/' . $this->token . '/view'),
            ]
        );

        // --------------------------------------------------------------------------

        //  Data blobs
        $this->callback_data = Factory::resource(
            'InvoiceDataCallback',
            Constants::MODULE_SLUG,
            json_decode((string) $this->callback_data) ?: (object) []
        );

        $this->payment_data = Factory::resource(
            'InvoiceDataPayment',
            Constants::MODULE_SLUG,
            json_decode((string) $this->payment_data) ?: (object) []
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Charges this invoice
     *
     * @param ChargeRequest $oChargeRequest The ChargeRequest object to use
     * @param string|null   $sDescription   The description to give the charge
     *
     * @return ChargeResponse
     * @throws InvoiceException
     */
    public function charge(ChargeRequest $oChargeRequest, ?string $sDescription = null)
    {
        return $oChargeRequest
            ->setInvoice($this)
            ->setDescription($sDescription ?? $oChargeRequest->getDescription() ?? 'Payment for invoice ' . $this->ref)
            ->execute();
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the invoice's customer object
     *
     * @return null|Customer
     * @throws FactoryException
     * @throws ModelException
     */
    public function customer(): ?Customer
    {
        if (empty($this->customer) && !empty($this->customer_id)) {
            /** @var \Nails\Invoice\Model\Customer $oModel */
            $oModel         = Factory::model('Customer', Constants::MODULE_SLUG);
            $this->customer = $oModel->getById($this->customer_id);
        }

        return $this->customer;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the invoice's billing address
     *
     * @return Address|null
     * @throws FactoryException
     * @throws ModelException
     */
    public function billingAddress(): ?Address
    {
        if (empty($this->billing_address) && !empty($this->billing_address_id)) {
            $oModel                = Factory::model('Address', \Nails\Address\Constants::MODULE_SLUG);
            $this->billing_address = $oModel->getById($this->billing_address_id);
        }

        return $this->billing_address;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the invoice's delivery address
     *
     * @return Address|null
     * @throws FactoryException
     * @throws ModelException
     */
    public function deliveryAddress(): ?Address
    {
        if (empty($this->delivery_address) && !empty($this->delivery_address_id)) {
            $oModel                 = Factory::model('Address', \Nails\Address\Constants::MODULE_SLUG);
            $this->delivery_address = $oModel->getById($this->delivery_address_id);
        }

        return $this->delivery_address;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the invoice's items
     *
     * @return ExpandableField
     * @throws FactoryException
     * @throws ModelException
     */
    public function items(): ExpandableField
    {
        if (empty($this->items)) {
            $oModel      = Factory::model('Invoice', Constants::MODULE_SLUG);
            $this->items = $oModel->getById($this->id, [new Expand('items')])->items;
        }

        return $this->items;
    }

    // --------------------------------------------------------------------------

    /**
     * Whether the invoice has been fully paid or not
     *
     * @param bool $bIncludeProcessing Whether to include payments which are still processing
     *
     * @return bool
     */
    public function isPaid(bool $bIncludeProcessing = false): bool
    {
        $iPaid = $this->totals->raw->paid;
        if ($bIncludeProcessing) {
            $iPaid += $this->totals->raw->processing;
        }

        return $iPaid >= $this->totals->raw->grand;
    }

    // --------------------------------------------------------------------------

    /**
     * Whetehr the invoice is due for payment
     *
     * @return bool
     */
    public function isDue(): bool
    {
        return $this->is_due;
    }

    // --------------------------------------------------------------------------

    /**
     * Whetehr the invoice is overdue for payment
     *
     * @return bool
     */
    public function isOverdue(): bool
    {
        return $this->is_overdue;
    }

    // --------------------------------------------------------------------------

    /**
     * Whetehr the invoice is scheduled for the future
     *
     * @return bool
     */
    public function isScheduled(): bool
    {
        return $this->is_scheduled;
    }
}
