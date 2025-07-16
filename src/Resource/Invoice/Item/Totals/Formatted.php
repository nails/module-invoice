<?php

namespace Nails\Invoice\Resource\Invoice\Item\Totals;

use Nails\Common\Exception\FactoryException;
use Nails\Common\Resource;
use Nails\Currency\Constants;
use Nails\Currency\Exception\CurrencyException;
use Nails\Currency\Service\Currency;
use Nails\Factory;

/**
 * Class Formatted
 *
 * @package Nails\Invoice\Resource\Invoice\Item\Totals
 */
class Formatted extends Resource
{
    /**
     * The currency
     *
     * @var \Nails\Currency\Resource\Currency
     */
    public $currency;

    /**
     * The sub total
     *
     * @var string
     */
    public $sub;

    /**
     * The tax total
     *
     * @var string
     */
    public $tax;

    /**
     * The grand total
     *
     * @var string
     */
    public $grand;

    // --------------------------------------------------------------------------

    /**
     * Formatted constructor.
     *
     * @throws FactoryException
     * @throws CurrencyException
     */
    public function __construct(self|\stdClass|array $resource = [])
    {
        parent::__construct($resource);

        /** @var Currency $oCurrencyService */
        $oCurrencyService = Factory::service('Currency', Constants::MODULE_SLUG);

        foreach (['sub', 'tax', 'grand'] as $sProperty) {
            $this->{$sProperty} = $oCurrencyService->format(
                $this->currency,
                $this->{$sProperty} / pow(10, $this->currency->decimal_precision)
            );
        }

        unset($this->currency);
    }
}
