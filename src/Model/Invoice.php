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

use Nails\Factory;
use Nails\Common\Model\Base;
use Nails\Invoice\Exception\InvoiceException;

class Invoice extends Base
{
    /**
     * The table where line items are stored
     * @var string
     */
    protected $tableItem;

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

    // --------------------------------------------------------------------------

    /**
     * Currency values
     * @todo  make this way more dynamic
     */
    const CURRENCY_DECIMAL_PLACES = 2;
    const CURRENCY_CODE           = 'GBP';
    const CURRENCY_SYMBOL_HTML    = '&pound;';
    const CURRENCY_SYMBOL_TEXT    = '£';
    const CURRENCY_LOCALISE_VALUE = 100;

    // --------------------------------------------------------------------------

    /**
     * Construct the model
     */
    public function __construct()
    {
        parent::__construct();
        $this->table             = NAILS_DB_PREFIX . 'invoice_invoice';
        $this->tablePrefix       = 'i';
        $this->tableItem         = NAILS_DB_PREFIX . 'invoice_invoice_item';
        $this->defaultSortColumn = 'created';
        $this->searchableFields  = array('id', 'ref');
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the invoice states with human friendly names
     * @return array
     */
    public function getStates()
    {
        return array(
            self::STATE_DRAFT           => 'Draft',
            self::STATE_OPEN            => 'Open',
            self::STATE_PAID_PARTIAL    => 'Partially Paid',
            self::STATE_PAID_PROCESSING => 'Paid (payments processing)',
            self::STATE_PAID            => 'Paid',
            self::STATE_WRITTEN_OFF     => 'Written Off'
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the invoice states which a user can select when creating/editing
     * @return array
     */
    public function getSelectableStates()
    {
        return array(
            self::STATE_DRAFT => 'Draft',
            self::STATE_OPEN  => 'Open'
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Retrieve all invoices from the databases
     * @param  int     $iPage           The page number to return
     * @param  int     $iPerPage        The number of results per page
     * @param  array   $aData           Data to pass _to getcount_common()
     * @param  boolean $bIncludeDeleted Whether to include deleted results
     * @return array
     */
    public function getAll($iPage = null, $iPerPage = null, $aData = array(), $bIncludeDeleted = false)
    {
        if (empty($aData['select'])) {

            $oPaymentModel = Factory::model('Payment', 'nailsapp/module-invoice');
            $sPaymentClass = get_class($oPaymentModel);

            $aData['select'] = array(
                $this->tablePrefix . '.id',
                $this->tablePrefix . '.ref',
                $this->tablePrefix . '.token',
                $this->tablePrefix . '.state',
                $this->tablePrefix . '.dated',
                $this->tablePrefix . '.terms',
                $this->tablePrefix . '.due',
                $this->tablePrefix . '.paid',
                $this->tablePrefix . '.customer_id',
                $this->tablePrefix . '.currency',
                $this->tablePrefix . '.sub_total',
                $this->tablePrefix . '.tax_total',
                $this->tablePrefix . '.grand_total',
                '(
                    SELECT
                        SUM(amount)
                        FROM `' . NAILS_DB_PREFIX . 'invoice_payment`
                        WHERE
                        invoice_id = ' . $this->tablePrefix . '.id
                        AND status = \'' . $sPaymentClass::STATUS_COMPLETE . '\'
                ) paid_total',
                '(
                    SELECT
                        SUM(amount)
                        FROM `' . NAILS_DB_PREFIX . 'invoice_payment`
                        WHERE
                        invoice_id = ' . $this->tablePrefix . '.id
                        AND status = \'' . $sPaymentClass::STATUS_PROCESSING . '\'
                ) processing_total',
                '(
                    SELECT
                        COUNT(id)
                        FROM `' . NAILS_DB_PREFIX . 'invoice_payment`
                        WHERE
                        invoice_id = ' . $this->tablePrefix . '.id
                        AND status = \'' . $sPaymentClass::STATUS_PROCESSING . '\'
                ) processing_payments',
                $this->tablePrefix . '.additional_text',
                $this->tablePrefix . '.callback_data',
                $this->tablePrefix . '.created',
                $this->tablePrefix . '.created_by',
                $this->tablePrefix . '.modified',
                $this->tablePrefix . '.modified_by'
            );
        }

        $aItems = parent::getAll($iPage, $iPerPage, $aData, $bIncludeDeleted);

        if (!empty($aItems)) {

            if (!empty($aData['includeAll']) || !empty($aData['includeCustomer'])) {

                $this->getSingleAssociatedItem(
                    $aItems,
                    'customer_id',
                    'customer',
                    'Customer',
                    'nailsapp/module-invoice'
                );
            }

            if (!empty($aData['includeAll']) || !empty($aData['includeEmails'])) {
                $this->getManyAssociatedItems(
                    $aItems,
                    'emails',
                    'invoice_id',
                    'InvoiceEmail',
                    'nailsapp/module-invoice'
                );
            }

            if (!empty($aData['includeAll']) || !empty($aData['includePayments'])) {
                $this->getManyAssociatedItems(
                    $aItems,
                    'payments',
                    'invoice_id',
                    'Payment',
                    'nailsapp/module-invoice'
                );
            }

            if (!empty($aData['includeAll']) || !empty($aData['includeRefunds'])) {
                $this->getManyAssociatedItems(
                    $aItems,
                    'refunds',
                    'invoice_id',
                    'Refund',
                    'nailsapp/module-invoice'
                );
            }

            if (!empty($aData['includeAll']) || !empty($aData['includeItems'])) {
                $this->getManyAssociatedItems(
                    $aItems,
                    'items',
                    'invoice_id',
                    'InvoiceItem',
                    'nailsapp/module-invoice'
                );
            }
        }

        return $aItems;
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new invoice
     * @param  array   $aData         The data to create the invoice with
     * @param  boolean $bReturnObject Whether to return the complete invoice object
     * @return mixed
     */
    public function create($aData = array(), $bReturnObject = false)
    {
        $oDb = Factory::service('Database');

        try {

            if (empty($aData['customer_id'])) {
                throw new InvoiceException('"customer_id" is a required field.', 1);
            }

            $oDb->trans_begin();

            $this->prepareInvoice($aData);

            if (empty($aData['ref'])) {
                $aData['ref'] = $this->generateValidRef();
            }

            $aData['token'] = $this->generateValidToken($aData['ref']);

            $aItems = $aData['items'];
            unset($aData['items']);

            $oInvoice = parent::create($aData, true);

            if (!$oInvoice) {
                throw new InvoiceException('Failed to create invoice.', 1);
            }

            if (!empty($aItems)) {
                $this->updateLineItems($oInvoice->id, $aItems);
            }

            $oDb->trans_commit();

            //  Trigger the invoice.created event
            $this->triggerEvent('EVENT_INVOICE_CREATED', $oInvoice->id);

            return $bReturnObject ? $oInvoice : $oInvoice->id;

        } catch (\Exception $e) {

            $oDb->trans_rollback();
            $this->setError($e->getMessage());
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Update an invoice
     * @param  integer $iInvoiceId The ID of the invoice to update
     * @param  array   $aData      The data to update the invoice with
     * @return boolean
     */
    public function update($iInvoiceId, $aData = array())
    {
        try {

            if (array_key_exists('customer_id', $aData) && empty($aData['customer_id'])) {
                throw new InvoiceException('"customer_id" cannot be empty.', 1);
            }

            $oDb = Factory::service('Database');
            $oDb->trans_begin();

            $this->prepareInvoice($aData, $iInvoiceId);

            if (array_key_exists('items', $aData)) {
                $aItems = $aData['items'];
                unset($aData['items']);
            }

            unset($aData['ref']);
            unset($aData['token']);

            $bResult = parent::update($iInvoiceId, $aData);

            if (!$bResult) {
                throw new InvoiceException('Failed to update invoice.', 1);
            }

            if (!empty($aItems)) {
                $this->updateLineItems($iInvoiceId, $aItems);
            }

            $oDb->trans_commit();

            //  Trigger the invoice.updated event
            $this->triggerEvent('EVENT_INVOICE_UPDATED', $iInvoiceId);

            return $bResult;

        } catch (\Exception $e) {

            $oDb->trans_rollback();
            $this->setError($e->getMessage());
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Format and validate passed data
     * @param  array   &$aData     The data to format/validate
     * @param  integer $iInvoiceId The invoice ID
     * @return void
     */
    private function prepareInvoice(&$aData, $iInvoiceId = null)
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
            $oDate = Factory::factory('DateTime');
            $aData['dated'] = $oDate->format('Y-m-d');
        }

        //  Calculate the due date
        if (!array_key_exists('due', $aData) && !empty($aData['dated'])) {

            if (array_key_exists('terms', $aData)) {

                $iTerms = (int) $aData['terms'];

            } else {

                $iTerms = (int) appSetting('default_payment_terms', 'nailsapp/module-invoice');
            }

            $oDate = new \DateTime($aData['dated']);
            $oDate->add(new \DateInterval('P' . $iTerms . 'D'));
            $aData['due'] = $oDate->format('Y-m-d');
        }


        //  Always has a currency
        if (array_key_exists('currency', $aData)) {
            $aData['currency'] = strtoupper(trim($aData['currency']));
        }

        //  Callback data is encoded as JSON
        if (array_key_exists('callback_data', $aData)) {
            $aData['callback_data'] = array_key_exists('callback_data', $aData) ? $aData['callback_data'] : null;
            $aData['callback_data'] = json_encode($aData['callback_data']);
        }

        //  Sanitize each item
        if (array_key_exists('items', $aData)) {

            $iCounter = 0;
            $aTaxIds  = array();
            foreach ($aData['items'] as &$aItem) {

                //  Has an ID or is null
                $aItem['id'] = !empty($aItem['id']) ? (int) $aItem['id'] : null;

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
                throw new InvoiceException('Reference "' . $aData['ref'] . '" is already in use.', 1);
            }
        }

        //  Invalid state
        if (array_key_exists('state', $aData)) {
            $aStates = $this->getStates();
            if (!array_key_exists($aData['state'], $aStates)) {
                throw new InvoiceException('State "' . $aData['ref'] . '" does not exist.', 1);
            }
        }

        //  Inavlid Customer ID
        if (array_key_exists('customer_id', $aData) && !empty($aData['customer_id'])) {
            $oCustomerModel = Factory::model('Customer', 'nailsapp/module-invoice');
            if (!$oCustomerModel->getById($aData['customer_id'])) {
                throw new InvoiceException('"' . $aData['customer_id'] . '" is not a valid customer ID.', 1);
            }
        }

        //  Invalid currency
        if (array_key_exists('currency', $aData)) {
            //  @todo
        }

        //  Invalid Tax IDs
        if (!empty($aTaxIds)) {
            $oTaxModel = Factory::model('Tax', 'nailsapp/module-invoice');
            $aTaxRates = $oTaxModel->getByIds($aTaxIds);
            if (count($aTaxRates) != count($aTaxIds)) {
                throw new InvoiceException('An invalid Tax Rate was supplied.', 1);
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
            $oItemModel = Factory::model('InvoiceItem', 'nailsapp/module-invoice');
            foreach ($aData['items'] as &$aItem) {

                //  Has a positive quantity
                if ($aItem['quantity'] <= 0) {
                    throw new InvoiceException('Each item must have a positive quantity.', 1);
                }

                //  Has a valid unit
                $aUnits = $oItemModel->getUnits();
                if (!empty($aItem['unit']) && !array_key_exists($aItem['unit'], $aUnits)) {
                    throw new InvoiceException('Unit "' . $aItem['unit'] . '" does not exist.', 1);
                }

                //  Has a label
                if (empty($aItem['label'])) {
                    throw new InvoiceException('Each item must be given a label.', 1);
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
                if (!empty($aItem['tax_id'])) {

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
     * @param  integer $iInvoiceId The invoice ID
     * @param  array   $aItems     The items to update
     * @return void
     */
    private function updateLineItems($iInvoiceId, $aItems)
    {
        $oItemModel  = Factory::model('InvoiceItem', 'nailsapp/module-invoice');
        $aTouchedIds = array();

        //  Update/insert all known items
        foreach ($aItems as $aItem) {

            $aData = array(
                'label'       => !empty($aItem['label']) ? $aItem['label'] : null,
                'body'        => !empty($aItem['body']) ? $aItem['body'] : null,
                'order'       => !empty($aItem['order']) ? $aItem['order'] : 0,
                'unit'        => !empty($aItem['unit']) ? $aItem['unit'] : null,
                'tax_id'      => !empty($aItem['tax_id']) ? $aItem['tax_id'] : null,
                'quantity'    => !empty($aItem['quantity']) ? $aItem['quantity'] : 1,
                'unit_cost'   => !empty($aItem['unit_cost']) ? $aItem['unit_cost'] : 0,
                'sub_total'   => !empty($aItem['sub_total']) ? $aItem['sub_total'] : 0,
                'tax_total'   => !empty($aItem['tax_total']) ? $aItem['tax_total'] : 0,
                'grand_total' => !empty($aItem['grand_total']) ? $aItem['grand_total'] : 0
            );

            if (!empty($aItem['id'])) {

                //  Update
                if (!$oItemModel->update($aItem['id'], $aData)) {

                    throw new InvoiceException('Failed to update invoice item.', 1);

                } else {

                    $aTouchedIds[] = $aItem['id'];
                }

            } else {

                //  Insert
                $aData['invoice_id'] = $iInvoiceId;

                $iItemId = $oItemModel->create($aData);

                if (!$iItemId) {

                    throw new InvoiceException('Failed to create invoice item.', 1);

                } else {

                    $aTouchedIds[] = $iItemId;
                }
            }
        }

        //  Delete those we no longer require
        if (!empty($aTouchedIds)) {

            $oDb = Factory::service('Database');
            $oDb->where_not_in('id', $aTouchedIds);
            $oDb->where('invoice_id', $iInvoiceId);
            if (!$oDb->delete($oItemModel->getTableName())) {
                throw new InvoiceException('Failed to delete old invoice items.', 1);
            }
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Fetch an invoice by it's ref
     * @param  string $sRef  The ref of the invoice to fetch
     * @param  mixed  $aData Any data to pass to getCountCommon()
     * @return mixed
     */
    public function getByRef($sRef, $aData = array())
    {
        if (empty($sRef)) {
            return false;
        }

        if (!isset($aData['where'])) {
            $aData['where'] = array();
        }

        $aData['where'][] = array($this->tablePrefix . '.ref', $sRef);

        $aResult = $this->getAll(null, null, $aData);

        if (empty($aResult)) {
            return false;
        }

        return $aResult[0];
    }

    // --------------------------------------------------------------------------

    /**
     * Fetch an invoice by it's token
     * @param  string $sToken  The token of the invoice to fetch
     * @param  mixed  $aData Any data to pass to getCountCommon()
     * @return mixed
     */
    public function getByToken($sToken, $aData = array())
    {
        if (empty($sRef)) {
            return false;
        }

        if (!isset($aData['where'])) {
            $aData['where'] = array();
        }

        $aData['where'][] = array($this->tablePrefix . '.token', $sToken);

        $aResult = $this->getAll(null, null, $aData);

        if (empty($aResult)) {
            return false;
        }

        return $aResult[0];
    }

    // --------------------------------------------------------------------------

    /**
     * Generates a valid invoice ref
     * @return string
     */
    public function generateValidRef()
    {
        Factory::helper('string');

        $oDb  = Factory::service('Database');
        $oNow = Factory::factory('DateTime');

        do {

            $sRef = $oNow->format('Ym') .'-' . strtoupper(random_string('alnum'));
            $oDb->where('ref', $sRef);
            $bRefExists = (bool) $oDb->count_all_results($this->table);

        } while ($bRefExists);

        return $sRef;
    }

    // --------------------------------------------------------------------------

    /**
     * Generates a valid invoice token
     * @return string
     */
    public function generateValidToken($sRef)
    {
        $oDb = Factory::service('Database');

        do {

            //  @todo: use more secure token generation, like random_bytes();
            $sToken = md5(microtime(true) . $sRef . APP_PRIVATE_KEY);
            $oDb->where('token', $sToken);
            $bTokenExists = (bool) $oDb->count_all_results($this->table);

        } while ($bTokenExists);

        return $sToken;
    }

    // --------------------------------------------------------------------------

    /**
     * Send an invoice by email
     * @param  integer $iInvoiceId     The ID of the invoice to send
     * @param  string  $sEmailOverride Send to this email instead of the email defined by the invoice object
     * @return boolean
     */
    public function send($iInvoiceId, $sEmailOverride = null)
    {
        try {

            $oInvoice = $this->getById($iInvoiceId);

            if (empty($oInvoice)) {
                throw new InvoiceException('Invalid Invoice ID', 1);
            }

            if ($oInvoice->state->id !== self::STATE_OPEN) {
                throw new InvoiceException('Invoice must be in an open state to send.', 1);
            }

            if (!empty($sEmailOverride)) {

                //  @todo, validate email address (or addresses if an array)
                $aEmails = explode(',', $sEmailOverride);

            } elseif (!empty($oInvoice->customer->billing_email)) {

                $aEmails = explode(',', $oInvoice->customer->billing_email);

            } elseif (!empty($oInvoice->customer->email)) {

                $aEmails = array($oInvoice->customer->email);

            } else {

                throw new InvoiceException('No email address to send the invoice to', 1);
            }

            $oEmailer           = Factory::service('Emailer', 'nailsapp/module-email');
            $oInvoiceEmailModel = Factory::model('InvoiceEmail', 'nailsapp/module-invoice');

            $oEmail       = new \stdClass();
            $oEmail->type = 'send_invoice';
            $oEmail->data = array(
                'invoice' => $oInvoice
            );

            foreach ($aEmails as $sEmail) {

                $oEmail->to_email = $sEmail;

                $oResult = $oEmailer->send($oEmail);

                if (!empty($oResult)) {

                    $oInvoiceEmailModel->create(
                        array(
                            'invoice_id' => $oInvoice->id,
                            'email_id'   => $oResult->id,
                            'email_type' => $oEmail->type,
                            'recipient'  => $oEmail->to_email
                        )
                    );

                } else {

                    throw new InvoiceException($oEmailer->lastError(), 1);
                }
            }

        } catch (\Exception $e) {

            $this->setError($e->getMessage());
            return false;
        }

        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Whether an invoice has been fully paid or not
     * @param  integer $iInvoiceId         The Invoice to query
     * @param  boolean $bIncludeProcessing Whether to include payments which are still processing
     * @return boolean
     */
    public function isPaid($iInvoiceId, $bIncludeProcessing = false)
    {
        $oInvoice = $this->getById($iInvoiceId);

        if (!empty($oInvoice)) {

            $iPaid = $oInvoice->totals->base->paid;
            if ($bIncludeProcessing) {
                $iPaid += $oInvoice->totals->base->processing;
            }

            return $iPaid >= $oInvoice->totals->base->grand;
        }

        return false;
    }

    // --------------------------------------------------------------------------

    /**
     * Set an invoice as paid
     * @param  integer  $iInvoiceId The Invoice to query
     * @return boolean
     */
    public function setPaid($iInvoiceId)
    {
        $oNow    = Factory::factory('DateTime');
        $bResult = $this->update(
            $iInvoiceId,
            array(
                'state' => self::STATE_PAID,
                'paid'  => $oNow->format('Y-m-d H:i:s')
            )
        );

        //  Trigger the invoice.paid event
        $this->triggerEvent('EVENT_INVOICE_PAID', $iInvoiceId);

        return $bResult;
    }

    // --------------------------------------------------------------------------

    /**
     * Set an invoice as paid but with processing payments
     * @param  integer  $iInvoiceId The Invoice to query
     * @return boolean
     */
    public function setPaidProcessing($iInvoiceId)
    {
        $oNow    = Factory::factory('DateTime');
        $bResult = $this->update(
            $iInvoiceId,
            array(
                'state' => self::STATE_PAID_PROCESSING,
                'paid'  => $oNow->format('Y-m-d H:i:s')
            )
        );

        //  Trigger the invoice.paid.processing event
        $this->triggerEvent('EVENT_INVOICE_PAID_PROCESSING', $iInvoiceId);

        return $bResult;
    }

    // --------------------------------------------------------------------------

    /**
     * Trigger a callback event for an invoice
     * @param  string  $sEvent     The event to trigger (the name of the constant)
     * @param  integer $iInvoiceId The invoice ID
     * @return void
     */
    protected function triggerEvent($sEvent, $iInvoiceId)
    {
        $oPEH   = Factory::model('PaymentEventHandler', 'nailsapp/module-invoice');
        $oClass = new \ReflectionClass($oPEH);

        $oPEH->trigger(
            $oClass->getConstant($sEvent),
            $this->getById($iInvoiceId, array('includeAll' => true))
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Formats a single object
     *
     * The getAll() method iterates over each returned item with this method so as to
     * correctly format the output. Use this to cast integers and booleans and/or organise data into objects.
     *
     * @param  object $oObj      A reference to the object being formatted.
     * @param  array  $aData     The same data array which is passed to _getcount_common, for reference if needed
     * @param  array  $aIntegers Fields which should be cast as integers if numerical and not null
     * @param  array  $aBools    Fields which should be cast as booleans if not null
     * @param  array  $aFloats   Fields which should be cast as floats if not null
     * @return void
     */
    protected function formatObject(
        &$oObj,
        $aData = array(),
        $aIntegers = array(),
        $aBools = array(),
        $aFloats = array()
    ) {

        $aIntegers[] = 'terms';

        parent::formatObject($oObj, $aData, $aIntegers, $aBools, $aFloats);

        //  Sate
        $aStateLabels = $this->getStates();
        $sState       = $oObj->state;

        $oObj->state            = new \stdClass();
        $oObj->state->id        = $sState;
        $oObj->state->label     = $aStateLabels[$sState];

        //  Dated
        $oDated = new \DateTime($oObj->dated . ' 00:00:00');
        $oObj->dated            = new \stdClass();
        $oObj->dated->raw       = $oDated->format('Y-m-d');
        $oObj->dated->formatted = toUserDate($oDated);

        //  Due
        $oDue = new \DateTime($oObj->due . ' 23:59:59');
        $oObj->due            = new \stdClass();
        $oObj->due->raw       = $oDue->format('Y-m-d');
        $oObj->due->formatted = toUserDate($oDue);

        //  Paid
        $oPaid = new \DateTime($oObj->paid);
        $oObj->paid            = new \stdClass();
        $oObj->paid->raw       = $oPaid->format('Y-m-d H:i:s');
        $oObj->paid->formatted = toUserDateTime($oPaid);

        //  Compute boolean flags
        $oNow  = Factory::factory('DateTime');

        $oObj->is_scheduled = false;
        if ($oObj->state->id == self::STATE_OPEN && $oNow < $oDated) {
            $oObj->is_scheduled = true;
        }

        $oObj->is_overdue = false;
        if ($oObj->state->id == self::STATE_OPEN && $oNow > $oDue) {
            $oObj->is_overdue = true;
        }

        $oObj->has_processing_payments = $oObj->processing_payments > 0;
        unset($oObj->processing_payments);

        //  Totals
        $oObj->totals                  = new \stdClass();
        $oObj->totals->base            = new \stdClass();
        $oObj->totals->base->sub       = (int) $oObj->sub_total;
        $oObj->totals->base->tax       = (int) $oObj->tax_total;
        $oObj->totals->base->grand     = (int) $oObj->grand_total;
        $oObj->totals->base->paid      = (int) $oObj->paid_total;
        $oObj->totals->base->processing = (int) $oObj->processing_total;

        //  Localise to the User's preference; perform any currency conversions as required
        $oObj->totals->localised             = new \stdClass();
        $oObj->totals->localised->sub        = (float) number_format($oObj->totals->base->sub/self::CURRENCY_LOCALISE_VALUE, self::CURRENCY_DECIMAL_PLACES, '', '');
        $oObj->totals->localised->tax        = (float) number_format($oObj->totals->base->tax/self::CURRENCY_LOCALISE_VALUE, self::CURRENCY_DECIMAL_PLACES, '', '');
        $oObj->totals->localised->grand      = (float) number_format($oObj->totals->base->grand/self::CURRENCY_LOCALISE_VALUE, self::CURRENCY_DECIMAL_PLACES, '', '');
        $oObj->totals->localised->paid       = (float) number_format($oObj->totals->base->paid/self::CURRENCY_LOCALISE_VALUE, self::CURRENCY_DECIMAL_PLACES, '', '');
        $oObj->totals->localised->processing = (float) number_format($oObj->totals->base->processing/self::CURRENCY_LOCALISE_VALUE, self::CURRENCY_DECIMAL_PLACES, '', '');

        $oObj->totals->localised_formatted             = new \stdClass();
        $oObj->totals->localised_formatted->sub        = self::CURRENCY_SYMBOL_HTML . number_format($oObj->totals->base->sub/self::CURRENCY_LOCALISE_VALUE, self::CURRENCY_DECIMAL_PLACES);
        $oObj->totals->localised_formatted->tax        = self::CURRENCY_SYMBOL_HTML . number_format($oObj->totals->base->tax/self::CURRENCY_LOCALISE_VALUE, self::CURRENCY_DECIMAL_PLACES);
        $oObj->totals->localised_formatted->grand      = self::CURRENCY_SYMBOL_HTML . number_format($oObj->totals->base->grand/self::CURRENCY_LOCALISE_VALUE, self::CURRENCY_DECIMAL_PLACES);
        $oObj->totals->localised_formatted->paid       = self::CURRENCY_SYMBOL_HTML . number_format($oObj->totals->base->paid /self::CURRENCY_LOCALISE_VALUE, self::CURRENCY_DECIMAL_PLACES);
        $oObj->totals->localised_formatted->processing = self::CURRENCY_SYMBOL_HTML . number_format($oObj->totals->base->processing /self::CURRENCY_LOCALISE_VALUE, self::CURRENCY_DECIMAL_PLACES);

        unset($oObj->sub_total);
        unset($oObj->tax_total);
        unset($oObj->grand_total);
        unset($oObj->paid_total);
        unset($oObj->processing_total);

        //  URLs
        $oObj->urls           = new \stdClass();
        $oObj->urls->payment  = site_url('invoice/invoice/' . $oObj->ref . '/' . $oObj->token . '/pay');
        $oObj->urls->download = site_url('invoice/invoice/' . $oObj->ref . '/' . $oObj->token . '/download');
        $oObj->urls->view     = site_url('invoice/invoice/' . $oObj->ref . '/' . $oObj->token . '/view');

        //  Callback data
        $oObj->callback_data = json_decode($oObj->callback_data);
    }
}
