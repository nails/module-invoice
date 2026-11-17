<?php

/**
 * Returns information about customers
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
 * Class Customer
 *
 * @package Nails\Invoice\Api\Controller
 */
class Customer extends CrudController
{
    const CONFIG_MODEL_NAME     = 'Customer';
    const CONFIG_MODEL_PROVIDER = Constants::MODULE_SLUG;

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
                if (!userHasPermission(\Nails\Invoice\Admin\Permission\Customer\Create::class)) {
                    throw new ApiException(
                        'You are not authorised to access this resource.',
                        401
                    );
                }
                break;

            case static::ACTION_READ;
                if (!userHasPermission(\Nails\Invoice\Admin\Permission\Customer\Browse::class)) {
                    throw new ApiException(
                        'You are not authorised to access this resource.',
                        401
                    );
                }
                break;

            case static::ACTION_UPDATE;
                if (!userHasPermission(\Nails\Invoice\Admin\Permission\Customer\Edit::class)) {
                    throw new ApiException(
                        'You are not authorised to access this resource.',
                        401
                    );
                }
                break;

            case static::ACTION_DELETE;
                if (!userHasPermission(\Nails\Invoice\Admin\Permission\Customer\Delete::class)) {
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
    protected function formatObject($oObj)
    {
        $sLabel = $oObj->label;
        $sEmail = $oObj->billing_email ?: $oObj->email;
        if (!empty($sEmail)) {
            $sLabel .= ' (' . $sEmail . ')';
        }

        return (object) [
            'id'    => $oObj->id,
            'label' => $sLabel . ' - ID ' . $oObj->id,
        ];
    }
}
