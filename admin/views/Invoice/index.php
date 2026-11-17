<?php

use Nails\Factory;
use Nails\Invoice\Constants;

/**
 * @var stdClass                          $search
 * @var stdClass                          $pagination
 * @var \Nails\Invoice\Resource\Invoice[] $invoices
 */
/** @var \Nails\Invoice\Model\Invoice $oInvoiceModel */
$oInvoiceModel = Factory::model('Invoice', Constants::MODULE_SLUG);

?>
<div class="group-invoice invoice browse">
    <?=adminHelper('loadSearch', $search)?>
    <?=adminHelper('loadPagination', $pagination)?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th class="ref">Ref</th>
                    <th class="state">State</th>
                    <th class="details">Details</th>
                    <th class="currency">Currency</th>
                    <th class="amount sub">Sub Total</th>
                    <th class="amount tax">Tax</th>
                    <th class="amount grand">Grand Total</th>
                    <th class="datetime">Created</th>
                    <th class="datetime">Modified</th>
                    <th class="actions" style="width:175px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php

                if ($invoices) {

                    foreach ($invoices as $oInvoice) {

                        ?>
                        <tr>
                            <td class="ref">
                                <?=$oInvoice->ref?>
                            </td>
                            <?php

                            if ($oInvoice->is_overdue) {

                                $sClass = 'danger';
                                $sText  = 'Overdue';
                                $sText  .= '<small>Due: ' . toUserDate($oInvoice->due->raw) . '</small>';

                            } elseif ($oInvoice->is_scheduled) {

                                $sClass = 'warning';
                                $sText  = 'Scheduled';
                                $sText  .= '<small>Sending: ' . toUserDate($oInvoice->dated->raw) . '</small>';

                            } elseif ($oInvoice->state->id == $oInvoiceModel::STATE_OPEN) {

                                $sClass = 'success';
                                $sText  = $oInvoice->state->label;
                                $sText  .= '<small>Due: ' . toUserDate($oInvoice->due->raw) . '</small>';

                            } elseif ($oInvoice->state->id == $oInvoiceModel::STATE_PAID || $oInvoice->state->id == $oInvoiceModel::STATE_PAID_PROCESSING) {

                                $sClass = 'success';
                                $sText  = $oInvoice->state->label;
                                if ($oInvoice->paid->raw) {
                                    $sText .= '<small>Paid: ' . toUserDateTime($oInvoice->paid->raw) . '</small>';
                                }

                            } elseif ($oInvoice->state->id == $oInvoiceModel::STATE_CANCELLED || $oInvoice->state->id == $oInvoiceModel::STATE_WRITTEN_OFF) {

                                $sClass = 'danger';
                                $sText  = $oInvoice->state->label;

                            } else {
                                $sClass = '';
                                $sText  = $oInvoice->state->label;
                            }

                            ?>
                            <td class="state <?=$sClass?>">
                                <?=$sText?>
                            </td>
                            <td class="details">
                                <?php

                                $aStylesInline = [
                                    'display: inline-block;',
                                    'margin: 0;',
                                    'margin-top: 0.5rem;',
                                    'margin-right: 0.25rem;',
                                    'list-style-type: none;',
                                    'border: 1px solid #ececec;',
                                    'padding: 0.5rem;',
                                    'border-radius: 3px;',
                                    'background: #fff;',
                                ];
                                $sStylesInline = implode('', $aStylesInline);

                                $aStylesBlock = [
                                    'margin: 0;',
                                    'margin-top: 0.5rem;',
                                    'list-style-type: none;',
                                    'border: 1px solid #ececec;',
                                    'padding: 0.5rem;',
                                    'border-radius: 3px;',
                                    'background: #fff;',
                                ];
                                $sStylesBlock = implode('', $aStylesBlock);

                                if (!empty($oInvoice->customer)) {
                                    ?>
                                    <strong>Customer</strong>
                                    <div style="<?=$sStylesBlock?> margin-bottom: 1rem;">
                                        <?php
                                        echo $oInvoice->customer->name;
                                        echo $oInvoice->customer->label !== $oInvoice->customer->name
                                            ? '<br/>' . $oInvoice->customer->label
                                            : '';
                                        echo ($oInvoice->customer->billing_email ?: $oInvoice->customer->email)
                                            ? '<br><small>' . mailto($oInvoice->customer->billing_email ?: $oInvoice->customer->email) . '</small>'
                                            : '';
                                        ?>
                                    </div>
                                    <?php
                                }

                                if ($oInvoice->billingAddress()) {
                                    ?>
                                    <strong>Billing Address:</strong>
                                    <div style="<?=$sStylesBlock?> margin-bottom: 1rem;">
                                        <?=$oInvoice->billingAddress()->formatted()->asCsv()?>
                                    </div>
                                    <?php
                                }

                                if ($oInvoice->payments->count) {
                                    ?>
                                    <strong>Payments:</strong>
                                    <ul style="margin: 0; margin-bottom: 1rem; list-style-type: none;">
                                        <?php
                                        /** @var \Nails\Invoice\Resource\Payment $oPayment */
                                        foreach ($oInvoice->payments->data as $oPayment) {
                                            echo userHasPermission(\Nails\Invoice\Admin\Permission\Payment\View::class)
                                                ? sprintf(
                                                    '<li style="%s"><a href="%s" class="fancybox">%s<small>%s — %s</small></a></li>',
                                                    $sStylesInline,
                                                    \Nails\Invoice\Admin\Controller\Payment::url('view/' . $oPayment->id),
                                                    $oPayment->ref,
                                                    $oPayment->amount->formatted,
                                                    $oPayment->status->label
                                                )
                                                : sprintf(
                                                    '<li style="%s">%s<small>%s — %s</small></li>',
                                                    $sStylesInline,
                                                    $oPayment->ref,
                                                    $oPayment->amount->formatted,
                                                    $oPayment->status->label
                                                );
                                        }

                                        ?>
                                    </ul>
                                    <?php
                                }

                                if ($oInvoice->refunds->count) {
                                    ?>
                                    <strong>Refunds:</strong>
                                    <ul style="margin: 0; margin-bottom: 1rem; list-style-type: none;">
                                        <?php
                                        /** @var \Nails\Invoice\Resource\Refund $oRefund */
                                        foreach ($oInvoice->refunds->data as $oRefund) {
                                            echo userHasPermission(\Nails\Invoice\Admin\Permission\Payment\View::class)
                                                ? sprintf(
                                                    '<li style="%s"><a href="%s" class="fancybox">%s<small>%s — %s</small></a></li>',
                                                    $sStylesInline,
                                                    \Nails\Invoice\Admin\Controller\Refund::url('view/' . $oRefund->id),
                                                    $oRefund->ref,
                                                    $oRefund->amount->formatted,
                                                    $oRefund->status->label
                                                )
                                                : sprintf(
                                                    '<li style="%s">%s<small>%s — %s</small></li>',
                                                    $sStylesInline,
                                                    $oRefund->ref,
                                                    $oRefund->amount->formatted,
                                                    $oRefund->status->label
                                                );
                                        }

                                        ?>
                                    </ul>
                                    <?php
                                }

                                ?>
                                <strong>Line Items:</strong>
                                <ul style="margin: 0; list-style-type: none;">
                                    <?php

                                    /** @var \Nails\Invoice\Resource\Invoice\Item $oItem */
                                    foreach ($oInvoice->items()->data as $oItem) {
                                        ?>
                                        <li style="<?=$sStylesBlock?>">
                                            <?php
                                            echo sprintf(
                                                '%s x %s<small>%s</small>',
                                                $oItem->quantity,
                                                $oItem->label,
                                                $oItem->totals->formatted->grand
                                            );
                                            ?>
                                        </li>
                                        <?php
                                    }
                                    ?>

                                </ul>
                            </td>
                            <td class="currency">
                                <?=$oInvoice->currency->code?>
                            </td>
                            <td class="amount total">
                                <?=$oInvoice->totals->formatted->sub?>
                            </td>
                            <td class="amount tax">
                                <?=$oInvoice->totals->formatted->tax?>
                            </td>
                            <td class="amount grand">
                                <?=$oInvoice->totals->formatted->grand?>
                            </td>
                            <?=adminHelper('loadDateTimeCell', $oInvoice->created)?>
                            <?=adminHelper('loadDateTimeCell', $oInvoice->modified)?>
                            <?php
                            //  So that the "no actions" text shows when cell is empty
                            echo '<td class="actions">';
                            if (userHasPermission(\Nails\Invoice\Admin\Permission\Invoice\Edit::class)) {

                                if ($oInvoice->state->id == $oInvoiceModel::STATE_DRAFT) {

                                    echo anchor(
                                        \Nails\Invoice\Admin\Controller\Invoice::url('edit/' . $oInvoice->id),
                                        lang('action_edit'),
                                        'class="btn btn-xs btn-primary"'
                                    );

                                } elseif (in_array($oInvoice->state->id, [
                                    'WRITTEN_OFF',
                                    'PAID',
                                    'PAID_PROCESING',
                                ])) {

                                    echo anchor(
                                        \Nails\Invoice\Admin\Controller\Invoice::url('view/' . $oInvoice->id),
                                        lang('action_view'),
                                        'class="btn btn-xs btn-default"'
                                    );

                                    echo anchor(
                                        $oInvoice->urls->download,
                                        lang('action_download'),
                                        'class="btn btn-xs btn-primary" target="_blank"'
                                    );

                                } else {

                                    echo anchor(
                                        \Nails\Invoice\Admin\Controller\Invoice::url('view/' . $oInvoice->id),
                                        lang('action_view'),
                                        'class="btn btn-xs btn-default"'
                                    );

                                    echo anchor(
                                        $oInvoice->urls->download,
                                        lang('action_download'),
                                        'class="btn btn-xs btn-primary" target="_blank"'
                                    );

                                    if ($oInvoice->state->id == $oInvoiceModel::STATE_OPEN || $oInvoice->state->id == $oInvoiceModel::STATE_PAID_PARTIAL) {
                                        echo anchor(
                                            $oInvoice->urls->payment,
                                            'Pay',
                                            'class="btn btn-xs btn-primary" target="_blank"'
                                        );
                                    }

                                    $aValidPayments = array_filter(
                                        $oInvoice->payments->data,
                                        function ($oPayment) {
                                            return $oPayment->status->id !== 'FAILED';
                                        }
                                    );

                                    if (empty($aValidPayments)) {
                                        echo anchor(
                                            \Nails\Invoice\Admin\Controller\Invoice::url('make_draft/' . $oInvoice->id),
                                            'Make Draft',
                                            'class="btn btn-xs btn-warning"'
                                        );
                                        if (in_array($oInvoice->state->id, [$oInvoiceModel::STATE_OPEN])) {
                                            echo anchor(
                                                \Nails\Invoice\Admin\Controller\Invoice::url('write_off/' . $oInvoice->id),
                                                'Write Off',
                                                'class="btn btn-xs btn-danger confirm" data-body="Write invoice ' . $oInvoice->ref . ' off?"'
                                            );
                                        }
                                    }
                                }
                            }

                            if (userHasPermission(\Nails\Invoice\Admin\Permission\Invoice\Delete::class) && $oInvoice->state->id == $oInvoiceModel::STATE_DRAFT) {
                                echo anchor(
                                    \Nails\Invoice\Admin\Controller\Invoice::url('delete/' . $oInvoice->id),
                                    lang('action_delete'),
                                    'class="btn btn-xs btn-danger confirm" data-body="You cannot undo this action"'
                                );
                            }

                            echo anchor(
                                \Nails\Invoice\Admin\Controller\Invoice::url('resend/' . $oInvoice->id),
                                'Re-send',
                                'class="btn btn-xs btn-default"'
                            );

                            echo '</td>';

                            ?>
                        </tr>
                        <?php
                    }

                } else {
                    ?>
                    <tr>
                        <td colspan="10" class="no-data">
                            No Invoices Found
                        </td>
                    </tr>
                    <?php
                }

                ?>
            </tbody>
        </table>
    </div>
    <?=adminHelper('loadPagination', $pagination)?>
</div>
