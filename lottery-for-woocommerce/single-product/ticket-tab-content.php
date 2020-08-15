<?php
/**
 * This template is used for displaying the ticket tab content. 
 *
 * This template can be overridden by copying it to yourtheme/lottery-for-woocommerce/ticket-tab-content.php
 *
 * To maintain compatibility, Lottery for WooCommerce will update the template files and you have to copy the updated files to your theme
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ;
}

do_action('woocommerce_check_cart_items')
?>

<div class="lty-ticket-number-wrapper">
	<ul class="lty-ticket-number-content">
		<?php
		$reserved_tickets = lwx_get_reserved_tickets($product->get_id());
		for ( $i = $start_range ; $i <= $end_range ; $i ++ ) :
			$class_name = 'lty-ticket' ;
			$list_title = '' ;

			$ticket_number = $product->format_ticket_number( $i ) ;

			if ( in_array( $ticket_number, $cart_tickets ) ) :
				$class_name .= ' lty-processing-ticket' ;
				$list_title = esc_html__( 'Ticket in Cart' , 'lottery-for-woocommerce' ) ;
			elseif ( in_array( $ticket_number , $sold_tickets ) ) :
				$class_name .= ' lty-booked-ticket' ;
				$list_title = esc_html__( 'Sold!' , 'lottery-for-woocommerce' ) ;
			elseif ( in_array( $ticket_number , $reserved_tickets ) ) :
				$class_name .= ' lty-reserved-ticket' ;
				$list_title = esc_html__( 'Ticket is Reserved' , 'lottery-for-woocommerce' ) ;
			endif ;
			?>
			<li class="<?php echo esc_attr( $class_name ) ; ?>" data-product_id="<?= $product->get_id() ?>" data-ticket="<?php echo esc_attr( $ticket_number ) ; ?>" title="<?php echo esc_attr( $list_title ) ; ?>"><?php echo esc_attr( $ticket_number ) ; ?></li>
			<?php
		endfor ;
		?>
	</ul>
</div>
