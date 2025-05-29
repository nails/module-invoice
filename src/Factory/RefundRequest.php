<?php

/**
 * Refund Request
 *
 * @package     Nails
 * @subpackage  module-invoice
 * @category    Factory
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Invoice\Factory;

use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Invoice\Exception\RefundRequestException;
use Nails\Invoice\Exception\RequestException;

/**
 * Class RefundRequest
 *
 * @package Nails\Invoice\Factory
 */
class RefundRequest extends RequestBase
{
    protected ?string $sReason;
    protected int     $iAmount;

    // --------------------------------------------------------------------------

    /**
     * Set the reason
     *
     * @param string|null $sReason The reason of the charge
     *
     * @return $this
     */
    public function setReason(?string $sReason)
    {
        $this->sReason = $sReason;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Get the reason
     *
     * @return string|null
     */
    public function getReason(): ?string
    {
        return $this->sReason;
    }

    // --------------------------------------------------------------------------

    /**
     * Get the amount
     *
     * @return int|null
     */
    public function getAmount(): ?int
    {
        return $this->iAmount;
    }

    // --------------------------------------------------------------------------

    /**
     * Set the amount
     *
     * @param int|null $iAmount The reason of the charge
     *
     * @return $this
     */
    public function setAmount(?int $iAmount)
    {
        $this->iAmount = $iAmount;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Execute the refund
     *
     * @param int $iAmount The amount to refund
     *
     * @return RefundResponse
     * @throws RefundRequestException
     * @throws FactoryException
     * @throws ModelException
     * @throws RequestException
     */
    public function execute(?int $iAmount = null)
    {
        $iAmount  = $iAmount ?? $this->getAmount();
        $oPayment = $this->getPayment();

        //  Ensure we have a payment
        if (empty($oPayment)) {
            throw new RefundRequestException('No payment selected.');
        }

        //  Ensure we have a driver
        if (empty($this->oDriver)) {
            $this->setDriver($oPayment->driver);
        }

        if (empty($this->oDriver)) {
            throw new RefundRequestException('No driver selected.');
        }

        //  Ensure we have an invoice
        if (empty($this->oInvoice)) {
            $this->setInvoice($oPayment->invoice());
        }

        //  Validate ability to refund
        if (!$oPayment->is_refundable) {
            if ($oPayment->available_for_refund->raw === 0) {
                throw new RefundRequestException('Payment is already fully refunded.');
            } else {
                throw new RefundRequestException('Payment is not in a state where it can be refunded.');
            }
        }

        $iRefundAmount = is_null($iAmount) ? $oPayment->available_for_refund->raw : $iAmount;

        if (empty($iRefundAmount)) {
            throw new RefundRequestException('Refund amount must be greater than 0.');
        }

        //  Validate refund amount
        if ($iRefundAmount > $oPayment->available_for_refund->raw) {
            throw new RefundRequestException(sprintf(
                'Requested refund amount is greater than the value of the remaining payment balance (%s)',
                $oPayment->available_for_refund->formatted
            ));
        }

        //  Create a refund against the payment if one hasn't been specified
        if (empty($this->oRefund)) {

            $oRefund = $this->oRefundModel->create([
                'reason'     => $this->getReason(),
                'payment_id' => $this->getPayment()->id,
                'invoice_id' => $this->getInvoice()->id,
                'currency'   => $this->getPayment()->currency->code,
                'amount'     => $iRefundAmount,
            ], true);

            if (empty($oRefund)) {
                throw new RefundRequestException('Failed to create new refund.');
            }

            $this->setRefund($oRefund);
        }

        //  Execute the refund
        $oRefundResponse = $this->oDriver->refund(
            $oPayment->transaction_id,
            $iRefundAmount,
            $oPayment->currency,
            $oPayment->custom_data,
            $this->getReason(),
            $oPayment,
            $this->getRefund(),
            $this->getInvoice()
        );

        if (!$oRefundResponse instanceof RefundResponse) {
            throw new RefundRequestException(sprintf(
                'Response from driver must be an instance of %s, received %s.',
                \Nails\Invoice\Factory\RefundResponse::class,
                gettype($oRefundResponse)
            ));
        }

        if ($oRefundResponse->isComplete()) {

            //  Driver has confirmed that the refund was accepted
            $this->setRefundComplete(
                $oRefundResponse->getTransactionId(),
                $oRefundResponse->getFee()
            );

        } elseif ($oRefundResponse->isFailed()) {

            //  Update the payment
            $sRefundClass = get_class($this->oRefundModel);
            $bResult      = $this->oRefundModel->update(
                $this->oRefund->id,
                [
                    'status'    => $sRefundClass::STATUS_FAILED,
                    'fail_msg'  => $oRefundResponse->getError()->msg,
                    'fail_code' => $oRefundResponse->getError()->code,
                ]
            );

            if (empty($bResult)) {
                throw new RefundRequestException('Failed to update existing payment.');
            }
        }

        //  Lock the response so it cannot be altered
        $oRefundResponse->lock();

        return $oRefundResponse;
    }
}
