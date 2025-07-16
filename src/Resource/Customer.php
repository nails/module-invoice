<?php

/**
 * This class represents objects dispensed by the Customer model
 *
 * @package  Nails\Invoice\Resource
 * @category resource
 */

namespace Nails\Invoice\Resource;

use Nails\Address;
use Nails\Common\Model\Base;
use Nails\Common\Resource\Entity;
use Nails\Factory;
use stdClass;

/**
 * Class Customer
 *
 * @package Nails\Invoice\Resource
 */
class Customer extends Entity
{
    /**
     * @var string
     */
    public $label;

    /**
     * @var string
     */
    public $organisation;

    /**
     * @var string
     */
    public $first_name;

    /**
     * @var string
     */
    public $last_name;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $email;

    /**
     * @var string
     */
    public $billing_email;

    /**
     * @var string
     */
    public $telephone;

    /**
     * @var string
     */
    public $vat_number;

    /**
     * @var bool
     */
    public $is_deleted;

    // --------------------------------------------------------------------------

    /**
     * Customer constructor.
     */
    public function __construct(self|stdClass|array $resource = [], ?Base $model = null)
    {
        parent::__construct($resource, $model);

        $this->name = trim(sprintf(
            '%s %s',
            $this->first_name,
            $this->last_name
        ));
    }

    // --------------------------------------------------------------------------

    /**
     * Returns associated customer addresses
     *
     * @return Address\Resource\Address[]
     */
    public function addresses(): array
    {
        /** @var Address\Service\Address $oAddressService */
        $oAddressService = Factory::service('Address', Address\Constants::MODULE_SLUG);
        return $oAddressService->associatedAddressesGet($this);
    }
}
