<?php

/**
 * This class represents objects dispensed by the Source model
 *
 * @package  Nails\Invoice\Resource
 * @category resource
 */

namespace Nails\Invoice\Resource;

use DateTime;
use Nails\Address\Resource\Address;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Resource\Date;
use Nails\Common\Resource\Entity;
use Nails\Factory;
use Nails\Invoice\Constants;
use stdClass;

/**
 * Class Source
 *
 * @package Nails\Invoice\Resource
 */
class Source extends Entity
{
    /**
     * The source's customer ID
     *
     * @var int
     */
    public $customer_id;

    /**
     * The source's customer
     *
     * @var Customer
     */
    public $customer;

    /**
     * The source's billing address ID
     *
     * @var int|null
     */
    public $billing_address_id;

    /**
     * The source's billing address
     *
     * @var Address|null
     */
    public $billing_address;

    /**
     * Which driver is responsible for the source
     *
     * @var string
     */
    public $driver;

    /**
     * Any data required by the driver
     *
     * @var stdClass
     */
    public $data;

    /**
     * The source's label
     *
     * @var string
     */
    public $label;

    /**
     * The source's name (e.g. the cardholder)
     *
     * @var string
     */
    public $name;

    /**
     * The source's brand
     *
     * @var string
     */
    public $brand;

    /**
     * The source's last four digits
     *
     * @var string
     */
    public $last_four;

    /**
     * The source's expiry date
     *
     * @var Date
     */
    public $expiry;

    /**
     * Whether the source is the default for the customer or not
     *
     * @var bool
     */
    public $is_default = false;

    /**
     * Whether the source has expired or not
     *
     * @var bool
     */
    public $is_expired = false;

    // --------------------------------------------------------------------------

    /**
     * Source constructor.
     *
     * @param array $mObj The data to populate the resource with
     *
     * @throws FactoryException
     */
    public function __construct($mObj = [])
    {
        parent::__construct($mObj);

        if ($this->expiry) {
            $this->expiry = Factory::resource('Date', null, ['raw' => $this->expiry]);
        }

        $this->is_expired = $this->isExpired();
        $this->data       = json_decode((string) $this->data) ?? (object) [];
    }

    // --------------------------------------------------------------------------

    /**
     * Return's the source's customer
     *
     * @return Customer
     * @throws FactoryException
     * @throws \Nails\Common\Exception\ModelException
     */
    public function customer(): Customer
    {
        if (empty($this->customer) && !empty($this->customer_id)) {

            /** @var \Nails\Invoice\Model\Customer $oCustomerModel */
            $oCustomerModel = Factory::model('Customer', Constants::MODULE_SLUG);
            $this->customer = $oCustomerModel->getById($this->customer_id);
        }

        return $this->customer;
    }

    // --------------------------------------------------------------------------

    /**
     * Return's the source's billing address
     *
     * @return Address|null
     * @throws FactoryException
     * @throws \Nails\Common\Exception\ModelException
     */
    public function billingAddress(): ?Address
    {
        if (empty($this->billing_address) && !empty($this->billing_address_id)) {

            /** @var \Nails\Address\Model\Address $oAddressModel */
            $oAddressModel         = Factory::model('Address', \Nails\Address\Constants::MODULE_SLUG);
            $this->billing_address = $oAddressModel->getById($this->billing_address_id);
        }

        return $this->billing_address;
    }

    // --------------------------------------------------------------------------

    /**
     * Determines whether the source has expired
     *
     * @param DateTime $oWhen
     *
     * @return bool
     */
    public function isExpired(?DateTime $oWhen = null): bool
    {
        if (!$this->expiry) {
            return false;
        }

        /** @var DateTime $oWhen */
        $oWhen = $oWhen ?? Factory::factory('DateTime');
        return $this->expiry->getDateTimeObject() < $oWhen;
    }
}
