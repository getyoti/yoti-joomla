<?php
$yotiavatar = $displayData['yotiavatar'];
?>
<div class="yotiprofile">
    <?php if (!empty($yotiavatar)) : ?>
        <div class="control-group">
            <?php echo $yotiavatar; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($lastname)) : ?>
        <div class="control-group">
            <?php echo $lastname; ?>
        </div>
    <?php endif; ?>
</div>