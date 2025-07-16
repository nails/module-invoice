<?php

/**
 * This class represents objects dispensed by the Refund model
 *
 * @package  Nails\Invoice\Resource
 * @category resource
 */

namespace Nails\Invoice\Resource;

use Nails\Common\Exception\FactoryException;
use Nails\Common\Model\Base;
use Nails\Common\Resource\Entity;
use Nails\Currency\Exception\CurrencyException;
use Nails\Currency\Service\Currency;
use Nails\Factory;
use Nails\Invoice\Constants;
use Nails\Invoice\Resource\Refund\Amount;
use Nails\Invoice\Resource\Refund\Status;
use stdClass;

class Refund extends Entity
{
    /**
     * The refund's payment ID
     *
     * @var int
     */
    public $payment_id;

    /** @var Payment */
    public $payment;

    /**
     * The refund's invoice ID
     *
     * @var int
     */
    public $invoice_id;

    /** @var Invoice */
    public $invoice;

    /**
     * The refund's ref
     *
     * @var string
     */
    public $ref;

    /**
     * The refund's reason
     *
     * @var string
     */
    public $reason;

    /**
     * The refund's status
     *
     * @var Status
     */
    public $status;

    /**
     * The refund's transaction ID
     *
     * @var string
     */
    public $transaction_id;

    /**
     * The refund's fail message
     *
     * @var string
     */
    public $fail_msg;

    /**
     * The refund's fail code
     *
     * @var string
     */
    public $fail_code;

    /**
     * @var \Nails\Currency\Resource\Currency
     */
    public $currency;

    /** @var Amount */
    public $amount;

    /** @var Amount */
    public $fee;

    // --------------------------------------------------------------------------

    /**
     * Refund constructor.
     *
     * @throws FactoryException
     * @throws CurrencyException
     */
    public function __construct(self|stdClass|array $resource = [], ?Base = $model = null)
    {
        parent::__construct($resource, $model);

        // --------------------------------------------------------------------------

        /** @var \Nails\Invoice\Model\Refund $oModel */
        $oModel    = Factory::model('Refund', Constants::MODULE_SLUG);
        $aStatuses = $oModel->getStatusesHuman();

        $this->status = Factory::resource(
            'RefundStatus',
            Constants::MODULE_SLUG,
            (object) [
                'id'    => $entity->status,
                'label' => $aStatuses[$entity->status],
            ]
        );

        // --------------------------------------------------------------------------

        //  Currency
        /** @var Currency $oCurrency */
        $oCurrency      = Factory::service('Currency', \Nails\Currency\Constants::MODULE_SLUG);
        $this->currency = $oCurrency->getByIsoCode($entity->currency);

        // --------------------------------------------------------------------------

        //  Amounts and values
        $this->amount = Factory::resource(
            'RefundAmount',
            Constants::MODULE_SLUG,
            (object) [
                'currency' => $this->currency,
                'raw'      => $entity->amount,
            ]
        );

        $this->fee = Factory::resource(
            'RefundAmount',
            Constants::MODULE_SLUG,
            (object) [
                'currency' => $this->currency,
                'raw'      => $entity->fee,
            ]
        );
    }
}
