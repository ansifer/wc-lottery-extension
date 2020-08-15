<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Adding Script
add_action('wp_enqueue_scripts', 'lwx_override_script',100);

// Overriding the verification process of adding the tickets to cart.
remove_action('woocommerce_check_cart_items', array('LTY_Lottery_Cart', 'check_cart_items'), 1);
add_action('woocommerce_check_cart_items', 'lwx_check_cart_items', 1);

// Remove reserved tickets with the procust removal from cart
add_action('woocommerce_cart_item_removed', 'lwx_action_woocommerce_cart_item_removed', 10, 2);

// Handle ajax call to check if ticket is reserved or not
add_action("wp_ajax_is_ticket_reserved", "lwx_is_ticket_reserved");
add_action("wp_ajax_nopriv_is_ticket_reserved", "lwx_is_ticket_reserved");

// Clear cart of those users who already took more then five minutes but didn't checkout.
add_action('clear_cart_every_minute', 'auto_remove_cart');

// Removing notice of pruct added to cart.
add_filter('woocommerce_cart_item_removed_notice_type', '__return_null');

// Change Add to Cart message for 5 minutes reservation.
add_filter('wc_add_to_cart_message_html', 'lwx_add_to_cart_message',10, 3);

// Override Template
add_filter( 'woocommerce_locate_template', 'lwx_lttery_for_woocommerce_locate_template', 10, 3 );