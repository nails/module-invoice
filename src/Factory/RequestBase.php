<?php

/**
 * Base Request
 *
 * @package     Nails
 * @subpackage  module-invoice
 * @category    Factory
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Invoice\Factory;

use Nails\Factory;
use Nails\Invoice\Exception\RequestException;

class RequestBase
{
    protected $oDriver;
    protected $oDriverModel;
    protected $oInvoice;
    protected $oInvoiceModel;
    protected $oPayment;
    protected $oPaymentModel;
    protected $oRefund;
    protected $oRefundModel;
    protected $oPaymentEventHandler;

    // --------------------------------------------------------------------------

    /**
     * Construct the request
     */
    public function __construct()
    {
        $this->oDriverModel         = Factory::model('PaymentDriver', 'nailsapp/module-invoice');
        $this->oInvoiceModel        = Factory::model('Invoice', 'nailsapp/module-invoice');
        $this->oPaymentModel        = Factory::model('Payment', 'nailsapp/module-invoice');
        $this->oRefundModel         = Factory::model('Refund', 'nailsapp/module-invoice');
        $this->oPaymentEventHandler = Factory::model('PaymentEventHandler', 'nailsapp/module-invoice');
    }

    // --------------------------------------------------------------------------

    /**
     * Set the driver to be used for the request
     *
     * @param string $sDriverSlug The driver's slug
     *
     * @return $this
     * @throws RequestException
     */
    public function setDriver($sDriverSlug)
    {
        //  Validate the driver
        $aDrivers = $this->oDriverModel->getEnabled();
        $oDriver  = null;

        foreach ($aDrivers as $oDriverConfig) {
            if ($oDriverConfig->slug == $sDriverSlug) {
                $oDriver = $this->oDriverModel->getInstance($oDriverConfig->slug);
                break;
            }
        }

        if (empty($oDriver)) {
            throw new RequestException('"' . $sDriverSlug . '" is not a valid payment driver.', 1);
        }

        $this->oDriver = $oDriver;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Set the invoice object
     *
     * @param integer $iInvoiceId The invoice to use for the request
     *
     * @return $this
     * @throws RequestException
     */
    public function setInvoice($iInvoiceId)
    {
        //  Validate
        $oInvoice = $this->oInvoiceModel->getById($iInvoiceId, ['includeAll' => true]);

        if (empty($oInvoice)) {
            throw new RequestException('Invalid invoice ID.', 1);
        }

        $this->oInvoice = $oInvoice;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Set the payment object
     *
     * @param integer $iPaymentId The payment to use for the request
     *
     * @return $this
     * @throws RequestException
     */
    public function setPayment($iPaymentId)
    {
        //  Validate
        $oPayment = $this->oPaymentModel->getById($iPaymentId, ['includeInvoice' => true]);

        if (empty($oPayment)) {
            throw new RequestException('Invalid payment ID.', 1);
        }

        $this->oPayment = $oPayment;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Set the refund  object
     *
     * @param integer $iRefundId The refund to use for the request
     *
     * @return $this
     * @throws RequestException
     */
    public function setRefund($iRefundId)
    {
        //  Validate
        $oRefund = $this->oRefundModel->getById($iRefundId);

        if (empty($oRefund)) {
            throw new RequestException('Invalid refund ID.', 1);
        }

        $this->oRefund = $oRefund;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Set a payment as PROCESSING
     *
     * @param string  $sTxnId The payment's transaction ID
     * @param integer $iFee   The fee charged by the processor, if known
     *
     * @return $this
     * @throws RequestException
     */
    protected function setPaymentProcessing($sTxnId = null, $iFee = null)
    {
        //  Ensure we have a payment
        if (empty($this->oPayment)) {
            throw new RequestException('No payment selected.', 1);
        }

        //  Update the payment
        $aData = ['txn_id' => $sTxnId ? $sTxnId : null];

        if (!is_null($iFee)) {
            $aData['fee'] = $iFee;
        }

        if (!$this->oPaymentModel->setComplete($this->oPayment->id, $aData)) {
            throw new RequestException('Failed to update existing payment.', 1);
        }

        //  Has the invoice been paid in full? If so, mark it as paid and fire the invoice.paid.processing event
        if ($this->oInvoiceModel->isPaid($this->oInvoice->id, true)) {

            //  Mark Invoice as PAID_PROCESSING
            if (!$this->oInvoiceModel->setPaidProcessing($this->oInvoice->id)) {
                throw new RequestException('Failed to mark invoice as paid (processing).', 1);
            }
        }

        //  Send receipt email
        $this->oPaymentModel->sendReceipt($this->oPayment->id);
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Set a payment as COMPLETE, and mark the invoice as paid if so
     *
     * @param string  $sTxnId The payment's transaction ID
     * @param integer $iFee   The fee charged by the processor, if known
     *
     * @return $this
     * @throws RequestException
     */
    protected function setPaymentComplete($sTxnId = null, $iFee = null)
    {
        //  Ensure we have a payment
        if (empty($this->oPayment)) {
            throw new RequestException('No payment selected.', 1);
        }

        //  Ensure we have an invoice
        if (empty($this->oInvoice)) {
            throw new RequestException('No invoice selected.', 1);
        }

        //  Update the payment
        $aData = ['txn_id' => $sTxnId ? $sTxnId : null];

        if (!is_null($iFee)) {
            $aData['fee'] = $iFee;
        }

        if (!$this->oPaymentModel->setComplete($this->oPayment->id, $aData)) {
            throw new RequestException('Failed to update existing payment.', 1);
        }

        //  Has the invoice been paid in full? If so, mark it as paid and fire the invoice.paid event
        if ($this->oInvoiceModel->isPaid($this->oInvoice->id)) {

            //  Mark Invoice as PAID
            if (!$this->oInvoiceModel->setPaid($this->oInvoice->id)) {
                throw new RequestException('Failed to mark invoice as paid.', 1);
            }
        }

        //  Send receipt email
        $this->oPaymentModel->sendReceipt($this->oPayment->id);

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Set a refund as COMPLETE
     *
     * @param string  $sTxnId       The refund's transaction ID
     * @param integer $iFeeRefunded The fee refunded by the processor, if known
     *
     * @return $this
     * @throws RequestException
     */
    protected function setRefundComplete($sTxnId = null, $iFeeRefunded = null)
    {
        //  Ensure we have a payment
        if (empty($this->oRefund)) {
            throw new RequestException('No refund selected.', 1);
        }

        //  Update the refund
        $aData = ['txn_id' => $sTxnId ? $sTxnId : null];

        if (!is_null($iFeeRefunded)) {
            $aData['fee'] = $iFeeRefunded;
        }

        if (!$this->oRefundModel->setComplete($this->oRefund->id, $aData)) {
            throw new RequestException('Failed to update existing refund.', 1);
        }

        // Update the associated payment, if the payment is fully refunded then mark it so
        $oPayment = $this->oPaymentModel->getById($this->oRefund->payment_id);
        if ($oPayment->available_for_refund->raw > 0) {
            $this->oPaymentModel->setRefundedPartial($oPayment->id);
        } else {
            $this->oPaymentModel->setRefunded($oPayment->id);
        }

        //  Send receipt email
        $this->oRefundModel->sendReceipt($this->oRefund->id);

        return $this;
    }
}