<?php

use Nails\Factory;
use Nails\Invoice\Constants;
use Nails\Invoice\Resource;
use Nails\Invoice\Model;

/**
 * @var Resource\Refund $refund
 */

?>
<div class="group-invoice refund view">
    <div class="row">
        <div class="col-md-4">
            <div class="panel panel-default match-height">
                <div class="panel-heading">
                    <strong>Details</strong>
                </div>
                <table>
                    <tbody>
                        <tr>
                            <td class="header">Gateway</td>
                            <td>
                                <?=$refund->payment->driver->getLabel()?>
                                <small>
                                    <?=$refund->payment->driver->getSlug()?>
                                </small>
                            </td>
                        </tr>
                        <tr>
                            <td class="header">Transaction ID</td>
                            <td><?=$refund->transaction_id?></td>
                        </tr>
                        <tr>
                            <td class="header">Invoice</td>
                            <td><?=anchor(\Nails\Invoice\Admin\Controller\Invoice::url('view/' . $refund->payment->invoice->id), $refund->payment->invoice->ref)?></td>
                        </tr>
                        <tr>
                            <td class="header">Reason</td>
                            <td><?=$refund->reason?></td>
                        </tr>
                        <tr>
                            <td class="header">Status</td>
                            <td>
                                <?php

                                echo $refund->status->label;

                                if (!empty($refund->fail_msg)) {
                                    ?>
                                    <small class="text-danger">
                                        <?=$refund->fail_msg?> (Code: <?=$refund->fail_code?>)
                                    </small>
                                    <?php
                                }

                                ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-md-4">
            <div class="panel panel-default match-height">
                <div class="panel-heading">
                    <strong>Values</strong>
                </div>
                <table>
                    <tbody>
                        <tr>
                            <td class="header">Currency</td>
                            <td><?=$refund->currency->code?></td>
                        </tr>
                        <tr>
                            <td class="header">Amount</td>
                            <td><?=$refund->amount->formatted?></td>
                        </tr>
                        <tr>
                            <td class="header">Fee</td>
                            <td><?=$refund->fee->formatted?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-md-4">
            <div class="panel panel-default match-height">
                <div class="panel-heading">
                    <strong>Dates</strong>
                </div>
                <table>
                    <tbody>
                        <tr>
                            <td class="header">Created</td>
                            <td><?=toUserDateTime($refund->created)?></td>
                        </tr>
                        <tr>
                            <td class="header">Modified</td>
                            <td><?=toUserDateTime($refund->modified)?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
