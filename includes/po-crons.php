<?php
/**
 * Main Admin
 *
 * @package PO_Crons
 * @since   1.0.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;


/**
 * Main class to send request for sync
 */
class PO_Crons {

	public function __construct() {

// 		if ( 'completed' !== get_option('piy_all_orders_synced') ) {

			// Start Crons after 5 mints to sync products
			add_filter( 'cron_schedules', array($this, 'piy_add_cron_interval'), 10);

			if ( ! wp_next_scheduled( 'piy_send_orders' ) ) {
				wp_schedule_event( time(), 'po_five_mints', 'piy_send_orders' );
			}

			add_action('piy_send_orders', array($this, 'piy_send_orders'));
// 		}
	}

	public function piy_add_cron_interval( $schedules) {
		
		$schedules['po_five_mints'] = array(
			'interval' =>  	empty(get_option('po_cron_time')) ? 300 : get_option('po_cron_time'), // in seconds
			'display'  => esc_html__( 'Sent Orders to PIY Dashboard after every five minutes or admin scheduled time.', 'piy-online' )
		);
		return $schedules;
	}

	public function piy_send_orders() {
    global $wpdb;

    error_log("Starting piy_send_orders function...");

    if (empty(get_option('po_order_status'))) {
        error_log("Order status is empty, exiting function.");
        return;
    }

    $request = new PO_Request();

    $meta_key    = 'piy_ribbon';
    $post_type   = 'shop_order';
    $post_status = get_option('po_order_status');
    $limit       = 500;

    do {

//         error_log("Preparing the SQL query to fetch orders...");
        
        $query = $wpdb->prepare(
            "SELECT {$wpdb->prefix}posts.ID
            FROM {$wpdb->prefix}posts
            WHERE EXISTS (
                SELECT *
                FROM {$wpdb->prefix}postmeta
                WHERE {$wpdb->prefix}postmeta.post_id = {$wpdb->prefix}posts.ID

                AND {$wpdb->prefix}postmeta.meta_key = %s
            )
            AND {$wpdb->prefix}posts.post_type IN (%s)
			

            AND {$wpdb->prefix}posts.post_status IN (%s)
            ORDER BY {$wpdb->prefix}posts.post_date DESC
            LIMIT %d",
            $meta_key,
            $post_type,
            $post_status,
            $limit
        );

//     error_log("No results found $query .");
		
        $results = $wpdb->get_results($query, ARRAY_A);
// 		 error_log("Response: " . print_r($response, true));


if (!empty($results)) {
} else {
    error_log("No results found.");
}
		
        if (!empty($results)) {
            $results = array_column($results, 'ID');

            foreach ($results as $id) {

                // Skip if the order is already created
                if ('created' == get_post_meta((int) $id, 'piy_created', true)) {
                    continue;
                }
                
                $payload = $this->piy_get_order_payload((int) $id);
        error_log("paload: " . print_r($payload, true)); // Logs the array contents

                $response = $request->piy_send_payload($payload, '/order', 'PUT');
//   				  error_log("Response: " . print_r($response, true));


                $body = json_decode(wp_remote_retrieve_body($response));
                $code = wp_remote_retrieve_response_code($response);

                if (!is_wp_error($response) && 200 == $code) {
                    if ($body->success) {
                        add_post_meta((int) $id, 'piy_created', 'created');
                        add_post_meta((int) $id, 'piy_order_id', $body->order_id);
                        $order->add_order_note(esc_html('Order pushed to PIY Ribbons Dashboard Successfully.'));
                    } else {
                    }
                } else {
                }
            }
        }

        if (empty($results) && 0 == count($results)) {
            update_option('piy_all_orders_synced', 'completed');
//             error_log("All orders synchronized. No new orders found.");
        }

    } while ($results && get_option('piy_all_orders_synced') != 'completed');
}


	public function piy_get_order_payload( $order_id ) {
		$order = wc_get_order($order_id);

		$payload = $this->piy_prepare_order_payload($order);
		
		return $payload;
	}

	public function piy_prepare_order_payload($order) {
        $temp_fonts = wp_cache_get('po_template_fonts');
            
        if (false === $temp_fonts) {
            $request 	= new PO_Request();
            $temp_fonts = $request->po_get_temp_fonts();
            wp_cache_set('po_template_fonts', $temp_fonts, '', HOUR_IN_SECONDS);
        }
    
        $payload = array(
            'order_id'  			=> $order->get_id(),
            'order_key' 			=> $order->get_order_key(),
            'order_reference' 		=> $order->get_order_number(),
            'order_items'           => []
        );
    
        $bundles = [];
    
        foreach ($order->get_items('line_item') as $id => $line_item) {
            $product = wc_get_product($line_item->get_product_id());
            if (!$product) continue;
    
            // Retrieve Ribbon Text
            $text_on_ribbon = wc_get_order_item_meta($id, esc_html__('Ribbon text'));
            if (empty($text_on_ribbon)) {
                $text_on_ribbon = wc_get_order_item_meta($id, esc_html__('Tekst op het lint')); // Support for another language
            }
    
            // Retrieve Font
            $font = wc_get_order_item_meta($id, 'Fonts');
            if (empty($font)) {
                $font = wc_get_order_item_meta($id, esc_html__('Lettertypen'));
            }
    
            // Identify bundled items and skip them
            if (wc_pb_is_bundled_order_item($line_item)) {
                error_log("⏩ Skipping bundled child product: " . $product->get_name());
                continue;
            }
    
            // If this is a bundle, store it in a separate array and continue to ensure no child items are added
            if ($product->is_type('bundle')) {
                error_log("✅ Identified Main Bundle: " . $product->get_name());
    
                $bundles[$product->get_id()] = array(
                    'product_name'  => $product->get_name(),
                    'product_sku' 	=> $product->get_sku(),
                    'product_qty'  	=> $line_item->get_quantity(),
                    'font_family' 	=> $font,  
                    'print_message' => $text_on_ribbon,  // ✅ Uses actual Ribbon text
                    'template_id' 	=> array_search(wc_get_order_item_meta($id, 'PIY Ribbons Template'), $temp_fonts['templates'])
                );
    
                continue; // Ensure child items are skipped
            }
    
            // Add only simple products to the payload
            if ($product->is_type('simple')) {
                $payload['order_items'][] = array(
                    'product_name'  => $line_item->get_name(),
                    'product_sku' 	=> $product->get_sku(),
                    'product_qty'  	=> $line_item->get_quantity(),
                    'font_family' 	=> $font,
                    'print_message' => $text_on_ribbon,  // ✅ Uses actual Ribbon text
                    'template_id' 	=> array_search(wc_get_order_item_meta($id, 'PIY Ribbons Template'), $temp_fonts['templates'])
                );
            }
        }
    
        // Ensure only bundle products are added if they exist
        if (!empty($bundles)) {
            foreach ($bundles as $bundle) {
                $payload['order_items'][] = $bundle;
            }
        }
    
        error_log("📡 Final Payload Sent to API: " . print_r($payload, true));
    
        return $payload;
    }    
}

new PO_Crons();
