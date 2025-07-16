<?php

namespace Nails\Invoice\Resource\Invoice;

use Nails\Common\Exception\FactoryException;
use Nails\Common\Resource;
use Nails\Factory;
use Nails\Invoice\Constants;
use Nails\Invoice\Resource\Invoice\Totals\Formatted;
use Nails\Invoice\Resource\Invoice\Totals\Raw;

/**
 * Class Totals
 *
 * @package Nails\Invoice\Resource\Invoice
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
            'InvoiceTotalsRaw',
            Constants::MODULE_SLUG,
            [
                'sub'        => (int) $resource->sub,
                'tax'        => (int) $resource->tax,
                'grand'      => (int) $resource->grand,
                'paid'       => (int) $resource->paid,
                'processing' => (int) $resource->processing,
            ]
        );

        $this->formatted = Factory::resource(
            'InvoiceTotalsFormatted',
            Constants::MODULE_SLUG,
            [
                'currency'   => $this->currency,
                'sub'        => (int) $resource->sub,
                'tax'        => (int) $resource->tax,
                'grand'      => (int) $resource->grand,
                'paid'       => (int) $resource->paid,
                'processing' => (int) $resource->processing,
            ]
        );

        unset($this->sub);
        unset($this->tax);
        unset($this->grand);
        unset($this->paid);
        unset($this->processing);
        unset($this->currency);
    }
}
