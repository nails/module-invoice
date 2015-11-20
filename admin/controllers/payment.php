<?php

/**
 * Manage payments
 *
 * @package     Nails
 * @subpackage  module-invoice
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Invoice;

use Nails\Factory;
use Nails\Admin\Helper;
use Nails\Admin\Controller\Base;

class Payment extends Base
{
    protected $oInvoiceModel;
    protected $oPaymentModel;
    protected $oDriverModel;

    // --------------------------------------------------------------------------

    /**
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        if (userHasPermission('admin:invoice:payment:manage')) {

            $navGroup = Factory::factory('Nav', 'nailsapp/module-admin');
            $navGroup->setLabel('Invoices &amp; Payments');
            $navGroup->setIcon('fa-credit-card');
            $navGroup->addAction('Manage Payments');

            return $navGroup;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of extra permissions for this controller
     * @return array
     */
    public static function permissions()
    {
        $permissions = parent::permissions();

        $permissions['manage'] = 'Can manage payments';

        return $permissions;
    }

    // --------------------------------------------------------------------------

    /**
     * Construct the controller
     */
    public function __construct()
    {
        parent::__construct();

        $this->oInvoiceModel = Factory::model('Invoice', 'nailsapp/module-invoice');
        $this->oPaymentModel = Factory::model('Payment', 'nailsapp/module-invoice');
        $this->oDriverModel  = Factory::model('Driver', 'nailsapp/module-invoice');
    }

    // --------------------------------------------------------------------------

    /**
     * Browse payments
     * @return void
     */
    public function index()
    {
        if (!userHasPermission('admin:invoice:payment:manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = 'Manage Payments';

        // --------------------------------------------------------------------------

        $sTablePrefix = $this->oPaymentModel->getTablePrefix();

        //  Get pagination and search/sort variables
        $page      = $this->input->get('page')      ? $this->input->get('page')      : 0;
        $perPage   = $this->input->get('perPage')   ? $this->input->get('perPage')   : 50;
        $sortOn    = $this->input->get('sortOn')    ? $this->input->get('sortOn')    : $sTablePrefix . '.created';
        $sortOrder = $this->input->get('sortOrder') ? $this->input->get('sortOrder') : 'desc';
        $keywords  = $this->input->get('keywords')  ? $this->input->get('keywords')  : '';

        // --------------------------------------------------------------------------

        //  Define the sortable columns
        $sortColumns = array(
            $sTablePrefix . '.created'        => 'Received Date',
            $sTablePrefix . '.processor'      => 'Payment Processor',
            $sTablePrefix . '.invoice_id'     => 'Invoice ID',
            $sTablePrefix . '.transaction_id' => 'Transaction ID',
            $sTablePrefix . '.amount'         => 'Amount',
            $sTablePrefix . '.currency'       => 'Currency'
        );

        // --------------------------------------------------------------------------

        //  Define the filters
        $aCbFilters = array();
        $aOptions   = array();
        $aDrivers   = $this->oDriverModel->getAll();

        foreach ($aDrivers as $sSlug => $oDriver) {
            $aOptions[] = array(
                $oDriver->getLabel(),
                $sSlug,
                true
            );
        }

        $aCbFilters[] = Helper::searchFilterObject(
            $sTablePrefix . '.processor',
            'Processor',
            $aOptions
        );

        // --------------------------------------------------------------------------

        //  Define the $data variable for the queries
        $data = array(
            'sort' => array(
                array($sortOn, $sortOrder)
            ),
            'keywords'  => $keywords,
            'cbFilters' => $aCbFilters
        );

        //  Get the items for the page
        $totalRows                   = $this->oPaymentModel->count_all($data);
        $this->data['payments']      = $this->oPaymentModel->get_all($page, $perPage, $data);
        $this->data['drivers']       = $aDrivers;
        $this->data['invoiceStates'] = $this->oInvoiceModel->getStates();;

        //  Set Search and Pagination objects for the view
        $this->data['search']     = Helper::searchObject(true, $sortColumns, $sortOn, $sortOrder, $perPage, $keywords, $aCbFilters);
        $this->data['pagination'] = Helper::paginationObject($page, $perPage, $totalRows);

        // --------------------------------------------------------------------------

        //  Add a header button
        if (userHasPermission('admin:invoice:invoice:create')) {

             Helper::addHeaderButton(
                'admin/invoice/invoice/create',
                'Request Payment'
            );
        }

        // --------------------------------------------------------------------------

        Helper::loadView('index');
    }

    // --------------------------------------------------------------------------

    /**
     * View a single payment
     * @return void
     */
    public function view()
    {
        $this->data['payment'] = $this->oInvoiceModel->get_by_id($this->uri->segment(5));
        if (!$this->data['payment']) {
            show_404();
        }

        $this->data['page']->title = 'View Payment &rsaquo; ' . $this->data['payment']->id;

        Helper::loadView('view');
    }
}
