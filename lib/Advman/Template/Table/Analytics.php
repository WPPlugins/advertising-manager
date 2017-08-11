<?php
/*
Plugin Name: Test List Table Example
*/
require_once(ADVMAN_LIB . '/Tools.php');

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Advman_Template_Table_Analytics extends WP_List_Table
{

    function __construct(){
        global $status, $page, $hook_suffix;

        parent::__construct( array(
            'singular'  => __( 'ad', 'advman-analytics' ),     //singular name of the listed records
            'plural'    => __( 'ads', 'advman-analytics' ),   //plural name of the listed records
            'ajax'      => false,        //does this table support ajax?
			'screen' => null
        ) );
    }

    function get_views()
    {
        $views = array();

        $filter = $this->_get_filter();

        $options = array('today' => __('Today', 'advman'), 'yesterday' => __('Yesterday', 'advman'), 'last7days' => __('Last 7 Days', 'advman'), 'thismonth' => __('This Month', 'advman'), 'lastmonth' => __('Last Month','advman'));
        foreach ($options as $n => $v) {
            $url = remove_query_arg('filter');
            $url = add_query_arg('filter', $n);
            $class = ($filter == $n ? ' class="current"' :'');
            $views[$n] = "<a href='{$url}'{$class}>{$v}</a>";
        }

        return $views;
    }

    function get_data($filter = 'today', $q = false)
    {

        global $advman_engine;
        $data = array();
        //$date_range = $this->_filter2date($filter);
        switch($filter) {
            case 'today' :
                $dt = date('Y-m-d');
                $date_range = array('begin' => $dt, 'end' => $dt);
                break;
            case 'yesterday':
                $dt = date('Y-m-d', strtotime('- 1 day'));
                $date_range = array('begin' => $dt, 'end' => $dt);
                break;
            default:
                die;
        }

        $date_range = array('begin' => date('Y-m-d'), 'end' => date('Y-m-d'));
        $date_breakdown = 'hour'; // 'hour', 'day',
        $entity_breakdown = 'ads';  // In the future, 'posts', 'categories', 'tags', etc.
        $stats = $advman_engine->getStats($date_range, $entity_breakdown, $date_breakdown);

        if (!empty($stats)) {
            foreach ($stats as $stat) {
                if (!$q || stristr($stat['name'], $q) !== false) {
                    $data[] = $stat;
                }
            }
        }

        return $data;
    }

    function no_items()
    {
        _e( 'There are no analytics for this time period.  Choose a different time period or add some ads to your blog.' );
    }

    function column_name($item)
    {
        //Return the title contents
        return $item['name'] . " <span style='color:silver'> (id:" . $item['i'] . ")</span>";
    }

    function column_default( $item, $column_name )
    {
        switch( $column_name ) {
            case 'name': return $item['name'];
            case 'impressions': return empty($item['i']) ? '-' : $item['i'];
            case 'views': return empty($item['v']) ? '-' : $item['v'];
            case 'clicks': return empty($item['c']) ? '-' : $item['c'];
            case 'quality': return empty($item['q']) ? '-' : $item['q'];
            default:
                return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
        }
    }

    function get_columns()
    {
        $columns = array(
            'name'        => __( 'Name', 'advman' ),
            'impressions' => __( 'Impressions', 'advman' ),
            'views'       => __( 'Views', 'advman' ),
            'clicks'      => __( 'Clicks', 'advman' ),
            'quality'     => __( 'Quality', 'advman' )
        );
        return $columns;
    }

    function get_sortable_columns()
    {
        $sortable_columns = array(
            'name'        => array('name',false),
            'impressions' => array('i',false),
            'views'       => array('v',false),
            'clicks'      => array('c',false),
            'quality'     => array('q',false),
        );

        return $sortable_columns;
    }

    function get_search_columns()
    {
        $search_columns = array(
            'name'
        );

        return $search_columns;
    }

    function usort_reorder( $a, $b )
    {
        // If no sort, default to date descending
        $orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'name';
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
            'ad'        => __('by Ad','advman'),
            'author'    => __('by Author','advman'),
            'category'  => __('by Category','advman'),
            'tag'       => __('by Tag','advman')
        );
        return $actions;
    }

    function _get_filter()
    {
        $filter = 'today';
        $options = array('today','yesterday','last7days','thismonth','lastmonth');

        if ( isset($_REQUEST['filter'] ) ) {
            if (in_array($_REQUEST['filter'], $options)) {
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
} //class
