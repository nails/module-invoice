<?php

/**
 * Checkout SCA
 *
 * @package     Nails
 * @subpackage  module-invoice
 * @category    controller
 * @author      Nails Dev Team
 * @link
 */

use Nails\Auth\Service\Session;
use Nails\Common\Service\Uri;
use Nails\Factory;
use Nails\Invoice\Controller\Base;
use Nails\Invoice\Exception\InvoiceException;
use Nails\Invoice\Factory\ScaRequest;
use Nails\Invoice\Service\PaymentDriver;

/**
 * Class Sca
 */
class Sca extends Base
{
    public function index()
    {
        /** @var Uri $oUri */
        $oUri = Factory::service('Uri');
        /** @var \Nails\Invoice\Model\Payment $oPaymentModel */
        $oPaymentModel = Factory::model('Payment', 'nails/module-invoice');
        /** @var PaymentDriver $oPaymentDriverService */
        $oPaymentDriverService = Factory::service('PaymentDriver', 'nails/module-invoice');

        $oPayment = $oPaymentModel->getByToken($oUri->segment(4), ['expand' => ['invoice']]);
        if (empty($oPayment) || md5($oPayment->sca_data) !== $oUri->segment(5)) {
            show404();
        }

        // --------------------------------------------------------------------------

        /** @var ScaRequest $oScaRequest */
        $oScaRequest = Factory::factory('ScaRequest', 'nails/module-invoice');

        $oScaRequest->setPayment($oPayment->id);
        $oScaRequest->setInvoice($oPayment->invoice->id);
        $oScaRequest->setDriver($oPayment->driver->slug);

        $oScaResponse = $oScaRequest->execute();

        if ($oScaResponse->isComplete()) {

            if (!empty($oPayment->urls->continue)) {
                redirect($oPayment->urls->continue);
            } else {
                redirect($oPayment->urls->thanks);
            }

        } elseif ($oScaResponse->isRedirect()) {

            redirect($oScaResponse->getRedirectUrl());

        } elseif ($oScaResponse->isFail()) {

            /** @var Session $oSession */
            $oSession = Factory::service('Session', 'nails/module-auth');
            $oSession->setFlashData('error', $oScaResponse->getError());

            redirect($oPayment->invoice->urls->payment);

        } else {
            throw new InvoiceException('Unhandled SCA status');
        }
    }
}