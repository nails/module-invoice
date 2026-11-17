<?php

use Nails\Common\Helper\Inflector;
use Nails\Factory;
use Nails\Invoice\Constants;
use Nails\Invoice\Resource\Invoice\Email;
use Nails\Invoice\Resource\Refund;

/**
 * @var \Nails\Invoice\Resource\Invoice $invoice
 */

/** @var \Nails\Invoice\Model\Payment $oPaymentModel */
$oPaymentModel = Factory::model('Payment', Constants::MODULE_SLUG);
/** @var \Nails\Invoice\Model\Refund $oRefundModel */
$oRefundModel = Factory::model('Refund', Constants::MODULE_SLUG);

$bHasAddress = $invoice->billingAddress() || $invoice->deliveryAddress();
$iColWidth   = [
    'details'    => 3,
    'customer' => $bHasAddress ? 3 : 4,
    'address'  => 3,
    'notes'    => $bHasAddress ? 3 : 5,
]

?>
<div class="group-invoice invoice view">
    <div class="row">
        <div class="col-md-<?=$iColWidth['details']?>">
            <div class="panel panel-default match-height">
                <div class="panel-heading">
                    <strong>Details</strong>
                </div>
                <table>
                    <tbody>
                        <tr>
                            <td class="header">State</td>
                            <td><?=$invoice->state->label?></td>
                        </tr>
                        <tr>
                            <td class="header">Dated</td>
                            <td><?=toUserDate($invoice->dated->raw)?></td>
                        </tr>
                        <tr>
                            <td class="header">Terms</td>
                            <td><?=$invoice->terms?> Days</td>
                        </tr>
                        <tr>
                            <td class="header">Due</td>
                            <td><?=toUserDate($invoice->due->raw)?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-md-<?=$iColWidth['customer']?>">
            <div class="panel panel-default match-height">
                <div class="panel-heading">
                    <strong>Customer</strong>
                </div>
                <?php

                if (!empty($invoice->customer)) {

                    ?>
                    <table>
                        <tbody>
                            <tr>
                                <td class="header">Organisation</td>
                                <td>
                                    <?=anchor(\Nails\Invoice\Admin\Controller\Customer::url('edit/' . $invoice->customer->id), $invoice->customer->label)?>
                                </td>
                            </tr>
                            <tr>
                                <td class="header">Sent To</td>
                                <td>
                                    <?php

                                    if (!empty($invoice->customer->billing_email)) {
                                        echo mailto($invoice->customer->billing_email);
                                    } else {
                                        echo mailto($invoice->customer->email);
                                    }

                                    ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <?php

                } elseif (!empty($invoice->email)) {

                    ?>
                    <table>
                        <tbody>

                            <tr>
                                <td class="header">Sent To</td>
                                <td>
                                    <?=mailto($invoice->email)?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <?php

                } else {

                    ?>
                    <div class="panel-body text-muted">
                        Unknown
                    </div>
                    <?php
                }

                ?>
            </div>
        </div>
        <?php

        if ($bHasAddress) {
            ?>
            <div class="col-md-<?=$iColWidth['address']?>">
                <div class="panel panel-default match-height">
                    <div class="panel-heading">
                        <strong>Addresses</strong>
                    </div>
                    <table>
                        <tbody>
                            <?php

                            if ($invoice->billingAddress()) {
                                ?>
                                <tr>
                                    <td class="header">Billing Address</td>
                                    <td>
                                        <?=implode('<br>', array_filter($invoice->billingAddress()->formatted()->asArray()))?>
                                    </td>
                                </tr>
                                <?php
                            }

                            if ($invoice->deliveryAddress()) {
                                ?>
                                <tr>
                                    <td class="header">Delivery Address</td>
                                    <td>
                                        <?=implode('<br>', array_filter($invoice->deliveryAddress()->formatted()->asArray()))?>
                                    </td>
                                </tr>
                                <?php
                            }

                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php
        }

        ?>
        <div class="col-md-<?=$iColWidth['notes']?>">
            <div class="panel panel-default match-height">
                <div class="panel-heading">
                    <strong>Notes</strong>
                </div>
                <div class="panel-body">
                    <?=$invoice->additional_text ?: '<span class="text-muted">No additional text</span>'?>
                </div>
            </div>
        </div>
    </div>
    <div class="panel panel-default">
        <div class="panel-heading">
            <strong>Line Items &amp; Totals</strong>
        </div>
        <?php

        if ($invoice->items->count > 0) {

            ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>&nbsp;</th>
                            <th class="text-center">Unit Cost</th>
                            <th class="text-center">Quantity</th>
                            <th class="text-center">Sub Total</th>
                            <th class="text-center">Tax</th>
                            <th class="text-center">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php

                        foreach ($invoice->items->data as $oItem) {

                            ?>
                            <tr>
                                <td>
                                    <?=$oItem->label?>
                                    <?=$oItem->body ? '<small>' . $oItem->body . '</small>' : ''?>
                                </td>
                                <td class="text-center">
                                    <?=$oItem->unit_cost->formatted?>
                                </td>
                                <td class="text-center">
                                    <?=$oItem->quantity?>
                                    <?=$oItem->unit->id !== 'NONE' ? Inflector::pluralise($oItem->quantity, $oItem->unit->label) : ''?>
                                </td>
                                <td class="text-center">
                                    <?=$oItem->totals->formatted->sub?>
                                </td>
                                <td class="text-center">
                                    <?=$oItem->totals->formatted->tax?>
                                    <small>
                                        at <?=$oItem->tax ? $oItem->tax->rate : 0?>%
                                    </small>
                                </td>
                                <td class="text-center">
                                    <?=$oItem->totals->formatted->grand?>
                                </td>
                            </tr>
                            <?php
                        }

                        ?>
                    </tbody>
                    <tfoot class="invoice-total" data-bind="visible: items().length">
                        <tr class="total-row">
                            <td colspan="7" class="text-right">
                                <strong>Sub Total:</strong>
                                <span><?=$invoice->totals->formatted->sub?></span>
                            </td>
                        </tr>
                        <tr class="total-row">
                            <td colspan="7" class="text-right">
                                <strong>Tax:</strong>
                                <span><?=$invoice->totals->formatted->tax?></span>
                            </td>
                        </tr>
                        <tr class="total-row">
                            <td colspan="7" class="text-right">
                                <strong>Grand Total:</strong>
                                <span><?=$invoice->totals->formatted->grand?></span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php

        } else {

            ?>
            <div class="panel-body">
                No line items recorded on this invoice.
            </div>
            <?php
        }

        ?>
    </div>
    <div class="panel panel-default">
        <div class="panel-heading">
            <strong>Associated Payments</strong>
        </div>
        <?php

        if (userHasPermission(\Nails\Invoice\Admin\Permission\Payment\View::class) && $invoice->payments->count > 0) {

            ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th class="text-center">ID</th>
                            <th class="text-center">Status</th>
                            <th>Gateway</th>
                            <th>Reference</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Fee</th>
                            <th>Source</th>
                            <th>Created</th>
                            <th>Modified</th>
                            <th class="actions">Actions</th>
                    </thead>
                    <tbody>
                        <?php

                        /** @var \Nails\Invoice\Resource\Payment $oPayment */
                        foreach ($invoice->payments->data as $oPayment) {

                            ?>
                            <tr>
                                <td class="text-center"><?=$oPayment->id?></td>
                                <?php

                                switch ($oPayment->status->id) {
                                    case $oPaymentModel::STATUS_COMPLETE:
                                    case $oPaymentModel::STATUS_PROCESSING:
                                        $sClass = 'success';
                                        $sText  = '';
                                        break;
                                    case $oPaymentModel::STATUS_PENDING:
                                    case $oPaymentModel::STATUS_SENT_FOR_AUTH:
                                    case $oPaymentModel::STATUS_REFUNDED:
                                    case $oPaymentModel::STATUS_REFUNDED_PARTIAL:
                                        $sClass = 'warning';
                                        $sText  = '';
                                        break;
                                    case $oPaymentModel::STATUS_FAILED:
                                        $sClass = 'danger';
                                        $sText  = $oPayment->fail_msg . ' (Code: ' . $oPayment->fail_code . ')';
                                        break;
                                    default:
                                        $sClass = '';
                                        $sText  = '';
                                        break;
                                }

                                ?>
                                <td class="text-center <?=$sClass?>">
                                    <?php
                                    echo $oPayment->status->label;
                                    if (!empty($sText)) {
                                        ?>
                                        <small>
                                            <?=$sText?>
                                        </small>
                                        <?php
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    if ($oPayment->driver) {
                                        echo $oPayment->driver->getLabel();
                                        echo '<small>' . $oPayment->driver->getSlug() . '</small>';
                                    } else {
                                        echo '<span class="text-muted">&mdash;</span>';
                                    }
                                    ?>
                                </td>
                                <td><?=$oPayment->transaction_id ?: '<span class="text-muted">&mdash;</span>'?></td>
                                <td><?=$oPayment->description ?: '<span class="text-muted">&mdash;</span>'?></td>
                                <td>
                                    <?php

                                    echo $oPayment->amount->formatted;
                                    if ($oPayment->amount_refunded->raw) {
                                        echo '<small>';
                                        echo 'Refunded: ' . $oPayment->amount_refunded->formatted;
                                        echo '</small>';
                                    }

                                    ?>
                                </td>
                                <td>
                                    <?php

                                    echo $oPayment->fee->formatted;
                                    if ($oPayment->fee_refunded->raw) {
                                        echo '<small>';
                                        echo 'Refunded: ' . $oPayment->fee_refunded->formatted;
                                        echo '</small>';
                                    }

                                    ?>
                                </td>
                                <td>
                                    <?php
                                    if (!empty($oPayment->source)) {
                                        echo $oPayment->source->label;
                                        echo '<small>';
                                        echo 'Driver: ' . $oPayment->source->driver;
                                        echo $oPayment->source->brand ? '<br>Brand: ' . $oPayment->source->brand : '';
                                        echo $oPayment->source->last_four ? '<br>Ending: ' . $oPayment->source->last_four : '';
                                        echo '</small>';
                                    } else {
                                        echo '<span class="text-muted">&mdash;</span>';
                                    }
                                    ?>
                                </td>
                                <?=adminHelper('loadDateTimeCell', $oPayment->created)?>
                                <?=adminHelper('loadDateTimeCell', $oPayment->modified)?>
                                <td class="actions">
                                    <?php

                                    echo anchor(
                                        \Nails\Invoice\Admin\Controller\Payment::url('view/' . $oPayment->id),
                                        'View',
                                        'class="btn btn-xs btn-default"'
                                    );

                                    if ($oPayment->is_refundable && userHasPermission(\Nails\Invoice\Admin\Permission\Payment\Refund::class)) {
                                        ?>
                                        <button class="btn btn-xs btn-danger js-invoice-refund pull-right"
                                                data-id="<?=$oPayment->id?>"
                                                data-max="<?=$oPayment->available_for_refund->raw?>"
                                                data-max-formatted="<?=$oPayment->available_for_refund->formatted?>"
                                        >
                                            Refund
                                        </button>
                                        <?php
                                    }

                                    ?>
                                </td>
                            </tr>
                            <?php

                        }

                        ?>
                    </tbody>
                </table>
            </div>
            <?php

        } else {

            ?>
            <div class="panel-body text-muted">
                No Associated Payments
            </div>
            <?php
        }

        ?>
    </div>
    <div class="panel panel-default">
        <div class="panel-heading">
            <strong>Associated Refunds</strong>
        </div>
        <?php

        if (userHasPermission(\Nails\Invoice\Admin\Permission\Payment\View::class) && $invoice->refunds->count > 0) {

            ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th class="text-center">ID</th>
                            <th class="text-center">Status</th>
                            <th>Reference</th>
                            <th>Reason</th>
                            <th>Amount</th>
                            <th>Fee</th>
                            <th>Created</th>
                            <th>Modified</th>
                    </thead>
                    <tbody>
                        <?php

                        /** @var Refund $oRefund */
                        foreach ($invoice->refunds->data as $oRefund) {

                            ?>
                            <tr>
                                <td class="text-center"><?=$oRefund->id?></td>
                                <?php

                                switch ($oRefund->status->id) {
                                    case $oRefundModel::STATUS_COMPLETE:
                                    case $oRefundModel::STATUS_PROCESSING:
                                        $sClass = 'success';
                                        $sText  = '';
                                        break;
                                    case $oRefundModel::STATUS_PENDING:
                                        $sClass = 'warning';
                                        $sText  = '';
                                        break;
                                    case $oRefundModel::STATUS_FAILED:
                                        $sClass = 'danger';
                                        $sText  = $oRefund->fail_msg . ' (Code: ' . $oRefund->fail_code . ')';
                                        break;
                                    default:
                                        $sClass = '';
                                        $sText  = '';
                                        break;
                                }

                                ?>
                                <td class="text-center <?=$sClass?>">
                                    <?php
                                    echo $oRefund->status->label;
                                    if (!empty($sText)) {
                                        ?>
                                        <small>
                                            <?=$sText?>
                                        </small>
                                        <?php
                                    }
                                    ?>
                                </td>
                                <td><?=$oRefund->transaction_id ?: '<span class="text-muted">&mdash;</span>'?></td>
                                <td><?=$oRefund->reason ?: '<span class="text-muted">&mdash;</span>'?></td>
                                <td><?=$oRefund->amount->formatted?></td>
                                <td><?=$oRefund->fee->formatted?></td>
                                <?=adminHelper('loadDateTimeCell', $oRefund->created)?>
                                <?=adminHelper('loadDateTimeCell', $oRefund->modified)?>
                            </tr>
                            <?php

                        }

                        ?>
                    </tbody>
                </table>
            </div>
            <?php

        } else {

            ?>
            <div class="panel-body text-muted">
                No Associated Refunds
            </div>
            <?php
        }

        ?>
    </div>
    <div class="panel panel-default">
        <div class="panel-heading">
            <strong>Associated Emails</strong>
        </div>
        <?php

        if ($invoice->emails->count > 0) {

            ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Recipient</th>
                            <th class="text-center">Status</th>
                            <th>Sent</th>
                            <th class="text-center">Preview</th>
                    </thead>
                    <tbody>
                        <?php

                        /** @var Email $oEmail */
                        foreach ($invoice->emails->data as $oEmail) {
                            ?>
                            <tr>
                                <td>
                                    <?php
                                    if (is_object($oEmail->email_type)) {

                                        echo $oEmail->email_type->name;
                                        echo '<small>' . $oEmail->email_type->description . '</small>';

                                    } else {
                                        echo $oEmail->email_type ?: '<span class="text-muted">Unknown</span>';
                                    }
                                    ?>
                                </td>
                                <?php

                                if (!empty($oEmail->email->user_id)) {
                                    echo adminHelper('loadUserCell', $oEmail->email->user_id);
                                } else {
                                    ?>
                                    <td><?=$oEmail->recipient?></td>
                                    <?php
                                }

                                if (!empty($oEmail->email)) {
                                    echo \Nails\Email\Admin\Helper::emailStatusCell($oEmail->email);

                                } elseif (!empty($oEmail->error)) {
                                    ?>
                                    <td class="text-center danger">
                                        <b class="fa fa-lg fa-times-circle"></b>
                                        <small>
                                            <?=$oEmail->error?>
                                        </small>
                                    </td>
                                    <?php
                                } else {
                                    ?>
                                    <td>
                                        <span class="text-muted">
                                            Not Available
                                        </span>
                                    </td>
                                    <?php
                                }

                                ?>
                                <?=adminHelper('loadDateTimeCell', $oEmail->created)?>
                                <td class="text-center">
                                    <?php

                                    if (!empty($oEmail->preview_url)) {

                                        echo anchor(
                                            $oEmail->preview_url,
                                            'Preview',
                                            'class="btn btn-xs btn-primary fancybox"'
                                        );

                                    } else {

                                        echo '<span class="text-muted">Not Available</span>';
                                    }

                                    ?>
                                </td>
                            </tr>
                            <?php

                        }

                        ?>
                    </tbody>
                </table>
            </div>
            <?php

        } else {

            ?>
            <div class="panel-body text-muted">
                No Associated Emails
            </div>
            <?php
        }

        ?>
    </div>
</div>
