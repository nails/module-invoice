<?php

/**
 * This is a convenience class for generating invoices
 *
 * @package     Nails
 * @subpackage  module-invoice
 * @category    Factory
 * @author      Nails Dev Team
 */

namespace Nails\Invoice\Factory;

use DateTime;
use Nails\Address\Resource\Address;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Common\Resource\Date;
use Nails\Common\Traits\ErrorHandling;
use Nails\Currency\Exception\CurrencyException;
use Nails\Currency\Resource\Currency;
use Nails\Factory;
use Nails\Invoice\Constants;
use Nails\Invoice\Exception\ChargeRequestException;
use Nails\Invoice\Exception\InvoiceException;
use Nails\Invoice\Exception\RequestException;
use Nails\Invoice\Factory\Invoice\CallbackData;
use Nails\Invoice\Factory\Invoice\Item;
use Nails\Invoice\Factory\Invoice\PaymentData;
use Nails\Invoice\Interfaces\Driver\Payment;
use Nails\Invoice\Model;
use Nails\Invoice\Resource;
use stdClass;

/**
 * Class Invoice
 *
 * @package Nails\Invoice\Factory
 */
class Invoice
{
    use ErrorHandling;

    // --------------------------------------------------------------------------

    /**
     * The invoice's ID
     *
     * @var int
     */
    protected $iId = null;

    /**
     * The invoice's ref
     *
     * @var string
     */
    protected $sRef = '';

    /**
     * The invoice's state
     *
     * @var string
     */
    protected $sState = Model\Invoice::STATE_OPEN;

    /**
     * The invoice's dated date
     *
     * @var string
     */
    protected $sDated = '';

    /**
     * The date the invoice was paid
     *
     * @var string
     */
    protected $sPaidDate;

    /**
     * The invoice's terms
     *
     * @var int
     */
    protected $iTerms = 0;

    /**
     * The invoice's customer ID
     *
     * @var int
     */
    protected $iCustomerId = null;

    /**
     * The invoice's email
     *
     * @var string
     */
    protected $sEmail = '';

    /**
     * The invoice's currency
     *
     * @var string
     */
    protected $sCurrency = '';

    /**
     * The invoice's billing address ID
     *
     * @var int
     */
    protected $iBillingAddressId = null;

    /**
     * The invoice's delivery address ID
     *
     * @var int
     */
    protected $iDeliveryAddressId = null;

    /**
     * The invoice's additional text
     *
     * @var string
     */
    protected $sAdditionalText = '';

    /**
     * The invoice's callback data
     *
     * @var CallbackData|null
     */
    protected $oCallbackData = null;

    /**
     * The invoice's payment data
     *
     * @var PaymentData|null
     */
    protected $oPaymentData = null;

    /**
     * Define which driver can be sued to pay this invoice
     *
     * @var string|null
     */
    protected $sPaymentDriver = null;

    /**
     * The invoice's items
     *
     * @var array
     */
    protected $aItems = [];

    // --------------------------------------------------------------------------

    /**
     * Invoice constructor.
     *
     * @throws FactoryException
     */
    public function __construct()
    {
        $this->oCallbackData = Factory::factory('InvoiceCallbackData', Constants::MODULE_SLUG);
        $this->oPaymentData  = Factory::factory('InvoicePaymentData', Constants::MODULE_SLUG);
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the invoice's ID
     *
     * @param int $iId The invoice's ID
     *
     * @return $this
     * @throws InvoiceException
     */
    public function setId(int $iId): Invoice
    {
        $this->ensureNotSaved();
        $this->iId = $iId;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the invoice's ID
     *
     * @return int
     */
    public function getId(): ?int
    {
        return $this->iId;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the invoice's ref
     *
     * @param string $sRef The invoice's ref
     *
     * @return $this
     * @throws InvoiceException
     */
    public function setRef(string $sRef): Invoice
    {
        $this->ensureNotSaved();
        $this->sRef = $sRef;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the invoice's ref
     *
     * @return string
     */
    public function getRef(): string
    {
        return $this->sRef;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the invoice's state
     *
     * @param string $sState The invoice's state
     *
     * @return $this
     * @throws InvoiceException
     */
    public function setState(string $sState): Invoice
    {
        $this->ensureNotSaved();
        $this->sState = $sState;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the invoice's state
     *
     * @return string
     */
    public function getState(): string
    {
        return $this->sState;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the invoice's dated date
     *
     * @param DateTime|Date|string $mDate The invoice's dated date
     *
     * @return $this
     * @throws InvoiceException
     */
    public function setDated($mDate): Invoice
    {
        $this->ensureNotSaved();

        if ($mDate instanceof DateTime) {
            $this->sDated = $mDate->format('Y-m-d');
        } elseif ($mDate instanceof Date) {
            $this->sDated = (string) $mDate;
        } elseif (is_string($mDate)) {
            $this->sDated = $mDate;
        } else {
            throw new InvoiceException(
                'Invalid data type (' . gettype($mDate) . ') passed to ' . __METHOD__
            );
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the invoice's dated date
     *
     * @return string
     */
    public function getDated()
    {
        return $this->sDated;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the invoice's paid date
     *
     * @param DateTime|Date|string $mDate The invoice's paid date
     *
     * @return $this
     * @throws InvoiceException
     */
    public function setPaidDate($mDate): Invoice
    {
        $this->ensureNotSaved();

        if ($mDate instanceof DateTime) {
            $this->sPaidDate = $mDate->format('Y-m-d');
        } elseif ($mDate instanceof Date) {
            $this->sPaidDate = (string) $mDate;
        } elseif (is_string($mDate)) {
            $this->sPaidDate = $mDate;
        } else {
            throw new InvoiceException(
                'Invalid data type (' . gettype($mDate) . ') passed to ' . __METHOD__
            );
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the invoice's paid date
     *
     * @return string
     */
    public function getPaidDate()
    {
        return $this->sPaidDate;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the invoice's terms
     *
     * @param int $iTerms The invoice's terms
     *
     * @return $this
     * @throws InvoiceException
     */
    public function setTerms(int $iTerms): Invoice
    {
        $this->ensureNotSaved();
        $this->iTerms = $iTerms;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the invoice's terms
     *
     * @return int
     */
    public function getTerms(): int
    {
        return $this->iTerms;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the invoice's Customer ID
     *
     * @param int|Resource\Customer $mCustomer The invoice's Customer ID
     *
     * @return $this
     * @throws InvoiceException
     */
    public function setCustomerId($mCustomer): Invoice
    {
        $this->ensureNotSaved();

        if ($mCustomer instanceof Resource\Customer) {
            $this->iCustomerId = $mCustomer->id;
        } elseif (is_int($mCustomer)) {
            $this->iCustomerId = $mCustomer;
        } else {
            throw new InvoiceException(
                'Invalid data type (' . gettype($mCustomer) . ') passed to ' . __METHOD__
            );
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the invoice's Customer ID
     *
     * @return int
     */
    public function getCustomerId(): ?int
    {
        return $this->iCustomerId;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the invoice's email
     *
     * @param string $sEmail The invoice's email
     *
     * @return $this
     * @throws InvoiceException
     */
    public function setEmail(string $sEmail): Invoice
    {
        $this->ensureNotSaved();
        $this->sEmail = $sEmail;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the invoice's email
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->sEmail;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the invoice's currency
     *
     * @param Currency|string $mCurrency The invoice's currency
     *
     * @return $this
     * @throws InvoiceException
     */
    public function setCurrency($mCurrency): Invoice
    {
        $this->ensureNotSaved();

        if ($mCurrency instanceof Currency) {
            $this->sCurrency = $mCurrency->code;
        } elseif (is_string($mCurrency)) {
            $this->sCurrency = $mCurrency;
        } else {
            throw new InvoiceException(
                'Invalid data type (' . gettype($mCurrency) . ') passed to ' . __METHOD__
            );
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the invoice's currency
     *
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->sCurrency;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the invoice's Billing Address ID
     *
     * @param Address|int $mAddress The address to use
     *
     * @return $this
     * @throws InvoiceException
     */
    public function setBillingAddressId($mAddress): Invoice
    {
        $this->ensureNotSaved();

        if ($mAddress instanceof Address) {
            $this->iBillingAddressId = $mAddress->id;

        } elseif (is_int($mAddress)) {
            $this->iBillingAddressId = $mAddress;

        } else {
            throw new InvoiceException(
                'Invalid data type (' . gettype($mAddress) . ') passed to ' . __METHOD__
            );
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the invoice's billing address ID
     *
     * @return int|null
     */
    public function getBillingAddressId(): ?int
    {
        return $this->iBillingAddressId;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the invoice's Delivery Address ID
     *
     * @param Address|int $mAddress The address to use
     *
     * @return $this
     * @throws InvoiceException
     */
    public function setDeliveryAddressId($mAddress): Invoice
    {
        $this->ensureNotSaved();

        if ($mAddress instanceof Address) {
            $this->iDeliveryAddressId = $mAddress->id;

        } elseif (is_int($mAddress)) {
            $this->iDeliveryAddressId = $mAddress;

        } else {
            throw new InvoiceException(
                'Invalid data type (' . gettype($mAddress) . ') passed to ' . __METHOD__
            );
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the invoice's billing address ID
     *
     * @return int|null
     */
    public function getDeliveryAddressId(): ?int
    {
        return $this->iDeliveryAddressId;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the invoice's additional text
     *
     * @param string $sAdditionalText The invoice's additional text
     *
     * @return $this
     * @throws InvoiceException
     */
    public function setAdditionalText(string $sAdditionalText): Invoice
    {
        $this->ensureNotSaved();
        $this->sAdditionalText = $sAdditionalText;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the invoice's additional text
     *
     * @return string
     */
    public function getAdditionalText(): string
    {
        return $this->sAdditionalText;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the invoice's callback data
     *
     * @param string|CallbackData $mKey   The key to set, if CallbackData is provided, the entire object is replaced
     * @param mixed|null          $mValue The value to set
     *
     * @return $this
     * @throws InvoiceException
     */
    public function setCallbackData($mKey, $mValue = null): Invoice
    {
        $this->ensureNotSaved();

        if ($mKey instanceof CallbackData) {
            $this->oCallbackData = $mKey;
        } elseif ($mKey instanceof stdClass) {
            $this->oCallbackData = new CallbackData($mKey);
        } else {
            if ($this->oCallbackData === null) {
                $this->oCallbackData = new stdClass();
            }
            $this->oCallbackData->{$mKey} = $mValue;
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the invoice's callback data
     *
     * @return CallbackData|null
     */
    public function getCallbackData(): ?CallbackData
    {
        return $this->oCallbackData;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the invoice's payment data
     *
     * @param string|PaymentData $mKey   The key to set, if PaymentData is provided, the entire object is replaced
     * @param mixed|null         $mValue The value to set
     *
     * @return $this
     * @throws InvoiceException
     */
    public function setPaymentData($mKey, $mValue = null): Invoice
    {
        $this->ensureNotSaved();

        if ($mKey instanceof PaymentData) {
            $this->oPaymentData = $mKey;
        } else {
            $this->oPaymentData->{$mKey} = $mValue;
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the invoice's payment driver
     *
     * @return string|null
     */
    public function getPaymentDriver(): ?string
    {
        return $this->sPaymentDriver;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the invoice's payment driver
     *
     * @param string|Payment $mPaymentDriver The invoice's payment driver
     *
     * @return $this
     * @throws InvoiceException
     */
    public function setPaymentDriver($mPaymentDriver): Invoice
    {
        $this->ensureNotSaved();

        if ($mPaymentDriver instanceof Payment) {
            $this->sPaymentDriver = $mPaymentDriver->getSlug();
        } elseif (is_string($mPaymentDriver)) {
            $this->sPaymentDriver = $mPaymentDriver;
        } else {
            throw new InvoiceException(
                'Invalid data type (' . gettype($mPaymentDriver) . ') passed to ' . __METHOD__
            );
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the invoice's payment data
     *
     * @return PaymentData|null
     */
    public function getPaymentData(): ?PaymentData
    {
        return $this->oPaymentData;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the invoice's items
     *
     * @param array $aItems The invoice's items
     *
     * @return $this
     * @throws InvoiceException
     */
    public function setItems($aItems): Invoice
    {
        $this->ensureNotSaved();
        $this->aItems = $aItems;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the invoice's items
     *
     * @return array
     */
    public function getItems(): array
    {
        return $this->aItems;
    }

    // --------------------------------------------------------------------------

    /**
     * Add an item to the invoice
     *
     * @param Item $oItem the item to add
     *
     * @return $this
     * @throws InvoiceException
     */
    public function addItem(Item $oItem)
    {
        $this->ensureNotSaved();
        $this->aItems[] = $oItem;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Add multiple items to the invoice
     *
     * @param array $aItems The array of items to add
     *
     * @return $this
     * @throws InvoiceException
     */
    public function addItems(array $aItems)
    {
        foreach ($aItems as $oItem) {
            $this->addItem($oItem);
        }
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Throws an exception if the item's ID has been set
     *
     * @throws InvoiceException
     */
    protected function ensureNotSaved()
    {
        if (!empty($this->iId)) {
            throw new InvoiceException('Invoice has been saved and cannot be modified.');
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Saves a new invoice
     *
     * @return Resource\Invoice
     * @throws FactoryException
     * @throws InvoiceException
     * @throws ModelException
     */
    public function save(): Resource\Invoice
    {
        /** @var Model\Invoice $oInvoiceModel */
        $oInvoiceModel = Factory::model('Invoice', Constants::MODULE_SLUG);
        if (empty($this->iId)) {

            $oInvoice = $oInvoiceModel->create($this->toArray(), true);
            if (empty($oInvoice)) {
                throw new InvoiceException($oInvoiceModel->lastError());
            }

            $this->setRef($oInvoice->ref);
            $this->setId($oInvoice->id);

        } else {
            $oInvoice = $oInvoiceModel->getById($this->iId);
        }

        return $oInvoice;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an invoice resource
     *
     * @return Resource\Invoice
     * @throws InvoiceException
     * @throws FactoryException
     * @throws ModelException
     */
    public function get(): Resource\Invoice
    {
        if (empty($this->iId)) {
            return $this->save();
        }

        /** @var Model\Invoice $oInvoiceModel */
        $oInvoiceModel = Factory::model('Invoice', Constants::MODULE_SLUG);
        /** @var Resource\Invoice $oInvoice */
        $oInvoice = $oInvoiceModel->getById($this->iId);

        return $oInvoice;
    }

    // --------------------------------------------------------------------------

    /**
     * Deletes an invoice
     *
     * @return $this
     * @throws FactoryException
     * @throws InvoiceException
     * @throws ModelException
     */
    public function delete()
    {
        if (!empty($this->iId)) {
            /** @var Model\Invoice $oInvoiceModel */
            $oInvoiceModel = Factory::model('Invoice', Constants::MODULE_SLUG);
            if (!$oInvoiceModel->delete($this->iId)) {
                throw new InvoiceException('Failed to delete invoice.');
            }
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Writes an invoice off
     *
     * @return $this
     * @throws FactoryException
     * @throws InvoiceException
     * @throws ModelException
     */
    public function writeOff()
    {
        $this->sState = Model\Invoice::STATE_WRITTEN_OFF;

        if (!empty($this->iId)) {
            /** @var Model\Invoice $oInvoiceModel */
            $oInvoiceModel = Factory::model('Invoice', Constants::MODULE_SLUG);
            if (!$oInvoiceModel->setWrittenOff($this->iId)) {
                throw new InvoiceException('Failed to write off invoice.');
            }
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Cancels an invoice
     *
     * @return $this
     * @throws FactoryException
     * @throws InvoiceException
     * @throws ModelException
     */
    public function cancel()
    {
        $this->sState = Model\Invoice::STATE_CANCELLED;

        if (!empty($this->iId)) {
            /** @var Model\Invoice $oInvoiceModel */
            $oInvoiceModel = Factory::model('Invoice', Constants::MODULE_SLUG);
            if (!$oInvoiceModel->setCancelled($this->iId)) {
                throw new InvoiceException('Failed to cancel invoice.');
            }
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Charges an invoice
     *
     * @param ChargeRequest $oChargeRequest The ChargeRequest object to use
     * @param string        $sDescription   The description to give the charge
     *
     * @return ChargeResponse
     * @throws FactoryException
     * @throws InvoiceException
     * @throws ModelException
     * @throws ChargeRequestException
     * @throws RequestException
     * @throws CurrencyException
     */
    public function charge(ChargeRequest $oChargeRequest, ?string $sDescription = null)
    {
        if (empty($this->iId)) {
            $oInvoice = $this->save();
        } else {
            /** @var Model\Invoice $oInvoiceModel */
            $oInvoiceModel = Factory::model('Invoice', Constants::MODULE_SLUG);
            $oInvoice      = $oInvoiceModel->getById($this->iId);
        }

        return $oChargeRequest
            ->setInvoice($oInvoice)
            ->setDescription($sDescription ?? $oChargeRequest->getDescription() ??  'Payment for invoice ' . $this->sRef)
            ->execute();
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the item as an array
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'ref'                 => $this->getRef(),
            'state'               => $this->getState(),
            'dated'               => $this->getDated(),
            'paid'                => $this->getPaidDate(),
            'terms'               => $this->getTerms(),
            'customer_id'         => $this->getCustomerId(),
            'email'               => $this->getEmail(),
            'currency'            => $this->getCurrency(),
            'additional_text'     => $this->getAdditionalText(),
            'callback_data'       => $this->getCallbackData(),
            'payment_data'        => $this->getPaymentData(),
            'payment_driver'      => $this->getPaymentDriver(),
            'billing_address_id'  => $this->getBillingAddressId(),
            'delivery_address_id' => $this->getDeliveryAddressId(),
            'items'               => $this->getItems(),
        ];
    }
}
