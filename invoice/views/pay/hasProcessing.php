<div class="nails-invoice pending center-screen" id="js-invoice">
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
    <div class="panel">
        <div class="panel__header">
            <h1 class="panel__title text-center">
                Invoice <?=$oInvoice->ref?>
            </h1>
        </div>
        <div class="panel__body text-center">
            <p class="alert alert--warning">There are pending payments against this invoice.</p>
            <p>
                The following payment<?=count($aProcessingPayments) > 1 ? 's are' : ' is'?> pending against this
                invoice. To avoid duplicate payments, this system will not let you make further payments.
            </p>
            <ul class="list list--bordered">
                <?php

                foreach ($aProcessingPayments as $oPayment) {
                    ?>
                    <li>
                        <strong><?=$oPayment->ref?></strong>
                        &ndash; <?=$oPayment->amount->formatted?>
                    </li>
                    <?php
                }

                ?>
            </ul>
        </div>
    </div>
</div>
