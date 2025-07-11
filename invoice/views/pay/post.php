<?php
/**
 * @var string $sMessage
 * @var string $sFormUrl
 * @var array  $aFormData
 */
?>
<div class="nails-invoice post center-screen">
    <div class="panel">
        <div class="panel__body text-center">
            <p><?=$sMessage?></p>
        </div>
    </div>
</div>
<form id="form" method="POST" action="<?=$sFormUrl?>">
    <?php
    foreach ($aFormData as $sKey => $sValue) {
        ?>
        <input type="hidden" name="<?=$sKey?>" value="<?=$sValue?>" />
        <?php
    }
    ?>
</form>
<?=scriptOpen()?>
window.onload = function() {
    document.getElementById('form').submit();
}
<?=scriptClose()?>
