<?php

/**
 * Attempts a SCA authorisation
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
use Nails\Factory;
use Nails\Invoice\Constants;
use Nails\Invoice\Exception\RequestException;
use Nails\Invoice\Exception\ScaRequestException;
use Throwable;

/**
 * Class ScaRequest
 *
 * @package Nails\Invoice\Factory
 */
class ScaRequest extends RequestBase
{
    /**
     * Executes the SCA request
     *
     * @return ScaResponse
     * @throws FactoryException
     * @throws ModelException
     * @throws RequestException
     */
    public function execute(): ScaResponse
    {
        /** @var ScaResponse $oScaResponse */
        $oScaResponse = Factory::factory('ScaResponse', Constants::MODULE_SLUG);
        /** @var \Nails\Invoice\Factory\ChargeRequest $oChargeRequest */
        $oChargeRequest = Factory::factory('ChargeRequest', Constants::MODULE_SLUG);

        if ($this->getPayment()->hasBeenProcessed()) {
            $bSkipPaymentUpdate = true;
            $oScaResponse
                ->setStatusFailed(
                    'Payment is not in a `pending` or `sent for authentication` state',
                    null,
                    'Payment has already been processed'
                );

        } else {
            try {
                $oScaResponse = $this->oDriver->sca(
                    $oScaResponse,
                    $this->oPayment->sca_data,
                    $oChargeRequest::compileScaUrl($this->oPayment, $this->oPayment->sca_data)
                );
            } catch (Throwable $e) {
                /**
                 * Something unexpected happened within the driver, we do not want this error reaching
                 * the user. Ensure that we gracefully handle the exception so the user sees something
                 * which isn't a 500. Log the real exception for debugging purposes.
                 */
                $oScaResponse->setStatusFailed(
                    $oScaResponse->getErrorMessage(),
                    $oScaResponse->getErrorCode(),
                    'Failed to authorise the payment.'
                );
                $this->setPaymentFailed(
                    $oScaResponse->getErrorMessage(),
                    $oScaResponse->getErrorCode()
                );
            }
        }

        $oScaResponse->lock();

        // --------------------------------------------------------------------------

        if ($oScaResponse->isComplete()) {
            $this->setPaymentComplete(
                $oScaResponse->getTransactionId(),
                $oScaResponse->getFee()
            );
        } elseif ($oScaResponse->isFailed() && empty($bSkipPaymentUpdate)) {
            $this->setPaymentFailed(
                $oScaResponse->getErrorMessage(),
                $oScaResponse->getErrorCode()
            );
        }

        // --------------------------------------------------------------------------

        return $oScaResponse;
    }
}
