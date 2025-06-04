<?php

/**
 * Payment driver base
 *
 * @package     Nails
 * @subpackage  module-invoice
 * @category    Interface
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Invoice\Driver;

use Nails\Common\Driver\Base;
use Nails\Currency\Resource\Currency;
use Nails\Invoice\Exception\ChargeRequestException;
use Nails\Invoice\Exception\DriverException;
use Nails\Invoice\Factory\ChargeRequest;
use Nails\Invoice\Interfaces\Driver\Payment;

/**
 * Class PaymentBase
 *
 * @package Nails\Invoice\Driver
 */
abstract class PaymentBase extends Base implements Payment
{
    /**
     * Shortcut for requiring basic card details
     *
     * @var string
     */
    const PAYMENT_FIELDS_CARD = 'CARD';

    // --------------------------------------------------------------------------

    /**
     * Determines whether the driver supports the specified currency
     *
     * @param Currency|string $mCurrency The currency
     *
     * @return bool
     * @throws DriverException
     */
    public function supportsCurrency($mCurrency): bool
    {
        $aSupported = $this->getSupportedCurrencies();
        if (is_null($aSupported)) {
            throw new DriverException('Currency support not configured for driver "' . $this->getSlug() . '"');

        } elseif (empty($aSupported)) {
            //  If not defined assume support for all currencies
            return true;

        } elseif ($mCurrency instanceof Currency) {
            return in_array($mCurrency->code, $aSupported);

        } elseif (is_string($mCurrency)) {
            return in_array($mCurrency, $aSupported);
        }

        throw new DriverException(
            'Argument must be an instant of ' . Currency::class . ' or a string'
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Prepares a ChargeRequest object
     *
     * @param ChargeRequest $oChargeRequest The ChargeRequest object to prepare
     * @param array         $aData          Any data which was requested by getPaymentFields()
     *
     * @throws ChargeRequestException
     */
    public function prepareChargeRequest(
        ChargeRequest $oChargeRequest,
        array $aData
    ): void {

        $mPaymentFields = $this->getPaymentFields();

        if (is_string($mPaymentFields) && $mPaymentFields == static::PAYMENT_FIELDS_CARD) {

            $this->setChargeRequestCardDetails(
                $oChargeRequest,
                $aData
            );

        } elseif (is_array($mPaymentFields)) {
            $this->setChargeRequestFields(
                $oChargeRequest,
                $aData,
                $mPaymentFields
            );
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the card details of a ChargeRequest
     *
     * @param ChargeRequest $oChargeRequest The ChargeRequest object
     * @param array         $aData
     *
     * @throws ChargeRequestException
     */
    protected function setChargeRequestCardDetails(ChargeRequest $oChargeRequest, array $aData): void
    {
        $aCard = getFromArray('card', $aData, []);

        $sName = getFromArray('name', $aCard);
        $sNum  = getFromArray('number', $aCard);
        $sExp  = getFromArray('expire', $aCard);
        $sCvc  = getFromArray('cvc', $aCard);

        $aExp   = explode('/', $sExp);
        $aExp   = array_map('trim', $aExp);
        $sMonth = !empty($aExp[0]) ? $aExp[0] : null;
        $sYear  = !empty($aExp[1]) ? $aExp[1] : null;

        $oChargeRequest
            ->setCardName($sName)
            ->setCardNumber($sNum)
            ->setCardExpMonth($sMonth)
            ->setCardExpYear($sYear)
            ->setCardCvc($sCvc);
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the values of the payment fields
     *
     * @param ChargeRequest $oChargeRequest The ChargeRequest object
     * @param array         $aData          The data sent to the driver
     * @param array|null    $aFields        The payment fields
     */
    protected function setChargeRequestFields(
        ChargeRequest $oChargeRequest,
        array $aData,
        ?array $aFields = null
    ) {

        if (is_null($aFields)) {
            $aFields = $this->getPaymentFields();
        }

        $oPaymentData = (object) [];

        foreach ($aFields as $aField) {

            $sKey   = getFromArray('key', $aField);
            $sValue = getFromArray($sKey, $aData);

            $oChargeRequest
                ->setPaymentData($sKey, $sValue);
        }
    }
}
