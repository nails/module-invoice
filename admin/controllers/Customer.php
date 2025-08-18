<?php

/**
 * Manage customers
 *
 * @package     Nails
 * @subpackage  module-invoice
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Invoice;

use Nails\Address;
use Nails\Admin\Controller\DefaultController;
use Nails\Admin\Factory\Model\Field\DynamicTable;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\NailsException;
use Nails\Common\Exception\ValidationException;
use Nails\Common\Service\Country;
use Nails\Common\Service\Input;
use Nails\Factory;
use Nails\Invoice\Constants;
use Nails\Invoice\Resource;

/**
 * Class Customer
 *
 * @package Nails\Admin\Invoice
 */
class Customer extends DefaultController
{
    const CONFIG_MODEL_NAME     = 'Customer';
    const CONFIG_MODEL_PROVIDER = Constants::MODULE_SLUG;
    const CONFIG_SIDEBAR_GROUP  = 'Invoices &amp; Payments';
    const CONFIG_SIDEBAR_ICON   = 'fa-credit-card';
    const CONFIG_PERMISSION     = 'invoice:customer';
    const CONFIG_INDEX_DATA     = [
        'expand' => ['invoices'],
    ];

    // --------------------------------------------------------------------------

    /**
     * Customer constructor.
     *
     * @throws FactoryException
     * @throws NailsException
     */
    public function __construct()
    {
        parent::__construct();

        //  Ignore label
        $this->aConfig['CREATE_IGNORE_FIELDS'][] = 'label';
        $this->aConfig['EDIT_IGNORE_FIELDS'][]   = 'label';

        //  Update existing buttons
        $iDeleteKey     = arraySearchMulti(lang('action_delete'), 'label', $this->aConfig['INDEX_ROW_BUTTONS']);
        $cParentEnabled = clone($this->aConfig['INDEX_ROW_BUTTONS'][$iDeleteKey]['enabled']);

        $this->aConfig['INDEX_ROW_BUTTONS'][$iDeleteKey]['enabled'] = function (Resource\Customer $oCustomer) use ($cParentEnabled) {
            return $cParentEnabled($oCustomer) && $oCustomer->invoices->count === 0;
        };

        //  Additional buttons
        if (userHasPermission('admin:invoice:invoice:create')) {
            $this->aConfig['INDEX_ROW_BUTTONS'][] = [
                'url'   => siteUrl('admin/invoice/invoice/create?customer_id={{id}}'),
                'label' => 'New Invoice',
                'class' => 'btn-success',
            ];
        }

        if (userHasPermission('admin:invoice:invoice:manage')) {
            $this->aConfig['INDEX_ROW_BUTTONS'][] = [
                'url'     => siteUrl('admin/invoice/invoice?customer_id={{id}}'),
                'label'   => 'View Invoices',
                'class'   => 'btn-warning',
                'enabled' => function (Resource\Customer $oCustomer) {
                    return $oCustomer->invoices->count > 0;
                },
            ];
        }

        //  Update cells
        $this->aConfig['INDEX_FIELDS']['Label'] = function (Resource\Customer $oCustomer) {
            $sOut = '';
            if (!empty($oCustomer->first_name)) {
                $sOut .= $oCustomer->first_name . ' ' . $oCustomer->last_name . '<br />';
            }

            if (!empty($oCustomer->billing_email)) {
                $sOut .= mailto($oCustomer->billing_email);
            } else {
                $sOut .= mailto($oCustomer->email);
            }

            return $sOut;
        };

        // --------------------------------------------------------------------------

        /** @var Country $oCountryService */
        $oCountryService = Factory::service('Country');

        /** @var DynamicTable $oField */
        $oField = Factory::factory('ModelFieldDynamicTable', \Nails\Admin\Constants::MODULE_SLUG);
        $oField
            ->setKey('addresses')
            ->setLabel('Addresses')
            ->setFieldset('Addresses')
            ->setColumns([
                'Details' => implode('', [
                    '<input type="hidden" name="addresses[{{index}}][id]" value="{{id}}">' .
                    '<input type="text" name="addresses[{{index}}][label]" placeholder="Label" value="{{label}}">',
                    '<input type="text" name="addresses[{{index}}][line_1]" placeholder="Line 1" value="{{line_1}}">',
                    '<input type="text" name="addresses[{{index}}][line_2]" placeholder="Line 2" value="{{line_2}}">',
                    '<input type="text" name="addresses[{{index}}][line_3]" placeholder="Line 3" value="{{line_3}}">',
                    '<input type="text" name="addresses[{{index}}][town]" placeholder="Town" value="{{town}}">',
                    '<input type="text" name="addresses[{{index}}][region]" placeholder="Region" value="{{region}}">',
                    '<input type="text" name="addresses[{{index}}][postcode]" placeholder="Postcode" value="{{postcode}}">',
                    form_dropdown(
                        'addresses[{{index}}][country]',
                        ['Select Country'] + $oCountryService->getCountriesFlat(),
                        null,
                        'data-dynamic-table-value="{{country.iso}}"'
                    ),
                ]),
            ]);

        $this->aConfig['FIELDS']['addresses'] = $oField;
    }

    // --------------------------------------------------------------------------

    protected function loadEditViewData(?\Nails\Common\Resource $oItem = null): void
    {
        /** @var Input $oInput */
        $oInput = Factory::service('Input');

        if ($oInput->post()) {
            $oItem->addresses = $oInput->post('addresses');
        } else {
            $oItem->addresses = $oItem->addresses();
        }

        parent::loadEditViewData($oItem);
    }

    // --------------------------------------------------------------------------

    protected function runFormValidation(string $sMode, array $aOverrides = []): void
    {
        parent::runFormValidation($sMode, $aOverrides);

        /** @var Input $oInput */
        $oInput = Factory::service('Input');
        /** @var Address\Service\Address $oAddressService */
        $oAddressService = Factory::service('Address', Address\Constants::MODULE_SLUG);

        try {

            $aAddresses = array_filter((array) $oInput->post('addresses'));
            $aAddresses = array_values($aAddresses);

            foreach ($aAddresses as $iIndex => $aAddress) {

                $oAddress = Address\Helper\Address::extractAddressFromArray($aAddress);
                $oAddress->validate();
            }

        } catch (ValidationException $e) {
            throw new ValidationException(
                sprintf(
                    'Validation failed for address at position %s: %s',
                    $iIndex + 1,
                    $e->getMessage()
                ),
                $e->getCode(),
                $e
            );
        }
    }

    // --------------------------------------------------------------------------

    protected function getPostObject(): array
    {
        $aData = parent::getPostObject();
        unset($aData['addresses']);
        return $aData;
    }

    // --------------------------------------------------------------------------

    protected function afterCreateAndEdit(
        $sMode,
        ?\Nails\Common\Resource $oNewItem,
        ?\Nails\Common\Resource $oOldItem = null
    ): void {

        parent::afterCreateAndEdit($sMode, $oNewItem, $oOldItem);

        /** @var Address\Service\Address $oAddressService */
        $oAddressService = Factory::service('Address', Address\Constants::MODULE_SLUG);

        /** @var array[] $aAddresses */
        $aAddresses = getFromArray('addresses', parent::getPostObject());
        $aAddresses = array_filter((array) $aAddresses);

        $oAddressService->associatedAddressesSet($oNewItem, $aAddresses);
    }
}

