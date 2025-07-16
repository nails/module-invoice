<?php

namespace Nails\Invoice\Resource\Payment;

use Nails\Common\Exception\FactoryException;
use Nails\Common\Resource;
use Nails\Currency\Constants;
use Nails\Currency\Exception\CurrencyException;
use Nails\Currency\Service\Currency;
use Nails\Factory;

/**
 * Class Amount
 *
 * @package Nails\Invoice\Resource\Payment
 */
class Amount extends Resource
{
    /**
     * The amount's currency
     *
     * @var \Nails\Currency\Resource\Currency
     */
    public $currency;

    /**
     * The amount's raw value
     *
     * @var string
     */
    public $raw;

    /**
     * The amount' sformatted value
     *
     * @var string
     */
    public $formatted;

    // --------------------------------------------------------------------------

    /**
     * Amount constructor.
     *
     * @throws FactoryException
     * @throws CurrencyException
     */
    public function __construct(self|\stdClass|array $resource = [])
    {
        parent::__construct($resource);

        /** @var Currency $oCurrency */
        $oCurrency = Factory::service('Currency', Constants::MODULE_SLUG);

        $this->formatted = $oCurrency->format(
            $this->currency,
            $this->raw / pow(10, $this->currency->decimal_precision)
        );

        unset($this->currency);
    }
}
