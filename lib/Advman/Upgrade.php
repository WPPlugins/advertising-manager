<?php

include_once (ADVMAN_LIB . '/Admin.php');

class Advman_Upgrade
{
	// Upgrade the underlying data structure to the latest version
	static function upgrade_advman(&$data)
	{
		$version = Advman_Upgrade::_get_version($data);
		Advman_Upgrade::_backup($data, $version);
		$versions = array('3.4', '3.4.2', '3.4.3', '3.4.7', '3.4.9', '3.4.12', '3.4.14', '3.4.15', '3.4.20', '3.4.25', '3.4.29', '3.5.1', '3.5.2');
		foreach ($versions as $v) {
			if (version_compare($version, $v, '<')) {
				$func = 'advman_' . str_replace('.','_',$v);
				Advman_Upgrade::$func($data);
			}
		}

		$data['settings']['version'] = ADVMAN_VERSION;
	}

	static function _selectAd($data, $name)
	{
		if (!empty($name)) {
			// Find the ads which match the name
			$ads = $data['ads'];

			foreach ($ads as $id => $ad) {
				if (is_array($ad) && $ad['name'] == $name) return $ad;
				if (is_object($ad) && $ad->name == $name) return $ad;
			}
		}
	}

    static function advman_3_5_2(&$data)
    {
        // Split out data into different data keys, and remove the 'general' key
        update_option('plugin_advman_settings', $data['settings']);
        update_option('plugin_advman_ads', $data['ads']);
        update_option('plugin_advman_networks', $data['networks']);
        update_option('plugin_advman_stats', $data['stats']);
        // Delete the 'general' data key
        delete_option('plugin_advman');
        // Set backup to position 1 (position 0 is the current backup)
        $backup = get_option('plugin_advman_backup');
        if (!empty($backup)) {
            update_option('plugin_advman_backup_1', $backup);
        }
        delete_option('plugin_advman_backup');
        delete_option('plugin_adsensem_backup');
    }

	static function advman_3_5_1(&$data)
	{
		// Check to see if we need to migrate any old ad tags to new ad tags
		global $advman_engine;
		$patterns = array(
			'/<!--adsense-->/',
			'/&lt;!--adsense--&gt;/',
			'/<!--adsense#(.*?)-->/',
			'/&lt;!--adsense#(.*?)--&gt;/',
			'/<!--am-->/',
			'/&lt;!--am--&gt;/',
			'/<!--am#(.*?)-->/',
			'/&lt;!--am#(.*?)--&gt;/',
			'/\[ad#(.*?)\]/',
		);

		$found_posts = 0;
		$found_ads = 0;

		$offset = 0;
		$numberposts = 10;

		$args = array('numberposts' => $numberposts, 'offset' => $offset, 'post_type' => array('post','page'), 'suppress_filters' => false);
		$posts = get_posts($args);

		while ($posts) {
			foreach ($posts as $post) {
				$ad_found_in_post = false;
				if (!empty($post->post_content)) {
					foreach ($patterns as $pattern) {
						if (preg_match_all($pattern, $post->post_content, $matches)) {
							if (!empty($matches[1])) {
								for ($i=0; $i<sizeof($matches[1]); $i++) {
									$name = $matches[1][$i];
									$ad = Advman_Upgrade::_selectAd($data, $name);
									if (!$ad) {
										$name = html_entity_decode($name);
										$ad = Advman_Upgrade::_selectAd($data, $name);
									}
									if (!$ad) {
										$name = str_replace(";","\\;",$name);
										$ad = Advman_Upgrade::_selectAd($data, $name);
									}
									if ($ad) {
										$found_ads++;
										if (!$ad_found_in_post) {
											$ad_found_in_post = true;
											$found_posts++;
										}
									}
								}
							} else {
								$found_ads++;
								if (!$ad_found_in_post) {
									$ad_found_in_post = true;
									$found_posts++;
								}
							}
						}
					}
				}
			}
			$offset += $numberposts;
			$args = array('numberposts' => $numberposts, 'offset' => $offset, 'post_type' => array('post','page'), 'suppress_filters' => false);
			$posts = get_posts($args);
		}
		if ($found_ads) {
			$question = __('You have [num_ads] Advertising Manager ads in [num_posts] posts that need to be upgraded to a new ad shortcode format.  <b>Do you want to automatically upgrade these shortcodes?</b>', 'advman');
			$question = str_replace('[num_ads]', $found_ads, $question);
			$question = str_replace('[num_posts]', $found_posts, $question);
			Advman_Admin::add_notice('update_shortcodes', $question, 'yn');
		}
	}

	static function update_shortcodes()
	{
		// Check to see if we need to migrate any old ad tags to new ad tags
		global $advman_engine;
		$data = $advman_engine->dal->data;

		$patterns = array(
			'/<!--adsense-->/',
			'/&lt;!--adsense--&gt;/',
			'/<!--adsense#(.*?)-->/',
			'/&lt;!--adsense#(.*?)--&gt;/',
			'/<!--am-->/',
			'/&lt;!--am--&gt;/',
			'/<!--am#(.*?)-->/',
			'/&lt;!--am#(.*?)--&gt;/',
			'/\[ad#(.*?)\]/',
		);

		$offset = 0;
		$numberposts = 10;

		$args = array('numberposts' => $numberposts, 'offset' => $offset, 'post_type' => array('post','page'), 'suppress_filters' => false);
		$posts = get_posts($args);

		while ($posts) {
			foreach ($posts as $post) {
				if (!empty($post->post_content)) {
					$post_content = $post->post_content;
					foreach ($patterns as $pattern) {
						if (preg_match_all($pattern, $post->post_content, $matches)) {
							if (!empty($matches[1])) {
								for ($i = 0; $i < sizeof($matches[1]); $i++) {
									$name = $matches[1][$i];
									$ad = Advman_Upgrade::_selectAd($data, $name);
									if (!$ad) {
										$name = html_entity_decode($name);
										$ad = Advman_Upgrade::_selectAd($data, $name);
									}
									if (!$ad) {
										$name = str_replace(";", "\\;", $name);
										$ad = Advman_Upgrade::_selectAd($data, $name);
									}
									if ($ad) {
										$search = $matches[0][$i];
										$replace = "[ad name=\"$name\"]";
										$post_content = str_replace($search, $replace, $post_content);
									}
								}
							} else {
								$search = str_replace("/", "", $pattern);
								$replace = "[ad]";
								$post_content = str_replace($search, $replace, $post_content);
							}
						}
					}
					if ($post->post_content != $post_content) {
						$post->post_content = $post_content;
						wp_update_post($post);
					}
				}
			}

			$offset += $numberposts;
			$args = array('numberposts' => $numberposts, 'offset' => $offset, 'post_type' => array('post','page'), 'suppress_filters' => false);
			$posts = get_posts($args);
		}
	}

	static function advman_3_4_29(&$data)
	{
		// Convert stats to a new format
		$stats = $data['stats'];
		$new_stats = array();

		foreach ($stats as $dt => $stat) {
			foreach ($stat as $adId => $impressions) {
				$new_stats['d'][$dt]['ad'][$adId]['i'] = $impressions;
			}
		}
		$data['stats'] = $new_stats;

		// Remove publisher-id  (not used)
		if (isset($data['settings']['publisher-id'])) {
			unset($data['settings']['publisher-id']);
		}
		// Change the purge stats days to 100 if still at the default of 30
		if (isset($data['settings']['purge-stats-days']) && $data['settings']['purge-stats-days'] == 30) {
			$data['settings']['purge-stats-days'] = 100;
		}
		// Re-send adjs client ID
		if ($data['settings']['enable-adjs']) {

			$clientId = $data['settings']['adjs-clientid'];
			if ($clientId) {
				$url = "http://adjs.io/beta_signups/$clientId";
				$params = array(
					'method'  => 'PUT',
					'headers' => array("Accept"=>'application/json'),
					'body'    => array('beta_signup' => array("email"=>get_option('admin_email'),"url"=> get_option('siteurl')))
				);

				wp_remote_request($url, $params);
			}
		}
	}
	static function advman_3_4_25(&$data)
	{
		// Remove OpenX Market - does not work
		unset($data['settings']['openx-market']);
		unset($data['settings']['openx-market-cpm']);

		// Set the category to be 'all' (by making it = '')
		foreach ($data['ads'] as $id => $ad) {
			if (!isset($data['ads'][$id]['openx-market'])) {
				unset($data['ads'][$id]['openx-market']);
			}
			if (!isset($data['ads'][$id]['openx-market-cpm'])) {
				unset($data['ads'][$id]['openx-market-cpm']);
			}
		}
		foreach ($data['networks'] as $id => $network) {
			if (!isset($data['networks'][$id]['openx-market'])) {
				unset($data['networks'][$id]['openx-market']);
			}
			if (!isset($data['networks'][$id]['openx-market-cpm'])) {
				unset($data['networks'][$id]['openx-market-cpm']);
			}
		}

	}
	static function advman_3_4_20(&$data)
	{
		// Remove synchronization settings
		unset($data['settings']['last-sync']);
		unset($data['settings']['openx-sync']);
	}
	static function advman_3_4_15(&$data)
	{
		// Set the category to be 'all' (by making it = '')
		foreach ($data['ads'] as $id => $ad) {
			if (!isset($data['ads'][$id]['show-tag'])) {
				$data['ads'][$id]['show-tag'] = '';
			}
		}
	}
	static function advman_3_4_14(&$data)
	{
		// Add the 'enable php' setting
		if (!isset($data['settings']['enable-php'])) {
			$data['settings']['enable-php'] = false;
		}

		// Add the 'enable stats' setting
		if (!isset($data['settings']['stats'])) {
			$data['settings']['stats'] = true;
		}

		// Add the 'purge stats' setting
		if (!isset($data['settings']['enable-php'])) {
			$data['settings']['purge-stats-days'] = 30;
		}

		// Initialise the stats array
		$data['stats'] = array();
	}

	static function advman_3_4_12(&$data)
	{
		// Set the category to be 'all' (by making it = '')
		foreach ($data['ads'] as $id => $ad) {
			if (!isset($data['ads'][$id]['show-category'])) {
				$data['ads'][$id]['show-category'] = '';
			}
		}
	}
	static function advman_3_4_9(&$data)
	{
		// If all authors are selected, make the value '' (which means all), so that when new users are added, they will be included.
		$users = get_users_of_blog();
		foreach ($data['ads'] as $id => $ad) {
			if (is_array($ad['show-author'])) {
				$all = true;
				foreach ($users as $user) {
					if (!in_array($user->user_id, $ad['show-author'])) {
						$all = false;
						break;
					}
				}
				if ($all) {
					$data['ads'][$id]['show-author'] = '';
				}
			}
		}
	}
	static function advman_3_4_7(&$data)
	{
		// Account ID for adsense did not get saved correctly.  See if we can grab it and save it correctly
		if (isset($data['networks']['ox_plugin_adsense']['account-id'])) {
			$accountId = $data['networks']['ox_plugin_adsense']['account-id'];
			if (is_numeric($accountId)) {
				$accountId = 'pub-' . $accountId;
				$data['networks']['ox_plugin_adsense']['account-id'] = $accountId;
			}
			foreach ($data['ads'] as $id => $ad) {
				if ($ad['class'] = 'ox_plugin_adsense' && empty($ad['account-id'])) {
					$data['ads'][$id]['account-id'] = $accountId;
				}
			}
		}
	}
	static function advman_3_4_3(&$data)
	{
		// for some reason our meta boxes were hidden - remove this from database
		$us = get_users_of_blog();
		foreach ($us as $u) {
			delete_usermeta($u->user_id, 'meta-box-hidden_advman');
		}
	}
	static function advman_3_4_2(&$data)
	{
		// Combine all show-* stuff into a single variable
		// Also remove the default values for the show-* stuff
		$types = array('page', 'post', 'home', 'search', 'archive');
		// Authors
		$users = array();
		$us = get_users_of_blog();
		foreach ($us as $u) {
			$users[] = $u->user_id;
		}

		foreach ($data['ads'] as $id => $ad) {

			$pageTypes = array();
			foreach ($types as $type) {
				if ($ad['show-' . $type] == 'yes') {
					$pageTypes[] = $type;
				} elseif (empty($ad['show-' . $type])) {
					$nw = $data['networks'][$ad['class']];
					if ($nw['show-' . $type] == 'yes') {
						$pageTypes[] = $type;
					}
				}

				unset($data['ads'][$id]['show-' . $type]);
			}
			$data['ads'][$id]['show-pagetype'] = $pageTypes;

			if (!empty($ad['show-author'])) {
				if (!is_array($ad['show-author'])) {
					$data['ads'][$id]['show-author'] = $data['ads'][$id]['author'] == 'all' ? $users : array($data['ads'][$id]['author']);
				}
			} else {
				$nw = $data['networks'][$ad['class']];
				if (!empty($nw['show-author'])) {
					if (is_array($nw['show-author'])) {
						$data['ads'][$id]['show-author'] = $nw['show-author'];
					} elseif ($nw['show-author'] == 'all') {
						$data['ads'][$id]['show-author'] = $users;
					} else {
						$data['ads'][$id]['show-author'] = array($nw['show-author']);
					}
				}
			}
		}

		foreach ($data['networks'] as $c => $nw) {
			foreach ($types as $type) {
				unset($data['networks'][$c]['show-' . $type]);
			}
			unset($data['networks'][$c]['show-author']);
		}
	}

	static function advman_3_4(&$data)
	{
		// Move the where last-sync is stored
		if (isset($data['last-sync'])) {
			$data['settings']['last-sync'] = $data['last-sync'];
			unset($data['last-sync']);
		}
		// Move the 'slot' and 'ad' adtypes to 'all'
		foreach ($data['ads'] as $id => $ad) {
			if (isset($ad['adtype'])) {
				$v = $ad['adtype'];
				if ($v == 'slot' || $v == 'ad') {
					$data['ads'][$id]['adtype'] = 'all';
				}
			}
		}
		// Make sure the class name key is lower case (php4 is case insensitive)
		$nw = array();
		foreach ($data['networks'] as $k => $v) {
			$nw[strtolower($k)] = $v;
		}
		$data['networks'] = $nw;
	}

	static function _get_version(&$data)
	{
		$version = $data['settings']['version'];
		if (empty($version)) {
			$version = $data['version'];
			if ($version == 'ADVMAN_VERSION') {
				$version = '3.3.4';
			}
			unset ($data['version']);
			$data['settings']['version'] = $version;
		}
		return $version;
	}

	static function _backup($data, $version)
	{
        // Backup the last 4 versions of plugin_advman.

        // remove the oldest version
        delete_option('plugin_advamn_backup_3');
        // move pos 2 to pos 3
		$backup = get_option('plugin_advman_backup_2');
		if (!empty($backup)) {
			update_option('plugin_advman_backup_3', $backup);
		}
        // move pos 1 to pos 2
        $backup = get_option('plugin_advman_backup_1');
        if (!empty($backup)) {
            update_option('plugin_advman_backup_2', $backup);
        }
        // move latest to pos 1
        $backup = get_option('plugin_advman_backup_0');
        if (!empty($backup)) {
            update_option('plugin_advman_backup_1', $backup);
        }
        // insert current into latest
        update_option('plugin_advman_backup_0', $data);
	}

	static function _get_code(&$ad)
	{
		$code = '';

		switch (strtolower($ad['class'])) {
			case 'ox_plugin_adbrite' : $code = Advman_Upgrade::_get_code_adbrite($ad); break;
			case 'ox_plugin_adgridwork' : $code = Advman_Upgrade::_get_code_adgridwork($ad); break;
			case 'ox_plugin_adpinion' : $code = Advman_Upgrade::_get_code_adpinion($ad); break;
			case 'ox_plugin_adroll' : $code = Advman_Upgrade::_get_code_adroll($ad); break;
			case 'ox_plugin_adsense' : $code = Advman_Upgrade::_get_code_adsense($ad); break;
			case 'ox_plugin_cj' : $code = Advman_Upgrade::_get_code_cj($ad); break;
			case 'ox_plugin_crispads' : $code = Advman_Upgrade::_get_code_crispads($ad); break;
			case 'ox_plugin_openx' : $code = Advman_Upgrade::_get_code_openx($ad); break;
			case 'ox_plugin_shoppingads' : $code = Advman_Upgrade::_get_code_shoppingads($ad); break;
			case 'ox_plugin_widgetbucks' : $code = Advman_Upgrade::_get_code_widgetbucks($ad); break;
			case 'ox_plugin_ypn' : $code = Advman_Upgrade::_get_code_ypn($ad); break;
		}

		if (!empty($code)) {
			$oAd = new $ad['class'];
			$oAd->import_settings($code);
			foreach ($oAd->p as $property => $value) {
				$ad[$property] = $value;
			}
		}
	}

	static function _get_code_adsense($ad)
	{
		$code = '<script type="text/javascript"><!--' . "\n";
		$code.= 'google_ad_client = "' . $ad['account-id'] . '";' . "\n";
		$code.= 'google_ad_slot = "' . str_pad($ad['slot'],10,'0',STR_PAD_LEFT) . '"' . ";\n"; //String padding to max 10 char slot ID

		if($ad['adtype']=='ref_text'){
			$code.= 'google_ad_output = "textlink"' . ";\n";
			$code.= 'google_ad_format = "ref_text"' . ";\n";
			$code.= 'google_cpa_choice = ""' . ";\n";
		} else if($ad['adtype']=='ref_image'){
			$code.= 'google_ad_width = ' . $ad['width'] . ";\n";
			$code.= 'google_ad_height = ' . $ad['height'] . ";\n";
			$code.= 'google_cpa_choice = ""' . ";\n";
		} else {
			$code.= 'google_ad_width = ' . $ad['width'] . ";\n";
			$code.= 'google_ad_height = ' . $ad['height'] . ";\n";
		}

		$code.= '//--></script>' . "\n";

		$code.= '<script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js"></script>' . "\n";

		return $code;
	}

	static function _get_code_adbrite($ad)
	{
		$code ='<!-- Begin: AdBrite -->';
		$code .= '<script type="text/javascript">' . "\n";
		$code .= "var AdBrite_Title_Color = '" . $ad['color-title'] . "'\n";
		$code .= "var AdBrite_Text_Color = '" . $ad['color-text'] . "'\n";
		$code .= "var AdBrite_Background_Color = '" . $ad['color-bg'] . "'\n";
		$code .= "var AdBrite_Border_Color = '" . $ad['color-border'] . "'\n";
		$code .= '</script>' . "\n";
		$code .= '<script src="http://ads.adbrite.com/mb/text_group.php?sid=' . $ad['slot'] . '&zs=' . $ad['account-id'] . '" type="text/javascript"></script>';
		$code .= '<div><a target="_top" href="http://www.adbrite.com/mb/commerce/purchase_form.php?opid=' . $ad['slot'] . '&afsid=1" style="font-weight:bold;font-family:Arial;font-size:13px;">Your Ad Here</a></div>';
		$code .= '<!-- End: AdBrite -->';

		return $code;
	}

	static function _get_code_adgridwork($ad)
	{
		$code ='<a href="http://www.adgridwork.com/?r=' . $ad['account-id'] . '" style="color: #' . $ad['color-link'] .  '; font-size: 14px" target="_blank">Free Advertising</a>';
		$code.='<script type="text/javascript">' . "\n";
		$code.="var sid = '"  . $ad['slot'] . "';\n";
		$code.="var title_color = '" . $ad['color-title'] . "';\n";
		$code.="var description_color = '" . $ad['color-text'] . "';\n";
		$code.="var link_color = '" . $ad['color-link'] . "';\n";
		$code.="var background_color = '" . $ad['color-bg'] . "';\n";
		$code.="var border_color = '" . $ad['color-border'] . "';\n";
		$code.='</script><script type="text/javascript" src="http://www.mediagridwork.com/mx.js"></script>';

		return $code;
	}

	static function _get_code_adpinion($ad)
	{
		if($ad['width']>$ad['height']){$xwidth=18;$xheight=17;} else {$xwidth=0;$xheight=35;}
		$code ='';
		$code .= '<iframe src="http://www.adpinion.com/app/adpinion_frame?website=' . $ad['account-id'] . '&amp;width=' . $ad['width'] . '&amp;height=' . $ad['height'] . '" ';
		$code .= 'id="adframe" style="width:' . ($ad['width']+$xwidth) . 'px;height:' . ($ad['height']+$xheight) . 'px;" scrolling="no" frameborder="0">.</iframe>';

		return $code;
	}

	static function _get_code_adroll($ad)
	{
		$code ='';
		$code .= '<!-- Start: Adroll Ads -->';
		$code .= '<script type="text/javascript" src="http://c.adroll.com/r/' . $ad['account-id'] . '/' . $ad['slot'] . '/">';
		$code .= '</script>';
		$code .= '<!-- Start: Adroll Profile Link -->';
		$code .= '<script type="text/javascript" src="http://c.adroll.com/r/' . $ad['account-id'] . '/' . $ad['slot'] . '/link">';
		$code .= '</script>';

		return $code;
	}

	static function _get_code_adsense_ad($ad)
	{
		$code='';

		$code .= '<script type="text/javascript"><!--' . "\n";
		$code.= 'google_ad_client = "' . $ad['account-id'] . '";' . "\n";

		if($ad['channel']!==''){ $code.= 'google_ad_channel = "' . $ad['channel'] . '";' . "\n"; }
		if($ad['uistyle']!==''){ $code.= 'google_ui_features = "rc:' . $ad['uistyle'] . '";' . "\n"; }

		$code.= 'google_ad_width = ' . $ad['width'] . ";\n";
		$code.= 'google_ad_height = ' . $ad['height'] . ";\n";

		$code.= 'google_ad_format = "' . $ad['adformat'] . '_as"' . ";\n";
		$code.= 'google_ad_type = "' . $ad['adtype'] . '"' . ";\n";

		switch ($ad['alternate-ad']) {
			case 'url'		: $code.= 'google_alternate_ad_url = "' . $ad['alternate-url'] . '";' . "\n"; break;
			case 'color'	: $code.= 'google_alternate_ad_color = "' . $ad['alternate-color'] . '";' . "\n"; break;
			case ''				: break;
			default				:
				$alternateAd = $ad['alternate-ad'];
				if (!empty($alternateAd)) {
					$code.= 'google_alternate_ad_url = "' . get_bloginfo('wpurl') . '/?advman-ad-name=' . $alternateAd . '";'  . "\n";
				}
		}

		$code.= 'google_color_border = "' . $ad['color-border'] . '"' . ";\n";
		$code.= 'google_color_bg = "' . $ad['color-bg'] . '"' . ";\n";
		$code.= 'google_color_link = "' . $ad['color-title'] . '"' . ";\n";
		$code.= 'google_color_text = "' . $ad['color-text'] . '"' . ";\n";
		$code.= 'google_color_url = "' . $ad['color-link'] . '"' . ";\n";

		$code.= "\n" . '//--></script>' . "\n";

		$code.= '<script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js"></script>' . "\n";

		return $code;
	}

	static function _get_code_adsense_link($ad)
	{
		$code='';

		$code .= '<script type="text/javascript"><!--' . "\n";
		$code.= 'google_ad_client = "' . $ad['account-id'] . '";' . "\n";

		if($ad['channel']!==''){ $code.= 'google_ad_channel = "' . $ad['channel'] . '";' . "\n"; }
		if($ad['uistyle']!==''){ $code.= 'google_ui_features = "rc:' . $ad['uistyle'] . '";' . "\n"; }

		$code.= 'google_ad_width = ' . $ad['width'] . ";\n";
		$code.= 'google_ad_height = ' . $ad['height'] . ";\n";

		$code.= 'google_ad_format = "' . $ad['adformat'] . $ad['adtype'] . '"' . ";\n";

		//$code.=$ad->_render_alternate_ad_code();
		$code.= 'google_color_border = "' . $ad['color-border'] . '"' . ";\n";
		$code.= 'google_color_bg = "' . $ad['color-bg'] . '"' . ";\n";
		$code.= 'google_color_link = "' . $ad['color-title'] . '"' . ";\n";
		$code.= 'google_color_text = "' . $ad['color-text'] . '"' . ";\n";
		$code.= 'google_color_url = "' . $ad['color-link'] . '"' . ";\n";

		$code.= "\n" . '//--></script>' . "\n";

		$code.= '<script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js"></script>' . "\n";

		return $code;
	}

	static function _get_code_adsense_referral($ad)
	{
		//if($ad===false){$ad=$_advman['ads'][$_advman['default_ad']];}
		//$ad=advman::merge_defaults($ad); //Apply defaults
		if($ad['product']=='referral-image') {
			$format = $ad['adformat'] . '_as_rimg';
		} else if($ad['product']=='referral-text') {
			$format = 'ref_text';
		}
		$code='';


		$code .= '<script type="text/javascript"><!--' . "\n";
		$code.= 'google_ad_client = "' . $ad['account-id'] . '";' . "\n";

		if($ad['channel']!==''){ $code.= 'google_ad_channel = "' . $ad['channel'] . '";' . "\n"; }

		if($ad['product']=='referral-image'){
			$code.= 'google_ad_width = ' . $ad['width'] . ";\n";
			$code.= 'google_ad_height = ' . $ad['height'] . ";\n";
		}

		if($ad['product']=='referral-text'){$code.='google_ad_output = "textlink"' . ";\n";}
		$code.='google_cpa_choice = "' . $ad['referral'] . '"' . ";\n";

		$code.= "\n" . '//--></script>' . "\n";

		$code.= '<script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js"></script>' . "\n";

		return $code;
	}

	static function _get_code_cj($ad)
	{
		$cjservers=array(
			'www.kqzyfj.com',
			'www.tkqlhce.com',
			'www.jdoqocy.com',
			'www.dpbolvw.net',
			'www.lduhtrp.net');

		$code = '';
		$code .= '<!-- Start: CJ Ads -->';
		$code .= '<a href="http://' . $cjservers[array_rand($cjservers)] . '/click-' . $ad['account-id'] . '-' . $ad['slot'] . '"';
		if($ad['new-window']=='yes'){$code.=' target="_blank" ';}

		if($ad['hide-link']=='yes'){
			$code.='onmouseover="window.status=\'';
			$code.=$ad['hide-link-url'];
			$code.='\';return true;" onmouseout="window.status=\' \';return true;"';
		}

		$code .= '>';

		$code .= '<img src="http://' . $cjservers[array_rand($cjservers)] . '/image-' . $ad['account-id'] . '-' . $ad['slot'] . '"';
		$code .= ' width="' . $ad['width'] . '" ';
		$code .= ' height="' . $ad['height'] . '" ';
		$code .= ' alt="' . $ad['alt-text'] . '" ';
		$code .= '>';
		$code .= '</a>';

		return $code;
	}

	static function _get_code_crispads($ad)
	{
		global $_advman;

		if ($ad['codemethod']=='javascript'){
			$code='<script type="text/javascript"><!--//<![CDATA[' . "\n";
			$code.="var m3_u = (location.protocol=='https:'?'https://www.crispads.com/spinner/www/delivery/ajs.php':'http://www.crispads.com/spinner/www/delivery/ajs.php');\n";
			$code.="var m3_r = Math.floor(Math.random()*99999999999);\n";
			$code.="if (!document.MAX_used) document.MAX_used = ',';\n";
			$code.="document.write (\"<scr\"+\"ipt type='text/javascript' src='\"+m3_u);\n";
			$code.='document.write ("?zoneid=' . $ad['slot'] . '");' . "\n";
			$code.="document.write ('&amp;cb=' + m3_r);\n";
			$code.="if (document.MAX_used != ',') document.write (\"&amp;exclude=\" + document.MAX_used);\n";
			$code.='document.write ("&amp;loc=" + escape(window.location));' . "\n";
			$code.='if (document.referrer) document.write ("&amp;referer=" + escape(document.referrer));' . "\n";
			$code.='if (document.context) document.write ("&context=" + escape(document.context));' . "\n";
			$code.='if (document.mmm_fo) document.write ("&amp;mmm_fo=1");' . "\n";
			$code.='document.write ("\'><\/scr"+"ipt>");' . "\n";
			$code.='//]]>--></script><noscript><a href="http://www.crispads.com/spinner/www/delivery/ck.php?n=' . $ad['identifier'] . '&amp;cb=INSERT_RANDOM_NUMBER_HERE" target="_blank"><img src="http://www.crispads.com/spinner/www/delivery/avw.php?zoneid=' . $ad['slot'] . '&amp;n=' . $ad['identifier'] . '" border="0" alt="" /></a></noscript>';
		} else { //Iframe
			$code='<iframe id="' . $ad['identifier'] . '" name="' . $ad['identifier'] . '" src="http://www.crispads.com/spinner/www/delivery/afr.php?n=' . $ad['identifier'] . '&amp;zoneid=' . $ad['slot'] . '" framespacing="0" frameborder="no" scrolling="no" width="' . $ad['width'] . '" height="' . $ad['height'] . '"><a href="http://www.crispads.com/spinner/www/delivery/ck.php?n=' . $ad['identifier'] . '&amp;cb=INSERT_RANDOM_NUMBER_HERE" target="_blank"><img src="http://www.crispads.com/spinner/www/delivery/avw.php?zoneid=' . $ad['slot'] . '&amp;n=' . $ad['identifier'] . '" border="0" alt="" /></a></iframe>';
			$code.='<script type="text/javascript" src="http://www.crispads.com/spinner/www/delivery/ag.php"></script>';
		}

		return $code;
	}

	static function _get_code_shoppingads($ad)
	{
		$code = '<script type="text/javascript"><!--' . "\n";
		$code.= 'shoppingads_ad_client = "' . $ad['account-id'] . '";' . "\n";
		$code.= 'shoppingads_ad_campaign = "' . $ad['campaign'] . '";' . "\n";

		list($width,$height)=split('[x]',$ad['adformat']);
		$code.= 'shoppingads_ad_width = "' . $width . '";' . "\n";
		$code.= 'shoppingads_ad_height = "' . $height . '";' . "\n";

		$code.= 'shoppingads_ad_kw = "' . $ad['keywords'] . '";' . "\n";

		$code.= 'shoppingads_color_border = "' . $ad['color-border'] . '";' . "\n";
		$code.= 'shoppingads_color_bg = "' . $ad['color-bg'] . '";' . "\n";
		$code.= 'shoppingads_color_heading = "' . $ad['color-title'] . '";' . "\n";
		$code.= 'shoppingads_color_text = "' . $ad['color-text'] . '";' . "\n";
		$code.= 'shoppingads_color_link = "' . $ad['color-link'] . '";' . "\n";

		$code.= 'shoppingads_attitude = "' . $ad['attitude'] . '";' . "\n";
		if($ad['new-window']=='yes'){$code.= 'shoppingads_options = "n";' . "\n";}

		$code.= '--></script>
		<script type="text/javascript" src="http://ads.shoppingads.com/pagead/show_sa_ads.js">
		</script>' . "\n";

		return $code;
	}

	static function _get_code_widgetbucks($ad)
	{
		global $_advman;

		$code ='';
		$code .= '<!-- START CUSTOM WIDGETBUCKS CODE --><div>';
		$code .= '<script src="http://api.widgetbucks.com/script/ads.js?uid=' . $ad['slot'] . '"></script>';
		$code .= '</div><!-- END CUSTOM WIDGETBUCKS CODE -->';
		return $code;
	}

	static function _get_code_ypn($ad)
	{
		$code = '<script language="JavaScript">';
		$code .= '<!--';
		$code .= 'ctxt_ad_partner = "' . $ad['account-id'] . '";' . "\n";
		$code .= 'ctxt_ad_section = "' . $ad['channel'] . '";' . "\n";
		$code .= 'ctxt_ad_bg = "";' . "\n";
		$code .= 'ctxt_ad_width = "' . $ad['width'] . '";' . "\n";
		$code .= 'ctxt_ad_height = "' . $ad['height'] . '";' . "\n";

		$code .= 'ctxt_ad_bc = "' . $ad['color-bg'] . '";' . "\n";
		$code .= 'ctxt_ad_cc = "' . $ad['color-border'] . '";' . "\n";
		$code .= 'ctxt_ad_lc = "' . $ad['color-title'] . '";' . "\n";
		$code .= 'ctxt_ad_tc = "' . $ad['color-text'] . '";' . "\n";
		$code .= 'ctxt_ad_uc = "' . $ad['color-link'] . '";' . "\n";

		$code .= '// -->';
		$code .= '</script>';
		$code .= '<script language="JavaScript" src="http://ypn-js.overture.com/partner/js/ypn.js">';
		$code .= '</script>';

		return $code;
	}
}
?>