<?php if ($is_sandbox) { ?>
  <div class="warning"><?php echo $text_is_sandbox; ?></div>
<?php } ?>
<form action="<?php echo $action; ?>" method="post" id="payment">
  <div class="buttons">
    <div class="right">
      <input type="submit" value="<?php echo $button_confirm; ?>" class="button" />
    </div>
  </div>
</form>
