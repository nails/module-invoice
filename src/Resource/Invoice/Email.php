<?php

/**
 * This class represents objects dispensed by the InvoiceEmail model
 *
 * @package  Nails\Invoice\Resource\Invoice
 * @category resource
 */

namespace Nails\Invoice\Resource\Invoice;

use Nails\Common\Model\Base;
use Nails\Common\Resource\Entity;
use Nails\Email\Constants;
use Nails\Email\Service\Emailer;
use Nails\Factory;
use Nails\Invoice\Resource\Invoice;
use stdClass;

class Email extends Entity
{
    /**
     * The associated invoice ID
     *
     * @var int
     */
    public $invoice_id;

    /**
     * The invoice object
     *
     * @var Invoice
     */
    public $invoice;

    /**
     * The recipient
     *
     * @var string
     */
    public $recipient = '';

    /**
     * The error, if not sent
     *
     * @var string
     */
    public $error;

    /**
     * The email object
     *
     * @var \Nails\Email\Resource\Email
     */
    public $email;

    /**
     * The email type object
     *
     * @var stdClass|string
     */
    public $email_type;

    /**
     * The email's preview URL
     *
     * @var string
     */
    public $preview_url = '';

    // --------------------------------------------------------------------------

    /**
     * Email constructor.
     */
    public function __construct(self|stdClass|array $resource = [], ?Base $model = null)
    {
        parent::__construct($resource, $model);
        if ($this->email) {
            $this->preview_url = siteUrl('email/view/' . $this->email->ref);
        }

        /** @var Emailer $oEmailer */
        $oEmailer         = Factory::service('Emailer', Constants::MODULE_SLUG);
        $this->email_type = $oEmailer->getType($this->email_type);
        if (empty($this->email_type)) {
            $this->email_type = $resource->email_type;
        }
    }
}
