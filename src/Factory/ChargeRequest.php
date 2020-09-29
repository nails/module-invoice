<?php

/**
 * Attempts a charge
 *
 * @package     Nails
 * @subpackage  module-invoice
 * @category    Factory
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Invoice\Factory;

use DateTime;
use Exception;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Currency;
use Nails\Factory;
use Nails\Invoice\Constants;
use Nails\Invoice\Exception\ChargeRequestException;
use Nails\Invoice\Exception\RequestException;
use Nails\Invoice\Resource\Invoice\Data\Payment;
use stdClass;

/**
 * Class ChargeRequest
 *
 * @package Nails\Invoice\Factory
 */
class ChargeRequest extends RequestBase
{
    /**
     * The Card object
     *
     * @var stdClass
     */
    protected $oCard;

    /**
     * The custom fields object
     *
     * @var stdClass
     */
    protected $oCustomField;

    /**
     * The payment data object
     *
     * @var Payment
     */
    protected $oPaymentData;

    /**
     * The charge description
     *
     * @var string
     */
    protected $sDescription = '';

    /**
     * Whether to honour automatic redirects or not
     *
     * @var bool
     */
    protected $bAutoRedirect = true;

    /**
     * Whether the customer is present during the transaction
     *
     * @var bool
     */
    protected $bCustomerPresent = true;

    /**
     * The amount to charge
     *
     * @var int
     */
    protected $iAmount = 0;

    /**
     * The currency in which to charge
     *
     * @var Currency\Resource\Currency|null
     */
    protected $oCurrency = null;

    // --------------------------------------------------------------------------

    /**
     * ChargeRequest constructor.
     *
     * @throws FactoryException
     */
    public function __construct()
    {
        parent::__construct();

        //  Card details
        $this->oCard = (object) [
            'name'   => '',
            'number' => '',
            'exp'    => (object) [
                'month' => '',
                'year'  => '',
            ],
            'cvc'    => '',
        ];

        //  Container for custom fields and data
        $this->oCustomField = (object) [];
        $this->oPaymentData = (object) [];
    }

    // --------------------------------------------------------------------------

    /**
     * Set the cardholder's name
     *
     * @param string $sCardName The cardholder's name
     *
     * @return $this
     */
    public function setCardName(string $sCardName): ChargeRequest
    {
        $this->oCard->name = $sCardName;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Get the cardholder's Name
     *
     * @return string
     */
    public function getCardName(): string
    {
        return $this->oCard->name;
    }

    // --------------------------------------------------------------------------

    /**
     * Set the card's number
     *
     * @param string $sCardNumber The card's number
     *
     * @throws ChargeRequestException
     * @return $this
     */
    public function setCardNumber(string $sCardNumber): ChargeRequest
    {
        //  Validate
        if (preg_match('/[^\d ]/', $sCardNumber)) {
            throw new ChargeRequestException('Invalid card number; can only contain digits and spaces.', 1);

        }
        $this->oCard->number = $sCardNumber;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Get the card's number
     *
     * @return string
     */
    public function getCardNumber(): string
    {
        return $this->oCard->number;
    }

    // --------------------------------------------------------------------------

    /**
     * Set the card's expiry month
     *
     * @param string $sCardExpMonth The card's expiry month
     *
     * @throws ChargeRequestException
     * @return $this
     */
    public function setCardExpMonth(string $sCardExpMonth): ChargeRequest
    {
        //  Validate
        if (is_numeric($sCardExpMonth)) {

            $iMonth = (int) $sCardExpMonth;
            if ($iMonth < 1 || $iMonth > 12) {

                throw new ChargeRequestException(
                    '"' . $sCardExpMonth . '" is an invalid expiry month; must be in the range 1-12.',
                    1
                );

            } else {
                $this->oCard->exp->month = $iMonth < 10 ? '0' . $iMonth : (string) $iMonth;
                return $this;
            }

        } else {
            throw new ChargeRequestException(
                '"' . $sCardExpMonth . '" is an invalid expiry month; must be numeric.',
                1
            );
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Get the card's expiry month
     *
     * @return string
     */
    public function getCardExpMonth(): string
    {
        return $this->oCard->exp->month;
    }

    // --------------------------------------------------------------------------

    /**
     * Set the card's expiry year
     *
     * @param string $sCardExpYear The card's expiry year
     *
     * @throws ChargeRequestException
     * @throws FactoryException
     * @return $this
     */
    public function setCardExpYear(string $sCardExpYear): ChargeRequest
    {
        //  Validate
        if (is_numeric($sCardExpYear)) {

            //  Accept two digits or 4 digits only
            if (strlen($sCardExpYear) == 2 || strlen($sCardExpYear) == 4) {

                //  Two digit values should be turned into a 4 digit value
                if (strlen($sCardExpYear) == 2) {

                    //  Sorry people living in the 2100's, I'm very sorry everything is broken.
                    $sCardExpYear = '20' . $sCardExpYear;
                }

                $iYear = (int) $sCardExpYear;
                $oNow  = Factory::factory('DateTime');

                if ($oNow->format('Y') > $iYear) {
                    throw new ChargeRequestException(
                        '"' . $sCardExpYear . '" is an invalid expiry year; must be ' . $oNow->format('Y') . ' or later.',
                        1
                    );
                }

                $this->oCard->exp->year = (string) $iYear;
                return $this;

            } else {
                throw new ChargeRequestException(
                    '"' . $sCardExpYear . '" is an invalid expiry year; must be 2 or 4 digits.',
                    1
                );
            }

        } else {
            throw new ChargeRequestException(
                '"' . $sCardExpYear . '" is an invalid expiry year; must be numeric.',
                1
            );
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Get the card's expiry year
     *
     * @return string
     */
    public function getCardExpYear(): string
    {
        return $this->oCard->exp->year;
    }

    // --------------------------------------------------------------------------

    /**
     * Set the card's CVC number
     *
     * @param string $sCardCvc The card's cvc number
     *
     * @return $this
     */
    public function setCardCvc(string $sCardCvc): ChargeRequest
    {
        //  Validate
        $this->oCard->cvc = $sCardCvc;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Get the card's CVC number
     *
     * @return string
     */
    public function getCardCvc(): string
    {
        return $this->oCard->cvc;
    }

    // --------------------------------------------------------------------------

    /**
     * Set custom field data
     *
     * @param string|stdClass $mKey   The key to set, if a stdClass is provided, the entire object is replaced
     * @param mixed|null      $mValue The value to set
     *
     * @return ChargeRequest
     */
    public function setCustomField(string $mKey, $mValue = null): ChargeRequest
    {
        if ($mKey instanceof stdClass) {
            $this->oCustomField = $mKey;
        } else {
            $this->oCustomField->{$mKey} = $mValue;
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Retrieve custom field object
     *
     * @return stdClass
     */
    public function getCustomField(): stdClass
    {
        return $this->oCustomField;
    }

    // --------------------------------------------------------------------------

    /**
     * Set payment data
     *
     * @param string|stdClass $mKey   The key to set, if a stdClass is provided, the entire object is replaced
     * @param mixed|null      $mValue The value to set
     *
     * @return ChargeRequest
     */
    public function setPaymentData($mKey, $mValue = null): ChargeRequest
    {
        if ($mKey instanceof stdClass) {
            $this->oPaymentData = $mKey;
        } else {
            $this->oPaymentData->{$mKey} = $mValue;
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Retrieve payment data object
     *
     * @return Payment
     * @throws FactoryException
     */
    public function getPaymentData(): Payment
    {
        /** @var Payment $oData */
        $oData = Factory::resource('InvoiceDataPayment', Constants::MODULE_SLUG, $this->oPaymentData);
        return $oData;
    }

    // --------------------------------------------------------------------------

    /**
     * Set the description
     *
     * @param string $sDescription The description of the charge
     *
     * @return $this
     */
    public function setDescription(string $sDescription): ChargeRequest
    {
        $this->sDescription = $sDescription;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Get the description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->sDescription;
    }

    // --------------------------------------------------------------------------

    /**
     * Set whether the charge should automatically redirect
     *
     * @param bool $bAutoRedirect Whether to auto redirect or not
     *
     * @return $this
     */
    public function setAutoRedirect(bool $bAutoRedirect): ChargeRequest
    {
        $this->bAutoRedirect = (bool) $bAutoRedirect;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Whether the charge request will automatically redirect in the case of a
     * driver requesting a redirect flow.
     *
     * @return bool
     */
    public function isAutoRedirect(): bool
    {
        return $this->bAutoRedirect;
    }

    // --------------------------------------------------------------------------

    /**
     * Set whether the customer is present during the transaction
     *
     * @param bool $bCustomerPresent Whether the customer is present during the transaction
     *
     * @return $this
     */
    public function setCustomerPresent(bool $bCustomerPresent): ChargeRequest
    {
        $this->bCustomerPresent = (bool) $bCustomerPresent;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Whether the customer is present during the transaction
     *
     * @return bool
     */
    public function isCustomerPresent(): bool
    {
        return $this->bCustomerPresent;
    }

    // --------------------------------------------------------------------------

    /**
     * Set the amount to charge
     *
     * @param int $iAmount The amount to charge
     *
     * @return $this
     * @throws ChargeRequestException
     */
    public function setAmount(int $iAmount): ChargeRequest
    {
        if ($iAmount <= 0) {
            throw new ChargeRequestException('Amount must be positive');
        }
        $this->iAmount = $iAmount;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the amount to charge
     *
     * @return int
     */
    public function getAmount(): int
    {
        return $this->iAmount;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the charge currency
     *
     * @param Currency\Resource\Currency|string $mCurrency The currency in which to charge
     *
     * @return $this
     * @throws ChargeRequestException
     * @throws Currency\Exception\CurrencyException
     * @throws FactoryException
     */
    public function setCurrency($mCurrency): ChargeRequest
    {
        /** @var Currency\Service\Currency $oCurrencyService */
        $oCurrencyService = Factory::service('Currency', Currency\Constants::MODULE_SLUG);

        if (is_string($mCurrency)) {
            $mCurrency = $oCurrencyService->getByIsoCode($mCurrency);
        }

        if (!($mCurrency instanceof Currency\Resource\Currency)) {
            throw new ChargeRequestException('Invalid currency.');
        }

        if (!$oCurrencyService->isSupported($mCurrency)) {
            throw new ChargeRequestException('"' . $mCurrency->code . '"" is not a supported currency.');
        }

        $this->oCurrency = $mCurrency;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the charge currency
     *
     * @return Currency\Resource\Currency|null
     */
    public function getCurrency(): ?Currency\Resource\Currency
    {
        return $this->oCurrency;
    }

    // --------------------------------------------------------------------------

    /**
     * Execute the charge
     *
     * @param int|null                               $iAmount   The amount to charge the card
     * @param Currency\Resource\Currency|string|null $mCurrency The currency in which to charge
     *
     * @return ChargeResponse
     * @throws ChargeRequestException
     * @throws ChargeRequestException\PaymentSourceExpiredException
     * @throws Currency\Exception\CurrencyException
     * @throws FactoryException
     * @throws ModelException
     * @throws RequestException
     * @throws Exception
     */
    public function execute(int $iAmount = null, $mCurrency = null): ChargeResponse
    {
        /**
         * If a specific amount has been passed, use it
         * If the charge amount is empty and an invoice has been applied, assume the outstanding total
         */
        if (null !== $iAmount) {
            $this->setAmount($iAmount);
        } elseif (empty($this->iAmount) && !empty($this->oInvoice)) {

            $iTotal      = $this->oInvoice->totals->raw->grand;
            $iPaid       = $this->oInvoice->totals->raw->paid;
            $iProcessing = $this->oInvoice->totals->raw->processing;

            $this->setAmount(
                $iTotal - $iPaid - $iProcessing
            );
        }

        /**
         * If a specific currency has been passed, use it
         * If the charge currency is empty and an invoice has been passed, assume the invoice's currency
         */
        if (null !== $mCurrency) {
            $this->setCurrency($mCurrency);
        } elseif (empty($this->oCurrency) && !empty($this->oInvoice)) {
            $this->setCurrency($this->oInvoice->currency);
        }

        // --------------------------------------------------------------------------

        if (empty($this->oDriver)) {
            throw new ChargeRequestException('No driver selected.');
        } elseif (empty($this->oInvoice)) {
            throw new ChargeRequestException('No invoice selected.');
        } elseif (empty($this->iAmount)) {
            throw new ChargeRequestException('Amount to charge must be greater than zero.');
        } elseif (empty($this->oCurrency)) {
            throw new ChargeRequestException('No currency selected.');
        }

        // --------------------------------------------------------------------------

        $aDriverCurrencies = $this->oDriver->getSupportedCurrencies();
        if (!empty($aDriverCurrencies) && !in_array($this->oCurrency->code, $aDriverCurrencies)) {
            throw new ChargeRequestException(
                'Selected currency is not supported by payment driver.'
            );
        }

        // --------------------------------------------------------------------------

        if (!empty($this->oSource)) {

            if ($this->oSource->driver !== $this->oDriver->getSlug()) {
                throw new ChargeRequestException(
                    'Selected payment source is incompatible with the selected driver.'
                );
            }

            if (!empty($this->oSource->expiry->raw)) {

                $oNow     = Factory::factory('DateTime');
                $oExpires = new DateTime($this->oSource->expiry->raw);

                if ($oExpires < $oNow) {
                    throw new ChargeRequestException\PaymentSourceExpiredException(
                        'Selected payment source has expired.'
                    );
                }
            }
        }

        // --------------------------------------------------------------------------

        $oPaymentData        = $this->getPaymentData();
        $oInvoicePaymentData = $this->oInvoice->payment_data;
        foreach ($oInvoicePaymentData as $sKey => $mValue) {
            $oPaymentData->{$sKey} = $mValue;
        }

        // --------------------------------------------------------------------------

        //  Create a charge against the invoice if one hasn't been specified
        if (empty($this->oPayment)) {

            $iPaymentId = $this->oPaymentModel->create([
                'driver'           => $this->oDriver->getSlug(),
                'description'      => $this->getDescription(),
                'invoice_id'       => $this->oInvoice->id,
                'source_id'        => !empty($this->oSource) ? $this->oSource->id : null,
                'currency'         => $this->getCurrency()->code,
                'amount'           => $this->getAmount(),
                'url_success'      => $this->getSuccessUrl(),
                'url_error'        => $this->getErrorUrl(),
                'url_cancel'       => $this->getCancelUrl(),
                'custom_data'      => $oPaymentData,
                'customer_present' => $this->isCustomerPresent(),
            ]);

            if (empty($iPaymentId)) {
                throw new ChargeRequestException('Failed to create new payment.');
            }

            $this->setPayment($iPaymentId);
        }

        $mFields = $this->oDriver->getPaymentFields();

        if (!empty($mFields) && $mFields == 'CARD') {
            $oDriverData = $this->oCard;
        } else {
            $oDriverData = $this->oCustomField;
        }

        /**
         * The "success" URL will always be this, this will perform final checks and redirect as necessary
         */
        $sSuccessUrl = siteUrl('invoice/payment/' . $this->oPayment->id . '/' . $this->oPayment->token . '/complete');

        /**
         * The error URL is, by default, the checkout page but can be overridden
         */
        $sErrorUrl = $this->getErrorUrl() ?: siteUrl('invoice/invoice/' . $this->oInvoice->ref . '/' . $this->oInvoice->token . '/pay');

        //  Execute the charge
        $oChargeResponse = $this->oDriver->charge(
            $this->getAmount(),
            $this->getCurrency(),
            $oDriverData,
            $this->getPaymentData(),
            $this->getDescription(),
            $this->getPayment(),
            $this->getInvoice(),
            $sSuccessUrl,
            $sErrorUrl,
            $this->isCustomerPresent(),
            $this->getSource()
        );

        //  Set the payment reference and the success/fail URLs
        $oChargeResponse
            ->setPayment($this->getPayment())
            ->setSuccessUrl($sSuccessUrl)
            ->setErrorUrl($sErrorUrl);

        //  Validate driver response
        if (empty($oChargeResponse)) {
            throw new ChargeRequestException('Response from driver was empty.');
        }

        if (!($oChargeResponse instanceof ChargeResponse)) {
            throw new ChargeRequestException(
                'Response from driver must be an instance of \Nails\Invoice\Factory\ChargeResponse.'
            );
        }

        //  Handle the response
        if ($oChargeResponse->isSca()) {

            /**
             * Payment requires SCA, redirect to handle this
             */

            $sScaData = json_encode($oChargeResponse->getScaData());
            $this->oPaymentModel->update(
                $this->oPayment->id,
                ['sca_data' => $sScaData]
            );

            $sRedirectUrl = siteUrl('invoice/payment/sca/' . $this->oPayment->token . '/' . md5($sScaData));
            if ($this->isAutoRedirect()) {
                redirect($sRedirectUrl);
            } else {
                $oChargeResponse->setScaUrl($sRedirectUrl);
                //  Set the redirect values too, in case dev has not considered SCA
                $oChargeResponse->setIsRedirect(true);
                $oChargeResponse->setRedirectUrl($sRedirectUrl);
            }

        } elseif ($oChargeResponse->isRedirect() && $this->isAutoRedirect()) {

            /**
             * Driver uses a redirect flow, determine whether we can use a basic header redirect,
             * or if we need to POST some data to the endpoint
             */

            $sRedirectUrl = $oChargeResponse->getRedirectUrl();
            $aPostData    = $oChargeResponse->getRedirectPostData();

            if (empty($aPostData)) {

                redirect($sRedirectUrl);

            } else {

                $oView = Factory::service('View');
                echo $oView->load('structure/header/blank', getControllerData(), true);
                echo $oView->load(
                    'invoice/pay/post',
                    [
                        'redirectUrl' => $sRedirectUrl,
                        'postFields'  => $aPostData,
                    ],
                    true
                );
                echo $oView->load('structure/footer/blank', getControllerData(), true);
                exit();
            }

        } elseif ($oChargeResponse->isProcessing()) {

            //  Driver has started processing the charge, but it hasn't been confirmed yet
            $this->setPaymentProcessing(
                $oChargeResponse->getTransactionId(),
                $oChargeResponse->getFee()
            );

        } elseif ($oChargeResponse->isComplete()) {

            //  Driver has confirmed that payment has been taken.
            $this->setPaymentComplete(
                $oChargeResponse->getTransactionId(),
                $oChargeResponse->getFee()
            );

        } elseif ($oChargeResponse->isFailed()) {

            //  Driver reported a failure
            $this->setPaymentFailed(
                $oChargeResponse->getError()->msg,
                $oChargeResponse->getError()->code
            );
        }

        //  Lock the response so it cannot be altered
        $oChargeResponse->lock();

        return $oChargeResponse;
    }
}
