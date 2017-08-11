<?php
require_once(OX_LIB . '/Tools.php');
class Advman_Template_Settings
{
	function display($target = null)
	{
		global $advman_engine;
		
		$action = isset($_POST['advman-action']) ? OX_Tools::sanitize($_POST['advman-action'], 'key') : '';

        $oxEnableAdjs = $advman_engine->getSetting('enable-adjs');
        if (is_null($oxEnableAdjs)) {
            $oxEnableAdjs = false;
        }
		$oxEnablePhp = $advman_engine->getSetting('enable-php');
		if (is_null($oxEnablePhp)) {
			$oxEnablePhp = false;
		}
		$oxStats = $advman_engine->getSetting('stats');
		if (is_null($oxStats)) {
			$oxStats = true;
		}
		$oxPurgeStatsDays = $advman_engine->getSetting('purge-stats-days');
		if (is_null($oxPurgeStatsDays)) {
			$oxPurgeStatsDays = 30;
		}
		
?><div class="wrap">
	<div id="icon-options-general" class="icon32"><br /></div>
<h2><?php _e('Ad Settings', 'advman'); ?></h2>

<?php if ($action == 'save') : ?>
<div id="message" class="updated fade"><p><strong><?php _e('Settings saved.') ?></strong></p></div>
<?php endif; ?>

<form action="" method="post" id="advman-form" enctype="multipart/form-data">
<input type="hidden" name="advman-action" id="advman-action" value="save" />

<table class="form-table">
<tbody>
<tr valign="top">
    <th scope="row"><?php _e('Ad Quality', 'advman'); ?></th>
    <td>
        <span class="setting-description"><?php _e('Ad quality tools help small and medium sized publishers (like many Wordpress bloggers) learn more about their traffic quality, and get access to larger advertisers with bigger budgets.  These tools are installed automatically.', 'advman'); ?></span><br><br>
        <fieldset>
            <legend class="hidden"><?php _e('Ad Quality', 'advman'); ?></legend>
            <label for="advman-enable-adjs"><input name="advman-enable-adjs" type="checkbox" id="advman-enable-adjs" value="1"<?php echo $oxEnableAdjs ? ' checked="checked"' : ''; ?> /> <?php _e('Allow ad quality measurement (Beta)', 'advman'); ?></label>
        </fieldset><br>
        <span class="setting-description"><?php _e("By turning on ad quality measurement, your blog which will use javascript from Ad.js to measure the quality of your visitors, and report the results to you in the analytics screen.  Installation is automatic.<br><br><strong>Please Note:  The automatic installation will send your blog domain and admin email address to Ad.js to obtain a client ID.</strong>", 'advman'); ?></span><br><br>
    </td>
</tr>
<tr valign="top">
	<th scope="row">
		<label for="advman-stats"><?php _e('Statistics', 'advman'); ?></label>
	</th>
	<td>
		<fieldset>
			<legend class="hidden"><?php _e('Statistics', 'advman'); ?></legend>
			<label for="advman-stats"><input name="advman-stats" type="checkbox" id="advman-stats" value="1"<?php echo $oxStats ? ' checked="checked"' : ''; ?> /> <?php _e('Collect statistics about the number of ads served', 'advman'); ?></label>
		</fieldset>
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('Purge after:', 'advman'); ?> <input type="text" name="advman-purge-stats-days" value="<?php echo $oxPurgeStatsDays; ?>" class="small-text" /> <?php _e('days', 'advman'); ?><br />
		<span class="setting-description"><?php _e('Collecting statistics about your ad serving will give you insight on how many ads have been viewed by your users.  It is a good idea to purge these stats after maximum 100 days so that your database does not get too full.', 'advman'); ?></span>
	</td>
</tr>
<tr valign="top">
	<th scope="row"><?php _e('Other Settings', 'advman'); ?></th>
	<td>
		<fieldset>
			<legend class="hidden"><?php _e('Other Settings', 'advman'); ?></legend>
			<label for="advman-enable-php"><input name="advman-enable-php" type="checkbox" id="advman-enable-php" value="1"<?php echo $oxEnablePhp ? ' checked="checked"' : ''; ?> /> <?php _e('Allow PHP Code in Ads', 'advman'); ?></label>
		</fieldset>
		<span class="setting-description"><?php _e('Allowing PHP code in ads will execute any PHP code when delivering an ad.  Be careful - only enable if you know what you are doing.', 'advman'); ?></span>
	</td>
</tr>
</tbody>
</table>


<p class="submit">
<input type="submit" name="Submit" class="button-primary" value="<?php _e('Save Changes', 'advman'); ?>" />
</p>
</form>

</div>

<?php
	}
}
?>