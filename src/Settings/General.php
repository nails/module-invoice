<?php

namespace Nails\Invoice\Settings;

use Nails\Invoice\Admin\Permission;
use Nails\Invoice\Service\Invoice\Skin;
use Nails\Invoice\Service\PaymentDriver;
use Nails\Common\Helper\Form;
use Nails\Common\Interfaces;
use Nails\Common\Service\FormValidation;
use Nails\Components\Setting;
use Nails\Invoice\Constants;
use Nails\Factory;

/**
 * Class General
 *
 * @package Nails\Invoice\Settings
 */
class General implements Interfaces\Component\Settings
{
    const KEY_BUSINESS_NAME           = 'business_name';
    const KEY_BUSINESS_ADDRESS        = 'business_address';
    const KEY_BUSINESS_PHONE          = 'business_phone';
    const KEY_BUSINESS_EMAIL          = 'business_email';
    const KEY_BUSINESS_VAT            = 'business_vat_number';
    const KEY_DEFAULT_ADDITIONAL_TEXT = 'default_additional_text';
    const KEY_DEFAULT_PAYMENT_TERMS   = 'default_payment_terms';

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function getLabel(): string
    {
        return 'Invoices & Payments';
    }

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function getPermission(): string
    {
        return Permission\Settings\General::class;
    }

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function get(): array
    {
        /** @var PaymentDriver $oPaymentDriverService */
        $oPaymentDriverService = Factory::service('PaymentDriver', Constants::MODULE_SLUG);
        /** @var Skin $oSkinService */
        $oSkinService = Factory::service('InvoiceSkin', Constants::MODULE_SLUG);

        /** @var Setting $oBusinessName */
        $oBusinessName = Factory::factory('ComponentSetting');
        $oBusinessName
            ->setKey(static::KEY_BUSINESS_NAME)
            ->setLabel('Name')
            ->setFieldset('Business Details');

        /** @var Setting $oBusinessAddress */
        $oBusinessAddress = Factory::factory('ComponentSetting');
        $oBusinessAddress
            ->setKey(static::KEY_BUSINESS_ADDRESS)
            ->setType(Form::FIELD_TEXTAREA)
            ->setLabel('Address')
            ->setFieldset('Business Details');

        /** @var Setting $oBusinessPhone */
        $oBusinessPhone = Factory::factory('ComponentSetting');
        $oBusinessPhone
            ->setKey(static::KEY_BUSINESS_PHONE)
            ->setLabel('Phone')
            ->setFieldset('Business Details');

        /** @var Setting $oBusinessEmail */
        $oBusinessEmail = Factory::factory('ComponentSetting');
        $oBusinessEmail
            ->setKey(static::KEY_BUSINESS_EMAIL)
            ->setLabel('Email')
            ->setFieldset('Business Details');

        /** @var Setting $oBusinessVat */
        $oBusinessVat = Factory::factory('ComponentSetting');
        $oBusinessVat
            ->setKey(static::KEY_BUSINESS_VAT)
            ->setLabel('VAT Number')
            ->setFieldset('Business Details');

        /** @var Setting $oDefaultAdditionalText */
        $oDefaultAdditionalText = Factory::factory('ComponentSetting');
        $oDefaultAdditionalText
            ->setKey(static::KEY_DEFAULT_ADDITIONAL_TEXT)
            ->setType(Form::FIELD_TEXTAREA)
            ->setLabel('Invoice Additional Text')
            ->setFieldset('Defaults');

        /** @var Setting $oDefaultPaymentTerms */
        $oDefaultPaymentTerms = Factory::factory('ComponentSetting');
        $oDefaultPaymentTerms
            ->setKey(static::KEY_DEFAULT_PAYMENT_TERMS)
            ->setType(Form::FIELD_NUMBER)
            ->setLabel('Payment Terms')
            ->setFieldset('Defaults');

        /** @var Setting $oPaymentDriver */
        $oPaymentDriver = Factory::factory('ComponentSetting');
        $oPaymentDriver
            ->setKey($oPaymentDriverService->getSettingKey())
            ->setType($oPaymentDriverService->isMultiple()
                ? Form::FIELD_DROPDOWN_MULTIPLE
                : Form::FIELD_DROPDOWN
            )
            ->setLabel('Payment')
            ->setFieldset('Drivers')
            ->setClass('select2')
            ->setOptions($oPaymentDriverService->getAllFlat())
            ->setValidation([
                FormValidation::RULE_REQUIRED,
            ]);

        /** @var Setting $oSkin */
        $oSkin = Factory::factory('ComponentSetting');
        $oSkin
            ->setKey($oSkinService->getSettingKey())
            ->setType($oSkinService->isMultiple()
                ? Form::FIELD_DROPDOWN_MULTIPLE
                : Form::FIELD_DROPDOWN
            )
            ->setLabel('Invoice')
            ->setFieldset('Skins')
            ->setClass('select2')
            ->setOptions(['' => 'No Skin Selected'] + $oSkinService->getAllFlat())
            ->setValidation([
                FormValidation::RULE_REQUIRED,
            ]);


        Factory::destroy($oPaymentDriverService);
        Factory::destroy($oSkinService);

        return [
            $oBusinessName,
            $oBusinessAddress,
            $oBusinessPhone,
            $oBusinessEmail,
            $oBusinessVat,
            $oDefaultAdditionalText,
            $oDefaultPaymentTerms,
            $oPaymentDriver,
            $oSkin,
        ];
    }
}
