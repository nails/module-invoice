<div class="nails-invoice paid center-screen" id="js-invoice">
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
                Invoice <?=$oPayment->invoice->ref?>
            </h1>
        </div>
        <div class="panel__body text-center">
            <p>Thank you for your payment of <?=$oPayment->amount->formatted?>.</p>
            <p>Your payment reference is <strong><?=$oPayment->ref?></strong>.</p>
            <p>
                <a href="<?=siteUrl($oPayment->urls->success)?>" class="btn btn--block btn--primary">
                    Continue
                </a>
                <a href="<?=$oPayment->invoice->urls->download?>" class="btn btn--block">
                    Download Invoice
                </a>
            </p>
        </div>
    </div>
</div>
