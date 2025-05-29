<?php

/**
 * Base Response
 *
 * @package     Nails
 * @subpackage  module-invoice
 * @category    Factory
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Invoice\Factory;

use Nails\Invoice\Exception\ResponseException;
use stdClass;

/**
 * Class ResponseBase
 *
 * @package Nails\Invoice\Factory
 */
class ResponseBase
{
    //  Statuses; these are in line with the Payment statuses
    const STATUS_PENDING    = 'PENDING';
    const STATUS_PROCESSING = 'PROCESSING';
    const STATUS_COMPLETE   = 'COMPLETE';
    const STATUS_FAILED     = 'FAILED';

    // --------------------------------------------------------------------------

    /**
     * Whether the response is locked
     *
     * @var bool
     */
    protected bool $bIsLocked = false;

    /**
     * The response's status
     *
     * @var string
     */
    protected string $sStatus = self::STATUS_PENDING;

    /**
     * The error message
     *
     * @var string
     */
    protected string $sErrorMessage = '';

    /**
     * The user-friendly error message
     *
     * @var string
     */
    protected string $sErrorMessageUser = '';

    /**
     * The error code
     *
     * @var string
     */
    protected string $sErrorCode = '';

    /**
     * The transaction ID
     *
     * @var string
     */
    protected string $sTransactionId = '';

    /**
     * The fee associated with the transaction
     *
     * @var int
     */
    protected int $iFee = 0;

    /**
     * The URL to redirect to when successful
     *
     * @var string
     */
    protected string $sSuccessUrl = '';

    /**
     * The URL to redirect to in event of an error
     *
     * @var string
     */
    protected string $sErrorUrl = '';

    // --------------------------------------------------------------------------

    /**
     * Returns all the statuses as an array
     *
     * @return string[]
     */
    public function getStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PROCESSING,
            self::STATUS_COMPLETE,
            self::STATUS_FAILED,
        ];
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of statuses with human friendly labels
     *
     * @return string[]
     */
    public function getStatusesHuman(): array
    {
        return [
            self::STATUS_PENDING    => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_COMPLETE   => 'Complete',
            self::STATUS_FAILED     => 'Failed',
        ];
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the current status of the response
     *
     * @param string $sStatus The status to set
     *
     * @return $this
     * @throws ResponseException
     */
    public function setStatus(string $sStatus): ResponseBase
    {
        if ($this->isLocked()) {
            throw new ResponseException('Response is locked and cannot be modified');
        }

        $aStatuses = $this->getStatuses();
        if (!in_array($sStatus, $aStatuses)) {
            throw new ResponseException('"' . $sStatus . '" is an invalid response status.', 1);
        }

        $this->sStatus = $sStatus;

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Set the status as PENDING
     *
     * @return $this
     * @throws ResponseException
     */
    public function setStatusPending(): ResponseBase
    {
        if ($this->isLocked()) {
            throw new ResponseException('Response is locked and cannot be modified');
        }

        return $this->setStatus(self::STATUS_PENDING);
    }

    // --------------------------------------------------------------------------

    /**
     * Set the status as PROCESSING
     *
     * @return $this
     * @throws ResponseException
     */
    public function setStatusProcessing(): ResponseBase
    {
        if ($this->isLocked()) {
            throw new ResponseException('Response is locked and cannot be modified');
        }

        return $this->setStatus(self::STATUS_PROCESSING);
    }

    // --------------------------------------------------------------------------

    /**
     * Set the status as COMPLETE
     *
     * @return $this
     * @throws ResponseException
     */
    public function setStatusComplete(): ResponseBase
    {
        if ($this->isLocked()) {
            throw new ResponseException('Response is locked and cannot be modified');
        }

        return $this->setStatus(self::STATUS_COMPLETE);
    }

    // --------------------------------------------------------------------------

    /**
     * Set the status as FAILED
     *
     * @param string|null $sReasonMsg    The exception message, logged against the payment and not shown to the customer
     * @param string|null $sReasonCode   The exception code, logged against the payment and not shown to the customer
     * @param string      $sUserFeedback The message to show to the user explaining the error
     *
     * @return $this
     * @throws ResponseException
     */
    public function setStatusFailed(
        ?string $sReasonMsg = null,
        ?string $sReasonCode = null,
        string $sUserFeedback = ''
    ): ResponseBase {

        if ($this->isLocked()) {
            throw new ResponseException('Response is locked and cannot be modified');
        }

        $this->setErrorMessage(trim((string) $sReasonMsg));
        $this->setErrorCode(trim((string) $sReasonCode));
        $this->setErrorMessageUser(trim($sUserFeedback));

        return $this->setStatus(self::STATUS_FAILED);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the current status of the response
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->sStatus;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns if the request is pending
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->getStatus() === self::STATUS_PENDING;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns if the request was successful, but is still in a processing state
     *
     * @return bool
     */
    public function isProcessing(): bool
    {
        return $this->getStatus() === self::STATUS_PROCESSING;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns if the request was successful and completed fully
     *
     * @return bool
     */
    public function isComplete(): bool
    {
        return $this->getStatus() === self::STATUS_COMPLETE;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns if the request failed
     *
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->getStatus() === self::STATUS_FAILED;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the error message
     *
     * @param string $sValue
     *
     * @return $this
     * @throws ResponseException
     */
    public function setErrorMessage(string $sValue): ResponseBase
    {
        if ($this->isLocked()) {
            throw new ResponseException('Response is locked and cannot be modified');
        }

        $this->sErrorMessage = $sValue;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns any error message
     *
     * @return string
     */
    public function getErrorMessage(): string
    {
        return $this->sErrorMessage;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the user-friendly error message
     *
     * @param string $sValue
     *
     * @return $this
     * @throws ResponseException
     */
    public function setErrorMessageUser(string $sValue): ResponseBase
    {
        if ($this->isLocked()) {
            throw new ResponseException('Response is locked and cannot be modified');
        }

        $this->sErrorMessageUser = $sValue;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns any user-friendly error message
     *
     * @return string
     */
    public function getErrorMessageUser(): string
    {
        return $this->sErrorMessageUser;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the error code
     *
     * @param string $sValue
     *
     * @return $this
     * @throws ResponseException
     */
    public function setErrorCode(string $sValue): ResponseBase
    {
        if ($this->isLocked()) {
            throw new ResponseException('Response is locked and cannot be modified');
        }

        $this->sErrorCode = $sValue;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns any error code
     *
     * @return string
     */
    public function getErrorCode(): string
    {
        return $this->sErrorCode;
    }

    // --------------------------------------------------------------------------

    /**
     * Return the error messages
     *
     * @return stdClass
     */
    public function getError(): stdClass
    {
        return (object) [
            'msg'  => $this->getErrorMessage(),
            'code' => $this->getErrorCode(),
            'user' => $this->getErrorMessageUser(),
        ];
    }

    // --------------------------------------------------------------------------

    /**
     * Set the transaction ID
     *
     * @param string $sTransactionId The transaction ID
     *
     * @return $this
     * @throws ResponseException
     */
    public function setTransactionId(string $sTransactionId): ResponseBase
    {
        if ($this->isLocked()) {
            throw new ResponseException('Response is locked and cannot be modified');
        }

        $this->sTransactionId = $sTransactionId;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * The transaction ID
     *
     * @return string
     */
    public function getTransactionId(): string
    {
        return $this->sTransactionId;
    }

    // --------------------------------------------------------------------------

    /**
     * Set the fee charged by the payment processor
     *
     * @param int $iFee The fee charged by the payment processor
     *
     * @return $this
     * @throws ResponseException
     */
    public function setFee(int $iFee): ResponseBase
    {
        if ($this->isLocked()) {
            throw new ResponseException('Response is locked and cannot be modified');
        }

        $this->iFee = (int) $iFee;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * The fee charged by the payment processor
     *
     * @return int
     */
    public function getFee(): int
    {
        return $this->iFee;
    }

    // --------------------------------------------------------------------------

    /**
     * Prevent the object from being altered
     *
     * @return $this
     */
    public function lock(): ResponseBase
    {
        $this->bIsLocked = true;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Whether the response is locked
     *
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->bIsLocked;
    }

    // --------------------------------------------------------------------------

    /**
     * Set success URL
     *
     * @param string $sSuccessUrl The success URL
     *
     * @return $this
     * @throws ResponseException
     */
    public function setSuccessUrl(string $sSuccessUrl): ResponseBase
    {
        if ($this->isLocked()) {
            throw new ResponseException('Response is locked and cannot be modified');
        }

        $this->sSuccessUrl = $sSuccessUrl;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Get success URL
     *
     * @return string
     */
    public function getSuccessUrl(): string
    {
        return $this->sSuccessUrl;
    }

    // --------------------------------------------------------------------------

    /**
     * Set the error URL
     *
     * @param string $sErrorUrl The the error URL
     *
     * @return $this
     * @throws ResponseException
     */
    public function setErrorUrl(string $sErrorUrl): ResponseBase
    {
        if ($this->isLocked()) {
            throw new ResponseException('Response is locked and cannot be modified');
        }

        $this->sErrorUrl = $sErrorUrl;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Get the error URL
     *
     * @return string
     */
    public function getErrorUrl(): string
    {
        return $this->sErrorUrl;
    }
}
