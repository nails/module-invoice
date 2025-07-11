<?php

use Nails\Common\Service\UserFeedback;
use Nails\Factory;
use Nails\Invoice\Driver\PaymentBase;
use Nails\Invoice\Resource\Invoice;
use Nails\Invoice\Resource\Source;

/**
 * @var string        $sFormUrl
 * @var PaymentBase[] $aDrivers
 * @var Invoice       $oInvoice
 * @var Source[]      $aSavedPaymentSources
 * @var string        $sUrlCancel
 */

/** @var UserFeedback $oUserFeedback */
$oUserFeedback = Factory::service('UserFeedback');

$aAlertConf = [
    $oUserFeedback::TYPE_ERROR    => [
        'class' => 'danger',
    ],
    $oUserFeedback::TYPE_NEGATIVE => [
        'class' => 'danger',
    ],
    $oUserFeedback::TYPE_POSITIVE => [
        'class' => 'success',
    ],
    $oUserFeedback::TYPE_MESSAGE  => [
        'class' => 'warning',
    ],
    $oUserFeedback::TYPE_NOTICE   => [
        'class' => 'info',
    ],
];

?>
<div class="nails-invoice pay" id="js-invoice">
    <?php
    $sLogo = logoDiscover();
    if ($sLogo) {
        echo '<div class="logo">';
        echo img([
            'src' => $sLogo,
        ]);
        echo '</div>';
    }
    ?>
    <div class="panel shakeable">
        <div class="panel__header">
            <h1 class="panel__title text-center">
                Checkout
            </h1>
        </div>
        <div class="panel__body">
            <?php

            echo form_open($sFormUrl, 'id="js-invoice-main-form" class="form"');

            foreach ($oUserFeedback->getTypes() as $sType) {

                $sValue = (string) $oUserFeedback->get($sType);
                $sClass = $aAlertConf[$sType]['class'] ?? strtolower($sType);

                ?>
                <p class="alert alert--<?=$sClass?> <?=$sValue ? '' : 'hidden'?>" id="js-<?=strtolower($sType)?>">
                    <?=$sValue?>
                </p>
                <?php
            }

            if (empty($aDrivers)) {
                ?>
                <p class="text-center">
                    No payment options are available for this invoice.
                </p>
                <?php
            } else {
                ?>
                <table class="table table--striped" id="js-invoice-main-form-line-items">
                    <tbody>
                        <?php
                        foreach ($oInvoice->items->data as $oItem) {
                            ?>
                            <tr>
                                <td>
                                    <?=$oItem->label?>
                                    <br><small><?=$oItem->body?></small>
                                </td>
                                <td align="right">
                                    <?=$oItem->totals->formatted->grand?>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>
                                Total
                            </td>
                            <td align="right">
                                <?=$oInvoice->totals->formatted->grand?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
                <hr>
                <div id="js-invoice-main-form-payment-drivers">
                    <h5>Choose Payment Method</h5>
                    <ul class="list list--unstyled list--bordered" id="js-invoice-driver-select">
                        <?php

                        $i = 0;
                        foreach ($aSavedPaymentSources as $oSource) {
                            ?>
                            <li>
                                <label class="form__group form__group--radio js-invoice-driver-select">
                                    <?php
                                    $aData = [
                                        'data-has-fields="false"',
                                        'data-is-redirect="false"',
                                    ];
                                    echo form_radio(
                                        'driver',
                                        $oSource->id,
                                        set_radio('driver', $oSource->id, $i === 0),
                                        implode(' ', $aData)
                                    );
                                    echo $oSource->label;
                                    ?>
                                </label>
                            </li>
                            <?php
                            $i++;
                        }

                        foreach ($aDrivers as $oDriver) {

                            $sHasFields  = json_encode(!empty($oDriver->getPaymentFields()));
                            $sIsCard     = json_encode($oDriver->getPaymentFields() === PaymentBase::PAYMENT_FIELDS_CARD);
                            $sIsRedirect = json_encode($oDriver->isRedirect());

                            $aData = [
                                'data-driver="' . $oDriver->getSlug() . '"',
                                'data-has-fields="' . $sHasFields . '"',
                                'data-is-redirect="' . $sIsRedirect . '"',
                            ];

                            ?>
                            <li>
                                <label class="form__group form__group--radio js-invoice-driver-select">
                                    <?php

                                    echo form_radio(
                                        'driver',
                                        $oDriver->getSlug(),
                                        set_radio('driver', $oDriver->getSlug(), empty($aSavedPaymentSources) && count($aDrivers) === 1),
                                        implode(' ', $aData)
                                    );

                                    echo $oDriver->getLabel();

                                    ?>
                                </label>
                            </li>
                            <?php
                        }

                        ?>
                    </ul>
                </div>
                <div id="js-invoice-main-form-payment-fields">
                    <?php
                    foreach ($aDrivers as $oDriver) {

                        $mFields    = $oDriver->getPaymentFields();
                        $sDriverKey = md5($oDriver->getSlug());

                        ?>
                        <div class="hidden js-invoice-panel-payment-details" data-driver="<?=$oDriver->getSlug()?>">
                            <?php

                            if (!empty($mFields) && $mFields === PaymentBase::PAYMENT_FIELDS_CARD) {

                                ?>
                                <h5>
                                    Payment Details
                                </h5>
                                <?php

                                $sFieldKey         = $sDriverKey . '[card][name]';
                                $sFieldLabel       = 'Cardholder Name';
                                $sFieldPlaceholder = '';
                                $sFieldDefault     = activeUser('first_name, last_name');
                                $sFieldAttr        = implode(' ', [
                                    'class="form__control js-invoice-cc-name"',
                                    'id="input-' . $sFieldKey . '"',
                                    'placeholder="' . $sFieldPlaceholder . '"',
                                    'autocomplete="on"',
                                    'data-is-required="true"',
                                ]);

                                ?>
                                <div class="form__group">
                                    <label class="form__label" for="input-<?=$sFieldKey?>"><?=$sFieldLabel?></label>
                                    <?=form_text($sFieldKey, set_value($sFieldKey, $sFieldDefault), $sFieldAttr)?>
                                    <?=form_error($sFieldKey, '<p class="form__feedback form__feedback--invalid">', '</p>')?>
                                </div>
                                <?php

                                $sFieldKey         = $sDriverKey . '[card][number]';
                                $sFieldLabel       = 'Card Number';
                                $sFieldPlaceholder = '•••• •••• •••• ••••';
                                $sFieldDefault     = '';
                                $sFieldAttr        = implode(' ', [
                                    'class="form__control js-invoice-cc-num"',
                                    'id="input-' . $sFieldKey . '"',
                                    'placeholder="' . $sFieldPlaceholder . '"',
                                    'autocomplete="on"',
                                    'data-is-required="true"',
                                    'data-cc-num="true"',
                                ]);

                                ?>
                                <div class="form__group">
                                    <label class="form__label" for="input-<?=$sFieldKey?>"><?=$sFieldLabel?></label>
                                    <?=form_tel($sFieldKey, set_value($sFieldKey, $sFieldDefault), $sFieldAttr)?>
                                    <?=form_error($sFieldKey, '<p class="form__feedback form__feedback--invalid">', '</p>')?>
                                </div>
                                <div class="form__row">
                                    <?php

                                    $sFieldKey         = $sDriverKey . '[card][expire]';
                                    $sFieldLabel       = 'Expiry';
                                    $sFieldPlaceholder = '•• / ••';
                                    $sFieldDefault     = '';
                                    $sFieldAttr        = implode(' ', [
                                        'class="form__control js-invoice-cc-exp"',
                                        'id="input-' . $sFieldKey . '"',
                                        'placeholder="' . $sFieldPlaceholder . '"',
                                        'autocomplete="on"',
                                        'data-is-required="true"',
                                        'data-cc-exp="true"',
                                    ]);

                                    ?>
                                    <div class="form__group form__group--half">
                                        <label class="form__label" for="input-<?=$sFieldKey?>"><?=$sFieldLabel?></label>
                                        <?=form_tel($sFieldKey, set_value($sFieldKey, $sFieldDefault), $sFieldAttr)?>
                                        <?=form_error($sFieldKey, '<p class="form__feedback form__feedback--invalid">', '</p>')?>
                                    </div>
                                    <?php

                                    $sFieldKey         = $sDriverKey . '[card][cvc]';
                                    $sFieldLabel       = 'CVC';
                                    $sFieldPlaceholder = '•••';
                                    $sFieldDefault     = '';
                                    $sFieldAttr        = implode(' ', [
                                        'class="form__control js-invoice-cc-cvc"',
                                        'id="input-' . $sFieldKey . '"',
                                        'placeholder="' . $sFieldPlaceholder . '"',
                                        'autocomplete="on"',
                                        'data-is-required="true"',
                                        'data-cc-cvc="true"',
                                    ]);

                                    ?>
                                    <div class="form__group form__group--half">
                                        <label class="form__label" for="input-<?=$sFieldKey?>"><?=$sFieldLabel?></label>
                                        <?=form_tel($sFieldKey, set_value($sFieldKey, $sFieldDefault), $sFieldAttr)?>
                                        <?=form_error($sFieldKey, '<p class="form__feedback form__feedback--invalid">', '</p>')?>
                                    </div>
                                </div>
                                <?php

                            } elseif (!empty($mFields)) {

                                ?>
                                <h5>
                                    Payment Details
                                </h5>
                                <?php

                                foreach ($mFields as $aField) {

                                    $sKey         = $sDriverKey . '[' . $aField['key'] . ']';
                                    $sDefault     = !empty($aField['default']) ? $aField['default'] : '';
                                    $sLabel       = !empty($aField['label']) ? $aField['label'] : '';
                                    $sType        = !empty($aField['type']) ? $aField['type'] : 'text';
                                    $sPlaceholder = !empty($aField['placeholder']) ? $aField['placeholder'] : '';
                                    $sRequired    = !empty($aField['required']) ? 'true' : 'false';
                                    $aOptions     = !empty($aField['options']) ? $aField['options'] : [];
                                    $sErrorClass  = form_error($sKey) ? 'has-error' : '';

                                    $sId   = empty($aField['id']) ? 'input-' . $sKey : $aField['id'];
                                    $aAttr = [
                                        'id="' . $sId . '"',
                                        'placeholder="' . $sPlaceholder . '" ',
                                        'data-is-required="' . $sRequired . '"',
                                    ];

                                    ?>
                                    <div class="form__group">
                                        <label class="form__label" for="<?=$sId?>">
                                            <?=$sLabel?>
                                        </label>
                                        <?php

                                        switch ($sType) {

                                            case 'dropdown':
                                            case 'select':

                                                echo form_dropdown(
                                                    $sKey,
                                                    $aOptions,
                                                    set_value($sKey),
                                                    implode(' ', $aAttr + ['class="form__control form__control--select"'])
                                                );
                                                break;

                                            case 'password':
                                                echo form_password(
                                                    $sKey,
                                                    null,
                                                    implode(' ', $aAttr + ['class="form__control"'])
                                                );
                                                break;

                                            case 'text':
                                            default:
                                                echo form_input(
                                                    $sKey,
                                                    set_value($sKey),
                                                    implode(' ', $aAttr + ['class="form__control"'])
                                                );
                                                break;
                                        }
                                        ?>
                                        <?=form_error($sKey, '<p class="form__feedback form__feedback--invalid">', '</p>')?>
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                        </div>
                        <?php
                    }
                    ?>
                </div>
                <div class="form__action">
                    <button type="submit" class="btn btn--block btn--primary btn--disabled" id="js-invoice-pay-now">
                        Choose a Payment Method
                    </button>
                    <a href="<?=$sUrlCancel?>" class="btn btn--block btn--link" id="js-invoice-cancel">
                        Cancel payment
                    </a>
                </div>
                <?php
            }

            echo form_close();

            ?>
        </div>
    </div>
</div>
