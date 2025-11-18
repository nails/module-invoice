<?php

/**
 * This model handles interactions with the app's "nails_invoice_source" table.
 *
 * @package  Nails\Invoice\Model
 * @category model
 */

namespace Nails\Invoice\Model;

use DateTime;
use Exception;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Common\Exception\ValidationException;
use Nails\Common\Model\Base;
use Nails\Common\Service\Database;
use Nails\Factory;
use Nails\Invoice\Constants;
use Nails\Invoice\Driver\PaymentBase;
use Nails\Invoice\Exception\DriverException;
use Nails\Invoice\Exception\InvoiceException;
use Nails\Invoice\Resource;
use Nails\Invoice\Service\PaymentDriver;

/**
 * Class Source
 *
 * @package Nails\Invoice\Model
 */
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
    const RESOURCE_PROVIDER = Constants::MODULE_SLUG;

    // --------------------------------------------------------------------------

    /**
     * Source constructor.
     *
     * @throws ModelException
     */
    public function __construct()
    {
        parent::__construct();
        $this
            ->hasOne('customer', 'Customer', Constants::MODULE_SLUG)
            ->hasOne('billing_address', 'Address', \Nails\Address\Constants::MODULE_SLUG);
    }

    // --------------------------------------------------------------------------

    /**
     * Creates a new payment source. Delegates to the payment driver.
     *
     * @param array $aData         The data array
     * @param bool  $bReturnObject Whether to return the new object or not
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
        $oPaymentDriverService = Factory::service('PaymentDriver', Constants::MODULE_SLUG);
        /** @var PaymentBase $oDriver */
        $oDriver = $oPaymentDriverService->getInstance($aData['driver']);

        if (empty($oDriver)) {
            throw new DriverException('"' . $aData['driver'] . '" is not a valid payment driver.');
        }

        //  If an expiry date is passed, ensure it is valid
        if (array_key_exists('expiry', $aData)) {
            $this->validateExpiry($aData['expiry']);
        }

        /** @var Resource\Source $oResource */
        $oResource = Factory::resource('Source', Constants::MODULE_SLUG, [
            'customer_id'        => $aData['customer_id'],
            'driver'             => $aData['driver'],
            'billing_address_id' => getFromArray('billing_address_id', $aData),
            'label'              => getFromArray('label', $aData),
            'name'               => getFromArray('name', $aData),
            'brand'              => getFromArray('brand', $aData),
            'last_four'          => getFromArray('last_four', $aData),
            'expiry'             => getFromArray('expiry', $aData),
        ], $this);

        unset($aData['driver']);
        unset($aData['customer_id']);
        unset($aData['customer']);
        unset($aData['billing_address']);

        $oDriver->createSource($oResource, $aData);

        if (empty($oResource->label) && !empty($oResource->brand) && !empty($oResource->last_four)) {
            $oResource->label = $oResource->brand . ' ending ' . $oResource->last_four;
        } elseif (empty($oResource->label)) {
            $oResource->label = 'Payment Source';
        }

        /** @var Resource\Source $oSource */
        $oSource = parent::create(
            [
                'customer_id' => $oResource->customer_id,
                'driver'      => $oResource->driver,
                'data'        => json_encode($oResource->data),
                'label'       => $oResource->label,
                'name'        => $oResource->name,
                'brand'       => $oResource->brand,
                'last_four'   => $oResource->last_four,
                'expiry'      => $oResource->expiry,
            ],
            $bReturnObject
        );

        //  Set as default payment source if no existing default for customer
        $oDefault = $this->getDefault($oResource->customer_id);
        if (empty($oDefault)) {
            $this->setDefault($oResource->customer_id, $oSource);
        }

        return $oSource;
    }

    // --------------------------------------------------------------------------

    /**
     * Updates an existing payment source
     *
     * @param int   $iId   The ID of the object to update
     * @param array $aData The data to update the object with
     *
     * @return bool
     * @throws DriverException
     * @throws FactoryException
     * @throws ModelException
     * @throws ValidationException
     */
    public function update($iId, array $aData = []): bool
    {
        $aRestrictedProperties = ['id', 'driver', 'data'];
        foreach ($aRestrictedProperties as $sProperty) {
            if (array_key_exists($sProperty, $aData)) {
                throw new ValidationException('Cannot update restricted property "' . $sProperty . '"');
            }
        }

        if (array_key_exists('expiry', $aData)) {
            $this->validateExpiry($aData['expiry']);
        }

        /** @var Resource\Source $oResource */
        $oResource = $this->getById($iId);
        if (empty($oResource)) {
            throw new ValidationException('Invalid source ID');
        }

        //  Update values on the resource
        foreach ($aData as $sKey => $mValue) {
            if (property_exists($oResource, $sKey)) {
                $oResource->{$sKey} = $mValue;
            }
        }

        //  Give the driver the chance to update the remote source
        /** @var PaymentDriver $oPaymentDriverService */
        $oPaymentDriverService = Factory::service('PaymentDriver', Constants::MODULE_SLUG);
        /** @var PaymentBase $oDriver */
        $oDriver = $oPaymentDriverService->getInstance($oResource->driver);

        $oDriver->updateSource($oResource);

        //  Ensure data is encoded to a string
        $oResource->data = json_encode($oResource->data);

        return parent::update(
            $oResource->id,
            [
                'customer_id'        => $oResource->customer_id,
                'driver'             => $oResource->driver,
                'billing_address_id' => $oResource->billing_address_id,
                'data'               => $oResource->data,
                'label'              => $oResource->label,
                'name'               => $oResource->name,
                'brand'              => $oResource->brand,
                'last_four'          => $oResource->last_four,
                'expiry'             => $oResource->expiry,
                'is_default'         => $oResource->is_default,
            ]
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Deletes an existing payment source
     *
     * @param int  $iId                    The ID of the object to deleted
     * @param bool $bDeleteRemote          Whether to delete the remote source as well
     * @param bool $bTolerateRemoteFailure Whether to tolerate the remote delete failing
     *
     * @return bool
     * @throws FactoryException
     */
    public function delete($iId, bool $bDeleteRemote = true, bool $bTolerateRemoteFailure = false): bool
    {
        /** @var Database $oDb */
        $oDb = Factory::service('Database');
        $oDb->transaction()->start();

        try {

            /** @var Resource\Source $oSource */
            $oSource = $this->getById($iId);
            if (empty($oSource)) {
                throw new ValidationException('Invalid source ID');
            }

            if (!parent::delete($oSource->id)) {
                throw new ModelException('Failed to delete source. ' . $this->lastError());
            }

            if ($bDeleteRemote) {
                try {

                    /** @var PaymentDriver $oPaymentDriverService */
                    $oPaymentDriverService = Factory::service('PaymentDriver', Constants::MODULE_SLUG);
                    /** @var PaymentBase $oDriver */
                    $oDriver = $oPaymentDriverService->getInstance($oSource->driver);

                    $oDriver->deleteSource($oSource);

                } catch (\Exception $e) {
                    if (!$bTolerateRemoteFailure) {
                        throw $e;
                    }
                }
            }

            $oDb->transaction()->commit();
            return true;

        } catch (Exception $e) {
            $oDb->transaction()->rollback();
            $this->setError($e->getMessage());
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Gets the default payment source for a customer
     *
     * @param Resource\Customer|int $mCustomer The customer object or ID
     *
     * @return Resource\Source|null
     * @throws ValidationException
     * @throws ModelException
     */
    public function getDefault($mCustomer): ?Resource\Source
    {
        $iCustomerId = $this->getCustomerId($mCustomer, __METHOD__);
        $aSources    = $this->getAll([
            'where' => [
                ['customer_id', $iCustomerId],
                ['is_default', true],
            ],
        ]);

        return !empty($aSources) ? reset($aSources) : null;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the default payment source for a customer
     *
     * @param Resource\Customer|int $mCustomer The customer object or ID
     * @param Resource\Source|int   $mSource   The source object or ID
     *
     * @return bool
     * @throws FactoryException
     * @throws ValidationException
     */
    public function setDefault($mCustomer, $mSource): bool
    {
        $iCustomerId = $this->getCustomerId($mCustomer, __METHOD__);
        $iSourceId   = $this->getSourceId($mSource, __METHOD__);

        if (empty($iCustomerId)) {
            throw new ValidationException('Could not ascertain customer ID.');
        } elseif (empty($iSourceId)) {
            throw new ValidationException('Could not ascertain source ID.');
        }

        /** @var Database $oDb */
        $oDb = Factory::service('Database');

        $oDb->transaction()->start();
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

            $oDb->transaction()->commit();

            if ($mSource instanceof Resource\Source) {
                $mSource->is_default = true;
            }

            return true;

        } catch (Exception $e) {
            $oDb->transaction()->rollback();
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns payment sources for a particular customer
     *
     * @param Resource\Customer|int $mCustomer      The customer object or ID
     * @param bool                  $bRemoveExpired Whether to remove expired payment sources
     *
     * @return Resource\Source[]
     * @throws ModelException
     * @throws ValidationException
     * @throws FactoryException
     */
    public function getForCustomer($mCustomer, bool $bRemoveExpired = true): array
    {
        $iCustomerId = $this->getCustomerId($mCustomer, __METHOD__);

        if (empty($iCustomerId)) {
            return [];
        }

        /** @var PaymentDriver $oPaymentDriver */
        $oPaymentDriver = Factory::service('PaymentDriver', Constants::MODULE_SLUG);

        return $this->getAll([
            'where'    => array_filter([
                ['customer_id', $iCustomerId],
                $bRemoveExpired ? '(expiry IS NULL OR expiry > CURDATE())' : null,
            ]),
            'where_in' => [
                ['driver', $oPaymentDriver->getEnabledSlug()],
            ],
            'sort'     => [
                ['is_default', 'desc'],
                ['created', 'desc'],
            ],
        ]);
    }

    // --------------------------------------------------------------------------

    protected function formatObject(
        &$oObj,
        array $aData = [],
        array $aIntegers = [],
        array $aBools = [],
        array $aFloats = []
    ) {
        $aIntegers[] = 'customer_id';
        $aBools[]    = 'is_default';
        parent::formatObject($oObj, $aData, $aIntegers, $aBools, $aFloats);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the customer ID
     *
     * @param Resource\Customer|int $mCustomer The customer object or ID
     *
     * @return int|null
     * @throws ValidationException
     */
    protected function getCustomerId($mCustomer, $sMethod): ?int
    {
        if ($mCustomer instanceof Resource\Customer) {
            return $mCustomer->id;
        } elseif (is_int($mCustomer)) {
            return $mCustomer;
        } else {
            throw new ValidationException(
                'Invalid type "' . gettype($mCustomer) . '" for customer passed to ' . $sMethod
            );
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the source ID
     *
     * @param Resource\Source|int $mSource The source object or ID
     * @param string              $sMethod The called method
     *
     * @return int
     * @throws ValidationException
     */
    protected function getSourceId($mSource, $sMethod): int
    {
        if ($mSource instanceof Resource\Source) {
            return $mSource->id;
        } elseif (is_int($mSource)) {
            return $mSource;
        } else {
            throw new ValidationException(
                'Invalid type "' . gettype($mSource) . '" for source passed to ' . $sMethod
            );
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Validates an expiry string
     *
     * @param string $sExpiry The string to validate
     *
     * @throws DriverException
     * @throws FactoryException
     */
    protected function validateExpiry(string $sExpiry): void
    {
        try {
            $oExpiry = new DateTime($sExpiry);
        } catch (Exception $e) {
            throw new DriverException('"' . $sExpiry . '" is not a valid expiry date.', null, $e);
        }

        $oNow = Factory::factory('DateTime');
        if ($oExpiry < $oNow) {
            throw new DriverException('Expiry must be a future date.');
        }
    }
}
