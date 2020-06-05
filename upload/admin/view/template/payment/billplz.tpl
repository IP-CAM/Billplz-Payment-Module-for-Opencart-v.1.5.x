<?php echo $header; ?>
<div id="content">
    <div class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
        <?php } ?>
    </div>
    <?php if ($error_warning) { ?>
    <div class="warning"><?php echo $error_warning; ?></div>
    <?php } ?>
    <div class="box">
        <tr>
            <td colspan="2"><p><?php echo $text_unsupported_warning ?></p></td>
        </tr>
        <div class="heading">
            <h1><img src="view/image/payment.png" /><?php echo $heading_title; ?></h1>
            <div class="buttons">
                <a onclick="$('#form').submit();" class="button"><span><?php echo $button_save; ?></span></a>
                <a onclick="location = '<?php echo $cancel; ?>';" class="button"><span><?php echo $button_cancel; ?></span></a>
            </div>
        </div>
        <div class="content">
            <div id="htabs" class="htabs">
                <a href="#tab-api-details"><?php echo $tab_api_details; ?></a>
                <a href="#tab-general"><?php echo $tab_general; ?></a>
                <a href="#tab-status"><?php echo $tab_order_status; ?></a>
            </div>

            <form action="<?php echo $action; ?>" method="POST" enctype="multipart/form-data" id="form">
                <div id="tab-api-details">
                    <table class="form">
                        <tr>
                            <td><?php echo $billplz_is_sandbox; ?></td>
                            <td><select name="billplz_is_sandbox_value">
                                <?php if ($billplz_is_sandbox_value) { ?>
                                    <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                                    <option value="0"><?php echo $text_disabled; ?></option>
                                <?php } else { ?>
                                    <option value="1"><?php echo $text_enabled; ?></option>
                                    <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                                <?php } ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td><span class="required">*</span> <?php echo $billplz_api_key; ?></td>
                            <td><input type="text" name="billplz_api_key_value" value="<?php echo $billplz_api_key_value; ?>" />
                            <?php if ($error_api_key) { ?>
                                <span class="error"><?php echo $error_api_key; ?></span>
                            <?php } ?>
                            </td>
                        </tr>
                        <tr>
                            <td><span class="required">*</span> <?php echo $billplz_collection_id; ?></td>
                            <td><input type="text" name="billplz_collection_id_value" value="<?php echo $billplz_collection_id_value; ?>" />
                            <?php if ($error_collection_id) { ?>
                                <span class="error"><?php echo $error_collection_id; ?></span>
                            <?php } ?>
                            </td>
                        </tr>
                        <tr>
                            <td><span class="required">*</span> <?php echo $billplz_x_signature; ?></td>
                            <td><input type="text" name="billplz_x_signature_value" value="<?php echo $billplz_x_signature_value; ?>" />
                            <?php if ($error_x_signature) { ?>
                                <span class="error"><?php echo $error_x_signature; ?></span>
                            <?php } ?>
                            </td>
                        </tr>
                    </table>
                </div>

                <div id="tab-general">
                    <table class="form">
                        <tr>
                            <td><?php echo $entry_total; ?></td>
                            <td><input type="text" name="billplz_total" value="<?php echo $billplz_total; ?>" />
                            </td>
                        </tr>
                        <tr>
                            <td><?php echo $entry_geo_zone; ?></td>
                            <td><select name="billplz_geo_zone_id">
                                <option value="0"><?php echo $text_all_zones; ?></option>
                                <?php foreach ($geo_zones as $geo_zone) { ?>
                                <?php if ($geo_zone['geo_zone_id'] == $billplz_geo_zone_id) { ?>
                                    <option value="<?php echo $geo_zone['geo_zone_id']; ?>" selected="selected"><?php echo $geo_zone['name']; ?></option>
                                <?php } else { ?>
                                    <option value="<?php echo $geo_zone['geo_zone_id']; ?>"><?php echo $geo_zone['name']; ?></option>
                                <?php } ?>
                                <?php } ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td><?php echo $entry_status; ?></td>
                            <td><select name="billplz_status">
                                <?php if ($billplz_status) { ?>
                                    <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                                    <option value="0"><?php echo $text_disabled; ?></option>
                                <?php } else { ?>
                                    <option value="1"><?php echo $text_enabled; ?></option>
                                    <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                                <?php } ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td><?php echo $entry_sort_order; ?>
                            </td>
                            <td><input type="text" name="billplz_sort_order" value="<?php echo $billplz_sort_order; ?>" size="1" />
                            </td>
                        </tr>
                    </table>
                </div>

                <div id="tab-status">
                    <table class="form">
                        <tr>
                            <td><?php echo $entry_completed_status; ?></td>
                            <td><select name="billplz_completed_status_id">
                                <?php foreach ($order_statuses as $order_status) { ?>
                                <?php if ($order_status['order_status_id'] == $billplz_completed_status_id) { ?>
                                    <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                                <?php } else { ?>
                                    <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                                <?php } ?>
                                <?php } ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td><?php echo $entry_pending_status; ?></td>
                            <td><select name="billplz_pending_status_id">
                                <?php foreach ($order_statuses as $order_status) { ?>
                                <?php if ($order_status['order_status_id'] == $billplz_pending_status_id) { ?>
                                    <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                                <?php } else { ?>
                                    <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                                <?php } ?>
                                <?php } ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
            </form>
        </div>
    </div>
</div>
<script type="text/javascript"><!--
    $('#htabs a').tabs();
//--></script>
<?php echo $footer; ?>