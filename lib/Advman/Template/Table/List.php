<?php
/*
Plugin Name: Test List Table Example
*/
require_once(ADVMAN_LIB . '/Tools.php');

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Advman_Template_Table_List extends WP_List_Table
{

    function __construct(){
        global $status, $page, $hook_suffix;

        parent::__construct( array(
            'singular'  => __( 'ad', 'advman-list' ),     //singular name of the listed records
            'plural'    => __( 'ads', 'advman-list' ),   //plural name of the listed records
            'ajax'      => false        //does this table support ajax?

        ) );
    }

    function get_views()
    {
        global $advman_engine;
        $ads = $advman_engine->getAds();
        $all = count($ads);
        $active = 0;
        foreach ( $ads as $ad ) {
            if ($ad->active) {
                $active++;
            }
        }
        $inactive = $all - $active;

        $views = array();

        //
        $filter = $this->_get_filter();

        //All link
        $class = ($filter == 'all' ? ' class="current"' :'');
        $url = remove_query_arg('filter');
        $views['all'] = "<a href='{$url}' {$class} >" . __('All', 'advman') . " <span class='count'>($all)</span></a>";

        // Active link
        if ($active) {
            $class = ($filter == 'active' ? ' class="current"' :'');
            $url = add_query_arg('filter','active');
            $views['active'] = "<a href='{$url}' {$class} >" . __('Active', 'advman') . " <span class='count'>($active)</span></a>";
        }

        // Inctive link
        if ($inactive) {
            $class = ($filter == 'inactive' ? ' class="current"' :'');
            $url = add_query_arg('filter','inactive');
            $views['inactive'] = "<a href='{$url}' {$class} >" . __('Inactive', 'advman') . " <span class='count'>($inactive)</span></a>";
        }

        return $views;
    }

    function get_data($filter = 'all', $q = false)
    {

        global $advman_engine;
        $data = array();
        $ads = $advman_engine->getAds();
        $defaultAdName = $advman_engine->getSetting('default-ad');
        $date = date('Y-m-d');
        if (!empty($ads)) {
            foreach ($ads as $ad) {
                if (!$q || stristr($ad->name, $q) !== false || stristr(''.$ad->id, $q) !== false) {
                    if ($filter == 'all' || ($filter == 'active' && $ad->active) || ($filter == 'inactive' && !$ad->active)) {
                        list($user, $human, $formatted, $ts) = Advman_Tools::get_last_edit($ad->get_property('revisions'));
                        $data[] = array(
                            'id' => $ad->id,
                            'name' => $ad->name,
                            'type' => $ad->network_name,
                            'class' => strtolower(get_class($ad)),
                            'format' => $this->displayFormat($ad),
                            'active' => ($ad->active),
                            'def' => ($ad->name == $defaultAdName),
                            'date' => $ts,
                            'date1' => $human,
                            'date2' => $formatted,
                            'user' => $user
                        );
                    }
                }
            }
        }

        return $data;
    }

    function no_items()
    {
        _e( 'There are currently no ads.  Try to <a href="admin.php?page=advman-ad-new">create</a> one to get started' );
    }

    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],
            /*$2%s*/ $item['id']
        );
    }

    function column_name($item)
    {
        $edit_url = "?page=advman-ad&ad={$item['id']}";
        $copy_url = wp_nonce_url("?page=advman-list&action=copy&ad={$item['id']}", 'copy_ad_'.$item['id']);
        $delete_url = wp_nonce_url("?page=advman-list&action=delete&ad={$item['id']}", 'delete_ad_'.$item['id']);

        $actions = array(
            'edit'     => sprintf('<a href="' . $edit_url . '">%s</a>', __('Edit')),
            'copy'     => sprintf('<a href="' . $copy_url . '">%s</a>', __('Copy')),
            'delete'     => sprintf('<a href="' . $delete_url . '">%s</a>', __('Delete'))
        );

        //Return the title contents
        return "<a href='?page=advman-ad&ad={$item['id']}'>" . $item['name'] . "</a> <span style='color:silver'> (id:" . $item['id'] . ")</span>" . $this->row_actions($actions);
    }

    function column_type($item)
    {
        //Return with hyperlink to edit network
        return "<a href='?page=advman-network&network={$item['class']}'>" . $item['type'] . "</a>";
    }

    function column_active($item)
    {
        return $item['active'] ? "<a href='?page=advman-list&action=deactivate&ad={$item['id']}'>" . __('Yes', 'advman') . "</a>" : "<a href='?page=advman-list&action=activate&ad={$item['id']}'>" . __('No', 'advman') . "</a>";
    }

    function column_def($item)
    {
        $default = $item['def'] ? __('Yes', 'advman') : __('No', 'advman');
        return "<a href='?page=advman-list&action=default&ad={$item['id']}'>" . $default . "</a>";
    }

    function column_date($item)
    {
        return $item['date1'] . __(' ago by ', 'advman') . $item['user'];
    }

    function column_default( $item, $column_name )
    {
        switch( $column_name ) {
            case 'name':
            case 'type':
            case 'format':
            case 'active':
            case 'def':
            case 'date':
                return $item[ $column_name ];
            default:
                return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
        }
    }

    function get_columns()
    {
        $columns = array(
            'cb'      => '<input type="checkbox" />',
            'name'    => __( 'Name', 'advman' ),
            'type'    => __( 'Type', 'advman' ),
            'format'  => __( 'Format', 'advman' ),
            'active'  => __( 'Active', 'advman' ),
            'def'     => __( 'Default', 'advman' ),
            'date'    => __( 'Last Edit', 'advman' )
        );
        return $columns;
    }

    function get_sortable_columns()
    {
        $sortable_columns = array(
            'name'  => array('name',false),
            'type' => array('type',false),
            'format' => array('format',false),
            'active' => array('active',false),
            'def' => array('def',false),
            'date' => array('date',false),
        );

        return $sortable_columns;
    }

    function get_search_columns()
    {
        $search_columns = array(
            'id',
            'name',
            'format'
        );

        return $search_columns;
    }

    function usort_reorder( $a, $b )
    {
        // If no sort, default to date descending
        $orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'date';
        // If no order, default to asc
        $order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'desc';
        // Determine sort order
        $result = strcasecmp( $a[$orderby], $b[$orderby] );
        // Send final sort direction to usort
        return ( $order === 'asc' ) ? $result : -$result;
    }

    function get_bulk_actions()
    {
        $actions = array(
            'copy'    => __('Copy'),
            'delete'    => __('Delete')
        );
        return $actions;
    }

    function _get_filter()
    {
        $filter = 'all';
        if ( isset($_REQUEST['filter'] ) ) {
            if ($_REQUEST['filter'] == 'active' || $_REQUEST['filter'] == 'inactive') {
                $filter = $_REQUEST['filter'];
            }
        }
        return $filter;
    }

    function prepare_items()
    {
        $filter = $this->_get_filter();
        $q = Advman_Tools::get_search_query();
        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array( $columns, $hidden, $sortable );

        // Retrieve the ad data from the DB
        $data = $this->get_data($filter, $q);
        usort( $data, array( &$this, 'usort_reorder' ) );
        $total_items = count( $data );

        $per_page = 10;
        $current_page = $this->get_pagenum();

        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page                     //WE have to determine how many items to show on a page
        ) );

        $this->items = array_slice( $data,( ( $current_page-1 )* $per_page ), $per_page );
    }

    /**
     * Display the format field according to the following rules:
     * 1.  If a format and type combination is set, fill it in
     * 2.  If not, display the default in grey
     */
    function displayFormat($ad)
    {
        $format = $ad->get_property('adformat');

        // If format is custom, format it like:  Custom (468x60)
        if ($format == 'custom') {
            $format = __('Custom', 'advman') . ' (' . $ad->get_property('width') . 'x' . $ad->get('height') . ')';
        }

        // Find a default if the format is not filled in
        if (empty($format)) {
            $format = $ad->get_network_property('adformat');
            if ($format == 'custom') {
                $format = __('Custom', 'advman') . ' (' . $ad->get_property('width') . 'x' . $ad->get('height') . ')';
            }
            if (!empty($format)) {
                $format = "<span style='color:gray;'>" . $format . "</span>";
            }
        }

        $type = $ad->get_property('adtype');

        // If there is an ad type, prefix it on to the format
        if (empty($type)) {
            $type = $ad->get_network_property('adtype');
            if (!empty($type)) {
                $types = array(
                    'ad' => __('Ad Unit', 'advman'),
                    'link' => __('Link Unit', 'advman'),
                    'ref_text' => __('Text Referral', 'advman'),
                    'ref_image' => __('Image Referral', 'advman'),
                );
                $type = "<span style='color:gray;'>" . $types[$type] . "</span>";
            }
        }

        if (!empty($format) && (!empty($type))) {
            return $type . '<br />' . $format;
        }

        return $type . $format;
    }


} //class
