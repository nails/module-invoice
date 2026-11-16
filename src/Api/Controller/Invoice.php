<?php

/**
 * Returns information about invoices
 *
 * @package     Nails
 * @subpackage  module-invoice
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Invoice\Api\Controller;

use Nails\Api\Controller\CrudController;
use Nails\Api\Exception\ApiException;
use Nails\Invoice\Constants;
use stdClass;

/**
 * Class Invoice
 *
 * @package Nails\Invoice\Api\Controller
 */
class Invoice extends CrudController
{
    const CONFIG_MODEL_NAME     = 'Invoice';
    const CONFIG_MODEL_PROVIDER = Constants::MODULE_SLUG;
    const CONFIG_LOOKUP_DATA    = ['expand' => ['customer']];

    // --------------------------------------------------------------------------

    /**
     * @param string $sAction
     * @param null   $oItem
     *
     * @throws ApiException
     */
    protected function userCan($sAction, $oItem = null)
    {
        switch ($sAction) {
            case static::ACTION_CREATE:
                if (!userHasPermission('admin:invoice:customer:create')) {
                    throw new ApiException(
                        'You are not authorised to access this resource.',
                        401
                    );
                }
                break;

            case static::ACTION_READ:
                if (!userHasPermission('admin:invoice:customer:manage')) {
                    throw new ApiException(
                        'You are not authorised to access this resource.',
                        401
                    );
                }
                break;

            case static::ACTION_UPDATE:
                if (!userHasPermission('admin:invoice:customer:edit')) {
                    throw new ApiException(
                        'You are not authorised to access this resource.',
                        401
                    );
                }
                break;

            case static::ACTION_DELETE:
                if (!userHasPermission('admin:invoice:customer:delete')) {
                    throw new ApiException(
                        'You are not authorised to access this resource.',
                        401
                    );
                }
                break;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * @param stdClass $oObj
     *
     * @return object|stdClass
     */
    public function formatObject($oObj)
    {
        return [
            'id'       => $oObj->id,
            'ref'      => $oObj->ref,
            'terms'    => $oObj->terms,
            'dated'    => $oObj->dated->raw,
            'due'      => $oObj->due->raw,
            'paid'     => $oObj->paid->raw,
            'state'    => $oObj->state,
            'currency' => $oObj->currency->code,
            'totals'   => $oObj->totals,
            'urls'     => $oObj->urls,
            'customer' => empty($oObj->customer)
                ? null
                : [
                    'id'    => $oObj->customer->id,
                    'label' => $oObj->customer->label,
                    'email' => $oObj->customer->billing_email ?: $oObj->customer->email,
                ],
        ];
    }
}
