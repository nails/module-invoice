<?php

/**
 * Manages customer entities
 *
 * @package     Nails
 * @subpackage  module-invoice
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Invoice\Model;

use Exception;
use Nails\Admin\Helper\Form;
use Nails\Common\Exception\ModelException;
use Nails\Common\Factory\Model\Field;
use Nails\Common\Model\Base;
use Nails\Common\Service\Country;
use Nails\Common\Service\Database;
use Nails\Common\Service\FormValidation;
use Nails\Common\Traits\Model\Mergeable;
use Nails\Factory;
use Nails\Invoice\Constants;
use Nails\Invoice\Exception\CustomerException\MergeException;
use Nails\Invoice\Exception\InvoiceException;

/**
 * Class Customer
 *
 * @package Nails\Invoice\Model
 */
class Customer extends Base
{
    use Mergeable;

    // --------------------------------------------------------------------------

    /**
     * The table this model represents
     *
     * @var string
     */
    const TABLE = NAILS_DB_PREFIX . 'invoice_customer';

    /**
     * The name of the resource to use (as passed to \Nails\Factory::resource())
     *
     * @var string
     */
    const RESOURCE_NAME = 'Customer';

    /**
     * The provider of the resource to use (as passed to \Nails\Factory::resource())
     *
     * @var string
     */
    const RESOURCE_PROVIDER = Constants::MODULE_SLUG;

    /**
     * Whether this model uses destructive delete or not
     *
     * @var bool
     */
    const DESTRUCTIVE_DELETE = false;

    /**
     * The default column to sort on
     *
     * @var string|null
     */
    const DEFAULT_SORT_COLUMN = 'first_name';

    // --------------------------------------------------------------------------

    /**
     * Customer constructor.
     *
     * @throws ModelException
     */
    public function __construct()
    {
        parent::__construct();
        $this
            ->hasMany('invoices', 'Invoice', 'customer_id', Constants::MODULE_SLUG);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the searchable columns for this module
     *
     * @return string[]
     */
    public function getSearchableColumns(): array
    {
        return [
            'id',
            'label',
            'email',
            'billing_email',
        ];
    }

    // --------------------------------------------------------------------------

    /**
     * Describe the model's fields
     *
     * @param null $sTable
     *
     * @return Field[]
     */
    public function describeFields($sTable = null)
    {
        $aFields = parent::describeFields($sTable);

        $aFields['email']
            ->addValidation(FormValidation::RULE_VALID_EMAIL);

        $aFields['billing_email']
            ->addValidation(FormValidation::RULE_VALID_EMAIL);

        return $aFields;
    }

    // --------------------------------------------------------------------------

    /**
     * This method applies the conditionals which are common across the get_*()
     * methods and the count() method.
     *
     * @param array $aData Data passed from the calling method
     *
     * @return void
     */
    protected function getCountCommon(array &$aData = []): void
    {
        //  If there's a search term, then we better get %LIKING%
        if (!empty($aData['keywords'])) {

            if (empty($aData['or_like'])) {
                $aData['or_like'] = [];
            }

            $iKeywordAsId = (int) preg_replace('/[^0-9]/', '', $aData['keywords']);

            if ($iKeywordAsId) {
                $aData['or_like'][] = [
                    'column' => $this->getTableAlias() . '.id',
                    'value'  => $iKeywordAsId,
                ];
            }

            $aData['or_like'][] = [
                'column' => $this->getTableAlias() . '.label',
                'value'  => $aData['keywords'],
            ];
        }

        // --------------------------------------------------------------------------

        //  Let the parent method handle sorting, etc
        parent::getCountCommon($aData);
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new customer
     *
     * @param array   $aData         The data to create the customer with
     * @param boolean $bReturnObject Whether to return the complete customer object
     *
     * @return mixed
     */
    public function create(array $aData = [], $bReturnObject = false)
    {
        try {

            if (empty($aData['organisation']) && empty($aData['first_name']) && empty($aData['last_name'])) {
                throw new InvoiceException('"organisation", "first_name" or "last_name" must be supplied.', 1);
            }

            //  Compile the label
            $aData['label'] = $this->compileLabel($aData);

            return parent::create($aData, $bReturnObject);

        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Update an existing customer
     *
     * @param integer $iCustomerId The ID of the customer to update
     * @param array   $aData       The data to update the customer with
     *
     * @return mixed
     */
    public function update($iCustomerId, array $aData = []): bool
    {
        try {

            $sKeyExistsLabel = array_key_exists('label', $aData);
            $sKeyExistsOrg   = array_key_exists('organisation', $aData);
            $sKeyExistsFirst = array_key_exists('first_name', $aData);
            $sKeyExistsLast  = array_key_exists('last_name', $aData);

            if ($sKeyExistsOrg && $sKeyExistsFirst && $sKeyExistsLast) {
                if (empty($aData['organisation']) && empty($aData['first_name']) && empty($aData['last_name'])) {
                    throw new InvoiceException('"organisation", "first_name" and "last_name" cannot all be empty.', 1);
                }
            }

            //  Only compile the label if the label isn't defined and any of the other fields are present
            if (!$sKeyExistsLabel && ($sKeyExistsOrg || $sKeyExistsFirst || $sKeyExistsLast)) {
                $aData['label'] = $this->compileLabel($aData);
            }

            return parent::update($iCustomerId, $aData);

        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Compile the customer label
     *
     * @param array $aData The data passed to create() or update()
     *
     * @return string
     */
    protected function compileLabel($aData)
    {
        if (!empty($aData['organisation'])) {
            return trim($aData['organisation']);
        } else {
            return implode(
                ' ',
                array_filter([
                    !empty($aData['first_name']) ? trim($aData['first_name']) : '',
                    !empty($aData['last_name']) ? trim($aData['last_name']) : '',
                ])
            );
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the customer ID for the active user.
     *
     * This assumes that the user's customer ID is stored in the user_meta_app
     * table. If your application has different logic, you should override this
     * method and implement the appropriate behaviour.
     *
     * @return int|null
     */
    public function getCustomerIdForActiveUser(): ?int
    {
        return (int) activeUser('customer_id') ?: null;
    }

    // --------------------------------------------------------------------------

    /**
     * @param int   $iKeepId
     * @param array $aMergeIds
     * @param bool  $bMergeSources
     *
     * @return $this
     * @throws \Nails\Common\Exception\FactoryException
     * @throws \Nails\Common\Exception\ModelException
     * @throws \Nails\Invoice\Exception\CustomerException\MergeException
     */
    public function merge(int $iKeepId, array $aMergeIds, bool $bMergeSources = true): self
    {
        /** @var Database $oDb */
        $oDb           = Factory::service('Database');
        $oInvoiceModel = Factory::model('Invoice', Constants::MODULE_SLUG);
        $oSourceModel  = Factory::model('Source', Constants::MODULE_SLUG);

        //  Merge invoices
        $bResult = $oDb
            ->set('customer_id', $iKeepId)
            ->where_in('customer_id', $aMergeIds)
            ->update($oInvoiceModel->getTableName());

        if (!$bResult) {
            throw new MergeException('Failed to merge customer invoices');
        }

        //  Merge payment sources
        if ($bMergeSources) {
            $bResult = $oDb
                ->set('customer_id', $iKeepId)
                ->where_in('customer_id', $aMergeIds)
                ->update($oSourceModel->getTableName());

            if (!$bResult) {
                throw new MergeException('Failed to merge customer invoices');
            }
        }

        //  Delete merged customers
        foreach ($aMergeIds as $iMergeId) {
            if (!$this->destroy($iMergeId)) {
                throw new MergeException(sprintf(
                    'Failed to delete customer "%s"',
                    $iMergeId,
                ));
            }
        }

        return $this;
    }
}
