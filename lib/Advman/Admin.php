<?php
require_once (ADVMAN_LIB . '/Tools.php');
require_once (ADVMAN_LIB . '/List.php');
require_once (ADVMAN_LIB . '/Analytics.php');

class Advman_Admin
{
	/**
	 * Initialise menu items, notices, etc.
	 */
	static function init()
	{
		global $wp_version;

        add_object_page(__('Ads', 'advman'), __('Ads', 'advman'), 8, 'advman-list', array('Advman_List','process'), ADVMAN_URL . '/images/advman-menu-icon.svg');
        $list_hook = add_submenu_page('advman-list', __('All Ads', 'advman'), __('All Ads', 'advman'), 8, 'advman-list', array('Advman_List','process'));
        $analytics_hook = add_submenu_page('advman-list', __('Analytics', 'advman'), __('Analytics', 'advman'), 8, 'advman-analytics', array('Advman_Analytics','process'));
        add_submenu_page('advman-list', __('Create New Ad', 'advman'), __('Create New', 'advman'), 8, 'advman-ad-new', array('Advman_Admin','create'));
        add_submenu_page(null, __('Edit Ad', 'advman'), __('Edit', 'advman'), 8, 'advman-ad', array('Advman_Admin','edit_ad'));
        add_submenu_page(null, __('Preview Ad', 'advman'), __('Preview', 'advman'), 8, 'advman-ad-preview', array('Advman_Admin','preview_ad'));
        add_submenu_page(null, __('Edit Network', 'advman'), __('Edit', 'advman'), 8, 'advman-network', array('Advman_Admin','edit_network'));
        add_options_page(__('Ads', 'advman'), __('Ads', 'advman'), 8, 'advman-settings', array('Advman_Admin','settings'));

        // List items
        if ($list_hook) {
            add_action("load-$list_hook", array('Advman_List', 'add_options'));
            add_action("admin_head-$list_hook", array('Advman_List', 'add_contextual_help' ));
            add_action("admin_head-$list_hook", array('Advman_List', 'add_css' ));
        }
        // Analytics items
        if ($analytics_hook) {
            add_action("load-$analytics_hook", array('Advman_Analytics', 'add_options'));
            add_action("admin_head-$analytics_hook", array('Advman_Analytics', 'add_contextual_help' ));
            add_action("admin_head-$analytics_hook", array('Advman_Analytics', 'add_css' ));
        }

		add_action('admin_enqueue_scripts', array('Advman_Admin', 'admin_enqueue_scripts'));

        // Display any notices that exist
		add_action('admin_notices', array('Advman_Admin','display_notices'), 1 );

        // Add editor plugin to automatically insert an ad into a blog post
        add_action('admin_enqueue_scripts', array('Advman_Admin', 'add_editor_css'));
        add_action('before_wp_tiny_mce', array('Advman_Admin', 'tinymce_menu_script' ));
        add_filter('mce_buttons', array('Advman_Admin','editor_button'));
        add_filter('mce_external_plugins', array('Advman_Admin', 'register_tinymce_javascript'));

        // Add 'settings' to the plugin activate page
        add_filter( 'plugin_action_links_advertising-manager/advertising-manager.php', array('Advman_Admin', 'plugin_action_links' ));

        // Change footer text on Advertising Manager pages
        add_filter("admin_footer_text", array('Advman_Admin', 'admin_footer_text'));

        // Process any actions
        $action = OX_Tools::sanitize_post_var('advman-action');
        $page = OX_Tools::sanitize_request_var('page');

        // Check to see if the activate action is being fired
        Advman_Admin::notice_action($action);
        switch ($page) {
            case 'advman-ad-new'     : Advman_Admin::import_action($action); break;
            case 'advman-ad'         : Advman_Admin::ad_action($action); break;
            case 'advman-analytics'  : Advman_Analytics::init(); break;
            case 'advman-list'       : Advman_List::init(); break;
            case 'advman-network'    : Advman_Admin::network_action($action); break;
        }
    }

    static function notice_action($action)
    {
        // Activate advertising manager
        if ($action == 'activate advertising-manager') {
            Advman_Admin::remove_notice('activate advertising-manager');
        }

        // Learn more about Adjs beta
        if ($action == 'adjs-beta') {
            if (OX_Tools::sanitize_post_var('advman-notice-confirm-yes')) {
                wp_redirect(admin_url('options-general.php?page=advman-settings'));
            }
            Advman_Admin::remove_notice('adjs-beta');
        }

        // Update shortcodes
        if ($action == 'update_shortcodes') {
            if (OX_Tools::sanitize_post_var('advman-notice-confirm-yes')) {
                include_once(ADVMAN_LIB . '/Upgrade.php');
                Advman_Upgrade::update_shortcodes();
                Advman_Admin::add_notice('advman-notice-once', __("Ad shortcodes updated"), false);
            }
            Advman_Admin::remove_notice('update_shortcodes');
        }
    }

    static function network_action($action, $network = null)
    {
        global $advman_engine;

        if ($action) {

            $network = Advman_Tools::get_current_network();

            if ($network) {
                switch ($action) {
                    case 'apply' :
                        if (Advman_Admin::save_properties($network, true)) {
                            $advman_engine->setAdNetwork($network);
                            Advman_Admin::add_notice('advman-notice-once', __("Network updated"), false);
                        }
                        break;

                    case 'cancel' :
                        wp_redirect(admin_url('admin.php?page=advman-list'));
                        exit;

                    case 'reset':
                        $network->reset_network_properties();
                        $advman_engine->setAdNetwork($network);
                        Advman_Admin::add_notice('advman-notice-once', __("Network settings reset to defaults"), false);
                        break;

                    case 'save':
                        if (Advman_Admin::save_properties($network, true)) {
                            $advman_engine->setAdNetwork($network);
                            Advman_Admin::add_notice('advman-notice-once', __("Network updated"), false);
                        }
                        wp_redirect(admin_url('admin.php?page=advman-list'));
                        exit;
                }

            }

        }

    }

    static function import_action($action)
    {
        global $advman_engine;

        if ($action ==  'import') {
            $tag = OX_Tools::sanitize($_POST['advman-code']);
            $ad = $advman_engine->importAdTag($tag);
            wp_redirect(admin_url('admin.php?page=advman-ad&ad='.$ad->id));
        }
    }

    static function ad_action($action, $ad = null)
    {
        global $advman_engine;

        //wp_die("action1:$action");
        if (!$ad) {
            $ad = Advman_Tools::get_current_ad();
        }

        if ($ad) {
            switch ($action) {

                case 'apply' :
                    if (Advman_Admin::save_properties($ad)) {
                        $advman_engine->setAd($ad);
                        Advman_Admin::add_notice('advman-notice-once', __("Ad updated"), false);
                    }
                    break;

                case 'activate' :
                    if (!$ad->active) {
                        $ad->active = true;
                        $advman_engine->setAd($ad);
                        Advman_Admin::add_notice('advman-notice-once', __("Ad activated"), false);
                    }
                    break;

                case 'cancel' :
                    wp_redirect(admin_url('admin.php?page=advman-list'));
                    exit;

                case 'copy' :
                    $ad_new = $advman_engine->copyAd($ad->id);
                    Advman_Admin::add_notice('advman-notice-once', __("Ad copied. <a href='admin.php?page=advman-ad&ad={$ad->id}'>View original</a>"), false);
                    wp_redirect(admin_url('admin.php?page=advman-ad&ad='.$ad_new->id));
                    break;

                case 'deactivate' :
                    if ($ad->active) {
                        $ad->active = false;
                        $advman_engine->setAd($ad);
                        Advman_Admin::add_notice('advman-notice-once', __("Ad deactivated"), false);
                    }
                    break;

                case 'default' :
                    $default = ($advman_engine->getSetting('default-ad') != $ad->name ? $ad->name : '');
                    $advman_engine->setSetting('default-ad', $default);
                    $msg = $default ? __('Ad is now default') : __('Ad is no longer default');
                    Advman_Admin::add_notice('advman-notice-once', $msg, false);
                    break;

                case 'delete' :
                    $advman_engine->deleteAd($ad->id);
                    Advman_Admin::add_notice('advman-notice-once', __("Ad deleted"), false);
                    wp_redirect(admin_url('admin.php?page=advman-list'));
                    break;

                case 'edit-network' :
                    wp_redirect(admin_url('admin.php?page=advman-network&network='.strtolower(get_class($ad))));
                    exit;
                case 'edit' :
                    wp_redirect(admin_url('admin.php?page=advman-ad&ad='.$ad->id));
                    exit;

                case 'filter' :
                    $filter_active = OX_Tools::sanitize_post_var('advman-filter-active');
                    $filter_network = OX_Tools::sanitize_post_var('advman-filter-network');
                    if (!empty($filter_active)) {
                        $filter['active'] = $filter_active;
                    }
                    if (!empty($filter_network)) {
                        $filter['network'] = $filter_network;
                    }
                    break;

                case 'save' :
                    if (Advman_Admin::save_properties($ad)) {
                        $advman_engine->setAd($ad);
                        Advman_Admin::add_notice('advman-notice-once', __("Ad updated. <a href='admin.php?page=advman-ad&ad={$ad->id}'>View ad</a>"), false);
                    }
                    wp_redirect(admin_url('admin.php?page=advman-list'));
                    exit;
            }
        }
    }

    static function save_properties(&$ad, $default = false)
	{
		global $advman_engine;
		
		// Whether we changed any setting in this entity
		$changed = false;
		
		// Set the ad properties (if not setting default properties)
		if (!$default) {
			if (isset($_POST['advman-name'])) {
				$value = OX_Tools::sanitize($_POST['advman-name']);
				if ($value != $ad->name) {
					Advman_Admin::check_default($ad, $value);
					$ad->name = $value;
					$changed = true;
				}
			}
			
			if (isset($_POST['advman-active'])) {
				$value = $_POST['advman-active'] == 'yes';
				if ($ad->active != $value) {
					$ad->active = $value;
					$changed = true;
				}
			}
		}
		
		$properties = $ad->get_network_property_defaults();
		if (!empty($properties)) {
			foreach ($properties as $property => $d) {
				if (isset($_POST["advman-{$property}"])) {
					$value = OX_Tools::sanitize($_POST["advman-{$property}"]);
					if ($default) {
						// Deal with multi select 'show-author'
						if ($property == 'show-author') {
							Advman_Tools::format_author_value($value);
						}
						if ($property == 'show-category') {
							Advman_Tools::format_category_value($value);
						}
						if ($property == 'show-tag') {
							Advman_Tools::format_tag_value($value);
						}
						if ($ad->get_network_property($property) != $value) {
							$ad->set_network_property($property, $value);
							$changed = true;
						}
					} else {
						// Deal with multi select 'show-author'
						if ($property == 'show-author') {
							Advman_Tools::format_author_value($value);
						}
						if ($property == 'show-category') {
							Advman_Tools::format_category_value($value);
						}
						if ($property == 'show-tag') {
							Advman_Tools::format_tag_value($value);
						}
						if ($ad->get_property($property) != $value) {
							$ad->set_property($property, $value);
							$changed = true;
						}
					}
					// deal with adtype
					if ($property == 'adtype') {
						if (isset($_POST["advman-adformat-{$value}"])) {
							$v = OX_Tools::sanitize($_POST["advman-adformat-{$value}"]);
							if ($default) {
								if ($ad->get_network_property('adformat') != $v) {
									$ad->set_network_property('adformat', $v);
									$changed = true;
								}
							} else {
								if ($ad->get_property('adformat') != $v) {
									$ad->set_property('adformat', $v);
									$changed = true;
								}
							}
						}
					}
				}
			}
		}
		
		return $changed;
	}
	
	static function check_default($ad, $value)
	{
		global $advman_engine;
		
		$d = $advman_engine->getSetting('default-ad');
		if (!empty($d) && $ad->name == $d) {
			$modify = true;
			$ads = $advman_engine->getAds();
			foreach ($ads as $a) {
				if ($a->id != $ad->id && $a->name == $d) {
					$modify = false;
					break;
				}
			}
			if ($modify) {
				$advman_engine->setSetting('default-ad', $value);
			}
		}
	}
	
	/**
	 * Process input from the Admin UI.  Called staticly from the Wordpress form screen.
	 */
	static function process()
	{
		global $advman_engine;
		
		$filter = null;
		$template = false;
        $page = OX_Tools::sanitize_request_var('page');

		switch ($page) {

            case 'advman-ad' :
                $ad = Advman_Tools::get_current_ad();
                if ($ad) {
                    $template = Advman_Tools::get_template('Ad_Edit', $ad);
                    $template->display($ad);
                }
                break;

            case 'advman-ad-new' :
				$template = Advman_Tools::get_template('Ad_Create');
				$template->display();
				break;

            case 'advman-ad-preview' :
                $ad = Advman_Tools::get_current_ad();
                if ($ad) {
                    $template = Advman_Tools::get_template('Ad_Preview', $ad);
                    $template->display($ad);
                }
                break;

            case 'advman-network' :
                $network = Advman_Tools::get_current_network();
                if ($network) {
                    $template = Advman_Tools::get_template('Network_Edit', $network);
                    $template->display($network);
                }
                break;

            case 'advman-analytics' :
                $template = Advman_Tools::get_template('Table_Analytics');
                $template->display();
                break;
		}
		
		if (!$template) {
			$template = Advman_Tools::get_template('Table_List');
			$template->display();
		}
	}
	
	/**
	 * Display notices in the Admin UI.  Called staticly from the Wordpress 'admin_notices' hook.
	 */
	static function display_notices()
	{
//        $advman_page = Advman_Tools::is_advman_page();

		$notices = Advman_Admin::get_notices();
//		if (!empty($notices) && $advman_page) {
		if (!empty($notices)) {  // remove advman_page for now - showing across admin screens until users say something.
			$template = Advman_Tools::get_template('Notice');
			$template->display($notices);
            // Remove any 'one time' notices
            Advman_Admin::remove_notice('advman-notice-once');
		}
		
	}

	/**
	 * This function is called from the Wordpress Ads menu
	 */
	static function create()
	{
		$template = Advman_Tools::get_template('Ad_Create');
		$template->display();
	}

    /**
     * This function is called from the Wordpress Ads menu
     */
    static function edit_ad()
    {
        $ad = Advman_Tools::get_current_ad();
        $template = Advman_Tools::get_template('Ad_Edit', $ad);
        $template->display($ad);
    }

    static function preview_ad()
    {
        $ad = Advman_Tools::get_current_ad();
        $template = Advman_Tools::get_template('Ad_Preview', $ad);
        $template->display($ad);
    }

    static function edit_network()
    {
        $network = Advman_Tools::get_current_network();
        $template = Advman_Tools::get_template('Network_Edit', $network);
        $template->display($network);
    }

    /**
	 * This function is called from the Wordpress Settings menu
	 */
	static function settings()
	{
        global $advman_engine;

        // Get our options and see if we're handling a form submission.
		$action = OX_Tools::sanitize_post_var('advman-action');
		if ($action == 'save') {
			global $advman_engine;
            // We need to know if we are changing the adjs settings
            $adjs_pre = $advman_engine->getSetting('enable-adjs');
			$settings = array('enable-php', 'stats', 'purge-stats-days', 'enable-adjs');
			foreach ($settings as $setting) {
				$value = isset($_POST["advman-{$setting}"]) ? OX_Tools::sanitize($_POST["advman-{$setting}"]) : false;
				$advman_engine->setSetting($setting, $value);
			}
            $adjs_post = $advman_engine->getSetting('enable-adjs');

            // If a user is turning on adjs
            // This generates a client ID given the admin email address and the domain.  Note:  user has given consent in settings if we have made it this far.
            if (!$adjs_pre && $adjs_post) {
                $url = 'http://adjs.io/beta_signups';
                $params = array(
                    'headers' => array("Accept"=>'application/json'),
                    'body' =>  array('beta_signup' => array("email"=>get_option('admin_email'),"url"=> get_option('siteurl')))
                );

//                print_r($params);
//                exit;
                $response = wp_remote_post($url, $params);
                if (is_array($response) && $response['body']) {
                    $clientId = json_decode($response['body'])->client_id;
                    $advman_engine->setSetting('adjs-clientid', $clientId);
                } else {
                    // Fail silently!
//                    print_r($response);
//                    exit;
                }
            }
            // If a user is turning off adjs
            // This generates a client ID given the admin email address and the domain.  Note:  user has given consent in settings if we have made it this far.
            if ($adjs_pre && !$adjs_post) {

                $clientId = $advman_engine->getSetting('adjs-clientid');
                if ($clientId) {
                    $url = "http://adjs.io/beta_signups/$clientId";
                    $params = array(
                        'method' => 'DELETE',
                        'headers' => array("Accept"=>'application/json')
                    );
                    $response = wp_remote_request($url, $params);

                    if ($response['response'] && $response['response']['code'] == 204) {
                        $advman_engine->setSetting('adjs-clientid','');
                    }
                }
            }
		}

        $template = Advman_Tools::get_template('Settings');
		$template->display();
	}

	static function get_notices()
	{
		return get_option('plugin_advman_ui_notices');
	}
    static function set_notices($notices)
	{
		return update_option('plugin_advman_ui_notices', $notices);
	}
    static function add_notice($action,$text,$confirm=false)
	{
		$notices = Advman_Admin::get_notices();
		$notices[$action]['text'] = $text;
		$notices[$action]['confirm'] = $confirm;
		Advman_Admin::set_notices($notices);
	}
    static function remove_notice($action)
	{
		$notices = Advman_Admin::get_notices();
		if (!empty($notices[$action])) {
			unset($notices[$action]);
		}
		Advman_Admin::set_notices($notices);
	}

    /*
     * Add a custom CSS which contains the image that is used in the menu button of the editor
     */
    static function add_editor_css()
    {
        wp_enqueue_style('advman-editor', ADVMAN_URL . '/scripts/advman-editor.css');
    }

    /*
     * Generate a function that generates an array of ads for the editor menu button
     */
    static function tinymce_menu_script()
    {
        global $advman_engine;

        $ads = $advman_engine->getAds();
        ?>
        <script type="text/javascript">
            function advman_build_tinymce_menu(editor)
            {
                return [
<?php
        if ($ads) {
            foreach ($ads as $ad) {
                echo "{text: 'Ad {$ad->id}: {$ad->name}', value: '[ad name=\"{$ad->name}\"]', onclick: function() { editor.insertContent(this.value()); } },";
            }
        } else {
                echo "{text: '(No ads defined)', value: ''},";
        }
?>
                ];
            }
        </script>
    <?php
    }

    /*
     * Hook to add a custom button on the wordpress tinymce editor
     */
    static function editor_button($buttons) {
        array_push($buttons, 'advman_ad_key');
        return $buttons;
    }

    /*
     * Hook to register the javascript for the custom button on the wordpress tinymce editor
     */
    static function register_tinymce_javascript($plugin_array) {
        $plugin_array['advman'] = ADVMAN_URL . '/scripts/advman-editor.js';
        return $plugin_array;
    }

    /*
     * Hook to add some styling to advman lists and forms
     */
    static function admin_enqueue_scripts($hook)
    {
        if (Advman_Tools::is_advman_page($hook)) {
            // scripts
            wp_enqueue_script('prototype');
            wp_enqueue_script('postbox');
            wp_enqueue_script('jquery-multiselect', ADVMAN_URL . '/scripts/jquery.multiSelect.js', array('jquery'));
            wp_enqueue_script('advman', ADVMAN_URL . '/scripts/advman.js');

            // styles
            wp_enqueue_style('advman', ADVMAN_URL . '/scripts/advman.css');
            wp_enqueue_style('advman-multiselect', ADVMAN_URL . '/scripts/jquery.multiSelect.css');
        }
    }

    static function admin_footer_text($default_text)
    {
        $addition = '';
        $page = OX_Tools::sanitize_request_var('page');

        if (stristr($page, 'advman-') !== false) {
            $addition = " | <span id='footer-thankyou'>" . __("Ads by <a href='http://wordpress.org/plugins/advertising-manager/'>Advertising Manager</a>", "advman") . " </span><span style='font-size:x-small;color:silver'>v" . ADVMAN_VERSION . "</span>";
        }

        return $default_text . $addition;
    }

    static function plugin_action_links( $links ) {
        $settings = '<a href="'. get_admin_url(null, 'options-general.php?page=advman-settings') .'">' . __('Settings', 'advman') . '</a>';
        return array(0 => $settings) + $links;
    }

    static function activate()
    {
        // Add quality notice
        $notice = __('Would you like to enable ad quality measurement in <strong>Advertising Manager</strong>?', 'advman');
        Advman_Admin::add_notice('adjs-beta', $notice, 'learn');

    }
}
?>