<?php
class Advman_Template_Notice
{
	function display($notices = null)
	{
		if (is_array($notices)) {
			foreach ($notices as $action => $notice) {

?>				<div id="message" class="updated fade">
					<p>
						<form action="admin.php?page=advman-list" method="post" id="advman-config-manage" enctype="multipart/form-data">
						<input type="hidden" name="advman-action" value="<?php echo $action; ?>">
						<?php echo $notice['text']; ?>
<?php
				if ($notice['confirm'] == 'yn') {
?>						<input class="button-secondary action" name="advman-notice-confirm-yes" type="submit" value="<?php _e('Yes', 'advman'); ?>">
						<input class="button-secondary action" name="advman-notice-confirm-no" type="submit" value="<?php _e('No', 'advman'); ?>">
<?php
				} elseif ($notice['confirm'] == 'ok') {
?>						<input class="button-secondary action" name="advman-notice-confirm-ok" type="submit" value="<?php _e('OK', 'advman'); ?>">
<?php
				} elseif ($notice['confirm'] == 'x') {
?>						<input class="button-secondary action" name="advman-notice-confirm-x" type="submit" value="x">
<?php
				} elseif ($notice['confirm'] == 'learn') {
?>						<input class="button-secondary button-link action" name="advman-notice-confirm-yes" type="submit" value="<?php _e('Learn More', 'advman'); ?>">
                        <input class="button-secondary button-link action" name="advman-notice-confirm-no" type="submit" value="<?php _e('No Thanks', 'advman'); ?>">
<?php
                }
?>						</form>
					</p>
				</div>
<?php
			}
		}
	}
}
?>