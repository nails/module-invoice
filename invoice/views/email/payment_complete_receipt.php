<p>
    This email confirms receipt of <strong>{{{payment.amount}}}</strong> against invoice
    <strong>{{invoice.ref}}</strong>. The payment has been given reference <strong>{{payment.ref}}</strong>.
</p>
<?php
include __DIR__ . DIRECTORY_SEPARATOR . 'invoice.php';
?>
<p>
    <a href="{{invoice.urls.download}}" class="btn btn-primary btn-block">
        Download Invoice
    </a>
</p>
