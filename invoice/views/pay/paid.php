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
                Invoice <?=$oInvoice->ref?>
            </h1>
        </div>
        <div class="panel__body text-center">
            <p class="alert alert--success">This invoice has been paid.</p>
            <p>Payment was received <?=$oInvoice->paid->formatted?>, many thanks for your business.</p>
            <p>
                <a href="<?=$oInvoice->urls->download?>" class="btn btn--block">
                    Download Invoice
                </a>
            </p>
        </div>
    </div>
</div>
