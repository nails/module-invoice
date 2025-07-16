<?php

namespace Nails\Invoice\Resource\Invoice\Item;

use Nails\Common\Exception\FactoryException;
use Nails\Common\Resource;
use Nails\Currency\Exception\CurrencyException;
use Nails\Currency\Service\Currency;
use Nails\Factory;
use Nails\Invoice\Constants;

/**
 * Class UnitCost
 *
 * @package Nails\Invoice\Resource\Invoice\Item
 */
class UnitCost extends Resource
{
    /**
     * The currency
     *
     * @var \Nails\Currency\Resource\Currency
     */
    public $currency;

    /**
     * The raw totals
     *
     * @var int
     */
    public $raw;

    /**
     * The formatted totals
     *
     * @var string
     */
    public $formatted;

    // --------------------------------------------------------------------------

    /**
     * UnitCost constructor.
     *
     * @throws FactoryException
     * @throws CurrencyException
     */
    public function __construct(self|\stdClass|array $resource = [])
    {
        parent::__construct($resource);

        /** @var Currency $oCurrencyService */
        $oCurrencyService = Factory::service('Currency', \Nails\Currency\Constants::MODULE_SLUG);

        $this->formatted = $oCurrencyService->format(
            $this->currency,
            $this->raw / pow(10, $this->currency->decimal_precision)
        );

        unset($this->currency);
    }
}
