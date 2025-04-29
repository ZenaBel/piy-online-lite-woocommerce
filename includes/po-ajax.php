<?php

if (!defined('ABSPATH')) {
	exit;
}

class PO_Ajax {


	public function __construct() {

		add_action( 'wp_ajax_pogetproducts', array($this, 'po_search_products'), 20 );

		add_action('wp_ajax_get_fields', array($this, 'po_get_fields'), 20);

		add_action('wp_ajax_nopriv_get_fields', array($this, 'po_get_fields'), 20);
	}


	public function po_search_products() {

		if (isset($_POST['nonce']) && '' != $_POST['nonce']) {
			$nonce = sanitize_text_field( $_POST['nonce'] );
		} else {
			$nonce = 0;
		}

		if (isset($_POST['q']) && '' != $_POST['q']) {
			if ( ! wp_verify_nonce( $nonce, 'po-ajax-nonce' ) ) {
				die ( 'Failed nonce verification!');
			}
			$pro = sanitize_text_field( $_POST['q'] );
		} else {
			$pro = '';
		}
		$data_array = array();
		$args       = array(
			'post_type' => 'product',
			'post_status' => 'publish',
			'numberposts' => -1,
			's'	=>  $pro
		);
		$pros       = get_posts($args);

		if ( !empty($pros)) {
			foreach ($pros as $proo) {
				$title        = ( mb_strlen( $proo->post_title ) > 50 ) ? mb_substr( $proo->post_title, 0, 49 ) . '...' : $proo->post_title;
				$data_array[] = array( $proo->ID, $title ); // array( Post ID, Post Title )
			}
		}
		echo wp_json_encode( $data_array );
		die();
	}

	public function po_get_fields() {

		$nonce        = 0;
		$product_id   = 0;
		$term_id      = 0;
		$enable_by    = '';
		$emoji_picker = '';


		if (isset($_POST['nonce']) && '' != $_POST['nonce']) {
			$nonce = sanitize_text_field( $_POST['nonce'] );
		}

		if (isset($_POST['product_id']) && '' != $_POST['product_id']) {
			if ( ! wp_verify_nonce( $nonce, 'po-ajax-nonce' ) ) {
				die ( 'Failed nonce verification!');
			}
			$product_id = sanitize_text_field( $_POST['product_id'] );
			$enable_by 	= isset($_POST['enable_by']) ? sanitize_text_field( $_POST['enable_by'] ) : '';
			$term_id 	= isset($_POST['term_id']) ? sanitize_text_field( $_POST['term_id'] ) : '';


			switch ($enable_by) {
				case 'product':
					$emoji_picker = get_post_meta($product_id, 'po_pro_enable_emoji_picker', true);
					$ribbon_price = get_post_meta($product_id, 'po_pro_price', true);
                    $character_limit = get_post_meta($product_id, 'po_pro_character_limit', true);
					break;

				case 'category':
					$emoji_picker = get_term_meta($term_id, 'po_cat_emojis', true);
					$ribbon_price = get_term_meta($term_id, 'po_cat_price', true);
                    $character_limit = get_term_meta($term_id, 'po_cat_character_limit', true);
					break;

				case 'tag':
					$emoji_picker = get_term_meta($term_id, 'po_tag_emojis', true);
					$ribbon_price = get_term_meta($term_id, 'po_tag_price', true);
                    $character_limit = get_term_meta($term_id, 'po_tag_character_limit', true);
					break;


				default:
					$emoji_picker = get_option('po_gen_emojis', true);
					$ribbon_price = get_option('po_gen_price');
                    $character_limit = get_option('po_gen_character_limit');
					break;
			}

			ob_start();

			?>
			<input type="hidden" name="po_price" value="<?php echo esc_attr($ribbon_price); ?>">
			<?php
			?>
			<section>
				<input type="text" name="po_text" id="po_text" class="emoji-field" data-emoji-picker="<?php echo esc_attr($emoji_picker); ?>" autocomplete="off" <?php if ($character_limit) : ?>maxlength="<?php echo esc_attr($character_limit); ?>"<?php endif; ?>>
				<?php if ('yes' == $emoji_picker) : ?>
					<div id="emoji-picker"></div>
				<?php endif; ?>
			</section>
			<?php

			$html = ob_get_clean();
			wp_send_json_success($html);
			die();
		}
	}
}
new PO_Ajax();
