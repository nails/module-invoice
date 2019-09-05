<?php

/**
 * This model handles interactions with the app's "nails_invoice_source" table.
 *
 * @package  Nails\Invoice\Model
 * @category model
 */

namespace Nails\Invoice\Model;

use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Common\Model\Base;
use Nails\Common\Service\Database;
use Nails\Factory;
use Nails\Invoice\Exception\DriverException;
use Nails\Invoice\Exception\InvoiceException;
use Nails\Invoice\Resource;
use Nails\Invoice\Service\PaymentDriver;

class Source extends Base
{
    /**
     * The table this model represents
     *
     * @var string
     */
    const TABLE = NAILS_DB_PREFIX . 'invoice_source';

    /**
     * The name of the resource to use (as passed to \Nails\Factory::resource())
     *
     * @var string
     */
    const RESOURCE_NAME = 'Source';

    /**
     * The provider of the resource to use (as passed to \Nails\Factory::resource())
     *
     * @var string
     */
    const RESOURCE_PROVIDER = 'nails/module-invoice';

    // --------------------------------------------------------------------------

    /**
     * Creates a new payment source. Delegates to the payment driver.
     *
     * @param array $aData         the data array
     * @param bool  $bReturnObject Whether top return the new object or not
     *
     * @return mixed
     * @throws DriverException
     * @throws FactoryException
     * @throws ModelException
     */
    public function create(array $aData = [], $bReturnObject = false)
    {
        if (!array_key_exists('driver', $aData)) {
            throw new DriverException('"driver" is a required field');
        } elseif (!array_key_exists('customer_id', $aData)) {
            throw new DriverException('"customer_id" is a required field');
        }

        /** @var PaymentDriver $oPaymentDriverService */
        $oPaymentDriverService = Factory::service('PaymentDriver', 'nails/module-invoice');
        $oDriver               = $oPaymentDriverService->getBySlug($aData['driver']);

        if (empty($oDriver)) {
            throw new DriverException('"' . $sDriver . '" is not a valid payment driver.');
        }

        /** @var Resource\Source $oResource */
        $oResource = Factory::resource('Source', 'nails/module-invoice', [
            'customer_id' => $aData['customer_id'],
            'driver'      => $oDriver->slug,
        ]);

        unset($aData['driver']);
        unset($aData['customer_id']);

        $oDriver->createSource($oResource, $aData);

        return parent::create((array) $oSource, $bReturnObject);
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the default payment source for a customer
     *
     * @param int $iCustomerId The customer ID
     * @param int $iSourceId   The source ID
     *
     * @return bool
     * @throws FactoryException
     */
    public function setDefault(int $iCustomerId, int $iSourceId): bool
    {
        /** @var Database $oDb */
        $oDb = Factory::service('Database');

        $oDb->trans_begin();
        try {

            $oDb->set('is_default', false);
            $oDb->where('customer_id', $iCustomerId);
            $oDb->where('id !=', $iSourceId);
            if (!$oDb->update($this->getTableName())) {
                throw new InvoiceException(
                    'Failed to set default payment source; could not unset previous sources.'
                );
            }

            $oDb->set('is_default', true);
            $oDb->where('customer_id', $iCustomerId);
            $oDb->where('id', $iSourceId);
            if (!$oDb->update($this->getTableName())) {
                throw new InvoiceException(
                    'Failed to set default payment source; could not set desired source.'
                );
            }

            $oDb->trans_commit();

            return true;

        } catch (\Exception $e) {
            $oDb->trans_rollback();
            return false;
        }
    }
}
