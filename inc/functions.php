<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Check the cart items if the lottery product is validate to purchase.
 * 
 * @return bool
 * */
function lwx_check_cart_items()
{

    $return     = true;
    $cart_items = WC()->cart->get_cart();

    $reserved_tickets = WC()->session->get('reserved_tickets');
    if ($reserved_tickets == null) {
        $reserved_tickets = array();
    }

    global $wpdb;
    $session_table_name = $wpdb->prefix . 'woocommerce_sessions';
    $t = time();
    $query = "SELECT session_value FROM {$session_table_name}";
    $result = $wpdb->get_results($query);
    $users_reserved_tickets = array();
    foreach ($result as $session_data) {
        $users_reserved_tickets_data = unserialize(unserialize($session_data->session_value)['reserved_tickets']);
        if (!empty($users_reserved_tickets_data)) {
            foreach ($users_reserved_tickets_data as $product_id => $tickets) {
                if(empty($tickets)) {
                    $tickets = array();
                }
                if (empty($users_reserved_tickets[$product_id])) {
                    $users_reserved_tickets[$product_id] = array();
                }
                $users_reserved_tickets[$product_id] = array_merge($users_reserved_tickets[$product_id], $tickets);
            }
        }
    }

    if (!isset($cart_tickets)) {
        $cart_tickets = array();
    }

    if (!lty_check_is_array($cart_items)) {
        return $return;
    }

    foreach ($cart_items as $cart_item_key => $value) {

        $product_id = isset($value['product_id']) ? $value['product_id'] : '';
        $product    = wc_get_product($product_id);

        if ('lottery' != $product->get_type()) {
            continue;
        }

        $result = lwx_validate_lottery_cart_items($product, $value);
        if (!is_wp_error($result)) {
            if (!isset($cart_tickets[$product->get_id()])) {
                $cart_tickets[$product->get_id()] = array();
            }

            $cart_tickets[$product->get_id()] = array_merge($cart_tickets[$product->get_id()], $value['lty_lottery']['tickets']);
            continue;
        }

        // Remove the product from cart. 
        WC()->cart->set_quantity($cart_item_key, 0);

        wc_add_notice($result->get_error_message(), 'error');

        $return = false;
    }

    if (empty($reserved_tickets[$product->get_id()])) {
        $reserved_tickets[$product->get_id()] = array();
    }

    if (empty($users_reserved_tickets[$product->get_id()])) {
        $users_reserved_tickets[$product->get_id()] = array();
    }

    if ($return) {
        $temp_reserved_tickets = array();
        foreach ($users_reserved_tickets as $product_id => $ticket_numbers) {
            $temp_user_reserved_tickets = array_diff($ticket_numbers, $reserved_tickets[$product_id]);
            foreach ($value['lty_lottery']['tickets'] as $ticket_number) {
                if (!empty($temp_user_reserved_tickets) && in_array($ticket_number, $temp_user_reserved_tickets)) {
                    array_push($temp_reserved_tickets, $ticket_number);
                }
            }
        }

        if (!empty($temp_reserved_tickets) && sizeOf($temp_reserved_tickets) > 0) {
            WC()->cart->set_quantity($cart_item_key, 0);
            $result = new WP_Error('invalid', sprintf(esc_html__('You cannot add Ticket Number(s) %s as it was already reserved by another user(s).', 'lottery-for-woocommerce'), implode(' , ', $temp_reserved_tickets)));
            wc_add_notice($result->get_error_message(), 'error');
            $return = false;
        }
    }

    if ($return) {
        WC()->session->set('reserved_tickets', $cart_tickets);
        $expiry_time = $t + (60 * 5);
        WC()->session->set('reserved_tickets_expiry', $expiry_time);
    }
    return $return;
}

/**
 * Validate the lottery product is closed.
 * check the ticket count exists.
 * 
 * @return bool/error message.
 * */
function lwx_validate_lottery_cart_items($product, $value)
{

    $result = true;

    // Check if the lottery ticket is closed.
    if ($product->is_closed()) {
        $result = new WP_Error('invalid', esc_html__('Lottery product is removed from the cart because lottery has been closed.', 'lottery-for-woocommerce'));
    }

    // Check if the lottery ticket count exists.
    if (!$product->is_closed() && $product->get_placed_ticket_count() >= $product->get_lty_maximum_tickets()) {
        $result = new WP_Error('invalid', esc_html__('Lottery product is removed from the cart because the maximum ticket count for the lottery has been reached.', 'lottery-for-woocommerce'));
    }

    // Check if the user lottery ticket count exists.
    if (!$product->is_closed() && $product->get_user_placed_ticket_count() >= $product->get_lty_user_maximum_tickets()) {
        $result = new WP_Error('invalid', esc_html__('You cannot purchase any more tickets for this lottery.', 'lottery-for-woocommerce'));
    }

    // Check if the ticket numbers are already purchased.
    if (isset($value['lty_lottery']['tickets']) && lty_check_is_array($value['lty_lottery']['tickets'])) {

        $already_purchased_tickets = array();

        foreach ($value['lty_lottery']['tickets'] as $ticket_number) {
            $ticket_numbers = lty_product_ticket_number_exists($product->get_id(), $ticket_number);

            if (!$ticket_numbers) {
                continue;
            }

            $already_purchased_tickets[] = $ticket_number;
        }

        if (lty_check_is_array($already_purchased_tickets)) {
            /* translators: %s: ticket numbers */
            $result = new WP_Error('invalid', sprintf(esc_html__('You cannot purchase Ticket Number(s) %s as it was already purchased by another user(s).', 'lottery-for-woocommerce'), implode(' , ', $already_purchased_tickets)));
        }
    }

    if (!LTY_Lottery_Cart::maybe_validate_ip_address($product->get_id())) {
        $result = new WP_Error('invalid', get_option('lty_settings_ip_address_restriction_error_message', 'Sorry, you cannot participate in this Lottery because, your IP Address is restricted.'));
    }

    return $result;
}

/**
 * Remove reserved tickets while deleting it from cart.
 * 
 * @return null
 */

function lwx_action_woocommerce_cart_item_removed($cart_item_key, $cart)
{
    global $woocommerce;
    $reserved_tickets = WC()->session->get('reserved_tickets');
    $tickets = $cart->removed_cart_contents[$cart_item_key]['lty_lottery']['tickets'];
    $product_id =  $cart->removed_cart_contents[$cart_item_key]['product_id'];
    $removed_reserved_tickets = array_diff($reserved_tickets[$product_id], $tickets);
    $reserved_tickets[$product_id] = $removed_reserved_tickets;
    WC()->session->set('reserved_tickets', $reserved_tickets);
};

/**
 * Get all Reserved Tickets except the current user.
 * 
 * @return array
 */

function lwx_get_reserved_tickets($product_id)
{
    $reserved_tickets = WC()->session->get('reserved_tickets');
    if ($reserved_tickets == null) {
        $reserved_tickets = array();
    }

    if (!isset($reserved_tickets[$product_id])) {
        $reserved_tickets[$product_id] = array();
    }

    global $wpdb;
    $session_table_name = $wpdb->prefix . 'woocommerce_sessions';
    $t = time();
    $query = "SELECT session_value FROM {$session_table_name}";
    $result = $wpdb->get_results($query);
    $users_reserved_tickets = array();
    foreach ($result as $session_data) {
        $session_unserialized = unserialize($session_data->session_value);
        if (isset($session_unserialized['reserved_tickets'])) {
            $users_reserved_tickets_data = unserialize($session_unserialized['reserved_tickets']);
            if (!empty($users_reserved_tickets_data)) {
                foreach ($users_reserved_tickets_data as $loop_product_id => $tickets) {
                    if (empty($users_reserved_tickets[$loop_product_id])) {
                        $users_reserved_tickets[$loop_product_id] = array();
                    }
                    if (!isset($tickets)) {
                        $tickets = array();
                    }
                    $users_reserved_tickets[$loop_product_id] = array_merge($users_reserved_tickets[$loop_product_id], $tickets);
                }
            }
        }
    }

    if ($users_reserved_tickets == null) {
        $users_reserved_tickets = array();
    }

    if (!isset($users_reserved_tickets[$product_id])) {
        $users_reserved_tickets[$product_id] = array();
    }

    $temp_user_reserved_tickets = array_diff($users_reserved_tickets[$product_id], $reserved_tickets[$product_id]);
    return $temp_user_reserved_tickets;
}

/**
 * Check if tickets are reserved or on while adding to cart.
 * 
 * @return bool
 */

function lwx_is_ticket_reserved()
{
    $result = array();
    $product_id = $_REQUEST['product_id'];
    if (!wp_verify_nonce($_REQUEST['nonce'], "lty-lottery-tickets")) {
        $result['error'] = false;
        $result['message'] = 'Error!';
        $result['product_id'] = $product_id;
        exit(json_encode($result));
    }
    $ticket_number = $_REQUEST['ticket_number'];
    $reserved_tickets = lwx_get_reserved_tickets($product_id);
    $result = array();
    if (in_array($ticket_number, $reserved_tickets)) {
        $result['error'] = true;
        $result['message'] = 'Ticket is Reserved.';
        $result['product_id'] = $product_id;
    } else {
        $result['error'] = false;
        $result['message'] = 'Ticket is Available.';
        $result['product_id'] = $product_id;
    }

    echo json_encode($result);
    die();
}

/**
 * Auto clear cart after 5 minutes fo reserving the tickets.
 * 
 * @return null
 */
if (!function_exists('lwx_auto_remove_cart')) {
    function lwx_auto_remove_cart()
    {
        global $wpdb;
        $session_table_name = $wpdb->prefix . 'woocommerce_sessions';
        $usermeta_table_name = $wpdb->prefix . 'usermeta';
        $t = time();
        $query = "SELECT session_id, session_key, session_value FROM {$session_table_name}";
        $result = $wpdb->get_results($query);
        foreach ($result as $row) {
            $session_data = unserialize($row->session_value);
            if (array_key_exists('reserved_tickets_expiry', $session_data)) {
                $reserved_tickets_expiry = $session_data['reserved_tickets_expiry'];

                if ($reserved_tickets_expiry <= $t) {
                    $wpdb->delete($session_table_name, array('session_id' => $row->session_id));
                    $wpdb->delete($usermeta_table_name, array('user_id' => $row->session_key, 'meta_key' => '_woocommerce_persistent_cart_1'));
                }
            }
        }
    }
}

/**
 * Message for Add to Cart Hook
 * 
 * @return string
 */

function lwx_add_to_cart_message($message, $products, $show_qty)
{
    $message = __('Ticket numbers will be reserved for 5 minutes. After that someone else could reserve or buy the same ticket!', 'woocommerce');
    return $message;
}

/**
 * Override LTY JS Script for Ajax
 * 
 * 
 */

function lwx_override_script()
{
    wp_dequeue_script('lty-frontend');
    wp_enqueue_script('lty-custom-frontend', plugin_dir_url(__DIR__) . 'js/lty.js', array('jquery'));
    wp_localize_script('lty-custom-frontend', 'lty_frontend_params', array(
        'lottery_tickets_nonce' => wp_create_nonce('lty-lottery-tickets'),
        'ajaxurl'               => LTY_ADMIN_AJAX_URL,
        'guest_user'            => (!is_user_logged_in() && '2' == get_option('lty_settings_guest_user_participate_type')) ? 'yes' : 'no',
        'guest_error_msg'       => get_option('lty_settings_single_product_validate_guest_error_message'),
    ));
    wp_register_style( 'lwx-style',  plugin_dir_url(__DIR__) . 'css/style.css' );
    wp_enqueue_style('lwx-style');
}

function lwx_plugin_path()
{
    return untrailingslashit(plugin_dir_path(__DIR__));
}

function lwx_lttery_for_woocommerce_locate_template($template, $template_name, $template_path)
{
    global $woocommerce;

    $_template = $template;

    if (!$template_path) $template_path = $woocommerce->template_url;

    $plugin_path  = lwx_plugin_path() . '/lottery-for-woocommerce/';

    // Look within passed path within the theme - this is priority
    $template = locate_template(

        array(
            $template_path . $template_name,
            $template_name
        )
    );

    // Modification: Get the template from this plugin, if it exists
    if (!$template && file_exists($plugin_path . $template_name))
        $template = $plugin_path . $template_name;

    // Use default template
    if (!$template)
        $template = $_template;

    // Return what we found
    return $template;
}
