<?php

namespace Nails\Invoice\Resource\Invoice\Item;

use Nails\Common\Exception\FactoryException;
use Nails\Common\Resource;
use Nails\Factory;
use Nails\Invoice\Constants;
use Nails\Invoice\Resource\Invoice\Item\Totals\Formatted;
use Nails\Invoice\Resource\Invoice\Item\Totals\Raw;

/**
 * Class Totals
 *
 * @package Nails\Invoice\Resource\Invoice\Item
 */
class Totals extends Resource
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
     * @var Raw
     */
    public $raw;

    /**
     * The formatted totals
     *
     * @var Formatted
     */
    public $formatted;

    // --------------------------------------------------------------------------

    /**
     * Totals constructor.
     *
     * @throws FactoryException
     */
    public function __construct(self|\stdClass|array $resource = [])
    {
        parent::__construct($resource);

        $this->raw = Factory::resource(
            'InvoiceItemTotalsRaw',
            Constants::MODULE_SLUG,
            [
                'sub'   => (int) $resource->sub,
                'tax'   => (int) $resource->tax,
                'grand' => (int) $resource->grand,
            ]
        );

        $this->formatted = Factory::resource(
            'InvoiceItemTotalsFormatted',
            Constants::MODULE_SLUG,
            [
                'currency' => $this->currency,
                'sub'      => (int) $resource->sub,
                'tax'      => (int) $resource->tax,
                'grand'    => (int) $resource->grand,
            ]
        );

        unset($this->sub);
        unset($this->tax);
        unset($this->grand);
        unset($this->currency);
    }
}
