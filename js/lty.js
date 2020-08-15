/* global lty_frontend_params */

jQuery( function( $ ) {
    'use strict' ;
    var ticket_numbers = [ ] ;
    var LTY_Frontend = {
        init : function( ) {
            this.trigger_on_page_load( ) ;
            // Choose the ticket tab.
            $( document ).on( 'click' , '.lty-lottery-ticket-tab' , this.lottery_ticket_tab_selection ) ;
            // Select the ticket.
            $( document ).on( 'click' , '.lty-ticket' , this.lottery_ticket_selection ) ;
            // Unselect the ticket.
            $( document ).on( 'click' , '.lty-selected-ticket' , this.lottery_ticket_unselection ) ;
            // Select the question answer.
            $( document ).on( 'click' , 'ul.lty-lottery-answers li' , this.select_question_answer ) ;
            // Validate the participate.
            $( document ).on( 'click' , '.lty-participate-now-button .lty-lucky-dip-button' , this.validate_participate ) ;
            // Process the ticket lucky dip.
            $( document ).on( 'click' , '.lty-lucky-dip-button' , this.process_lucky_dip ) ;
        } , trigger_on_page_load : function( ) {
            $( '.lty_manual_add_to_cart' ).attr( 'disabled' , true ) ;
        } , select_question_answer : function( event ) {
            event.preventDefault( ) ;
            var $this = $( event.currentTarget ) ,
                    answers_wrapper = $( $this ).closest( '.lty-lottery-answers' ) ,
                    answer_id = $( $this ).data( 'answer-id' ) ;
            answers_wrapper.find( 'li' ).removeClass( 'lty-selected' ) ;
            $( $this ).addClass( 'lty-selected' ) ;
            $( '.lty-question-answer-id' ).val( answer_id ) ;
            $( '.lty-lottery-ticket-container' ).addClass('unlock');    
            LTY_Frontend.handle_add_cart_button( ) ;
        } , handle_add_cart_button : function( ) {
            var show_button = true ,
                    ticket_container = $( '.lty-lottery-ticket-container' ) ,
                    question_container = $( '.lty-lottery-question-answer-container' ) ,
                    lucky_dip_container = $( '.lty-lottery-ticket-lucky-dip-container' ) ;
            if( ticket_container.length && '' == ticket_container.find( '.lty-lottery-ticket-numbers' ).val( ) ) {
                show_button = false ;
            }

            if( question_container.length && 'yes' == question_container.data( 'force' ) && '' == question_container.find( '.lty-question-answer-id' ).val( ) ) {
                show_button = false ;
            }

            // Show manual add to cart button.
            if( show_button ) {
                $( '.lty_manual_add_to_cart' ).removeAttr( 'disabled' ) ;
            }

            // Show Lucky dip Button. 
            if( question_container.length && 'yes' == question_container.data( 'force' ) && '' != question_container.find( '.lty-question-answer-id' ).val( ) ) {
                if( lucky_dip_container.length ) {
                    lucky_dip_container.find( '.lty-lucky-dip-button' ).removeAttr( 'disabled' ) ;
                    lucky_dip_container.find( '.lty-lucky-dip-button' ).removeAttr( 'title' ) ;
                }
            }
        } , validate_participate : function( event ) {
            var error_message = null ;
            if( 'yes' == lty_frontend_params.guest_user ) {
                error_message = lty_frontend_params.guest_error_msg ;
            }

            if( error_message ) {
                event.preventDefault( ) ;
                alert( error_message ) ;
                return false ;
            }

            return true ;
        } , lottery_ticket_tab_selection : function( event ) {
            event.preventDefault( ) ;
            var $this = $( event.currentTarget ) ,
                    tickets_container = $( $this ).closest( '.lty-lottery-ticket-container' ) ;
            LTY_Frontend.block( tickets_container ) ;
            var data = ( {
                action : 'lty_ticket_tab_selection' ,
                product_id : tickets_container.find( '.lty-ticket-product-id' ).val( ) ,
                tab : $this.data( 'tab' ) ,
                lty_security : lty_frontend_params.lottery_tickets_nonce ,
            } ) ;
            $.post( lty_frontend_params.ajaxurl , data , function( res ) {
                if( true === res.success ) {

                    tickets_container.find( '.lty-lottery-ticket-tab' ).removeClass( 'lty-active-tab' ) ;
                    $( $this ).addClass( 'lty-active-tab' ) ;
                    tickets_container.find( '.lty-lottery-ticket-tab-content' ).html( res.data.html ) ;

                    // class 'lty-selected-ticket' added when ticket tab selection is clicked. 
                    var $selected_ticket_numbers = $( '.lty-lottery-ticket-numbers' ).val( ) ;
                    for( var i = 0 ; i < $selected_ticket_numbers.length ; i++ ) {
                        if( $.isNumeric( $selected_ticket_numbers[i] ) ) {
                            $( '.lty-ticket[data-ticket="' + $selected_ticket_numbers[i] + '"]' ).addClass( 'lty-selected-ticket' ) ;
                        }
                    }
                } else {
                    alert( res.data.error ) ;
                }

                LTY_Frontend.unblock( tickets_container ) ;
            }
            ) ;
        } , lottery_ticket_selection : function( event ) {
            event.preventDefault( ) ;
            var $this = $( event.currentTarget ) ,
                    tickets_container = $( $this ).closest( '.lty-lottery-ticket-container' ) ,
                    selected_tickets = tickets_container.find( '.lty-lottery-ticket-numbers' ) ,
                    quantity = tickets_container.find( '.lty-lottery-ticket-quantity' ) ;

            if( $this.hasClass( "lty-reserving-ticket" ) || $this.hasClass( "lty-reserved-ticket" ) || $this.hasClass( "lty-booked-ticket" ) || $this.hasClass( "lty-processing-ticket" ) || $this.hasClass( "lty-selected-ticket" ) ) {
                return ;
            }

            $( $this ).addClass( 'lty-reserving-ticket' ) ;

            $.post(
                lty_frontend_params.ajaxurl,
                {
                    nonce: lty_frontend_params.lottery_tickets_nonce, 
                    ticket_number: $( $this ).data( 'ticket' ),
                    action: 'is_ticket_reserved',
                    product_id: $( $this ).data( 'product_id' ),
                },
                function(data, status){
                    $( $this ).removeClass( 'lty-reserving-ticket' ) ;
					data = JSON.parse(data);
                    if(data.error) {
                        $( $this ).addClass( 'lty-reserved-ticket' ) ;
						alert("This ticket is being processed by another user.")
                    } else {
                        $( $this ).addClass( 'lty-selected-ticket' ) ;
                        ticket_numbers.push( $( $this ).data( 'ticket' ) ) ;
                        selected_tickets.val( ticket_numbers ) ;
                        quantity.val( ticket_numbers.length ) ;
                        LTY_Frontend.handle_add_cart_button( ) ;
                    }
                });
        } , lottery_ticket_unselection : function( event ) {
            event.preventDefault( ) ;
            var $this = $( event.currentTarget ) ,
                    tickets_container = $( $this ).closest( '.lty-lottery-ticket-container' ) ,
                    selected_tickets = tickets_container.find( '.lty-lottery-ticket-numbers' ) ,
                    quantity = tickets_container.find( '.lty-lottery-ticket-quantity' ) ;

            var $ticket = $( $this ).data( 'ticket' ) ;
            $( $this ).removeClass( 'lty-selected-ticket' ) ;
            $( $this ).removeClass( 'lty-reserving-ticket' ) ;

            // Splice index number , delete count.
            ticket_numbers.splice( ticket_numbers.indexOf( $ticket ) , 1 ) ;
            selected_tickets.val( ticket_numbers ) ;
            quantity.val( ticket_numbers.length ) ;

            if( !ticket_numbers.length ) {
                $( '.lty_manual_add_to_cart' ).attr( 'disabled' , true ) ;
            }

        } , process_lucky_dip : function( event ) {
            event.preventDefault( ) ;
            var $this = $( event.currentTarget ) ,
                    tickets_container = $( $this ).closest( '.lty-lottery-ticket-container' ) ;
            LTY_Frontend.block( $this ) ;
            var data = ( {
                action : 'lty_process_lucky_dip' ,
                product_id : tickets_container.find( '.lty-ticket-product-id' ).val( ) ,
                qty : tickets_container.find( '.lty-lucky-dip-quantity' ).val( ) ,
                answer : $( '.lty-question-answer-id' ).val( ) ,
                lty_security : lty_frontend_params.lottery_tickets_nonce ,
            } ) ;
            $.post( lty_frontend_params.ajaxurl , data , function( res ) {
                if( true === res.success ) {
                    alert( res.data.msg ) ;
                    window.location.reload( ) ;
                } else {
                    alert( res.data.error ) ;
                }

                LTY_Frontend.unblock( $this ) ;
            } ) ;
        } ,
        block : function( id ) {
            $( id ).block( {
                message : null ,
                overlayCSS : {
                    background : '#fff' ,
                    opacity : 0.7
                }
            } ) ;
        } , unblock : function( id ) {
            $( id ).unblock( ) ;
        } ,
    } ;
    LTY_Frontend.init( ) ;
} ) ;
