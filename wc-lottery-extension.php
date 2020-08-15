<?php

/**
 * Lottery For Woocommerce Extension
 * 
 * @package           lottery-for-woocommerce-extended
 * @author            Sanchit Varshney
 * @copyright         2019 Sanchit Varshney
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: WC Lottery Extension
 * Plugin URI: http://www.sanchitvarshney.com/wc-lottery-extension
 * Description: Get Extra Features like Reserving a Ticket, Check Tickets Reservation on ticket clicks, Removing Tickets Automatically in 5 minutes and more.
 * Version: 1.0
 * Requires at least: 5.2
 * Requires PHP:      7.1
 * Author: Sanchit Varshney
 * Author URI: http://www.sanchitvarshney.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wc-lottery-extension
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if (!is_plugin_active( 'lottery-for-woocommerce/lottery-for-woocommerce.php')) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-warning is-dismissible">
                 <p>Please install Lottery for Woocommerce Plugin to use WC Lottery Extension</p>
             </div>';
    });
    return;
}

require('inc/functions.php');
require('inc/hooks.php');

if (!function_exists('lwx_activation')) {
    function lwx_activation()
    {
    }
}

if (!function_exists('lwx_deactivation')) {
    function lwx_deactivation()
    {
    }
}

if (!function_exists('lwx_uninstallation')) {
    function lwx_uninstallation()
    {
    }
}


register_activation_hook(__FILE__, 'lwx_activation');
register_deactivation_hook(__FILE__, 'lwx_deactivation');
register_uninstall_hook(__FILE__, 'lwx_uninstallation');
