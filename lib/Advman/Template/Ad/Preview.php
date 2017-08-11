<?php

class Advman_Template_Ad_Preview
{
    function display($ad)
    {
        global $advman_engine;
        // Main pane - default options
?>
<div class="wrap">
	<div id="icon-edit" class="icon32"><br /></div>
    <h2><?php printf(__('Preview %s Ad:', 'advman'), $ad->network_name); ?> <span class="<?php echo strtolower(get_class($ad)); ?>"><?php echo "[{$ad->id}] " . $ad->name; ?></span></h2>
    <p><?php echo __('BEGIN AD PREVIEW:', 'advman'); ?></p>
<?php

        echo $ad->display();
        $advman_engine->incrementStats($ad);
?>
    <p><?php echo __('END AD PREVIEW:', 'advman'); ?></p>
    <div id="preview-action">
        <a class="edit button" href="<?php echo $ad->get_edit_url(); ?>"><?php _e('Back', 'advman'); ?></a>
    </div><!-- preview-action -->
</div>
<?php
    }
}
?>