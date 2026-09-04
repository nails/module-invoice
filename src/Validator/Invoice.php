<?php

/**
 * Validates the admin create/edit invoice form
 *
 * @package     Nails
 * @subpackage  module-invoice
 * @category    Validator
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Invoice\Validator;

use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ValidationException;
use Nails\Common\Factory\Service\FormValidation\Validator;
use Nails\Common\Service\FormValidation;
use Nails\Currency;
use Nails\Factory;

class Invoice extends Validator
{
    /**
     * @param Currency\Service\Currency|null $oCurrency The currency service; resolved from the Factory if not given
     */
    public function __construct(private ?Currency\Service\Currency $oCurrency = null)
    {
        parent::__construct();
    }

    // --------------------------------------------------------------------------

    /**
     * @throws FactoryException
     */
    protected function rules(): array
    {
        $oCurrency     = $this->oCurrency ??= Factory::service('Currency', Currency\Constants::MODULE_SLUG);
        $aEnabledCodes = array_map(
            fn($oCurrency) => $oCurrency->code,
            $oCurrency->getAllEnabled()
        );

        return [
            'ref'             => ['trim'],
            'state'           => ['trim', FormValidation::RULE_REQUIRED],
            'dated'           => ['trim', FormValidation::RULE_REQUIRED, FormValidation::RULE_VALID_DATE],
            'currency'        => [
                'trim',
                FormValidation::RULE_REQUIRED,
                function ($sCode) use ($aEnabledCodes) {
                    if (!in_array($sCode, $aEnabledCodes, true)) {
                        throw new ValidationException('Invalid currency.');
                    }
                },
            ],
            'terms'           => ['trim', FormValidation::RULE_IS_NATURAL],
            'customer_id'     => ['trim'],
            'additional_text' => ['trim'],
        ];
    }
}
