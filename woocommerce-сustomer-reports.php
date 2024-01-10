<?php

/*
Plugin Name: WooCommerce Customer Reports
Description: Easy-to-use tool for in-depth analysis of customer behavior that helps you optimize your business and create more effective marketing strategies.
Plugin URI:  
Author: 
Author URI: 
Version: 1.0
*/




class Woo_Statistics {

    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
        add_action('admin_menu', array($this, 'add_statistics_menu_item'));
    }

    public function enqueue_admin_scripts() {
        wp_enqueue_script('jquery-ui-tabs');
        wp_enqueue_style('wp-jquery-ui-tabs');
    }

    public function enqueue_admin_styles() {
        wp_enqueue_style('wp-jquery-ui-tabs');
    }

    public function add_statistics_menu_item() {
        add_submenu_page(
            'woocommerce',
            'Statistics',
            'Statistics',
            'manage_options',
            'woocommerce_statistics',
            array($this, 'display_statistics_page')
        );
    }

    




    public function display_statistics_page() {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'tab1';

        echo '<div class="wrap">';
        echo '<h2 class="nav-tab-wrapper">';
        echo '<a href="?page=woocommerce_statistics&tab=tab1" class="nav-tab ' . ($tab === 'tab1' ? 'nav-tab-active' : '') . '">New Customers</a>';
        echo '<a href="?page=woocommerce_statistics&tab=tab2" class="nav-tab ' . ($tab === 'tab2' ? 'nav-tab-active' : '') . '">Most Purchased</a>';
        echo '<a href="?page=woocommerce_statistics&tab=tab3" class="nav-tab ' . ($tab === 'tab3' ? 'nav-tab-active' : '') . '">Not Purchased</a>';
        echo '<a href="?page=woocommerce_statistics&tab=tab4" class="nav-tab ' . ($tab === 'tab4' ? 'nav-tab-active' : '') . '">Spent Most</a>';
        echo '</h2>';

        switch ($tab) {
            case 'tab1':
                $this->display_new_customers_table();
                break;
            case 'tab2':
                $this->display_most_purchased_table();
                break;
            case 'tab3':
                $this->display_not_purchased_table();
                break;
            case 'tab4':
                $this->display_spend_table();
                break;
            default:
                // Handle unknown tab or set a default tab to display
                break;
        }

        echo '</div>';
    }

    




    public function display_new_customers_table() {
        echo '<h2>New Customers</h2>';

        $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
        $end_date   = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';

        // Обработка формы
        if (isset($_GET['generate_table'])) {
            // Сохранение введенных дат в настройках WordPress
            update_option('new_customers_start_date', $start_date);
            update_option('new_customers_end_date', $end_date);
        }

        $per_page = 99999;
        $paged = ( get_query_var('paged') ) ? get_query_var('paged') : 1;

        $args = array(
            'posts_per_page' => $per_page,
            'paged'          => $paged,
            'post_type'      => 'shop_order',
            'post_status'    => 'wc-completed',
            'date_query'     => array(
                'after'     => $start_date,
                'before'    => date('Y-m-d', strtotime('+1 day', strtotime($end_date))), // Add 1 day to include orders on the end date
                'inclusive' => true,
            ),
        );

        $new_customers_query = new WP_Query($args);

        // Вывод формы
        ?>
        <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
            <label for="start_date">Start Date:</label>
            <input type="date" name="start_date" id="start_date" value="<?php echo esc_attr($start_date); ?>" required>

            <label for="end_date">End Date:</label>
            <input type="date" name="end_date" id="end_date" value="<?php echo esc_attr($end_date); ?>" required>

            <input type="hidden" name="page" value="woocommerce_statistics">
            <input type="hidden" name="tab" value="tab1">
            <input type="submit" name="generate_table" class="button button-primary" value="Generate Table">
        </form>

        <?php
        // Вывод таблицы с данными о новых покупателях
        if ($new_customers_query->have_posts()) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>User</th><th>Email</th><th>Phone</th><th>ID order</th><th>Date Order</th></tr></thead>';
            echo '<tbody>';

            while ($new_customers_query->have_posts()) {
                $new_customers_query->the_post();

                $order = wc_get_order(get_the_ID());
                $user_id = $order->get_user_id();
                $user_info = get_userdata($user_id);

                $billing_first_name = $user_id ? $order->get_billing_first_name() : $order->get_meta('_billing_first_name');
                $billing_last_name = $user_id ? $order->get_billing_last_name() : $order->get_meta('_billing_last_name');
                $billing_email = $user_id ? $order->get_billing_email() : $order->get_meta('_billing_email');
                $billing_phone = $user_id ? $order->get_billing_phone() : $order->get_meta('_billing_phone');
                $order_date = get_the_date();

                echo '<tr>';
                echo '<td>' . esc_html($billing_first_name) . ' ' . esc_html($billing_last_name) . '</td>';
                echo '<td>' . esc_html($billing_email) . '</td>';
                echo '<td>' . esc_html($billing_phone) . '</td>';
                echo '<td>' . esc_html(get_the_ID()) . '</td>';
                echo '<td>' . esc_html($order_date) . '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';

            // Сброс запроса и возврат оригинальных данных
            wp_reset_postdata();
            wp_reset_query();
        } else {
            echo '<p>No new customers found for the selected period.</p>';
        }
    }

    


    public function display_most_purchased_table() {

        echo '<h2>Most Purchased</h2>';

        $start_date = isset($_GET['start_date_tab2']) ? sanitize_text_field($_GET['start_date_tab2']) : '';
        $end_date   = isset($_GET['end_date_tab2']) ? sanitize_text_field($_GET['end_date_tab2']) : '';

        // Обработка формы
        if (isset($_GET['generate_table_tab2'])) {
            // Сохранение введенных дат в настройках WordPress
            update_option('not_purchased_start_date', $start_date);
            update_option('not_purchased_end_date', $end_date);
        }

        $per_page = 99999;
        $paged = ( get_query_var('paged') ) ? get_query_var('paged') : 1;

        $args = array(
            'posts_per_page' => $per_page,
            'paged'          => $paged,
            'post_type'      => 'shop_order',
            'post_status'    => 'wc-completed',
            'date_query'     => array(
                'after'     => $start_date,
                'before'    => date('Y-m-d', strtotime('+1 day', strtotime($end_date))), // Add 1 day to include orders on the end date
                'inclusive' => true,
            ),
        );

        $new_customers_query = new WP_Query($args);

        // Вывод формы
        ?>
        <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
            <label for="start_date_tab2">Start Date:</label>
            <input type="date" name="start_date_tab2" id="start_date_tab2" value="<?php echo esc_attr($start_date); ?>" required>

            <label for="end_date_tab2">End Date:</label>
            <input type="date" name="end_date_tab2" id="end_date_tab2" value="<?php echo esc_attr($end_date); ?>" required>

            <input type="hidden" name="page" value="woocommerce_statistics">
            <input type="hidden" name="tab" value="tab2">
            <input type="submit" name="generate_table_tab2" class="button button-primary" value="Generate Table">
        </form>

        <?php
        // Вывод таблицы с данными о несовершивших покупки покупателях
            if ($new_customers_query->have_posts()) {
            // В массиве $customer_orders необходимо сбросить значения перед новым циклом
            $customer_orders = array();

            while ($new_customers_query->have_posts()) {
                $new_customers_query->the_post();

                $order = wc_get_order(get_the_ID());
                $user_id = $order->get_user_id();
                $billing_email = $user_id ? $order->get_billing_email() : $order->get_meta('_billing_email');

                // Сохранение информации о заказе для конкретного клиента
                if (!isset($customer_orders[$billing_email])) {
                    $customer_orders[$billing_email] = array(
                        'count'   => 1,
                        'user_id' => $user_id,
                        'orders'  => array(get_the_ID()),
                    );
                } else {
                    $customer_orders[$billing_email]['count']++;
                    $customer_orders[$billing_email]['orders'][] = get_the_ID();
                }
            }

            // Сортировка массива по количеству заказов (по убыванию)
            uasort($customer_orders, function($a, $b) {
                return $b['count'] - $a['count'];
            });

            // Вывод таблицы с данными
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>User</th><th>Email</th><th>Phone</th><th>Order Count</th><th>Orders</th></tr></thead>';
            echo '<tbody>';

            foreach ($customer_orders as $email => $customer) {

                $order = wc_get_order($customer['orders'][0]);

                $billing_first_name = $user_id ? $order->get_billing_first_name() : $order->get_meta('_billing_first_name');
                $billing_last_name = $user_id ? $order->get_billing_last_name() : $order->get_meta('_billing_last_name');
                $billing_phone = $user_id ? $order->get_billing_phone() : $order->get_meta('_billing_phone');

                echo '<tr>';
                echo '<td>' . esc_html($billing_first_name . ' ' . $billing_last_name) . '</td>';
                echo '<td>' . esc_html($email) . '</td>';
                echo '<td>' . esc_html($billing_phone) . '</td>';
                echo '<td>' . esc_html($customer['count']) . '</td>';
                echo '<td>' . esc_html(implode(', ', $customer['orders'])) . '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';

            // Сброс запроса и возврат оригинальных данных
            wp_reset_postdata();
            wp_reset_query();
        } else {
            echo '<p>No customers found for the selected period.</p>';
        }

    }



    public function display_not_purchased_table() {

        echo '<h2>Not Purchased</h2>';

        
        $start_date = isset($_GET['start_date_tab3']) ? sanitize_text_field($_GET['start_date_tab3']) : '';
        $end_date   = isset($_GET['end_date_tab3']) ? sanitize_text_field($_GET['end_date_tab3']) : '';

        // Обработка формы
        if (isset($_GET['generate_table_tab3'])) {
            // Сохранение введенных дат в настройках WordPress
            update_option('not_purchased_start_date', $start_date);
            update_option('not_purchased_end_date', $end_date);
        }

        $per_page = 99999;
        $paged = ( get_query_var('paged') ) ? get_query_var('paged') : 1;

        $args = array(
            'posts_per_page' => $per_page,
            'paged'          => $paged,
            'post_type'      => 'shop_order',
            'post_status'    => 'wc-completed',
            'date_query'     => array(
                'relation' => 'OR', // Используем AND для связи условий
                array(
                    'before'    => $start_date,
                    'inclusive' => true,
                ),
                array(
                    'after'     => date('Y-m-d', strtotime('+1 day', strtotime($end_date))),
                    'inclusive' => true,
                ),
            ),
        );

        $not_customers_query = new WP_Query($args);

        ?>
        <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
            <label for="start_date_tab3">Start Date:</label>
            <input type="date" name="start_date_tab3" id="start_date_tab3" value="<?php echo esc_attr($start_date); ?>" required>

            <label for="end_date_tab3">End Date:</label>
            <input type="date" name="end_date_tab3" id="end_date_tab3" value="<?php echo esc_attr($end_date); ?>" required>

            <input type="hidden" name="page" value="woocommerce_statistics">
            <input type="hidden" name="tab" value="tab3">
            <input type="submit" name="generate_table_tab3" class="button button-primary" value="Generate Table">
        </form>

        <?php

         // Вывод таблицы с данными о новых покупателях
        if ($not_customers_query->have_posts()) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>User</th><th>Email</th><th>Phone</th><th>ID order</th><th>Date Order</th></tr></thead>';
            echo '<tbody>';

            while ($not_customers_query->have_posts()) {
                $not_customers_query->the_post();

                $order = wc_get_order(get_the_ID());
                $user_id = $order->get_user_id();
                $user_info = get_userdata($user_id);

                $billing_first_name = $user_id ? $order->get_billing_first_name() : $order->get_meta('_billing_first_name');
                $billing_last_name = $user_id ? $order->get_billing_last_name() : $order->get_meta('_billing_last_name');
                $billing_email = $user_id ? $order->get_billing_email() : $order->get_meta('_billing_email');
                $billing_phone = $user_id ? $order->get_billing_phone() : $order->get_meta('_billing_phone');
                $order_date = get_the_date();

                echo '<tr>';
                echo '<td>' . esc_html($billing_first_name) . ' ' . esc_html($billing_last_name) . '</td>';
                echo '<td>' . esc_html($billing_email) . '</td>';
                echo '<td>' . esc_html($billing_phone) . '</td>';
                echo '<td>' . esc_html(get_the_ID()) . '</td>';
                echo '<td>' . esc_html($order_date) . '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';

            // Сброс запроса и возврат оригинальных данных
            wp_reset_postdata();
            wp_reset_query();
        } else {
            echo '<p>No new customers found for the selected period.</p>';
        }
    }




    public function display_spend_table(){

        $start_date = isset($_GET['start_date_tab4']) ? sanitize_text_field($_GET['start_date_tab4']) : '';
        $end_date   = isset($_GET['end_date_tab_4']) ? sanitize_text_field($_GET['end_date_tab_4']) : '';

        // Обработка формы
        if (isset($_GET['generate_table'])) {
            // Сохранение введенных дат в настройках WordPress
            update_option('new_customers_start_date', $start_date);
            update_option('new_customers_end_date', $end_date);
        }

        $per_page = 99999;
        $paged = ( get_query_var('paged') ) ? get_query_var('paged') : 1;

        $args = array(
            'posts_per_page' => $per_page,
            'paged'          => $paged,
            'post_type'      => 'shop_order',
            'post_status'    => 'wc-completed',
            'date_query'     => array(
                'after'     => $start_date,
                'before'    => date('Y-m-d', strtotime('+1 day', strtotime($end_date))), // Add 1 day to include orders on the end date
                'inclusive' => true,
            ),
        );

        $new_customers_query = new WP_Query($args);

        // Вывод формы
        ?>
        <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
            <label for="start_date_tab4">Start Date:</label>
            <input type="date" name="start_date_tab4" id="start_date_tab4" value="<?php echo esc_attr($start_date); ?>" required>

            <label for="end_date_tab_4">End Date:</label>
            <input type="date" name="end_date_tab_4" id="end_date_tab_4" value="<?php echo esc_attr($end_date); ?>" required>

            <input type="hidden" name="page" value="woocommerce_statistics">
            <input type="hidden" name="tab" value="tab4">
            <input type="submit" name="generate_table" class="button button-primary" value="Generate Table">
        </form>

        <?php
            if ($new_customers_query->have_posts()) {
                // В массиве $customer_orders необходимо сбросить значения перед новым циклом
                $customer_orders = array();

                while ($new_customers_query->have_posts()) {
                    $new_customers_query->the_post();

                    $order = wc_get_order(get_the_ID());
                    $user_id = $order->get_user_id();
                    $billing_email = $user_id ? $order->get_billing_email() : $order->get_meta('_billing_email');
                    $order_total = $order->get_total(); // Получаем сумму заказа

                    // Сохранение информации о заказе для конкретного клиента
                    if (!isset($customer_orders[$billing_email])) {
                        $customer_orders[$billing_email] = array(
                            'user_id'    => $user_id,
                            'orders'     => array(get_the_ID()),
                            'total'      => $order_total,
                        );
                    } else {
                        $customer_orders[$billing_email]['orders'][] = get_the_ID();
                        $customer_orders[$billing_email]['total'] += $order_total;
                    }
                }

                // Сортировка массива по сумме заказов (по убыванию)
                uasort($customer_orders, function ($a, $b) {
                    return $b['total'] - $a['total'];
                });

                // Вывод таблицы с данными
                echo '<table class="wp-list-table widefat fixed striped">';
                echo '<thead><tr><th>User</th><th>Email</th><th>Phone</th><th>Total Orders Amount</th><th>Orders</th></tr></thead>';
                echo '<tbody>';

                foreach ($customer_orders as $email => $customer) {

                    $order = wc_get_order($customer['orders'][0]);

                    $billing_first_name = $user_id ? $order->get_billing_first_name() : $order->get_meta('_billing_first_name');
                    $billing_last_name = $user_id ? $order->get_billing_last_name() : $order->get_meta('_billing_last_name');
                    $billing_phone = $user_id ? $order->get_billing_phone() : $order->get_meta('_billing_phone');

                    echo '<tr>';
                    echo '<td>' . esc_html($billing_first_name . ' ' . $billing_last_name) . '</td>';
                    echo '<td>' . esc_html($email) . '</td>';
                    echo '<td>' . esc_html($billing_phone) . '</td>';
                    echo '<td>' . wc_price($customer['total']) . '</td>';
                    echo '<td>' . esc_html(implode(', ', $customer['orders'])) . '</td>';
                    echo '</tr>';
                }

                echo '</tbody></table>';


            // Сброс запроса и возврат оригинальных данных
            wp_reset_postdata();
            wp_reset_query();
        } else {
            echo '<p>No customers found for the selected period.</p>';
        }

    }

}

new Woo_Statistics();

