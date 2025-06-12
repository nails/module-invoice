<?php

/**
 * Manage invoices
 *
 * @package     Nails
 * @subpackage  module-invoice
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Invoice;

use Nails\Admin\Controller\Base;
use Nails\Admin\Factory\Nav;
use Nails\Admin\Helper;
use Nails\Common\Exception\AssetException;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Common\Helper\Model\Expand;
use Nails\Common\Service\Asset;
use Nails\Common\Service\FormValidation;
use Nails\Common\Service\Input;
use Nails\Common\Service\Uri;
use Nails\Currency;
use Nails\Factory;
use Nails\Invoice\Constants;
use Nails\Invoice\Model;
use Nails\Invoice\Model\Invoice\Item;
use Nails\Invoice\Model\Tax;
use stdClass;

/**
 * Class Invoice
 *
 * @package Nails\Admin\Invoice
 */
class Invoice extends Base
{
    /**
     * The Invoice model
     *
     * @var Model\Invoice
     */
    protected $oInvoiceModel;

    /**
     * The Invoice Item model
     *
     * @var Item
     */
    protected $oInvoiceItemModel;

    /**
     * The Tax model
     *
     * @var Tax
     */
    protected $oTaxModel;

    // --------------------------------------------------------------------------

    /**
     * Announces this controller's navGroups
     *
     * @throws FactoryException
     */
    public static function announce(): Nav|array|null
    {
        if (userHasPermission('admin:invoice:invoice:manage')) {
            /** @var Nav $oNavGroup */
            $oNavGroup = Factory::factory('Nav', \Nails\Admin\Constants::MODULE_SLUG)
                ->setLabel('Invoices &amp; Payments')
                ->setIcon('fa-credit-card')
                ->addAction('Manage Invoices', 'index', [], 0);
        }

        return $oNavGroup ?? null;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of extra permissions for this controller
     *
     * @return array
     */
    public static function permissions(): array
    {
        $aPermissions = parent::permissions();

        $aPermissions['manage'] = 'Can manage invoices';
        $aPermissions['create'] = 'Can create invoices';
        $aPermissions['edit']   = 'Can edit invoices';
        $aPermissions['delete'] = 'Can delete invoices';

        return $aPermissions;
    }

    // --------------------------------------------------------------------------

    /**
     * Invoice constructor.
     *
     * @throws FactoryException
     */
    public function __construct()
    {
        parent::__construct();

        /** @var Model\Invoice oInvoiceModel */
        $this->oInvoiceModel = Factory::model('Invoice', Constants::MODULE_SLUG);
        /** @var Item oInvoiceItemModel */
        $this->oInvoiceItemModel = Factory::model('InvoiceItem', Constants::MODULE_SLUG);
        /** @var Tax oTaxModel */
        $this->oTaxModel = Factory::model('Tax', Constants::MODULE_SLUG);
    }

    // --------------------------------------------------------------------------

    /**
     * Browse invoices
     *
     * @throws FactoryException
     * @throws ModelException
     */
    public function index()
    {
        if (!userHasPermission('admin:invoice:invoice:manage')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Are we dealing with a filtered view?
        /** @var Input $oInput */
        $oInput = Factory::service('Input');
        /** @var Customer $oCustomerModel */
        $oCustomerModel = Factory::model('Customer', Constants::MODULE_SLUG);
        $iCustomerId    = $oInput->get('customer_id');

        if ($iCustomerId) {
            $oCustomer = $oCustomerModel->getbyId($iCustomerId);
        }

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = empty($oCustomer) ? 'Manage Invoices' : possessive($oCustomer->label) . ' invoices';

        // --------------------------------------------------------------------------

        $sTableAlias = $this->oInvoiceModel->getTableAlias();

        //  Get pagination and search/sort variables
        $iPage      = $oInput->get('page') ? $oInput->get('page') : 0;
        $iPerPage   = $oInput->get('perPage') ? $oInput->get('perPage') : 50;
        $sSortOn    = $oInput->get('sortOn') ? $oInput->get('sortOn') : $sTableAlias . '.created';
        $sSortOrder = $oInput->get('sortOrder') ? $oInput->get('sortOrder') : 'desc';
        $sKeywords  = $oInput->get('keywords') ? $oInput->get('keywords') : '';

        // --------------------------------------------------------------------------

        //  Define the sortable columns
        $aSortColumns = [
            $sTableAlias . '.created'  => 'Created Date',
            $sTableAlias . '.modified' => 'Modified Date',
            $sTableAlias . '.state'    => 'Invoice State',
        ];

        // --------------------------------------------------------------------------

        //  Define the filters
        $aCbFilters = [];

        //  States
        /** @var \Nails\Admin\Factory\IndexFilter $oFilterState */
        $oFilterState = Factory::factory('IndexFilter', \Nails\Admin\Constants::MODULE_SLUG);
        $oFilterState
            ->setLabel('State')
            ->setColumn($sTableAlias . '.state');

        foreach ($this->oInvoiceModel->getStates() as $sState => $sLabel) {
            $oFilterState->addOption($sLabel, $sState, true);
        }

        $aCbFilters[] = $oFilterState;



        //  Currencies
        /** @var Currency\Service\Currency $oCurrency */
        $oCurrency        = Factory::service('Currency', Currency\Constants::MODULE_SLUG);
        /** @var \Nails\Admin\Factory\IndexFilter $oFilterCurrency */
        $oFilterCurrency = Factory::factory('IndexFilter', \Nails\Admin\Constants::MODULE_SLUG);
        $oFilterCurrency
            ->setLabel('Currency')
            ->setColumn($sTableAlias . '.currency');

        foreach ($oCurrency->getAllEnabled() as $oCurrency) {
            $oFilterCurrency->addOption($oCurrency->code, $oCurrency->code, true);
        }

        $aCbFilters[] = $oFilterCurrency;

        // --------------------------------------------------------------------------

        //  Define the $aData variable for the queries
        $aData = [
            new Expand('customer'),
            new Expand('items'),
            new Expand('billing_address'),
            new Expand('payments'),
            new Expand('refunds'),
            'where'     => array_filter([
                !empty($oCustomer) ? ['customer_id', $oCustomer->id] : null,
            ]),
            'sort'      => [
                [$sSortOn, $sSortOrder],
            ],
            'keywords'  => $sKeywords,
            'cbFilters' => $aCbFilters,

            /*
             * Take the minimum number of columns, callback data might be massive so avoid
             * including to avoid running out of sort memory.
             */
            'select'    => [
                $this->oInvoiceModel->getTableAlias() . '.id',
                $this->oInvoiceModel->getTableAlias() . '.ref',
                $this->oInvoiceModel->getTableAlias() . '.token',
                $this->oInvoiceModel->getTableAlias() . '.state',
                $this->oInvoiceModel->getTableAlias() . '.customer_id',
                $this->oInvoiceModel->getTableAlias() . '.dated',
                $this->oInvoiceModel->getTableAlias() . '.due',
                $this->oInvoiceModel->getTableAlias() . '.paid',
                $this->oInvoiceModel->getTableAlias() . '.currency',
                $this->oInvoiceModel->getTableAlias() . '.sub_total',
                $this->oInvoiceModel->getTableAlias() . '.tax_total',
                $this->oInvoiceModel->getTableAlias() . '.grand_total',
                $this->oInvoiceModel->getTableAlias() . '.billing_address_id',
                $this->oInvoiceModel->getTableAlias() . '.created',
                $this->oInvoiceModel->getTableAlias() . '.modified',

                //  Mock calculated columns
                '"0" paid_total',
                '"0" processing_total',
                '"0" processing_payments',
            ],
        ];

        //  Get the items for the page
        $iTotalRows             = $this->oInvoiceModel->countAll($aData);
        $this->data['invoices'] = $this->oInvoiceModel->getAll($iPage, $iPerPage, $aData);

        //  Set Search and Pagination objects for the view
        $this->data['search']     = Helper::searchObject(true, $aSortColumns, $sSortOn, $sSortOrder, $iPerPage, $sKeywords, $aCbFilters);
        $this->data['pagination'] = Helper::paginationObject($iPage, $iPerPage, $iTotalRows);

        //  Add a header button
        if (userHasPermission('admin:invoice:invoice:create')) {
            Helper::addHeaderButton(
                'admin/invoice/invoice/create',
                'Create Invoice'
            );
        }

        // --------------------------------------------------------------------------

        Helper::loadView('index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new invoice
     *
     * @throws FactoryException
     * @throws ModelException
     * @throws AssetException
     */
    public function create()
    {
        if (!userHasPermission('admin:invoice:invoice:create')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Page Title
        $this->data['page']->title = 'Create Invoice';

        // --------------------------------------------------------------------------

        /** @var Input $oInput */
        $oInput                    = Factory::service('Input');
        $this->data['customer_id'] = $oInput->get('customer_id');

        if ($oInput->post()) {
            if ($this->validatePost()) {
                $oInvoice = $this->oInvoiceModel->create($this->getObjectFromPost(), true);
                if (!empty($oInvoice)) {

                    $this->sendInvoice($oInvoice);

                    $this->oUserFeedback->success('Invoice created successfully.');

                    redirect('admin/invoice/invoice');

                } else {
                    $this->oUserFeedback->error('Failed to create invoice. ' . $this->oInvoiceModel->lastError());
                }

            } else {
                $this->oUserFeedback->error(lang('fv_there_were_errors'));
            }
        }

        // --------------------------------------------------------------------------

        //  Page data
        $aItemUnits = $this->oInvoiceItemModel->getUnits();
        $aTaxes     = $this->oTaxModel->getAll();

        $this->data['invoiceStates'] = $this->oInvoiceModel->getSelectableStates();

        // --------------------------------------------------------------------------

        //  Invoice Items
        /** @var Input $oInput */
        $oInput = Factory::service('Input');
        if ($oInput->post()) {

            $aItems = $oInput->post('items') ?: [];
            //  Tidy up post data as expected by JS
            foreach ($aItems as &$aItem) {

                $aItem['label'] = html_entity_decode($aItem['label'], ENT_QUOTES, 'UTF-8');
                $aItem['body']  = html_entity_decode($aItem['body'], ENT_QUOTES, 'UTF-8');

                $sUnitCost                     = $aItem['unit_cost'];
                $aItem['unit_cost']            = new stdClass();
                $aItem['unit_cost']->formatted = new stdClass();
                $aItem['unit_cost']->formatted = !empty($sUnitCost) ? $sUnitCost : null;

                $aItem['tax']     = new stdClass();
                $aItem['tax']->id = !empty($aItem['tax_id']) ? (int) $aItem['tax_id'] : null;

                $sUnit             = !empty($aItem['unit']) ? $aItem['unit'] : null;
                $aItem['unit']     = new stdClass();
                $aItem['unit']->id = $sUnit;
            }

        } else {
            $aItems = [];
        }

        // --------------------------------------------------------------------------

        /** @var Currency\Service\Currency $oCurrency */
        $oCurrency                = Factory::service('Currency', Currency\Constants::MODULE_SLUG);
        $aCurrencies              = $oCurrency->getAllEnabled();
        $this->data['currencies'] = $aCurrencies;

        // --------------------------------------------------------------------------

        /** @var Asset $oAsset */
        $oAsset = Factory::service('Asset');
        //  @todo (Pablo - 2018-11-30) - Load the minified version once the JS bundling has been sorted
        $oAsset->load('invoice.edit.js', Constants::MODULE_SLUG);
        $oAsset->inline(
            'ko.applyBindings(
                new invoiceEdit(
                    ' . json_encode($aItemUnits) . ',
                    ' . json_encode($aTaxes) . ',
                    ' . json_encode($aItems) . ',
                    ' . json_encode($aCurrencies) . '
                )
            );',
            'JS'
        );

        //  Load views
        Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit an invoice
     *
     * @throws AssetException
     * @throws FactoryException
     * @throws ModelException
     */
    public function edit()
    {
        if (!userHasPermission('admin:invoice:invoice:edit')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        /** @var Uri $oUri */
        $oUri = Factory::service('Uri');
        /** @var Input $oInput */
        $oInput = Factory::service('Input');

        $iInvoiceId            = (int) $oUri->segment(5);
        $oModel                = $this->oInvoiceModel;
        $this->data['invoice'] = $oModel->getById(
            $iInvoiceId,
            [
                'expand' => [
                    'customer',
                    ['payments', ['expand' => ['source']]],
                    'refunds',
                    'items',
                ],
            ]
        );

        if (!$this->data['invoice'] || $this->data['invoice']->state->id != 'DRAFT') {
            show404();
        }

        // --------------------------------------------------------------------------

        //  Page Title
        $this->data['page']->title = 'Edit Invoice &rsaquo; ' . $this->data['invoice']->ref;

        // --------------------------------------------------------------------------

        if ($oInput->post()) {
            if ($this->validatePost()) {
                if ($oModel->update($this->data['invoice']->id, $this->getObjectFromPost())) {

                    /** @var \Nails\Invoice\Resource\Invoice $oInvoice */
                    $oInvoice = $oModel->getById($this->data['invoice']->id);
                    $this->sendInvoice($oInvoice);

                    $this->oUserFeedback->success('Invoice was saved successfully.');

                    if ($oInvoice->state->id === $oModel::STATE_DRAFT) {
                        redirect('admin/invoice/invoice/edit/' . $oInvoice->id);
                    } else {
                        redirect('admin/invoice/invoice');
                    }

                } else {
                    $this->oUserFeedback->error('Failed to update invoice. ' . $oModel->lastError());
                }
            } else {
                $this->oUserFeedback->error(lang('fv_there_were_errors'));
            }
        }

        // --------------------------------------------------------------------------

        //  Page data
        $aItemUnits = $this->oInvoiceItemModel->getUnits();
        /** @var \Nails\Invoice\Resource\Tax[] $aTaxes */
        $aTaxes = $this->oTaxModel->getAll();

        $this->data['invoiceStates'] = $oModel->getSelectableStates();

        // --------------------------------------------------------------------------

        //  Invoice Items
        if ($oInput->post()) {

            $aItems = $oInput->post('items') ?: [];
            //  Tidy up post data as expected by JS
            foreach ($aItems as &$aItem) {

                $aItem['label'] = html_entity_decode($aItem['label'], ENT_QUOTES, 'UTF-8');
                $aItem['body']  = html_entity_decode($aItem['body'], ENT_QUOTES, 'UTF-8');

                $sUnitCost                     = $aItem['unit_cost'];
                $aItem['unit_cost']            = new stdClass();
                $aItem['unit_cost']->formatted = new stdClass();
                $aItem['unit_cost']->formatted = !empty($sUnitCost) ? $sUnitCost : null;

                $aItem['tax']     = new stdClass();
                $aItem['tax']->id = !empty($aItem['tax_id']) ? (int) $aItem['tax_id'] : null;

                $sUnit             = !empty($aItem['unit']) ? $aItem['unit'] : null;
                $aItem['unit']     = new stdClass();
                $aItem['unit']->id = $sUnit;
            }

        } else {
            $aItems = $this->data['invoice']->items->data;
        }

        // --------------------------------------------------------------------------

        /** @var Currency\Service\Currency $oCurrency */
        $oCurrency                = Factory::service('Currency', Currency\Constants::MODULE_SLUG);
        $aCurrencies              = $oCurrency->getAllEnabled();
        $this->data['currencies'] = $aCurrencies;
        //  A customer ID can be specified when creating an invoice; this prevents undefined var errors
        $this->data['customer_id'] = null;

        // --------------------------------------------------------------------------

        /** @var Asset $oAsset */
        $oAsset = Factory::service('Asset');
        //  @todo (Pablo - 2018-11-30) - Load the minified version once the JS bundling has been sorted
        $oAsset->load('invoice.edit.js', Constants::MODULE_SLUG);
        $oAsset->inline(
            'ko.applyBindings(
                new invoiceEdit(
                    ' . json_encode($aItemUnits) . ',
                    ' . json_encode($aTaxes) . ',
                    ' . json_encode($aItems) . ',
                    ' . json_encode($aCurrencies) . '
                )
            );',
            'JS'
        );

        //  Load views
        Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * View an invoice
     *
     * @throws FactoryException
     * @throws ModelException
     */
    public function view()
    {
        if (!userHasPermission('admin:invoice:invoice:edit')) {
            unauthorised();
        }

        /** @var Uri $oUri */
        $oUri                  = Factory::service('Uri');
        $oModel                = $this->oInvoiceModel;
        $iInvoiceId            = (int) $oUri->segment(5);
        $this->data['invoice'] = $this->oInvoiceModel->getById(
            $iInvoiceId,
            [
                new Expand('customer'),
                new Expand('emails', new Expand('email')),
                new Expand('payments', new Expand('source')),
                new Expand('refunds'),
                new Expand('items'),
            ]
        );

        if (!$this->data['invoice'] || $this->data['invoice']->state->id == 'DRAFT') {
            show404();
        }

        $this->data['page']->title = sprintf(
            'View Invoice &rsaquo; %s (%s)',
            $this->data['invoice']->ref,
            $this->data['invoice']->state->label,
        );

        Helper::loadView('view');
    }

    // --------------------------------------------------------------------------

    /**
     * Form validation cal;back to validate currency selection
     *
     * @param string $sCode the currency code
     *
     * @return bool
     * @throws FactoryException
     */
    public function _callbackValidCurrency($sCode)
    {
        /** @var FormValidation $oFormValidation */
        $oFormValidation = Factory::service('FormValidation');
        $oFormValidation->set_message('_callbackValidCurrency', 'Invalid currency.');

        $oCurrency = Factory::service('Currency', Currency\Constants::MODULE_SLUG);
        $aEnabled  = $oCurrency->getAllEnabled();

        foreach ($aEnabled as $oCurrency) {
            if ($oCurrency->code === $sCode) {
                return true;
            }
        }

        return false;
    }

    // --------------------------------------------------------------------------

    /**
     * Make an invoice a draft
     *
     * @throws FactoryException
     * @throws ModelException
     */
    public function make_draft()
    {
        if (!userHasPermission('admin:invoice:invoice:edit')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        /** @var Uri $oUri */
        $oUri = Factory::service('Uri');

        // --------------------------------------------------------------------------

        $oInvoice = $this->oInvoiceModel->getById($oUri->segment(5));
        if (!$oInvoice) {
            show404();
        }

        // --------------------------------------------------------------------------

        //  Allow getting a constant
        $oInvoiceModel = $this->oInvoiceModel;

        $aData = [
            'state' => $oInvoiceModel::STATE_DRAFT,
        ];
        if ($this->oInvoiceModel->update($oInvoice->id, $aData)) {
            $this->oUserFeedback->success('Invoice updated successfully!');
        } else {
            $this->oUserFeedback->error('Invoice failed to update invoice. ' . $this->oInvoiceModel->lastError());
        }

        redirect('admin/invoice/invoice/edit/' . $oInvoice->id);
    }

    // --------------------------------------------------------------------------

    /**
     * Write an invoice off
     *
     * @throws FactoryException
     * @throws ModelException
     */
    public function write_off()
    {
        if (!userHasPermission('admin:invoice:invoice:edit')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        /** @var Uri $oUri */
        $oUri = Factory::service('Uri');

        // --------------------------------------------------------------------------

        $oInvoice = $this->oInvoiceModel->getById($oUri->segment(5));
        if (!$oInvoice) {
            show404();
        }

        // --------------------------------------------------------------------------

        //  Allow getting a constant
        $oInvoiceModel = $this->oInvoiceModel;

        $aData = [
            'state' => $oInvoiceModel::STATE_WRITTEN_OFF,
        ];
        if ($this->oInvoiceModel->update($oInvoice->id, $aData)) {
            $this->oUserFeedback->success('Invoice written off successfully!');
        } else {
            $this->oUserFeedback->error('Failed to write off invoice. ' . $this->oInvoiceModel->lastError());
        }

        redirect('admin/invoice/invoice');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete an invoice
     *
     * @throws FactoryException
     * @throws ModelException
     */
    public function delete()
    {
        if (!userHasPermission('admin:invoice:invoice:delete')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        /** @var Uri $oUri */
        $oUri = Factory::service('Uri');

        // --------------------------------------------------------------------------

        $oInvoice = $this->oInvoiceModel->getById($oUri->segment(5));
        if (!$oInvoice) {
            show404();
        }

        // --------------------------------------------------------------------------

        if ($this->oInvoiceModel->delete($oInvoice->id)) {
            $this->oUserFeedback->success('Invoice deleted successfully!');
        } else {
            $this->oUserFeedback->error('Invoice failed to delete. ' . $this->oInvoiceModel->lastError());
        }

        redirect('admin/invoice/invoice');
    }

    // --------------------------------------------------------------------------

    /**
     * Resends an invoice
     *
     * @throws FactoryException
     * @throws ModelException
     */
    public function resend()
    {
        if (!userHasPermission('admin:invoice:invoice:manage')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        /** @var Uri $oUri */
        $oUri = Factory::service('Uri');
        /** @var Input $oInput */
        $oInput = Factory::service('Input');

        /** @var \Nails\Invoice\Resource\Invoice $oInvoice */
        $oInvoice = $this->oInvoiceModel->getById($oUri->segment(5));
        if (!$oInvoice) {
            show404();
        }

        if ($this->oInvoiceModel->send($oInvoice->id)) {
            $this->oUserFeedback->success('Invoice sent successfully.');
        } else {
            $this->oUserFeedback->error('Failed to resend invoice. ' . $this->oInvoiceModel->lastError());
        }

        //  @todo (Pablo - 2019-09-12) - Use returnToIndex() when this controller uses the Defaultcontroller
        $sReferrer = $oInput->server('HTTP_REFERER');

        if (!empty($sReferrer)) {
            redirect($sReferrer);
        } else {
            redirect('admin/invoice/invoice');
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Validate the POST data
     *
     * @return boolean
     * @throws FactoryException
     */
    protected function validatePost()
    {
        /** @var FormValidation $oFormValidation */
        $oFormValidation = Factory::service('FormValidation');

        $aRules = [
            'ref'             => 'trim',
            'state'           => 'trim|required',
            'dated'           => 'trim|required|valid_date',
            'currency'        => 'trim|required|callback__callbackValidCurrency',
            'terms'           => 'trim|is_natural',
            'customer_id'     => 'trim',
            'additional_text' => 'trim',
            'items'           => '',
        ];

        $aRulesFV = [];
        foreach ($aRules as $sKey => $sRules) {
            $aRulesFV[] = [
                'field' => $sKey,
                'label' => '',
                'rules' => $sRules,
            ];
        }

        $oFormValidation->set_rules($aRulesFV);

        $oFormValidation->set_message('required', lang('fv_required'));
        $oFormValidation->set_message('valid_date', lang('fv_valid_date'));
        $oFormValidation->set_message('is_natural', lang('fv_is_natural'));
        $oFormValidation->set_message('valid_email', lang('fv_valid_email'));

        return $oFormValidation->run(module: $this);
    }

    // --------------------------------------------------------------------------

    /**
     * Get an object generated from the POST data
     *
     * @return array
     * @throws FactoryException
     */
    protected function getObjectFromPost()
    {
        /** @var Input $oInput */
        $oInput = Factory::service('Input');
        /** @var Uri $oUri */
        $oUri  = Factory::service('Uri');
        $aData = [
            'ref'             => $oInput->post('ref') ?: null,
            'state'           => $oInput->post('state') ?: null,
            'dated'           => $oInput->post('dated') ?: null,
            'currency'        => $oInput->post('currency') ?: null,
            'terms'           => (int) $oInput->post('terms') ?: 0,
            'customer_id'     => (int) $oInput->post('customer_id') ?: null,
            'additional_text' => $oInput->post('additional_text') ?: null,
            'items'           => [],
            'currency'        => $oInput->post('currency'),
        ];

        if ($oInput->post('items')) {
            foreach ($oInput->post('items') as $aItem) {

                //  @todo convert to pence using a model
                $aData['items'][] = [
                    'id'        => array_key_exists('id', $aItem) ? $aItem['id'] : null,
                    'quantity'  => array_key_exists('quantity', $aItem) ? $aItem['quantity'] : null,
                    'unit'      => array_key_exists('unit', $aItem) ? $aItem['unit'] : null,
                    'label'     => array_key_exists('label', $aItem) ? $aItem['label'] : null,
                    'body'      => array_key_exists('body', $aItem) ? $aItem['body'] : null,
                    'unit_cost' => array_key_exists('unit_cost', $aItem) ? intval($aItem['unit_cost'] * 100) : null,
                    'tax_id'    => array_key_exists('tax_id', $aItem) ? $aItem['tax_id'] : null,
                ];
            }
        }

        if ($oUri->rsegment(5) == 'edit') {
            unset($aData['ref']);
        }

        return $aData;
    }

    // --------------------------------------------------------------------------

    /**
     * Send an invoice by email
     *
     * @param \Nails\Invoice\Resource\Invoice $oInvoice The Invoice to send
     *
     * @return bool
     * @throws FactoryException
     */
    protected function sendInvoice(\Nails\Invoice\Resource\Invoice $oInvoice): bool
    {
        if (empty($oInvoice)) {
            return false;
        }

        $sInvoiceClass = get_class($this->oInvoiceModel);

        if ($oInvoice->state->id === $sInvoiceClass::STATE_OPEN) {
            if (!$this->oInvoiceModel->send($oInvoice->id)) {
                $this->oUserFeedback->warning('Failed to email invoice to customer. ' . $this->oInvoiceModel->lastError());
                return false;
            }
            return true;
        }

        return false;
    }
}
