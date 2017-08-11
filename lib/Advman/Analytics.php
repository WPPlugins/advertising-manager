<?php
require_once (ADVMAN_LIB . '/Tools.php');
require_once (ADVMAN_LIB . '/Template/Table/Analytics.php');
//require_once (ADVMAN_LIB . '/Notice.php');

class Advman_Analytics
{
    // Perform any work here before anything gets written to the screen
    static function init()
    {
        global $advman_engine, $advman_analytics;
        $advman_analytics = new Advman_Template_Table_Analytics();

        //Detect when a bulk action is being triggered...
        $action = OX_Tools::sanitize_request_var('action');

        // Perform actions
        if ($action) {
            $ads = Advman_Tools::get_current_ads();
            if ($ads) {
                if (count($ads) == 1) {
                    // If there is a single ad selected, then perform the action on that ad.  Notice messages and workflow are different in this case
                    foreach ($ads as $ad) {
                        Advman_Admin::ad_action($action, $ad);
                    }
                } else {
                    // These are bulk actions
                    switch ($action) {
                        case 'copy' :
                            foreach ($ads as $ad) {
                                if ($ad) {
                                    $advman_engine->copyAd($ad->id);
                                }
                            }
                            Advman_Admin::add_notice('advman-notice-once', __("Ads copied"), false);
                            break;

                        case 'delete' :
                            foreach ($ads as $ad) {
                                if ($ad) {
                                    $advman_engine->deleteAd($ad->id);
                                }
                            }
                            Advman_Admin::add_notice('advman-notice-once', __("Ads deleted"), false);
                            break;

                    }
                }
            } else {
                $ad = Advman_Tools::get_current_ad();
                if ($ad) {
                    Advman_Admin::ad_action($action, $ad);
                }
            }
            $url = remove_query_arg(array('action', 'ad', 'network', '_wpnonce'));
            wp_redirect($url);
        }
    }

    static function ad_analytics_action($action)
    {
        global $advman_engine;

        // First, if there are no ads, redirect to the create screen
        $ads = $advman_engine->getAds();
        if (!$ads) {
            wp_redirect(admin_url('admin.php?page=advman-ad-new'));
        }

    }

    static function process()
    {
        global $advman_analytics;
        $q = Advman_Tools::get_search_query();

        echo '<div class="wrap"><h2>' . __('Analytics', 'advman');
        if (!empty($q))
            printf( ' <span class="subtitle">' . __('Search results for &#8220;%s&#8221;', 'advman') . '</span>', $q );

        echo '</h2>';
        $advman_analytics->views();
        $advman_analytics->prepare_items();
        ?>
        <form method="get">
            <input type="hidden" name="page" value="advman-analytics">
            <?php
            $advman_analytics->search_box( 'search', 'ad' );
            ?>
        </form>
        <form method="post">
            <?php
            wp_nonce_field('advman-bulk-actions');
            $advman_analytics->display();
            ?>
        </form>
        </div>
    <?php
    }
    /**
     * Initialise menu items, notices, etc.
     */
    static function add_css() {
        echo '<style type="text/css">';
        echo '.wp-list-table .column-id { width: 5%; }';
        echo '.wp-list-table .column-name { width: 30%; }';
        echo '.wp-list-table .column-type { width: 15%; }';
        echo '.wp-list-table .column-format { width: 15%; }';
        echo '.wp-list-table .column-active { width: 10%; }';
        echo '.wp-list-table .column-def { width: 10%; }';
        echo '.wp-list-table .column-date { width: 15%; }';
        echo '</style>';
    }

    /**
     * Add contextual help for ad list screen
     */
    static function add_contextual_help() {
        get_current_screen()->add_help_tab( array(
            'id'		=> 'overview',
            'title'		=> __('Overview'),
            'content'	=>
                '<p>' . __('This screen shows performance of ads running on your site.  Select the timeframe that you want to view, and all ads that were shown during that timeframe will appear below.') . '</p>'
        ) );
        get_current_screen()->add_help_tab( array(
            'id'		=> 'screen-content',
            'title'		=> __('Screen Content'),
            'content'	=>
                '<p>' . __('You can customize the display of this screen&#8217;s contents in a number of ways:') . '</p>' .
                '<ul>' .
                '<li>' . __('You can hide/display columns based on your needs and decide how many ads to list per screen using the Screen Options tab.') . '</li>' .
                '<li>' . __('You can sort the list to order by name, type, format, etc.  Click the title of the column to sort ascending.  Click the title again to sort decending.') . '</li>' .
                '<li>' . __('You can filter the list of ads by ad status using the text links in the upper left to show All, Active, or Inactive ads. The default view is to show all ads.') . '</li>' .
                '<li>' . __('You can refine the list to show only ads of a specific type. Click the Filter button after making your selection.') . '</li>' .
                '</ul>'
        ) );

        get_current_screen()->set_help_sidebar(
            '<p><strong>' . __('For more information:') . '</strong></p>' .
            '<p>' . __('<a href="http://wordpress.org/plugins/advertising-manager/" target="_blank">Plugin Page</a>') . '</p>' .
            '<p>' . __('<a href="http://wordpress.org/support/plugin/advertising-manager" target="_blank">Support Forums</a>') . '</p>'
        );
    }

    static function add_options() {
        $option = 'per_page';
        $args = array(
            'label' => 'Ads',
            'default' => 10,
            'option' => 'ads_per_page'
        );
        add_screen_option( $option, $args );
    }
}
?>