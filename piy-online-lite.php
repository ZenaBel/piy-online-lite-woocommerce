<?php
/*
 * Plugin Name:       PIY Online Lite
 * Plugin URI:        https://piyribbons.com
 * Description:       PIY Online Lite is a WooCommerce plugin that allows you to add emojis and ribbons to your cart and checkout pages.
 * Version:           1.0.2
 * Author:            Codi
 * Developed By:      Codi
 * Author URI:        https://piyribbons.com
 * Support:           https://piyribbons.com
 * Domain Path:       /languages
 * Text Domain:       piy-online-lite
 *
 * WC requires at least: 5.0.0
 * WC tested up to: 7.*.*
 *
 */


if (!defined('ABSPATH')) {
	exit;
}

if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) ) {

	function poe_admin_error_notice() {

		$poe_allowed_tags = array(
			'a' => array(
				'class' => array(),
				'href' => array(),
				'rel' => array(),
				'title' => array(),
			),
			'div' => array(
				'class' => array(),
				'title' => array(),
				'style' => array(),
			),
			'p' => array(
				'class' => array(),
			),
			'strong' => array(),
		);

		// Deactivate the plugin
		deactivate_plugins(__FILE__);

		$poe_plugin_check = '<div id="message" class="error">
            <p><strong>Could not activate PIY Online Lite.</strong> The <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce plugin</a> must be active for this plugin to work. Please install &amp; activate WooCommerce »</p></div>';
		echo wp_kses(__($poe_plugin_check, 'piy-online'), $poe_allowed_tags);

	}

	add_action('admin_notices', 'poe_admin_error_notice');
}

if (!class_exists('POE_Main')) {

	class POE_Main {

		public function __construct() {

			//Define Global Constants
			$this->poe_global_constents_vars();
			//load Text Domain
			add_action('init', array( $this, 'poe_load_text_domain'));

			include_once POE_PLUGIN_DIR . 'includes/poe-ajax.php';

			if (is_admin() ) {
				//Include Admin Files
				include_once POE_PLUGIN_DIR . '/includes/admin/poe-admin.php';
			}
			//Include Front File
			include_once POE_PLUGIN_DIR . '/includes/front/poe-front.php';

		}

		//Define GLobal Constant function
		public function poe_global_constents_vars() {

			if (! defined('PO_PLUGIN_DIR') ) {
				define('POE_PLUGIN_DIR', plugin_dir_path(__FILE__));
			}

			if (!defined('PO_URL') ) {
				define('POE_URL', plugin_dir_url(__FILE__));
			}

			if (!defined('PO_BASENAME') ) {
				define('POE_BASENAME', plugin_basename(__FILE__));
			}
		}

		//load text domain
		public function poe_load_text_domain() {
			if (function_exists('load_plugin_textdomain') ) {
				load_plugin_textdomain('piy-online-lite', false, dirname(plugin_basename(__FILE__)) . '/languages/');
			}
		}
	}
	new POE_Main();
}
