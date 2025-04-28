<?php
/*
 * Plugin Name:       PIY Online
 * Plugin URI:        https://piyribbons.com
 * Description:       Connect your store with the PIY Dashboard. Requires an active PIY Online subscription.
 * Version:           1.0.2
 * Author:            PIY RIBBONS
 * Developed By:      PIY RIBBONS
 * Author URI:        https://piyribbons.com
 * Support:           https://piyribbons.com
 * Domain Path:       /languages
 * Text Domain:       piy-online
 * 
 * WC requires at least: 5.0.0
 * WC tested up to: 7.*.*
 * 
 */


if (!defined('ABSPATH')) {
	exit;
}

if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) ) {

	function po_admin_error_notice() {

		$po_allowed_tags = array(
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

		$po_plugin_check = '<div id="message" class="error">
            <p><strong>Could not activate PIY Online.</strong> The <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce plugin</a> must be active for this plugin to work. Please install &amp; activate WooCommerce »</p></div>';
		echo wp_kses(__($po_plugin_check, 'piy-online'), $po_allowed_tags);

	}

	add_action('admin_notices', 'po_admin_error_notice');
}

if (!class_exists('PO_Main')) {
	
	class PO_Main {

		public function __construct() {

			//Define Global Constants
			$this->po_global_constents_vars();
			//load Text Domain
			add_action('init', array( $this, 'po_load_text_domain' ));
			
			include_once PO_PLUGIN_DIR . 'includes/po-ajax.php';
			include_once PO_PLUGIN_DIR . 'includes/po-request.php';
			include_once PO_PLUGIN_DIR . 'includes/po-crons.php';

			if (is_admin() ) {
				//Include Admin Files
				include_once PO_PLUGIN_DIR . '/includes/admin/po-admin.php';
			}
			//Include Front File
			include_once PO_PLUGIN_DIR . '/includes/front/po-front.php';

		}

		//Define GLobal Constant function
		public function po_global_constents_vars() {

			if (! defined('PO_PLUGIN_DIR') ) {
				define('PO_PLUGIN_DIR', plugin_dir_path(__FILE__));
			}

			if (!defined('PO_URL') ) {
				define('PO_URL', plugin_dir_url(__FILE__));
			}

			if (!defined('PO_BASENAME') ) {
				define('PO_BASENAME', plugin_basename(__FILE__));
			}			
		}

		//load text domain
		public function po_load_text_domain() {
			if (function_exists('load_plugin_textdomain') ) {
				load_plugin_textdomain('piy-online', false, dirname(plugin_basename(__FILE__)) . '/languages/');
			}
		}
	}
	new PO_Main();
}
