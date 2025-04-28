<?php

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('PO_Front')) {

	class PO_Front {

		public function __construct() {

			add_action( 'wp_enqueue_scripts', array( $this, 'po_front_scripts' ) );

			add_action('woocommerce_before_add_to_cart_button', array($this, 'po_show_fields'), 20 );

			add_filter('woocommerce_add_cart_item_data', array($this, 'po_add_cart_item_meta_data'), 20, 4);

			add_filter('woocommerce_get_item_data', array($this, 'po_get_cart_item_data'), 20, 2);

			add_action('woocommerce_new_order_item', array($this, 'po_add_order_item_meta'), 20, 3);

			add_action('woocommerce_checkout_create_order_line_item', array($this,'po_template_order_item_meta_data'), 20, 4 );

			add_filter('woocommerce_order_item_display_meta_key', array($this, 'po_wc_order_item_display_meta_key'), 20, 3 );

			// add_action('woocommerce_before_calculate_totals', array($this, 'po_set_ribbon_price'), 20);

			add_action('woocommerce_cart_calculate_fees', array($this, 'po_add_cart_item_fee'), 20);

			// add_action('woocommerce_cart_totals_before_order_total', array($this, 'po_display_ribbon_price_sum'), 20);

			add_action('woocommerce_checkout_order_created', array($this, 'po_create_piy_order'), 20 );
		}

		public function po_front_scripts() {

			wp_enqueue_style('po-front' , plugins_url('../../assets/css/po_front.css' , __FILE__) , false, '1.0');

			wp_enqueue_script('po-front' , plugins_url('../../assets/js/po_front.js', __FILE__) , array('jquery') , '1.0', false);

			wp_register_script('emojimart', 'https://cdn.jsdelivr.net/npm/emoji-mart@latest/dist/browser.js', array('jquery'), '3.0', true );
			wp_enqueue_script('emojimart');

			$info = array(
				'admin_url'  => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('po-ajax-nonce'),
			);

			wp_localize_script( 'po-front', 'info', $info );
		}

		public function po_get_applied_from($product_id) {

			$product = wc_get_product($product_id);

			if (!is_a($product, 'WC_Product')) {
				return;
			}

			$display   = false;
			$enable_by = '';
			$term_id   = 0;
			$price 	   = 0;


			$enable = get_post_meta($product_id, 'po_pro_enable', true);

			if ('yes' == $enable) {
				$enable_by = 'product';
				$price 	   = get_post_meta($product_id, 'po_pro_price', true);
				$display   = true;
			} else {

				$terms = wp_get_post_terms($product_id, array('product_cat', 'product_tag'));

				foreach ($terms as $term) {

					if ('product_cat' == $term->taxonomy) {
						$enable_by = 'category';
						$enable    = get_term_meta($term->term_id, 'po_cat_enable', true);
						$price 	   = get_term_meta($term->term_id, 'po_cat_price', true);
					} elseif('product_tag' == $term->taxonomy) {

						$enable_by = 'tag';
						$enable    = get_term_meta($term->term_id, 'po_tag_enable', true);
						$price 	   = get_term_meta($term->term_id, 'po_tag_price', true);
					}

					if ('yes' == $enable) {

						$display = true;
						$term_id = $term->term_id;
						break;
					}
				}


				if ('yes' !== $enable) {
					$enable = 'yes' == get_option('po_gen_enable') ? get_option('po_gen_enable') : 'no';

					if ('yes' === $enable ) {
						$enable_by = 'general';
						$display   = $this->po_is_in_gen_configs($product_id);
						$price 	   = get_option('po_gen_price');

					}
				}
			}

			return array('enable' => $enable, 'enable_by' => $enable_by, 'display' => $display, 'term_id' => $term_id, 'product_id' => $product_id, 'price' => $price);
		}

		public function po_show_fields() {

			$applied_data = $this->po_get_applied_from(get_the_ID());

			if (!$applied_data['display']) {
				return;
			}

			$term_id = $applied_data['term_id'];
			$enable_by = $applied_data['enable_by'];
			$price = $applied_data['price'];
			$text = empty(get_option('po_gen_text')) ? '<strong>This is a gift:</strong> add a personalised satin ribbon!' : get_option('po_gen_text');

			if( $price > 0 ) {
				$text = str_replace('{price}', wc_price($price), $text);
			}else{
				$text = str_replace('{price}', '', $text);
			}


			ob_start();
			?>
			<div class="po-parent">
			<div style="clear: both;"></div>
				<input type="checkbox" name="po_show_fields" id="po_show_fields" value="yes" data-product_id="<?php echo get_the_ID(); ?>" data-sent="false" data-enable_by="<?php echo esc_attr($enable_by); ?>" data-term = '<?php echo esc_attr($term_id); ?>'> <?php  if( $price > 0 ) { echo '<span class="piy-price">(+ ' . wc_price($price)  . ' ) </span>'; } echo wp_kses_post($text); ?>
				<div class="po-display-fields">

				</div>
			</div><div style="clear: both;"></div>
			<?php
			echo ob_get_clean();
		}

		public function po_is_in_gen_configs($product_id) {

			$products 	= !empty( get_option('po_gen_products') ) ? get_option('po_gen_products') : array();
			$categories = get_option('po_gen_categories');
			$tags 		= get_option('po_gen_tags');
			$attrs 		= get_option('po_gen_attributes');
			$result 	= false;

			if (!empty($categories)) {
				$args = array(

					'post_type' => 'product',

					'post_status' => 'publish',

					'fields' => 'ids',

					'orderby'=> 'menu_order',

					'numberposts' => -1,

					'suppress_filters' => true,

					'order' => 'ASC',

					'tax_query' => array(

						array(

							'taxonomy' => 'product_cat',

							'field' => 'ids',

							'terms' => $categories,
						)

					)
				);

				$posts = get_posts($args);

				$products = array_merge($products, $posts);
			}

			if (!empty($tags)) {
				$args = array(

					'post_type' => 'product',

					'post_status' => 'publish',

					'fields' => 'ids',

					'orderby'=> 'menu_order',

					'numberposts' => -1,

					'suppress_filters' => true,

					'order' => 'ASC',

					'tax_query' => array(

						array(

							'taxonomy' => 'product_tag',

							'field' => 'ids',

							'terms' => $tags,
						)

					)
				);

				$posts = get_posts($args);

				$products = array_merge($products, $posts);
			}

			if (!empty($attrs)) {
				$product = wc_get_product($product_id);
				$result = count(array_intersect($attrs,array_keys($product->get_attributes()))) > 0 ? true : false;
			}

			if (!$result && !empty($products)) {
				$result = in_array($product_id, $products);
			}

			return  $result;
		}

		public function po_add_cart_item_meta_data( $cart_item_data, $product_id, $variation_id, $quantity) {
			$meta_keys = array('po_template', 'po_font', 'po_text', 'po_price');

			foreach ($meta_keys as $key) {
				if (isset($_POST[$key]) && !empty($_POST[$key])) {
					$value = sanitize_text_field($_POST[$key]);

					// Normalize decimal comma to dot only for price
					if ($key === 'po_price') {
						$value = str_replace(',', '.', $value);
					}

					$cart_item_data[$key] = $value;
				}
			}

			return $cart_item_data;
		}

		public function po_get_cart_item_data($item_data, $cart_item) {
			$meta_keys = array(
				'po_font'  => esc_html__('Fonts', 'piy-online'),
				'po_text'  => esc_html__('Personalised satin ribbon', 'piy-online')
			);

			// Skip child items inside a bundle
//			if (wc_pb_is_bundled_cart_item($cart_item)) {
//				// error_log("⏩ Skipping Ribbon Text & Font for child item: " . $cart_item['data']->get_name());
//				return $item_data; // Do not add ribbon text/font to child products
//			}

			$temp_fonts = wp_cache_get('po_template_fonts');

			if (false === $temp_fonts) {
				$request 	= new PO_Request();
				$temp_fonts = $request->po_get_temp_fonts();
				wp_cache_set('po_template_fonts', $temp_fonts, '', HOUR_IN_SECONDS);
			}

			foreach ($meta_keys as $key => $name) {
				if (isset($cart_item[$key])) {
					switch ($key) {
						case 'po_font':
							$value = $temp_fonts['fonts'][$cart_item[$key]];
							break;

						case 'po_text':
							$value = $cart_item[$key];
							break;

						default:
							$value = $cart_item[$key];
							break;
					}

					if (!empty($value)) {
						$item_data[] = array(
							'name' 	=> esc_html__($name, 'piy-online'),
							'value' => esc_html__($value, 'piy-online')
						);
					}
				}
			}

			return $item_data;
		}


		public function po_add_order_item_meta($item_id, $item, $order_id) {
			$meta_keys = array(
				'po_font' => esc_html__('Fonts', 'piy-online'),
				'po_text' => esc_html__('Ribbon text', 'piy-online')
			);

			// ❌ Fix: Ensure we're only working with product items
			if (!$item instanceof WC_Order_Item_Product) {
				// error_log("⏩ Skipping non-product order item: " . get_class($item));
				return;
			}

			$product = $item->get_product();
			if (!$product) return;

			// ❌ Skip adding meta for child products inside a bundle
//			if (wc_pb_is_bundled_order_item($item)) {
//				// error_log("⏩ Skipping Ribbon Meta for child product in order: " . $product->get_name());
//				return;
//			}

			$temp_fonts = wp_cache_get('po_template_fonts');
			if (false === $temp_fonts) {
				$request 	= new PO_Request();
				$temp_fonts = $request->po_get_temp_fonts();
				wp_cache_set('po_template_fonts', $temp_fonts, '', HOUR_IN_SECONDS);
			}

			foreach ($meta_keys as $key => $name) {
				if (isset($item->legacy_values[$key]) && !empty($item->legacy_values[$key])) {
					$value = ($key == 'po_font' && isset($temp_fonts['fonts'][$item->legacy_values[$key]]))
						? $temp_fonts['fonts'][$item->legacy_values[$key]]
						: $item->legacy_values[$key];

					wc_add_order_item_meta($item_id, $name, $value, false);
					// error_log("✅ Added Ribbon Meta: $name | Value: $value");
				} else {
					error_log("❌ ERROR: Skipping meta key $name due to missing value.");
				}
			}
		}



		public function po_template_order_item_meta_data($item, $cart_item_key, $values, $order) {
			// Skip adding meta for child products inside a bundle
//			if (wc_pb_is_bundled_cart_item($values)) {
//				return; // Do not add ribbon text/font to child order items
//			}

			if (!isset($values['po_text'])) {
				return;
			}

			$temp_fonts = wp_cache_get('po_template_fonts');
			if (false === $temp_fonts) {
				$request 	= new PO_Request();
				$temp_fonts = $request->po_get_temp_fonts();
				wp_cache_set('po_template_fonts', $temp_fonts, '', HOUR_IN_SECONDS );
			}

			// Ensure template exists before applying
			$template = isset($temp_fonts['templates'][$values['po_template']]) ? $temp_fonts['templates'][$values['po_template']] : '';

			// Apply ribbon template and price only to the main product (not child items)
			if (!empty($template)) {
				$item->update_meta_data('_po_template', $template);
			}
			if (isset($values['po_price'])) {
				$item->update_meta_data('_po_price', wc_price($values['po_price']));
			}
		}

		public function po_wc_order_item_display_meta_key($display_key, $meta, $item) {
			// ❌ Prevent display of ribbon meta for child products inside a bundle
			if (wc_pb_is_bundled_order_item($item)) {
				// error_log("⏩ Hiding Ribbon Meta for child product in order display: " . $item->get_name());
				return $display_key; // Do not modify display for child items
			}

			if ($meta->key === '_po_template' && is_admin()) {
				$display_key = __("PIY Ribbons Template", "woocommerce");
			}

			if ($meta->key === '_po_price' && is_admin()) {
				$display_key = __("Ribbons Price", "woocommerce");
			}

			return $display_key;
		}

		public function po_set_ribbon_price($cart) {

			foreach ($cart->get_cart() as $item) {

				if (!isset($item['po_text'])) {
					continue;
				}

				$applied_data = $this->po_get_applied_from($item['product_id']);

				if (!isset($applied_data['display'])) {
					continue;
				}

				$ribbon_price = 0;

				switch ($applied_data['enable_by']) {
					case 'product':
					$ribbon_price = get_post_meta($item['product_id'], 'po_pro_price', true);
					break;

					case 'category':
					$ribbon_price = get_term_meta($applied_data['term_id'], 'po_cat_price', true);
					break;

					case 'tag':
					$ribbon_price = get_term_meta($applied_data['term_id'], 'po_tag_price', true);
					break;

					case 'general':
					$ribbon_price = get_option('po_gen_price');
					break;

					default:
					$ribbon_price = 0;
					break;
				}

				if (0 >= $ribbon_price) {
					continue;
				}

				$price = (float) $ribbon_price + $item['data']->get_price();

				$item['data']->set_price($price);
			}
		}

		public function po_add_cart_item_fee($cart) {
			$ribbon_price = 0;
			$ribbon_count = 0;
			$processed_bundles = []; // To track processed bundles

			foreach ($cart->get_cart() as $cart_item_key => $item) {
				$product = wc_get_product($item['product_id']);
				if (!$product) continue;

				// Skip child items inside a bundle
//				if (wc_pb_is_bundled_cart_item($item)) {
//					error_log("⏩ Skipping child item inside bundle: " . $product->get_name());
//					continue;
//				}

				// If it's a bundle, ensure we add the ribbon price only once
				if ($product->is_type('bundle')) {
					if (isset($processed_bundles[$product->get_id()])) {
						error_log("⏩ Skipping duplicate ribbon price for bundle: " . $product->get_name());
						continue; // Skip additional charges for the same bundle
					}

					$processed_bundles[$product->get_id()] = true; // Mark as processed
				}

				// Get ribbon price configuration
				$applied_data = $this->po_get_applied_from($item['product_id']);
				if (!isset($applied_data['display'])) {
					continue;
				}

				$price = 0;
				switch ($applied_data['enable_by']) {
					case 'product':
						$price = (float) get_post_meta($item['product_id'], 'po_pro_price', true);
						break;

					case 'category':
						$price = (float) get_term_meta($applied_data['term_id'], 'po_cat_price', true);
						break;

					case 'tag':
						$price = (float) get_term_meta($applied_data['term_id'], 'po_tag_price', true);
						break;

					case 'general':
						$price = (float) get_option('po_gen_price');
						break;

					default:
						$price = 0;
						break;
				}

				if ($price <= 0) {
					continue; // Skip if no ribbon price is set
				}

				// Apply ribbon price only once per bundle
				$ribbon_price += (float) $price * $item['quantity'];
				$ribbon_count += $item['quantity'];

				error_log("✅ Added ribbon fee ({$price}) for: " . $product->get_name());
			}

			if ($ribbon_price > 0) {
				$name = sprintf(esc_html__('%u personalised ribbon(s)', 'piy-online'), $ribbon_count);
				$amount = $ribbon_price;
				$taxable = true;
				$tax_class = '';

				error_log("📡 Adding total ribbon fee: " . $ribbon_price);
				$cart->add_fee($name, $amount, $taxable, $tax_class);
			}
		}


		public function po_display_ribbon_price_sum(){

			$ribbon_price = 0;
			$ribbon_count = 0;

			foreach (WC()->cart->get_cart() as $item) {

				if (!isset($item['po_text'])) {
					continue;
				}

				$applied_data = $this->po_get_applied_from($item['product_id']);

				if (!isset($applied_data['display'])) {
					continue;
				}
				$price = 0;
				switch ($applied_data['enable_by']) {
					case 'product':
					$price = (float)get_post_meta($item['product_id'], 'po_pro_price', true);
					break;

					case 'category':
					$price = (float)get_term_meta($applied_data['term_id'], 'po_cat_price', true);
					break;

					case 'tag':
					$price = (float)get_term_meta($applied_data['term_id'], 'po_tag_price', true);
					break;

					case 'general':
					$price = (float)get_option('po_gen_price');
					break;

					default:
					$price = 0;
					break;
				}

				if (0 >= $price) {
					continue;
				}

				$ribbon_price += (float) $price;
				$ribbon_count += 1;
			}

			if (0 >= $ribbon_price) {
				return;
			}

			ob_start();
			?>
			<tr class="ribbons-total">
				<th><?php echo esc_html__( sprintf( "Personalised ribbons:(%u)", $ribbon_count ), 'piy-online' ); ?></th>
				<td data-title="<?php esc_html_e( 'Personalised ribbons', 'piy-online' ); ?>"><?php echo wc_price($ribbon_price); ?></td>
			</tr>
			<?php
			echo ob_get_clean();
		}

		public function po_create_piy_order($order) {

			$needs_send = false;

			foreach ($order->get_items( 'line_item' ) as $id => $line_item) {

			if (
    !empty(wc_get_order_item_meta($id, esc_html__('Fonts'))) ||
    !empty(wc_get_order_item_meta($id, esc_html__('Ribbon text'))) ||

				 !empty(wc_get_order_item_meta($id, esc_html__('Lettertypen'))) ||
    !empty(wc_get_order_item_meta($id, 'PIY Ribbons Template'))
) {

					$needs_send = true;
					break;
				}
			}


			if (!$needs_send) {
				return;
			}

			$request = new PO_Request();
			$crons   = new PO_Crons();

			$payload = $crons->piy_get_order_payload($order->get_id());
//  								    error_log("$payload prepared for order ID $payload .");

			$response = $request->piy_send_payload($payload, '/order', 'PUT');
// 								    error_log("$response prepared for order ID $response .");

			$body = json_decode(wp_remote_retrieve_body($response));

			$code = wp_remote_retrieve_response_code($response);

			if (!is_wp_error($response) && 200 == $code ) {

				if ( $body->success ) {
					add_post_meta((int) $order->get_id(), 'piy_created', 'created');
					add_post_meta((int) $order->get_id(), 'piy_order_id', $body->order_id);
					$order->add_order_note(esc_html__('Order pushed to PIY Ribbons Dashboard successfully.'));
				}
			}
		}
	}

	new PO_Front();
}
