<div class="nails-invoice paid u-center-screen" id="js-invoice">
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
        <h1 class="panel__header text-center">
            Invoice <?=$oPayment->invoice->ref?>
        </h1>
        <div class="panel__body text-center">
            <p>Thank you for your payment of <?=$oPayment->amount->formatted?>.</p>
            <p>Your payment reference is <strong><?=$oPayment->ref?></strong>.</p>
            <p>
                <a href="<?=siteUrl($oPayment->urls->success)?>" class="btn btn--block btn--primary">
                    Continue
                </a>
            </p>
            <p>
                <a href="<?=$oPayment->invoice->urls->download?>" class="btn btn--block">
                    Download Invoice
                </a>
            </p>
        </div>
    </div>
</div>
