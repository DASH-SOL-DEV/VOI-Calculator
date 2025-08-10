<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class VOI_Submissions_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => 'Submission',
            'plural'   => 'Submissions',
            'ajax'     => false
        ]);
    }

    public function get_columns() {
        return [
            'cb'           => '<input type="checkbox" />',
            'full_name'    => 'Name',
            'email'        => 'Email',
            'company_name' => 'Company',
            'total_tb'     => 'Total TB',
            'total_vms'    => 'Total VMs',
            'time'         => 'Date'
        ];
    }

    public function column_default($item, $column_name) {
        return isset($item[$column_name]) ? esc_html($item[$column_name]) : print_r($item, true);
    }
    
    public function column_company_name($item) {
        return sprintf(
            '<a href="%s" target="_blank">%s</a>',
            esc_url($item['company_url']),
            esc_html($item['company_name'])
        );
    }

    protected function get_sortable_columns() {
        return [
            'full_name'    => ['full_name', false],
            'company_name' => ['company_name', false],
            'time'         => ['time', true]
        ];
    }

    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'voi_submissions';

        $per_page = 20;
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];

        $orderby = !empty($_GET['orderby']) ? esc_sql($_GET['orderby']) : 'time';
        $order = !empty($_GET['order']) ? esc_sql($_GET['order']) : 'DESC';

        $query = "SELECT * FROM {$table_name} ORDER BY {$orderby} {$order}";
        $data = $wpdb->get_results($query, ARRAY_A);

        $current_page = $this->get_pagenum();
        $total_items = count($data);
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page
        ]);
        $this->items = array_slice($data, (($current_page - 1) * $per_page), $per_page);
    }
}

class VOI_Calculator_Admin {
    public function init() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }

    public function add_admin_menu() {
        add_menu_page(
            'VOI Calculator Submissions',
            'VOI Calculator',
            'manage_options',
            'voi-calculator',
            [$this, 'render_submissions_page'],
            'dashicons-calculator',
            25
        );
    }

    public function render_submissions_page() {
        $list_table = new VOI_Submissions_List_Table();
        $list_table->prepare_items();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post">
                <?php
                $list_table->display();
                ?>
            </form>
        </div>
        <?php
    }
}
