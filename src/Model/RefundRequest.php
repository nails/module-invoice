<?php

/**
 * Refund Request Model
 *
 * @package     Nails
 * @subpackage  module-invoice
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Invoice\Model;

use Nails\Factory;
use Nails\Invoice\Model\RequestBase;
use Nails\Invoice\Exception\RefundRequestException;

class RefundRequest extends RequestBase
{
    protected $sReason;

    // --------------------------------------------------------------------------

    /**
     * Set the reason
     * @param string $sReason The reason of the charge
     */
    public function setReason($sReason)
    {
        $this->sReason = $sReason;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Get the reason
     * @return string
     */
    public function getReason()
    {
        return $this->sReason;
    }

    // --------------------------------------------------------------------------

    /**
     * Execute the refund
     * @param  integer $iAmount The amount to refund
     * @return \Nails\Invoice\Model\ChargeResponse
     */
    public function execute($iAmount)
    {
        //  Ensure we have a driver
        if (empty($this->oDriver)) {
            throw new RefundRequestException('No driver selected.', 1);
        }

        //  Ensure we have a payment
        if (empty($this->oPayment)) {
            throw new RefundRequestException('No payment selected.', 1);
        }

        //  Ensure we have an invoice
        if (empty($this->oInvoice)) {
            $this->setInvoice($this->oPayment->invoice->id);
        }

        //  Validate ability to refund
        if (!$this->oPayment->is_refundable) {

            //  Why?
            if ($this->oPayment->available_for_refund->base === 0) {

                throw new RefundRequestException('Payment is already fully refunded.', 1);
            } else {

                throw new RefundRequestException('Payment is not in a state where it can be refunded.', 1);
            }
        }

        $iRefundAmount = is_null($iAmount) ? $this->oPayment->available_for_refund->base : $iAmount;

        if (empty($iRefundAmount)) {
            throw new RefundRequestException('Refund amount must be greater than 0.', 1);
        }

        //  Validate refund amount
        if ($iRefundAmount > $this->oPayment->available_for_refund->base) {
            throw new RefundRequestException(
                'Requested refund amount is greater than the value of the remaining payment balance (' .
                $this->oPayment->available_for_refund->localised_formatted . ').',
                1
            );
        }

        //  Create a refund against the payment if one hasn't been specified
        if (empty($this->oRefund)) {

            $iRefundId = $this->oRefundModel->create(
                array(
                    'reason'     => $this->getReason(),
                    'payment_id' => $this->oPayment->id,
                    'invoice_id' => $this->oInvoice->id,
                    'currency'   => $this->oPayment->currency,
                    'amount'     => $iRefundAmount
                )
            );

            if (empty($iRefundId)) {
                throw new RefundRequestException('Failed to create new refund.', 1);
            }

            $this->setRefund($iRefundId);
        }

        //  Execute the refund
        $oRefundResponse = $this->oDriver->refund(
            $this->oPayment->txn_id,
            $iAmount,
            $this->oPayment->currency,
            $this->oPayment->custom_data,
            $this->getReason(),
            $this->oPayment,
            $this->oInvoice
        );

        //  Validate driver response
        if (empty($oRefundResponse)) {
            throw new RefundRequestException('Response from driver was empty.', 1);
        }

        if (!($oRefundResponse instanceof \Nails\Invoice\Model\RefundResponse)) {
            throw new RefundRequestException(
                'Response from driver must be an instance of \Nails\Invoice\Model\RefundResponse.',
                1
            );
        }

        if ($oRefundResponse->isComplete()) {

            //  Driver has confirmed that the refund was accepted
            $this->setRefundComplete(
                $oRefundResponse->getTxnId(),
                $oRefundResponse->getFee()
            );


        } elseif ($oRefundResponse->isFailed()) {

            //  Update the payment
            $sRefundClass = get_class($this->oRefundModel);
            $bResult             = $this->oRefundModel->update(
                $this->oRefund->id,
                array(
                    'status'    => $sRefundClass::STATUS_FAILED,
                    'fail_msg'  => $oRefundResponse->getError()->msg,
                    'fail_code' => $oRefundResponse->getError()->code
                )
            );

            if (empty($bResult)) {
                throw new ChargeRequestException('Failed to update existing payment.', 1);
            }
        }

        //  Lock the response so it cannot be altered
        $oRefundResponse->lock();

        return $oRefundResponse;
    }
}
