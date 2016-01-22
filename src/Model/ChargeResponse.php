<?php

/**
 * Charge Response Model
 *
 * @package     Nails
 * @subpackage  module-invoice
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Invoice\Model;

use Nails\Invoice\Exception\ChargeResponseException;

class ChargeResponse
{
    //  Statuses
    const STATUS_PENDING = 'PENDING';
    const STATUS_OK      = 'OK';
    const STATUS_FAIL    = 'FAIL';

    // --------------------------------------------------------------------------

    //  Locked
    protected $bIsLocked;

    //  Status
    protected $sStatus;

    //  Redirect variables
    protected $bIsRedirect;
    protected $sRedirectUrl;
    protected $aRedirectPostData;

    //  Successful charge variables
    protected $sTxnId;

    //  Urls
    protected $sSuccessUrl;
    protected $sFailUrl;

    //  Errors
    protected $sErrorMsg;
    protected $sErrorCode;
    protected $sErrorUser;

    // --------------------------------------------------------------------------

    /**
     * Construct the model
     */
    public function __construct()
    {
        $this->sStatus     = self::STATUS_PENDING;
        $this->bIsRedirect = false;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the current status of the charge request
     * @return string
     */
    public function setStatus($sStatus)
    {
        if (!$this->bIsLocked) {

            if (!in_array($sStatus, array(self::STATUS_PENDING, self::STATUS_OK, self::STATUS_FAIL))) {
                throw new ChargeResponseException('"' . $sStatus . '" is an invalid charge response status.', 1);
            }

            $this->sStatus = $sStatus;
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Set the status as pending
     */
    public function setStatusPending()
    {
        return $this->setStatus(self::STATUS_PENDING);
    }

    // --------------------------------------------------------------------------

    /**
     * Set the status as OK
     */
    public function setStatusOk()
    {
        return $this->setStatus(self::STATUS_OK);
    }

    // --------------------------------------------------------------------------

    /**
     * Set the status as failed
     * @param string $sReasonMsg    The exception message, logged against the payment and not shown to the customer
     * @param integr $iReasonCode   The exception code, logged against the payment and not shown to the customer
     * @param string $sUserFeedback The message to show to the user explaining the error
     */
    public function setStatusFail($sReasonMsg, $iReasonCode, $sUserFeedback = '')
    {
        $this->sErrorMsg  = trim($sReasonMsg);
        $this->sErrorCode = (int) $iReasonCode;
        $this->sErrorUser = !empty($sUserFeedback) ? trim($sUserFeedback) : $this->sErrorMsg;
        return $this->setStatus(self::STATUS_FAIL);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the current status of the charge request
     * @return string
     */
    public function getStatus()
    {
        return $this->sStatus;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns if the request was successful
     * @return boolean
     */
    public function isOk()
    {
        return $this->getStatus() == self::STATUS_OK;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns if the request is pending
     * @return boolean
     */
    public function isPending()
    {
        return $this->getStatus() == self::STATUS_PENDING;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns if the request failed
     * @return boolean
     */
    public function isFail()
    {
        return $this->getStatus() == self::STATUS_FAIL;
    }

    // --------------------------------------------------------------------------

    /**
     * Return the error messages
     * @return \stdClass
     */
    public function getError()
    {
        $oOut       = new \stdClass();
        $oOut->msg  = $this->sErrorMsg;
        $oOut->code = $this->sErrorCode;
        $oOut->user = $this->sErrorUser;

        return $oOut;
    }

    // --------------------------------------------------------------------------

    /**
     * Whether the response is a redirect
     * @return boolean
     */
    public function isRedirect() {
        return $this->bIsRedirect;
    }

    // --------------------------------------------------------------------------

    /**
     * Set whether the response is a redirect
     * @param boolean $bIsRedirect Whether the response is a redirect
     */
    protected function setIsRedirect($bIsRedirect)
    {
        if (!$this->bIsLocked) {
            $this->bIsRedirect = (bool) $bIsRedirect;
        }
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Set the redirectUrl value
     * @param string $sRedirectUrl The Redirect URL
     */
    public function setRedirectUrl($sRedirectUrl)
    {
        if (!$this->bIsLocked) {
            $this->sRedirectUrl = $sRedirectUrl;
            $this->setIsRedirect(!empty($sRedirectUrl));
        }
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * The URL to redirect to
     * @return string
     */
    public function getRedirectUrl() {
        return $this->sRedirectUrl;
    }

    // --------------------------------------------------------------------------

    /**
     * Set the sSuccessUrl value
     * @param string $sSuccessUrl The URL to go to on successful payment
     */
    public function setSuccessUrl($sSuccessUrl)
    {
        if (!$this->bIsLocked) {
            $this->sSuccessUrl = $sSuccessUrl;
        }
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * The URL to redirect to on failed payment
     * @return string
     */
    public function getFailUrl() {
        return $this->sFailUrl;
    }

    // --------------------------------------------------------------------------

    /**
     * Set the sFailUrl value
     * @param string $sFailUrl The URL to go to on failed payment
     */
    public function setFailUrl($sFailUrl)
    {
        if (!$this->bIsLocked) {
            $this->sFailUrl = $sFailUrl;
        }
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * The URL to redirect to on successsful payment
     * @return string
     */
    public function getSuccessUrl() {
        return $this->sSuccessUrl;
    }

    // --------------------------------------------------------------------------

    /**
     * Set any data which should be POST'ed to the endpoint
     * @param array $aRedirectPostData The data to post
     */
    public function setRedirectPostData($aRedirectPostData)
    {
        if (!$this->bIsLocked) {
            $this->aRedirectPostData = $aRedirectPostData;
            $this->setIsRedirect(!empty($aRedirectPostData));
        }
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Any data which should be POST'ed to the endpoint
     * @return string
     */
    public function getRedirectPostData() {
        return $this->aRedirectPostData;
    }

    // --------------------------------------------------------------------------

    /**
     * Set the transaction ID
     * @param string $sTxnId The transaction ID
     */
    public function setTxnId($sTxnId)
    {
        if (!$this->bIsLocked) {
            $this->sTxnId = $sTxnId;
        }
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * The transaction ID
     * @return string
     */
    public function getTxnId() {
        return $this->sTxnId;
    }

    // --------------------------------------------------------------------------

    /**
     * Prevent the object from being altered
     * @return object
     */
    public function lock()
    {
        $this->bIsLocked = true;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Whether the response is locked
     * @return boolean
     */
    public function isLocked() {
        return $this->bIsLocked;
    }
}
