<form action="<?php echo $action; ?>" method="post" id="payment">
    <div class="buttons">
        <div class="right">
                 <!--<a onclick="$('#payment').submit();" class="button"><span><?php echo $button_confirm; ?></span></a>-->
            <input type="hidden" name="name" value="<?php echo $name; ?>" />
            <input type="hidden" name="email" value="<?php echo $email; ?>" />
            <input type="hidden" name="description" value="<?php echo $description; ?>" />
            <input type="hidden" name="mobile" value="<?php echo $mobile; ?>" />
            <input type="hidden" name="reference_1_label" value="<?php echo $reference_1_label; ?>" />
            <input type="hidden" name="reference_1" value="<?php echo $reference_1; ?>" />
            <input type="hidden" name="amount" value="<?php echo $amount; ?>" />
            <input type="hidden" name="redirect_url" value="<?php echo $redirect_url; ?>" />
            <input type="hidden" name="callback_url" value="<?php echo $callback_url; ?>" />
            <input type="hidden" name="delivery" value="<?php echo $delivery; ?>" />
            <input type="hidden" name="sha256" value="<?php echo $sha256; ?>" />
            <input type="submit" value="<?php echo $button_confirm; ?>" class="button" />
        </div>
    </div>
</form>
