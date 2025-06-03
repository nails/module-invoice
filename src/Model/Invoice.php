<?php

/**
 * Invoice model
 *
 * @package     Nails
 * @subpackage  module-invoice
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Invoice\Model;

use CI_DB_mysqli_result;
use DateInterval;
use DateTime;
use Exception;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Common\Factory\Model\Field;
use Nails\Common\Model\Base;
use Nails\Common\Resource;
use Nails\Common\Traits\Model\HasDataColumns;
use Nails\Config;
use Nails\Currency;
use Nails\Factory;
use Nails\Invoice\Constants;
use Nails\Invoice\Events;
use Nails\Invoice\Exception\InvoiceException;
use Nails\Invoice\Factory\Email\Invoice\Send;
use Nails\Invoice\Factory\Invoice\Item;
use Nails\Invoice\Model\Invoice\Email;

/**
 * Class Invoice
 *
 * @package Nails\Invoice\Model
 */
class Invoice extends Base
{
    use HasDataColumns;

    // --------------------------------------------------------------------------

    /**
     * The table this model represents
     *
     * @var string
     */
    const TABLE = NAILS_DB_PREFIX . 'invoice_invoice';

    /**
     * The name of the resource to use (as passed to \Nails\Factory::resource())
     *
     * @var string
     */
    const RESOURCE_NAME = 'Invoice';

    /**
     * The provider of the resource to use (as passed to \Nails\Factory::resource())
     *
     * @var string
     */
    const RESOURCE_PROVIDER = Constants::MODULE_SLUG;

    /**
     * The default column to sort on
     *
     * @var string|null
     */
    const DEFAULT_SORT_COLUMN = 'created';

    // --------------------------------------------------------------------------

    /**
     * Turn caching off due to dynamic subqueries in the select statement
     *
     * @var bool
     */
    protected static $CACHING_ENABLED = false;

    // --------------------------------------------------------------------------

    /**
     * The Currency service
     *
     * @var Currency\Service\Currency
     */
    protected $oCurrency;

    // --------------------------------------------------------------------------

    /**
     * The various states that an invoice can be in
     */
    const STATE_DRAFT           = 'DRAFT';
    const STATE_OPEN            = 'OPEN';
    const STATE_PAID_PARTIAL    = 'PAID_PARTIAL';
    const STATE_PAID_PROCESSING = 'PAID_PROCESSING';
    const STATE_PAID            = 'PAID';
    const STATE_WRITTEN_OFF     = 'WRITTEN_OFF';
    const STATE_CANCELLED       = 'CANCELLED';

    // --------------------------------------------------------------------------

    /**
     * Invoice constructor.
     *
     * @throws FactoryException
     * @throws ModelException
     */
    public function __construct()
    {
        parent::__construct();
        $this->oCurrency = Factory::service('Currency', Currency\Constants::MODULE_SLUG);
        $this
            ->hasOne('customer', 'Customer', Constants::MODULE_SLUG)
            ->hasMany('emails', 'InvoiceEmail', 'invoice_id', Constants::MODULE_SLUG)
            ->hasMany('payments', 'Payment', 'invoice_id', Constants::MODULE_SLUG)
            ->hasMany('refunds', 'Refund', 'invoice_id', Constants::MODULE_SLUG)
            ->hasMany('items', 'InvoiceItem', 'invoice_id', Constants::MODULE_SLUG);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the searchable columns for this module
     *
     * @return string[]
     */
    public function getSearchableColumns(): array
    {
        $oPaymentModel = Factory::model('Payment', \Nails\Invoice\Constants::MODULE_SLUG);
        $oRefundModel  = Factory::model('Refund', \Nails\Invoice\Constants::MODULE_SLUG);

        return [
            $this->getTableAlias() . '.id',
            $this->getTableAlias() . '.ref',
            'c.label',
            'c.email',
            'c.billing_email',
            [
                sprintf(
                    '(SELECT GROUP_CONCAT(p.ref) FROM %s p WHERE p.invoice_id = %s.id)',
                    $oPaymentModel->getTableName(),
                    $this->getTableAlias()
                ),
            ],
            [
                sprintf(
                    '(SELECT GROUP_CONCAT(r.ref) FROM %s p LEFT JOIN %s r ON r.payment_id = p.id WHERE p.invoice_id = %s.id)',
                    $oPaymentModel->getTableName(),
                    $oRefundModel->getTableName(),
                    $this->getTableAlias()
                ),
            ],
        ];
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the invoice states with human friendly names
     *
     * @return array
     */
    public function getStates(): array
    {
        return [
            self::STATE_DRAFT           => 'Draft',
            self::STATE_OPEN            => 'Open',
            self::STATE_PAID_PARTIAL    => 'Partially Paid',
            self::STATE_PAID_PROCESSING => 'Paid (payments processing)',
            self::STATE_PAID            => 'Paid',
            self::STATE_WRITTEN_OFF     => 'Written Off',
            self::STATE_CANCELLED       => 'Cancelled',
        ];
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the invoice states which a user can select when creating/editing
     *
     * @return array
     */
    public function getSelectableStates(): array
    {
        return [
            self::STATE_DRAFT => 'Draft',
            self::STATE_OPEN  => 'Open',
        ];
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches all objects, optionally paginated. Returns the basic query object with no formatting.
     *
     * @param int|null $iPage           The page number of the results, if null then no pagination
     * @param int|null $iPerPage        How many items per page of paginated results
     * @param array    $aData           Any data to pass to getCountCommon()
     * @param bool     $bIncludeDeleted If non-destructive delete is enabled then this flag allows you to include deleted items
     *
     * @return object
     * @throws FactoryException
     * @throws ModelException
     */
    public function getAllRawQuery($iPage = null, $iPerPage = null, array $aData = [], bool $bIncludeDeleted = false): CI_DB_mysqli_result
    {
        /** @var \Nails\Common\Service\Database $oDb */
        $oDb            = Factory::service('Database');
        $oCustomerModel = Factory::model('Customer', Constants::MODULE_SLUG);
        $oDb->join($oCustomerModel->getTableName() . ' c', $this->getTableAlias() . '.customer_id = c.id', 'LEFT');

        return parent::getAllRawQuery($iPage, $iPerPage, $aData, $bIncludeDeleted);
    }

    // --------------------------------------------------------------------------

    /**
     * Counts all objects
     *
     * @param array $aData           An array of data to pass to getCountCommon()
     * @param bool  $bIncludeDeleted Whether to include deleted objects or not
     *
     * @return int
     * @throws FactoryException
     * @throws ModelException
     */
    public function countAll($aData = [], bool $bIncludeDeleted = false): int
    {
        /** @var \Nails\Common\Service\Database $oDb */
        $oDb            = Factory::service('Database');
        $oCustomerModel = Factory::model('Customer', Constants::MODULE_SLUG);
        $oDb->join($oCustomerModel->getTableName() . ' c', $this->getTableAlias() . '.customer_id = c.id', 'LEFT');

        return parent::countAll($aData, $bIncludeDeleted);
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches all objects and formats them, optionally paginated
     *
     * @param int|null $iPage           The page number of the results, if null then no pagination
     * @param int|null $iPerPage        How many items per page of paginated results
     * @param mixed    $aData           Any data to pass to getCountCommon()
     * @param bool     $bIncludeDeleted If non-destructive delete is enabled then this flag allows you to include deleted items
     *
     * @return array
     * @throws FactoryException
     */
    public function getAll($iPage = null, $iPerPage = null, array $aData = [], bool $bIncludeDeleted = false): array
    {
        //  If the first value is an array then treat as if called with getAll(null, null, $aData);
        if (is_array($iPage)) {
            $aData = $iPage;
            $iPage = null;
        }

        if (empty($aData['select'])) {

            /** @var \Nails\Invoice\Model\Payment $oPaymentModel */
            $oPaymentModel = Factory::model('Payment', Constants::MODULE_SLUG);

            $aData['select'] = array_values(
                array_merge(
                    array_map(
                        fn(Field $oField) => $this->getTableAlias(true) . $oField->getKey(),
                        $this->describeFields()
                    ),
                    $this->getDynamicColumns()
                )
            );
        }

        $aItems = parent::getAll($iPage, $iPerPage, $aData, $bIncludeDeleted);

        return $aItems;
    }

    // --------------------------------------------------------------------------

    public function getDynamicColumns(): array
    {
        /** @var \Nails\Invoice\Model\Payment $oPaymentModel */
        $oPaymentModel = Factory::model('Payment', Constants::MODULE_SLUG);

        return [
            '(
                    SELECT
                        SUM(amount)
                        FROM `' . Config::get('NAILS_DB_PREFIX') . 'invoice_payment`
                        WHERE
                        invoice_id = ' . $this->getTableAlias() . '.id
                        AND status IN (\'' . $oPaymentModel::STATUS_COMPLETE . '\', \'' . $oPaymentModel::STATUS_REFUNDED . '\', \'' . $oPaymentModel::STATUS_REFUNDED_PARTIAL . '\')
                ) paid_total',
            '(
                    SELECT
                        SUM(amount)
                        FROM `' . Config::get('NAILS_DB_PREFIX') . 'invoice_payment`
                        WHERE
                        invoice_id = ' . $this->getTableAlias() . '.id
                        AND status = \'' . $oPaymentModel::STATUS_PROCESSING . '\'
                ) processing_total',
            '(
                    SELECT
                        COUNT(id)
                        FROM `' . Config::get('NAILS_DB_PREFIX') . 'invoice_payment`
                        WHERE
                        invoice_id = ' . $this->getTableAlias() . '.id
                        AND status = \'' . $oPaymentModel::STATUS_PROCESSING . '\'
                ) processing_payments',
        ];
    }

    // --------------------------------------------------------------------------

    /**
     * Creates a new object
     *
     * @param array $aData         The data to create the object with
     * @param bool  $bReturnObject Whether to return just the new ID or the full object
     *
     * @return bool|mixed
     * @throws FactoryException
     */
    public function create(array $aData = [], $bReturnObject = false)
    {
        /** @var \Nails\Common\Service\Database $oDb */
        $oDb = Factory::service('Database');

        try {

            if (empty($aData['customer_id']) && empty($aData['email'])) {
                throw new InvoiceException('Either "customer_id" or "email" field must be supplied.');
            }

            $oDb->transaction()->start();

            $this->prepareInvoice($aData);

            if (empty($aData['ref'])) {
                $aData['ref'] = $this->generateValidRef();
            }

            $aData['token'] = $this->generateToken();

            $aItems = $aData['items'] ?? [];
            unset($aData['items']);

            $oInvoice = parent::create($aData, true);

            if (!$oInvoice) {
                throw new InvoiceException('Failed to create invoice.');
            }

            if (!empty($aItems)) {
                $this->updateLineItems($oInvoice->id, $aItems);
            }

            $oDb->transaction()->commit();
            $this->triggerEvent(
                Events::INVOICE_CREATED,
                [$this->getInvoiceForEvent($oInvoice->id)]
            );

            return $bReturnObject ? $oInvoice : $oInvoice->id;

        } catch (Exception $e) {
            $oDb->transaction()->rollback();
            $this->setError($e->getMessage());
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Update an invoice
     *
     * @param int|array $mIds  The ID (or array of IDs) of the object(s) to update
     * @param array     $aData The data to update the object(s) with
     *
     * @return bool
     * @throws FactoryException
     */
    public function update($mIds, array $aData = []): bool
    {
        //  @todo (Pablo - 2019-03-06) - Support passing in multiple IDs so as to be compatible with parent

        /** @var \Nails\Common\Service\Database $oDb */
        $oDb = Factory::service('Database');

        try {

            $sKeyExistsCustomerId = array_key_exists('customer_id', $aData);
            $sKeyExistsEmail      = array_key_exists('email', $aData);

            if ($sKeyExistsCustomerId && $sKeyExistsEmail) {
                throw new InvoiceException('An invoice cannot be assigned to both an email and a customer.');
            }

            if ($sKeyExistsCustomerId && empty($aData['customer_id'])) {
                throw new InvoiceException('If supplied, "customer_id" cannot be empty.');
            } elseif ($sKeyExistsCustomerId && $sKeyExistsEmail) {
                //  Ensure the email field is empty if it has been supplied
                $aData['email'] = null;
            }

            if ($sKeyExistsEmail && empty($aData['email'])) {
                throw new InvoiceException('If supplied, "email" cannot be empty.');
            } elseif ($sKeyExistsEmail && $sKeyExistsCustomerId) {
                //  Ensure the customer_id field is empty if it has been supplied
                $aData['customer_id'] = null;
            }

            $oDb->transaction()->start();

            $this->prepareInvoice($aData, $mIds);

            if (array_key_exists('items', $aData)) {
                $aItems = $aData['items'];
                unset($aData['items']);
            }

            unset($aData['ref']);
            unset($aData['token']);

            $bResult = parent::update($mIds, $aData);

            if (!$bResult) {
                throw new InvoiceException('Failed to update invoice.');
            }

            if (!empty($aItems)) {
                $this->updateLineItems($mIds, $aItems);
            }

            $oDb->transaction()->commit();
            $this->triggerEvent(
                Events::INVOICE_UPDATED,
                [$this->getInvoiceForEvent($mIds)]
            );

            return $bResult;

        } catch (Exception $e) {
            $oDb->transaction()->rollback();
            $this->setError($e->getMessage());
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Format and validate passed data
     *
     * @param array $aData      The data to format/validate
     * @param int   $iInvoiceId The invoice ID
     *
     * @throws FactoryException
     * @throws InvoiceException
     * @throws ModelException
     */
    protected function prepareInvoice(array &$aData, ?int $iInvoiceId = null): void
    {
        //  Always has an uppercase state
        if (array_key_exists('state', $aData)) {
            $aData['state'] = !empty($aData['state']) ? $aData['state'] : self::STATE_DRAFT;
            $aData['state'] = strtoupper(trim($aData['state']));
        }

        //  Always has terms
        if (array_key_exists('terms', $aData)) {
            $aData['terms'] = !empty($aData['terms']) ? $aData['terms'] : 0;
        }

        //  Always has a date
        if (array_key_exists('dated', $aData) && empty($aData['dated'])) {
            $oDate          = Factory::factory('DateTime');
            $aData['dated'] = $oDate->format('Y-m-d');
        }

        //  Calculate the due date
        if (!array_key_exists('due', $aData) && !empty($aData['dated'])) {

            if (array_key_exists('terms', $aData)) {
                $iTerms = (int) $aData['terms'];
            } else {
                $iTerms = (int) appSetting('default_payment_terms', Constants::MODULE_SLUG);
            }

            $oDate = new DateTime($aData['dated']);
            $oDate->add(new DateInterval('P' . $iTerms . 'D'));
            $aData['due'] = $oDate->format('Y-m-d');
        }

        //  Always has a currency
        if (array_key_exists('currency', $aData)) {
            $aData['currency'] = strtoupper(trim($aData['currency']));
        }

        //  Callback data is encoded as JSON
        if (array_key_exists('callback_data', $aData)) {
            $aData['callback_data'] = json_encode($aData['callback_data']);
        }
        if (array_key_exists('payment_data', $aData)) {
            $aData['payment_data'] = json_encode($aData['payment_data']);
        }

        //  Sanitize each item
        if (array_key_exists('items', $aData)) {

            $iCounter = 0;
            $aTaxIds  = [];
            foreach ($aData['items'] as &$aItem) {

                if ($aItem instanceof Item) {
                    $aItem = $aItem->toArray();
                }

                //  Has an ID or is null
                $aItem['id'] = !empty($aItem['id']) ? (int) $aItem['id'] : null;

                //  Currency is always the same as the invoice
                $aItem['currency'] = $aData['currency'];

                //  Always has a unit
                $aItem['unit'] = !empty($aItem['unit']) ? strtoupper(trim($aItem['unit'])) : null;

                //  Always has a unit cost
                $aItem['unit_cost'] = !empty($aItem['unit_cost']) ? (float) $aItem['unit_cost'] : 0;

                //  Always has a quantity
                $aItem['quantity'] = !empty($aItem['quantity']) ? (float) $aItem['quantity'] : 0;

                //  Always has a tax_id
                $aItem['tax_id'] = !empty($aItem['tax_id']) ? (int) $aItem['tax_id'] : null;
                if (!empty($aItem['tax_id'])) {
                    $aTaxIds[] = $aItem['tax_id'];
                }

                //  Give it an order
                $aItem['order'] = $iCounter;
                $iCounter++;
            }

            $aTaxIds = array_unique($aTaxIds);
            $aTaxIds = array_filter($aTaxIds);
        }

        // --------------------------------------------------------------------------

        //  Now check for errors

        //  Invalid ref
        if (array_key_exists('ref', $aData)) {
            $oInvoice = $this->getByRef($aData['ref']);
            if (!empty($oInvoice) && $iInvoiceId != $oInvoice->id) {
                throw new InvoiceException('Reference "' . $aData['ref'] . '" is already in use.');
            }
        }

        //  Invalid state
        if (array_key_exists('state', $aData)) {
            $aStates = $this->getStates();
            if (!array_key_exists($aData['state'], $aStates)) {
                throw new InvoiceException('State "' . $aData['ref'] . '" does not exist.');
            }
        }

        //  Invalid Customer ID
        if (array_key_exists('customer_id', $aData) && !empty($aData['customer_id'])) {
            $oCustomerModel = Factory::model('Customer', Constants::MODULE_SLUG);
            if (!$oCustomerModel->getById($aData['customer_id'])) {
                throw new InvoiceException('"' . $aData['customer_id'] . '" is not a valid customer ID.');
            }
        }

        //  Invalid Email
        if (array_key_exists('email', $aData) && !empty($aData['email'])) {
            Factory::helper('email');
            if (!valid_email($aData['email'])) {
                throw new InvoiceException('"' . $aData['email'] . '" is not a valid email address.');
            }
        }

        //  Invalid currency
        if (array_key_exists('currency', $aData)) {
            if (!$this->oCurrency->isEnabled($aData['currency'])) {
                throw new InvoiceException('"' . $aData['currency'] . '" is not a supported currency.');
            }
        }

        //  Invalid Tax IDs
        if (!empty($aTaxIds)) {
            $oTaxModel = Factory::model('Tax', Constants::MODULE_SLUG);
            $aTaxRates = $oTaxModel->getByIds($aTaxIds);
            if (count($aTaxRates) != count($aTaxIds)) {
                throw new InvoiceException('An invalid Tax Rate was supplied.');
            }
        }

        //  Missing items
        if (array_key_exists('items', $aData) && $aData['state'] !== self::STATE_DRAFT && empty($aData['items'])) {

            throw new InvoiceException(
                'At least one line item must be provided if saving a non-draft invoice.',
                1
            );

        } elseif (array_key_exists('items', $aData)) {

            //  Check each item
            /** @var \Nails\Invoice\Model\Invoice\Item $oItemModel */
            $oItemModel = Factory::model('InvoiceItem', Constants::MODULE_SLUG);
            foreach ($aData['items'] as &$aItem) {

                //  Has a positive quantity
                if ($aItem['quantity'] <= 0) {
                    throw new InvoiceException('Each item must have a positive quantity.');
                }

                //  Has a valid unit
                $aUnits = $oItemModel->getUnits();
                if (!empty($aItem['unit']) && !array_key_exists($aItem['unit'], $aUnits)) {
                    throw new InvoiceException('Unit "' . $aItem['unit'] . '" does not exist.');
                }

                //  Has a label
                if (empty($aItem['label'])) {
                    throw new InvoiceException('Each item must be given a label.');
                }
            }

            //  Calculate totals
            //  @todo: do this properly considering currencies etc
            $aData['sub_total'] = 0;
            $aData['tax_total'] = 0;

            foreach ($aData['items'] as &$aItem) {

                //  Add to sub total
                $aItem['sub_total'] = $aItem['quantity'] * $aItem['unit_cost'];

                //  Calculate tax
                if (!empty($aItem['tax_id']) && !empty($aTaxRates)) {
                    foreach ($aTaxRates as $oTaxRate) {
                        if ($oTaxRate->id == $aItem['tax_id']) {
                            $aItem['tax_total'] = $aItem['sub_total'] * $oTaxRate->rate_decimal;
                        }
                    }
                } else {
                    $aItem['tax_total'] = 0;
                }

                //  Ensure integers
                $aItem['unit_cost'] = intval($aItem['unit_cost']);
                $aItem['sub_total'] = intval($aItem['sub_total']);
                $aItem['tax_total'] = intval($aItem['tax_total']);

                //  Grand total
                $aItem['grand_total'] = $aItem['sub_total'] + $aItem['tax_total'];

                //  Update invoice total
                $aData['sub_total'] += $aItem['sub_total'];
                $aData['tax_total'] += $aItem['tax_total'];
            }

            $aData['grand_total'] = $aData['sub_total'] + $aData['tax_total'];
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Update the line items of an invoice
     *
     * @param int   $iInvoiceId The invoice ID
     * @param array $aItems     The items to update
     *
     * @throws FactoryException
     * @throws InvoiceException
     */
    protected function updateLineItems(int $iInvoiceId, array $aItems): void
    {
        $oItemModel  = Factory::model('InvoiceItem', Constants::MODULE_SLUG);
        $aTouchedIds = [];

        //  Update/insert all known items
        foreach ($aItems as $aItem) {

            $aData = [
                'label'         => getFromArray('label', $aItem, null),
                'body'          => getFromArray('body', $aItem, null),
                'order'         => getFromArray('order', $aItem, 0),
                'currency'      => getFromArray('currency', $aItem, null),
                'unit'          => getFromArray('unit', $aItem, null),
                'tax_id'        => getFromArray('tax_id', $aItem, null),
                'quantity'      => getFromArray('quantity', $aItem, 1),
                'unit_cost'     => getFromArray('unit_cost', $aItem, 0),
                'sub_total'     => getFromArray('sub_total', $aItem, 0),
                'tax_total'     => getFromArray('tax_total', $aItem, 0),
                'grand_total'   => getFromArray('grand_total', $aItem, 0),
                'callback_data' => getFromArray('callback_data', $aItem, null),
            ];

            //  Ensure callback data is encoded as JSON
            if (array_key_exists('callback_data', $aData)) {
                $aData['callback_data'] = json_encode($aData['callback_data']);
            }

            if (!empty($aItem['id'])) {

                //  Update
                if (!$oItemModel->update($aItem['id'], $aData)) {
                    throw new InvoiceException('Failed to update invoice item.');
                } else {
                    $aTouchedIds[] = $aItem['id'];
                }

            } else {

                //  Insert
                $aData['invoice_id'] = $iInvoiceId;
                $iItemId             = $oItemModel->create($aData);

                if (!$iItemId) {
                    throw new InvoiceException('Failed to create invoice item.');
                } else {
                    $aTouchedIds[] = $iItemId;
                }
            }
        }

        //  Delete those we no longer require
        if (!empty($aTouchedIds)) {

            /** @var \Nails\Common\Service\Database $oDb */
            $oDb = Factory::service('Database');
            $oDb->where_not_in('id', $aTouchedIds);
            $oDb->where('invoice_id', $iInvoiceId);
            if (!$oDb->delete($oItemModel->getTableName())) {
                throw new InvoiceException('Failed to delete old invoice items.');
            }
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Fetch an invoice by it's ref
     *
     * @param string $sRef  The ref of the invoice to fetch
     * @param mixed  $aData Any data to pass to getCountCommon()
     *
     * @return null|Resource
     * @throws ModelException
     */
    public function getByRef(?string $sRef, array $aData = []): ?Resource
    {
        return $this->getByColumn('ref', $sRef, $aData);
    }

    // --------------------------------------------------------------------------

    /**
     * Generates a valid invoice ref
     *
     * @return string
     * @throws FactoryException
     */
    public function generateValidRef(): string
    {
        Factory::helper('string');

        /** @var \Nails\Common\Service\Database $oDb */
        $oDb = Factory::service('Database');
        /** @var \DateTime $oNow */
        $oNow = Factory::factory('DateTime');

        do {

            $sRef = $oNow->format('Ym') . '-' . strtoupper(random_string('alnum'));
            $oDb->where('ref', $sRef);
            $bRefExists = (bool) $oDb->count_all_results($this->getTableName());

        } while ($bRefExists);

        return $sRef;
    }

    // --------------------------------------------------------------------------

    /**
     * Send an invoice by email
     *
     * @param int    $iInvoiceId     The ID of the invoice to send
     * @param string $sEmailOverride Send to this email instead of the email defined by the invoice object
     *
     * @return bool
     */
    public function send(int $iInvoiceId, ?string $sEmailOverride = null): bool
    {
        try {

            /** @var \Nails\Invoice\Resource\Invoice $oInvoice */
            $oInvoice = $this->getById($iInvoiceId, ['expand' => ['customer', 'items']]);

            if (empty($oInvoice)) {
                throw new InvoiceException('Invalid Invoice ID');
            }

            if ($oInvoice->state->id !== self::STATE_OPEN) {
                throw new InvoiceException('Invoice must be in an open state to send.');
            }

            $oBillingAddress  = $oInvoice->billingAddress();
            $oDeliveryAddress = $oInvoice->deliveryAddress();

            /** @var Send $oEmail */
            $oEmail = Factory::factory('EmailInvoiceSend', Constants::MODULE_SLUG);
            $oEmail
                ->data([
                    'invoice' => [
                        'id'       => $oInvoice->id,
                        'ref'      => $oInvoice->ref,
                        'due'      => $oInvoice->due->format('l jS F Y'),
                        'dated'    => $oInvoice->dated->format('l jS F Y'),
                        'customer' => [
                            'id'    => $oInvoice->customer->id,
                            'label' => $oInvoice->customer->label,
                        ],
                        'address'  => [
                            'billing'  => $oBillingAddress
                                ? array_filter($oBillingAddress->formatted()->asArray())
                                : null,
                            'delivery' => $oDeliveryAddress
                                ? array_filter($oDeliveryAddress->formatted()->asArray())
                                : null,
                        ],
                        'urls'     => [
                            'view'     => $oInvoice->urls->view,
                            'payment'  => $oInvoice->urls->payment,
                            'download' => $oInvoice->urls->download,
                        ],
                        'totals'   => [
                            'sub'   => html_entity_decode($oInvoice->totals->formatted->sub),
                            'tax'   => html_entity_decode($oInvoice->totals->formatted->tax),
                            'grand' => html_entity_decode($oInvoice->totals->formatted->grand),
                        ],
                        'items'    => array_map(function (\Nails\Invoice\Resource\Invoice\Item $oItem) {
                            return [
                                'id'       => $oItem->id,
                                'label'    => $oItem->label,
                                'body'     => $oItem->body,
                                'quantity' => $oItem->quantity,
                                'totals'   => [
                                    'sub'   => html_entity_decode($oItem->totals->formatted->sub),
                                    'tax'   => html_entity_decode($oItem->totals->formatted->tax),
                                    'grand' => html_entity_decode($oItem->totals->formatted->grand),
                                ],
                            ];
                        }, $oInvoice->items->data),
                    ],
                ]);

            if (!empty($sEmailOverride)) {
                //  @todo (Pablo - 2019-08-02) - validate email address (or addresses if an array)
                $aEmails = explode(',', $sEmailOverride);

            } elseif (!empty($oInvoice->customer->billing_email)) {
                $aEmails = explode(',', $oInvoice->customer->billing_email);

            } elseif (!empty($oInvoice->customer->email)) {
                $aEmails = [$oInvoice->customer->email];

            } elseif (!empty($oInvoice->email)) {
                $aEmails = [$oInvoice->email];

            } else {
                $aEmails = [];
            }

            /** @var Email $oInvoiceEmailModel */
            $oInvoiceEmailModel = Factory::model('InvoiceEmail', Constants::MODULE_SLUG);
            $oInvoiceEmailModel->sendEmails(
                $aEmails,
                $oEmail,
                $oInvoice
            );

        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }

        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Whether an invoice has been fully paid or not
     *
     * @param \Nails\Invoice\Resource\Invoice|int $mInvoice           The Invoice to query
     * @param bool                                $bIncludeProcessing Whether to include payments which are still processing
     *
     * @return bool
     * @throws ModelException
     */
    public function isPaid($mInvoice, bool $bIncludeProcessing = false): bool
    {
        if ($mInvoice instanceof \Nails\Invoice\Resource\Invoice) {
            $iInvoiceId = $mInvoice->id;

        } elseif (is_int($mInvoice)) {
            $iInvoiceId = $mInvoice;

        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    'Expected %s, got %s',
                    implode('|', [
                        \Nails\Invoice\Resource\Invoice::class,
                        'int',
                    ]),
                    gettype($mInvoice)
                )
            );
        }

        /** @var \Nails\Invoice\Resource\Invoice $oInvoice */
        $oInvoice = $this->skipCache()->getById($iInvoiceId);

        return $oInvoice
            ? $oInvoice->isPaid($bIncludeProcessing)
            : false;
    }

    // --------------------------------------------------------------------------

    /**
     * Set an invoice as paid
     *
     * @param int $iInvoiceId The Invoice to query
     *
     * @return bool
     * @throws ModelException
     * @throws FactoryException
     */
    public function setPaid($iInvoiceId): bool
    {
        /** @var \DateTime $oNow */
        $oNow    = Factory::factory('DateTime');
        $bResult = $this->update(
            $iInvoiceId,
            [
                'state' => self::STATE_PAID,
                'paid'  => $oNow->format('Y-m-d H:i:s'),
            ]
        );

        if ($bResult) {
            $this->triggerEvent(
                Events::INVOICE_PAID,
                [$this->getInvoiceForEvent($iInvoiceId)]
            );
        }

        return $bResult;
    }

    // --------------------------------------------------------------------------

    /**
     * Set an invoice as paid but with processing payments
     *
     * @param int $iInvoiceId The Invoice to query
     *
     * @return bool
     * @throws ModelException
     * @throws FactoryException
     */
    public function setPaidProcessing($iInvoiceId): bool
    {
        /** @var \DateTime $oNow */
        $oNow    = Factory::factory('DateTime');
        $bResult = $this->update(
            $iInvoiceId,
            [
                'state' => self::STATE_PAID_PROCESSING,
                'paid'  => $oNow->format('Y-m-d H:i:s'),
            ]
        );

        if ($bResult) {
            $this->triggerEvent(
                Events::INVOICE_PAID_PROCESSING,
                [$this->getInvoiceForEvent($iInvoiceId)]
            );
        }

        return $bResult;
    }

    // --------------------------------------------------------------------------

    /**
     * Set an invoice as written off
     *
     * @param int $iInvoiceId The Invoice to query
     *
     * @return bool
     * @throws ModelException
     * @throws FactoryException
     */
    public function setWrittenOff($iInvoiceId): bool
    {
        /** @var \DateTime $oNow */
        $oNow    = Factory::factory('DateTime');
        $bResult = $this->update(
            $iInvoiceId,
            [
                'state'       => self::STATE_WRITTEN_OFF,
                'written_off' => $oNow->format('Y-m-d H:i:s'),
            ]
        );

        if ($bResult) {
            $this->triggerEvent(
                Events::INVOICE_WRITTEN_OFF,
                [$this->getInvoiceForEvent($iInvoiceId)]
            );
        }

        return $bResult;
    }

    // --------------------------------------------------------------------------

    /**
     * Set an invoice as cancelled
     *
     * @param int $iInvoiceId The Invoice to query
     *
     * @return bool
     * @throws ModelException
     * @throws FactoryException
     */
    public function setCancelled($iInvoiceId): bool
    {
        /** @var \DateTime $oNow */
        $oNow    = Factory::factory('DateTime');
        $bResult = $this->update(
            $iInvoiceId,
            [
                'state'       => self::STATE_CANCELLED,
                'written_off' => $oNow->format('Y-m-d H:i:s'),
            ]
        );

        if ($bResult) {
            $this->triggerEvent(
                Events::INVOICE_CANCELLED,
                [$this->getInvoiceForEvent($iInvoiceId)]
            );
        }

        return $bResult;
    }

    // --------------------------------------------------------------------------

    /**
     * Get an invoice in a suitable format for the event triggers
     *
     * @param int $iInvoiceId The invoice ID
     *
     * @return Resource
     * @throws ModelException
     */
    protected function getInvoiceForEvent(int $iInvoiceId): Resource
    {
        $oInvoice = $this->getById($iInvoiceId, ['expand' => ['customer', 'items']]);
        if (empty($oInvoice)) {
            throw new ModelException('Invalid invoice ID');
        }
        return $oInvoice;
    }

    // --------------------------------------------------------------------------

    /**
     * Formats a single object
     * The getAll() method iterates over each returned item with this method so as to
     * correctly format the output. Use this to cast integers and booleans and/or organise data into objects.
     *
     * @param object $oObj      A reference to the object being formatted.
     * @param array  $aData     The same data array which is passed to getCountCommon, for reference if needed
     * @param array  $aIntegers Fields which should be cast as integers if numerical and not null
     * @param array  $aBools    Fields which should be cast as booleans if not null
     * @param array  $aFloats   Fields which should be cast as floats if not null
     */
    protected function formatObject(
        &$oObj,
        array $aData = [],
        array $aIntegers = [],
        array $aBools = [],
        array $aFloats = []
    ) {

        $aIntegers[] = 'paid_total';
        $aIntegers[] = 'processing_total';

        parent::formatObject($oObj, $aData, $aIntegers, $aBools, $aFloats);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the columns which contain large data
     *
     * @return string[]
     */
    public function getDataColumns(): array
    {
        return [
            'callback_data',
            'payment_data',
        ];
    }
}
