<?php

use Nails\Factory;
use Nails\Invoice\Constants;
use Nails\Invoice\Resource;
use Nails\Invoice\Model;

/**
 * @var Resource\Payment $payment
 */

/** @var Model\Refund $oRefundModel */
$oRefundModel = Factory::model('Refund', Constants::MODULE_SLUG);

?>
<div class="group-invoice payment view">
    <div class="row">
        <div class="col-md-3">
            <div class="panel panel-default match-height">
                <div class="panel-heading">
                    <strong>Details</strong>
                </div>
                <table>
                    <tbody>
                        <tr>
                            <td class="header">Gateway</td>
                            <td>
                                <?=$payment->driver->getLabel()?>
                                <small>
                                    <code><?=$payment->driver->getSlug()?></code>
                                </small>
                            </td>
                        </tr>
                        <tr>
                            <td class="header">Transaction ID</td>
                            <td><?=$payment->transaction_id?></td>
                        </tr>
                        <tr>
                            <td class="header">Invoice</td>
                            <td><?=anchor(\Nails\Invoice\Admin\Controller\Invoice::url('view/' . $payment->invoice->id), $payment->invoice->ref)?></td>
                        </tr>
                        <tr>
                            <td class="header">Source</td>
                            <td><?=$payment->source->label ?? '<span class="text-muted">&mdash;</span>'?></td>
                        </tr>
                        <tr>
                            <td class="header">Description</td>
                            <td><?=$payment->description ?: '<span class="text-muted">&mdash;</span>'?></td>
                        </tr>
                        <tr>
                            <td class="header">Status</td>
                            <td>
                                <?php

                                echo $payment->status->label;

                                if (!empty($payment->fail_msg)) {
                                    ?>
                                    <small class="text-danger">
                                        <?=$payment->fail_msg?> (Code: <?=$payment->fail_code?>)
                                    </small>
                                    <?php
                                }

                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="header">Customer</td>
                            <td>
                                <?=$payment->invoice->customer->label?>
                                <small>
                                    <?=mailto($payment->invoice->customer->email ?? $payment->invoice->customer->billing_email)?>
                                </small>
                            </td>
                        </tr>
                        <tr>
                            <td class="header">Customer Present</td>
                            <td><?=$payment->customer_present ? 'Yes' : 'No'?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-md-3">
            <div class="panel panel-default match-height">
                <div class="panel-heading">
                    <strong>Values</strong>
                </div>
                <table>
                    <tbody>
                        <tr>
                            <td class="header">Currency</td>
                            <td><?=$payment->currency->code?></td>
                        </tr>
                        <tr>
                            <td class="header">Amount</td>
                            <td><?=$payment->amount->formatted?></td>
                        </tr>
                        <tr>
                            <td class="header">Amount (refunded)</td>
                            <td><?=$payment->amount_refunded->formatted?></td>
                        </tr>
                        <tr>
                            <td class="header">Fee</td>
                            <td><?=$payment->fee->formatted?></td>
                        </tr>
                        <tr>
                            <td class="header">Fee (refunded)</td>
                            <td><?=$payment->fee_refunded->formatted?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-md-3">
            <div class="panel panel-default match-height">
                <div class="panel-heading">
                    <strong>Dates</strong>
                </div>
                <table>
                    <tbody>
                        <tr>
                            <td class="header">Created</td>
                            <td><?=toUserDateTime($payment->created)?></td>
                        </tr>
                        <tr>
                            <td class="header">Modified</td>
                            <td><?=toUserDateTime($payment->modified)?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-md-3">
            <div class="panel panel-default match-height">
                <div class="panel-heading">
                    <strong>Custom Data</strong>
                </div>
                <?php

                if (!empty((array) $payment->custom_data)) {

                    ?>
                    <pre style="padding: 1em;"><?=json_encode($payment->custom_data, JSON_PRETTY_PRINT)?></pre>
                    <?php

                } else {

                    ?>
                    <div class="panel-body text-muted">
                        No Custom Data
                    </div>
                    <?php
                }

                ?>
            </div>
        </div>
    </div>
    <div class="panel panel-default">
        <div class="panel-heading">
            <strong>Associated Refunds</strong>
            <?php
            if ($payment->is_refundable && userHasPermission(\Nails\Invoice\Admin\Permission\Payment\Refund::class)) {
                ?>
                <button class="btn btn-xs btn-danger js-invoice-refund pull-right"
                        data-id="<?=$payment->id?>"
                        data-max="<?=$payment->available_for_refund->raw?>"
                        data-max-formatted="<?=$payment->available_for_refund->formatted?>"
                >
                    Issue Refund
                </button>
                <?php
            }
            ?>
        </div>
        <?php

        if ($payment->refunds->count > 0) {

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
                        </tr>
                    </thead>
                    <tbody>
                        <?php

                        foreach ($payment->refunds->data as $oRefund) {

                            switch ($oRefund->status->id) {
                                case $oRefundModel::STATUS_COMPLETE:
                                    $sClass = 'success';
                                    $sText  = '';
                                    break;
                                case $oRefundModel::STATUS_PENDING:
                                case $oRefundModel::STATUS_PROCESSING:
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
                            <tr>
                                <td class="text-center"><?=$oRefund->id?></td>
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
                                <td>
                                    <?=$oRefund->amount->formatted?>
                                </td>
                                <td>
                                    <?=$oRefund->fee->formatted?>
                                </td>
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
</div>
