<?php

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('PO_Admin')) {
	class PO_Admin {

		public function __construct() {

			// Add Custom tab icon
			add_action( 'admin_head', array($this,'po_tab_icon'), 20 );

			add_action('admin_enqueue_scripts', array($this,'po_admin_scripts'), 20);

			/**
			 * 
			 * PIY Online for PRODUCTS
			 * 
			 */

			// Add PIY Online Tab for product edit page
			add_filter( 'woocommerce_product_data_tabs', array($this,'po_data_tab'), 20 );

			// Create fields for PIY Online tab
			add_filter( 'woocommerce_product_data_panels', array($this,'po_data_tab_fields' ), 20 );

			// Save PIY Online Product Data
			add_action( 'woocommerce_process_product_meta', array($this, 'po_save_data_tabs' ), 20  );


			/**
			 * 
			 * PIY Online for CATEGORIES
			 * 
			 */

			// Add PIY Online for CATEGORIES
			add_action('product_cat_add_form_fields', array($this,'po_new_category_fields') , 20 );

			add_action('product_cat_edit_form_fields', array($this,'po_edit_category'), 20 );

			add_action('edited_product_cat', array($this, 'po_save_category_fields'), 20, 1 );
			
			add_action('create_product_cat', array($this, 'po_save_category_fields'), 20, 1);

			/**
			 * 
			 * PIY Online for TAGS
			 * 
			 */

			// Add PIY Online for TAGS
			add_action('product_tag_add_form_fields', array($this,'po_new_category_fields') , 20 );

			add_action('product_tag_edit_form_fields', array($this,'po_edit_category'), 20 );

			add_action('edited_product_tag', array($this, 'po_save_category_fields'), 20, 1 );
			
			add_action('create_product_tag', array($this, 'po_save_category_fields'), 20, 1);

			/**
			 * 
			 * PIY Online for GENERAL
			 * 
			 */

			// Add PIY Online for Woo Submenu
			add_action('admin_menu', array($this,'po_add_submenu'), 20 );

			add_action('admin_init', array($this, 'po_submenu_tabs'), 20 );

			if (isset($_POST['po_save_configs'])) {
				if (isset($_POST['po_gen_nonce'])) {
					include_once ABSPATH . 'wp-includes/pluggable.php';

					if ( ! wp_verify_nonce( sanitize_text_field($_POST['po_gen_nonce']), 'po-gen-nonce' ) ) {	
						die( 'PIY Online General Verification Failed' );
					}

					$request  = new PO_Request();
					$response = $request->po_get_fonts();
					$code     = wp_remote_retrieve_response_code($response);

					update_option('po_api_key_authorization', $code);	
				}
			}

			/**
			 * 
			 * PIY Online for Push Orders Manually
			 * 
			 */
			add_filter('bulk_actions-edit-shop_order', array($this, 'po_add_bulk_order'), 20);
			add_action('handle_bulk_actions-edit-shop_order', array($this, 'po_push_orders'), 20, 3);
		}

		public function po_tab_icon() {
			?>
			<style>
				#woocommerce-product-data ul.wc-tabs li.po-data-tab_options a:before { font-family: WooCommerce; content: '\e004'; }
			</style>
			<?php 
		}

		public function po_admin_scripts() {

			$in_footer = true;
			wp_enqueue_style('po-admin', plugins_url('../../assets/css/po_admin.css', __FILE__), false, '1.0.0' );

			wp_enqueue_script('po-admin', plugins_url('../../assets/js/po_admin.js', __FILE__), array('jquery'), '1.0.0', $in_footer );

			wp_enqueue_style('select2', plugins_url('../../assets/css/select2.min.css', __FILE__), false, '1.0.0' );

			wp_enqueue_script('select2', plugins_url('../../assets/js/select2.min.js', __FILE__), array('jquery'), '1.0.0', $in_footer );

			$info = array(
				'admin_url'  => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('po-ajax-nonce'),
			);
			wp_localize_script( 'po-admin', 'info', $info );		 
		}


		/**
		 * 
		 * PRODUCTS PIY ONLINE
		 * 
		 */


		//PRODUCT DATA TAB
		public function po_data_tab( $tabs ) {

			$tabs['po-data-tab'] = array(
				'label' => esc_html__( 'PIY Online', 'piy-online' ),
				'target' => 'po_tab',
				'priority' => 80,
			);
			return $tabs;
		}

		public function po_data_tab_fields( $post_id ) {
			
			$temp_fonts = wp_cache_get('po_template_fonts');
			
			if (false === $temp_fonts) {
				
				$request 	= new PO_Request();
				$temp_fonts = $request->po_get_temp_fonts();

				wp_cache_set( 'po_template_fonts', $temp_fonts, '', HOUR_IN_SECONDS );
			}
			
			wp_nonce_field('po-pro-nonce', 'po_pro_nonce');
			?>
			
			<div id="po_tab" class="panel wc-metaboxes-wrapper woocommerce_options_panel">
				<div class = 'options_group' >
					<h4 class="data-fields-margin-left"> <?php esc_html_e( 'PIY Online', 'piy-online' ); ?> </h4>
					<?php
					woocommerce_wp_checkbox( 
						array( 
							'id'            => 'po_pro_enable',
							'label'         => esc_html__( 'Activate PIY Online', 'piy-online' ),
							'description'   => esc_html__( 'Whether or not Activate PIY Online for this product to override general settings.', 'piy-online' ),
							'default'  		=> '0',
							'desc_tip'    	=> true,
							'class'			=> 'checkbox',
						) 
					);

					woocommerce_wp_checkbox( 
						array( 
							'id'            => 'po_pro_enable_emoji_picker',
							'label'         => esc_html__( 'Enable emoji picker', 'piy-online' ),
							'description'   => esc_html__( 'Whether or not enable customers to add emojis.', 'piy-online' ),
							'default'  		=> '0',
							'desc_tip'    	=> true,
							'class'			=> 'checkbox',
						)  
					);

					woocommerce_wp_select(
						array( 
							'id'            => 'po_pro_template',
							'label'         => esc_html__( 'Default template', 'piy-online' ),
							'description'   => esc_html__( 'Select your default ribbon template. If you could not see any template please refresh the page.', 'piy-online' ),
							'desc_tip'    	=> true,
							'class'			=> 'select short',
							'options' 		=> $temp_fonts['templates']
						) 
					);

					woocommerce_wp_select(
						array( 
							'id'            => 'po_pro_font',
							'label'         => esc_html__( 'Default Ribbon Fonts', 'piy-online' ),
							'description'   => esc_html__( 'Select your default ribbon font style. If you could not see any font please refresh the page.', 'piy-online' ),
							'desc_tip'    	=> true,
							'class'			=> 'select short',
							'options' 		=> $temp_fonts['fonts']
						) 
					);

					woocommerce_wp_text_input( 
						array( 
							'id'            => 'po_pro_price',
							'label'         => esc_html__( 'Ribbon price', 'piy-online' ),
							'description'   => esc_html__( 'Leave this field empty to offer personalised ribbons for free.', 'piy-online' ),
							'default'  		=> '',
							'desc_tip'    	=> true,
							'class'			=> 'price short',
							'data_type' 	=> 'price'
						) 
					);
					?>
				</div>
			</div>
			<?php
		}

		public function po_save_data_tabs( $post_id ) {

			if (isset($_POST['po_pro_nonce'])) {
				if ( !wp_verify_nonce(sanitize_text_field($_POST['po_pro_nonce']), 'po-pro-nonce') ) {
					die('PIY-Online Product Nonce Verification Failed');
				}
			}
		
			$po_pro_enable = isset($_POST['po_pro_enable']) ? 'yes' : 'no';
			update_post_meta($post_id, 'po_pro_enable', $po_pro_enable);
		
			$po_pro_enable_emoji_picker = isset($_POST['po_pro_enable_emoji_picker']) ? 'yes' : 'no';
			update_post_meta($post_id, 'po_pro_enable_emoji_picker', $po_pro_enable_emoji_picker);
		
			$po_pro_template = isset($_POST['po_pro_template']) ? sanitize_text_field($_POST['po_pro_template']) : '';
			update_post_meta($post_id, 'po_pro_template', $po_pro_template);
		
			$po_pro_font = isset($_POST['po_pro_font']) ? sanitize_text_field($_POST['po_pro_font']) : '';
			update_post_meta($post_id, 'po_pro_font', $po_pro_font);
		
			$po_pro_price_raw = isset($_POST['po_pro_price']) ? sanitize_text_field($_POST['po_pro_price']) : '';
			$po_pro_price = str_replace(',', '.', $po_pro_price_raw);
			if (!is_numeric($po_pro_price)) {
				$po_pro_price = '';
			}
			update_post_meta($post_id, 'po_pro_price', $po_pro_price);
		}		


		/**
		 * 
		 * CATEGORIES/TAGS PIY ONLINE
		 * 
		 */


		// Create New Category
		public function po_new_category_fields() { 

			$temp_fonts = wp_cache_get('po_template_fonts');
			
			if (false === $temp_fonts) {
				
				$request 	= new PO_Request();
				$temp_fonts = $request->po_get_temp_fonts();

				wp_cache_set( 'po_template_fonts', $temp_fonts, '', HOUR_IN_SECONDS );
			}

			wp_nonce_field('po-cat-nonce', 'po_cat_nonce');

			?>
			<h3><?php esc_html_e('PIY Online', 'piy-online'); ?></h3>
			<div class="form-field term-po-enable-wrap">
				<label for="po_cat_enable"><?php esc_html_e('Activate PIY Online', 'piy-online'); ?></label>
				<input type="checkbox" name="po_cat_enable" id="po_cat_enable" value="yes">
				<p class="po-enable-description"><?php esc_html_e('Activate PIY Online for Category', 'piy-online'); ?></p>
			</div>

			<div class="form-field term-po-emojis-wrap">
				<label for="po_cat_emojis"><?php esc_html_e('Allow Emojis Picker', 'piy-online'); ?></label>
				<input type="checkbox" name="po_cat_emojis" id="po_cat_emojis" value="yes">
				<p class="po-emojis-description"><?php esc_html_e('Allow emojis picker', 'piy-online'); ?></p>
			</div>

			<div class="form-field term-po-templates-wrap">
				<label for="po_cat_template"><?php esc_html_e('Default template', 'piy-online'); ?></label>
				<select id="po_cat_template" name="po_cat_template">
					<?php
					foreach ($temp_fonts['templates'] as $id => $template) {
						?>
						<option value="<?php echo esc_attr($id); ?>"><?php echo esc_html__($template, 'piy-online'); ?></option>
						<?php
					}
					?>
				</select>
				<p class="po-templates-description"><?php esc_html_e('Select default template for Category', 'piy-online'); ?></p>
			</div>

			<div class="form-field term-po-fonts-wrap">
				<label for="po_cat_font"><?php esc_html_e('Default Ribbon Fonts', 'piy-online'); ?></label>
				<select id="po_cat_font" name="po_cat_font">
					<?php
					foreach ($temp_fonts['fonts'] as $id => $font) {
						?>
						<option value="<?php echo esc_attr($id); ?>"><?php echo esc_html__($font, 'piy-online'); ?></option>
						<?php
					}
					?>
				</select>
				<p class="po-fonts-description"><?php esc_html_e('Select default fonts for Category', 'piy-online'); ?></p>
			</div>

			<div class="form-field term-po-price-wrap">
				<label for="po_cat_price"><?php esc_html_e('Ribbon price', 'piy-online'); ?></label>
				<input type="text" class="price short wc_input_price" name="po_cat_price" id="po_cat_price">
				<p class="po-price-description"><?php esc_html_e('Leave this field empty to offer personalised ribbons for free.', 'piy-online'); ?></p>
			</div>
			
			<?php
		}

		//Category Edit
		public function po_edit_category( $term) { 
			
			$temp_fonts = wp_cache_get('po_template_fonts');
			
			if (false === $temp_fonts) {
				
				$request 	= new PO_Request();
				$temp_fonts = $request->po_get_temp_fonts();

				wp_cache_set( 'po_template_fonts', $temp_fonts, '', HOUR_IN_SECONDS );
			}

			$term_id = $term->term_id;

			$po_cat_enable = get_term_meta($term_id, 'po_cat_enable', true);

			$po_cat_emojis = get_term_meta($term_id, 'po_cat_emojis', true);

			$po_cat_template = get_term_meta($term_id, 'po_cat_template', true);

			$po_cat_font = get_term_meta($term_id, 'po_cat_font', true);

			$po_cat_price = get_term_meta($term_id, 'po_cat_price', true);


			wp_nonce_field('po-cat-nonce', 'po_cat_nonce');

			?>
			<tr class="form-field">
				<th colspan=2 scope="row" valign="top">
					<h3><?php esc_html_e('PIY Online', 'piy-online'); ?></h3>
				</th>
			</tr>

			<!-- For PIY Online -->
			<tr class="form-field term-po-enable-wrap">
				<th scope="row" valign="top">
					<label for="po_cat_enable"><?php esc_html_e('Activate PIY Online', 'piy-online'); ?></label>
				</th>
				<td>
					<input type="checkbox" name="po_cat_enable" id="po_cat_enable" value='yes' <?php echo checked('yes', $po_cat_enable); ?> >
					<p class="description" id="po-enable-description"><?php esc_html_e('Activate PIY Online', 'piy-online'); ?></p>
				</td>
			</tr>

			<tr class="form-field term-po-emojis-wrap">
				<th scope="row" valign="top">
					<label for="po_cat_emojis"><?php esc_html_e('Allow Emojis Picker', 'piy-online'); ?></label>
				</th>
				<td>
					<input type="checkbox" name="po_cat_emojis" id="po_cat_emojis" value='yes' <?php echo checked('yes', $po_cat_emojis); ?> >
					<p class="description"><?php esc_html_e('Allow/Disallow Emojis for ribbon text.', 'piy-online'); ?></p>
				</td>
			</tr>

			<tr class="form-field term-po-templates-wrap">
				<th scope="row" valign="top">
					<label for="po_cat_template"><?php esc_html_e('Default template', 'piy-online'); ?></label>
				</th>
				<td>
					<select id="po_cat_template" name="po_cat_template">
						<?php
						
						foreach ($temp_fonts['templates'] as $id => $template) {
							?>
							<option value="<?php echo esc_attr($id); ?>" <?php echo selected($id, $po_cat_template); ?>>
								<?php echo esc_html__($template); ?>
							</option>
							<?php
						}
						?>
					</select>
					<p class="description"><?php esc_html_e('Select default ribbon template for category.', 'piy-online'); ?></p>
				</td>
			</tr>

			<tr class="form-field term-po-fonts-wrap">
				<th scope="row" valign="top">
					<label for="po_cat_font"><?php esc_html_e('Default font', 'piy-online'); ?></label>
				</th>
				<td>
					<select id="po_cat_font" name="po_cat_font">
						<?php
						
						foreach ($temp_fonts['fonts'] as $id => $font) {
							?>
							<option value="<?php echo esc_attr($id); ?>" <?php echo selected($id, $po_cat_font); ?>>
								<?php echo esc_html__($font); ?>
							</option>
							<?php
						}
						?>
					</select>
					<p class="description"><?php esc_html_e('Select default ribbon font for category', 'piy-online'); ?></p>
				</td>
			</tr>

			<tr class="form-field term-po-price-wrap">
				<th scope="row" valign="top">
					<label for="po_cat_price"><?php esc_html_e('Ribbon price', 'piy-online'); ?></label>
				</th>
				<td>
					<input type="text" class="price short wc_input_price" name="po_cat_price" id="po_cat_price" value="<?php echo esc_attr($po_cat_price); ?>">
					<p class="description"><?php esc_html_e('Leave this field empty to offer personalised ribbons for free.', 'piy-online'); ?></p>
				</td>
			</tr>
			<?php
		}

		// Save Category PIY Online
		public function po_save_category_fields( $term_id) {
			
			if (isset($_POST['po_cat_nonce'])) {
				if ( ! wp_verify_nonce(sanitize_text_field($_POST['po_cat_nonce']), 'po-cat-nonce' ) ) {
					die( 'PIY-Online Category Verification Failed' );
				}
			}

			$po_cat_enable   = filter_input(INPUT_POST, 'po_cat_enable');
			$po_cat_emojis   = filter_input(INPUT_POST, 'po_cat_emojis');
			$po_cat_template = filter_input(INPUT_POST, 'po_cat_template');
			$po_cat_font     = filter_input(INPUT_POST, 'po_cat_font');
			$po_cat_price    = filter_input(INPUT_POST, 'po_cat_price');


			update_term_meta($term_id, 'po_cat_enable', $po_cat_enable);
			update_term_meta($term_id, 'po_cat_emojis', $po_cat_emojis);
			update_term_meta($term_id, 'po_cat_template', $po_cat_template);
			update_term_meta($term_id, 'po_cat_font', $po_cat_font);
			update_term_meta($term_id, 'po_cat_price', $po_cat_price);

		}


		/**
		 * 
		 * GENERAL PIY ONLINE
		 * 
		 */


		// ADD PIY Online SUBMENU
		public function po_add_submenu() {

			add_submenu_page(
				'woocommerce', // parent name
				esc_html__( 'PIY Online', 'piy-online' ), // Page Title
				esc_html__( 'PIY Online', 'piy-online' ), // Menu Title
				'manage_options', // capabilities
				'piy-online-settings', // page slug
				array($this,'po_submenu_content') // callback
			);
		}

		public function po_submenu_content() {
			
			global $active_tab;
			if ( isset( $_GET[ 'tab' ] )) {
				$active_tab = sanitize_text_field( $_GET[ 'tab' ] );
			} else {
				$active_tab = 'api_configs';
			}
			
			?>
			<div class="wrap piyonline">

			<div class="info-box"><strong>PIY Online</strong> is ⚡️ powered by <strong><a href="https://piyribbons.com" target="_blank">PIY RIBBONS</a></strong></div>
				
				<h2> <?php echo esc_html__( 'PIY Online', 'piy-online' ); ?></h2>
				<?php settings_errors(); ?>
				<h2 class="nav-tab-wrapper">

					<a href="?page=piy-online-settings&tab=api_configs" class="nav-tab <?php echo esc_attr($active_tab) == 'api_configs' ? 'nav-tab-active' : ''; ?>" > <?php esc_html_e( 'API settings', 'piy-online' ); ?> </a>
					<?php if ('yes' == get_option('po_gen_enable')) { 
						// 200 == get_option('po_api_key_authorization') &&
						?>
					<a href="?page=piy-online-settings&tab=po_gen_config" class="nav-tab <?php echo esc_attr($active_tab) == 'po_gen_config' ? 'nav-tab-active' : ''; ?> " > <?php esc_html_e( 'Configure PIY Online', 'piy-online' ); ?> </a>

					<?php } ?>

				</h2>

				<form method="post" action="options.php" id="save_options_form">
					<?php
					wp_nonce_field('po-gen-nonce', 'po_gen_nonce');
					
					if ('api_configs' == $active_tab) {
						settings_fields( 'api_fields' );		            
						do_settings_sections( 'api_configuration_page' );
					}

					if ('po_gen_config' == $active_tab) {
						settings_fields( 'gen_config' );
						do_settings_sections( 'gen_config_page' );
					}			 

					submit_button(esc_html__('Save configuration', 'piy-online'), 'primary', 'po_save_configs');
					?>
					
				</form>
				
			</div>
			<?php		

		}


		/**
		 * 
		 * General Configuration Tabs
		 * 
		 */

		public function po_submenu_tabs() {

			/**
			 * 
			 * API Configuration
			 * 
			 */

			add_settings_section(  
				'api_configs_sec', // ID used to identify this section and with which to register options  
				'', // Title to be displayed on the administration page  
				array($this, 'po_api_configs_sec_cb'), // Callback used to render the description of the section  
				'api_configuration_page' // Page on which to add this section of options  
			);

			add_settings_field (   
				'po_gen_enable', // ID used to identify the field throughout the theme  
				esc_html__('Activate PIY Online', 'piy-online'), // The label to the left of the option interface element  
				array($this, 'po_gen_enable_callback'),   // The name of the function responsible for rendering the option interface  
				'api_configuration_page',   // The page on which this option will be displayed  
				'api_configs_sec'        // The name of the section to which this field belongs  

			);  
			register_setting(  
				'api_fields',  
				'po_gen_enable'  
			);

			add_settings_field (   
				'po_api_key', // ID used to identify the field throughout the theme  
				esc_html__('API Key', 'piy-online'), // The label to the left of the option interface element  
				array($this, 'po_api_key_callback'), //The name of the function responsible for rendering the option interface  
				'api_configuration_page', // The page on which this option will be displayed  
				'api_configs_sec', // The name of the section to which this field belongs
				array(esc_html__('Generate your API key through the PIY Dashboard use the API key provided by us.', 'piy-online'))

			);  
			register_setting(  
				'api_fields',  
				'po_api_key'  
			);
			

			add_settings_field (   
				'po_order_status', // ID used to identify the field throughout the theme  
				esc_html__('Activate auto sync for order status', 'piy-online'), // The label to the left of the option interface element  
				array($this, 'po_send_orders_callback'),   // The name of the function responsible for rendering the option interface  
				'api_configuration_page', // The page on which this option will be displayed  
				'api_configs_sec', // The name of the section to which this field belongs
				array(esc_html__('Select the required order status for orders to be pushed to the PIY Dashboard. If you don\'t select any, orders will not be pushed to the dashboard automatically.', 'piy-online'))
			);  
			register_setting(  
				'api_fields',  
				'po_order_status'  
			);

			add_settings_field (   
				'po_cron_time', // ID used to identify the field throughout the theme  
				esc_html__('Sync frequency', 'piy-online'), // The label to the left of the option interface element  
				array($this, 'po_cron_time_callback'), //The name of the function responsible for rendering the option interface  
				'api_configuration_page', // The page on which this option will be displayed  
				'api_configs_sec', // The name of the section to which this field belongs
				array(esc_html__('Set the sync frequency. You need to have selected an order status for the sync to be executed. Default: 300s (5 minutes)', 'piy-online'))

			);  
			register_setting(  
				'api_fields',  
				'po_cron_time'  
			);

			/**
			 * 
			 * General Configuration
			 * 
			 */

			add_settings_section(  
				'gen_config_sec', // ID used to identify this section and with which to register options  
				'',  // Title to be displayed on the administration page  
				array($this, 'gen_config_sec_cb'), // Callback used to render the description of the section  
				'gen_config_page'      // Page on which to add this section of options  
			);

			add_settings_field (   
				'po_gen_emojis', // ID used to identify the field throughout the theme  
				esc_html__('Enable emoji picker', 'piy-online'), // The label to the left of the option interface element  
				array($this, 'po_geb_emojis_cb'),   // The name of the function responsible for rendering the option interface  
				'gen_config_page', // The page on which this option will be displayed  
				'gen_config_sec', // The name of the section to which this field belongs
				array(
					esc_html__('Allow customers to select emojis in the ribbon text field.', 'piy-online'),
				) 				  
			);
			register_setting(  
				'gen_config',  
				'po_gen_emojis'  
			);


			add_settings_field (   
				'po_gen_template', // ID used to identify the field throughout the theme  
				esc_html__('Default template', 'piy-online'), // The label to the left of the option interface element  
				array($this, 'po_gen_template_cb'),   // The name of the function responsible for rendering the option interface  
				'gen_config_page',                    // The page on which this option will be displayed  
				'gen_config_sec'         // The name of the section to which this field belongs  				  
			);  
			register_setting(  
				'gen_config',  
				'po_gen_template'  
			);

			add_settings_field (   
				'po_gen_font', // ID used to identify the field throughout the theme  
				esc_html__('Default font', 'piy-online'), // The label to the left of the option interface element  
				array($this, 'po_gen_font_cb'),   // The name of the function responsible for rendering the option interface  
				'gen_config_page',                    // The page on which this option will be displayed  
				'gen_config_sec'         // The name of the section to which this field belongs  				  
			);  
			register_setting(  
				'gen_config',  
				'po_gen_font'  
			);

			add_settings_field (   
				'po_gen_products', // ID used to identify the field throughout the theme
				esc_html__('Activate PIY Online for specific products', 'piy-online'), // The label to the left of the option interface element  
				array($this, 'po_gen_products_cb'), // The name of the function responsible for rendering the option interface  
				'gen_config_page', // The page on which this option will be displayed  
				'gen_config_sec' // The name of the section to which this field belongs  				  
			);  
			register_setting(  
				'gen_config',  
				'po_gen_products'  
			);

			add_settings_field (   
				'po_gen_categories', // ID used to identify the field throughout the theme  
				esc_html__('Activate PIY Online for specific categories', 'piy-online'), // The label to the left of the option interface element  
				array($this, 'po_gen_categories_callback'),   // The name of the function responsible for rendering the option interface  
				'gen_config_page', // The page on which this option will be displayed  
				'gen_config_sec' // The name of the section to which this field belongs  				  
			);  
			register_setting(  
				'gen_config',  
				'po_gen_categories'  
			);

			add_settings_field (   
				'po_gen_tags', // ID used to identify the field throughout the theme  
				esc_html__('Activate PIY Online for specific tags', 'piy-online'), // The label to the left of the option interface element  
				array($this, 'po_gen_tags_callback'),   // The name of the function responsible for rendering the option interface  
				'gen_config_page', // The page on which this option will be displayed  
				'gen_config_sec' // The name of the section to which this field belongs  				  
			);  
			register_setting(  
				'gen_config',  
				'po_gen_tags'  
			);

			add_settings_field (   
				'po_gen_attributes', // ID used to identify the field throughout the theme  
				esc_html__('Activate PIY Online for specific attributes', 'piy-online'), // The label to the left of the option interface element  
				array($this, 'po_gen_attributes_callback'),   // The name of the function responsible for rendering the option interface  
				'gen_config_page', // The page on which this option will be displayed  
				'gen_config_sec' // The name of the section to which this field belongs  				  
			);  
			register_setting(  
				'gen_config',  
				'po_gen_attributes'  
			);

			add_settings_field (   
				'po_gen_price', // ID used to identify the field throughout the theme  
				esc_html__('Ribbon price', 'piy-online'), // The label to the left of the option interface element  
				array($this, 'po_gen_price_callback'),   // The name of the function responsible for rendering the option interface  
				'gen_config_page', // The page on which this option will be displayed  
				'gen_config_sec' // The name of the section to which this field belongs  				  
			);  
			register_setting(  
				'gen_config',  
				'po_gen_price'  
			);

			add_settings_field (   
				'po_gen_text', // ID used to identify the field throughout the theme  
				esc_html__('Custom label', 'piy-online'), // The label to the left of the option interface element  
				array($this, 'po_gen_text_callback'),   // The name of the function responsible for rendering the option interface  
				'gen_config_page', // The page on which this option will be displayed  
				'gen_config_sec' // The name of the section to which this field belongs  				  
			);  
			register_setting(  
				'gen_config',  
				'po_gen_text'  
			);
		}


		/**
		 * 
		 * API Configuration
		 * 
		 */


		public function po_api_configs_sec_cb() {
			?>
			<h2><?php esc_html_e( esc_html__('API settings', 'piy-online') ); ?></h2>
			<?php
		}

		public function po_gen_enable_callback() {
			?>
			<input type="checkbox" name="po_gen_enable" id="po_gen_enable" value="yes" <?php checked('yes', esc_attr( get_option('po_gen_enable'))); ?> >
			<p class="description po_gen_enable"><?php esc_html_e('(De)activate PIY Online. Requires a valid API key.', 'piy-online'); ?> </p>
			<?php
		}

		public function po_api_key_callback( $args) {  
			?>
			<input type="password" name="po_api_key" id="po_api_key" class="fields-lenght" placeholder="<?php esc_html_e('Enter your API key...', 'piy-online'); ?>" value="<?php esc_html_e( get_option('po_api_key') ); ?>" /> 
			<p class="description"><?php esc_html_e(current($args), 'piy-online'); ?></p>
			<?php      
		}

		public function po_send_orders_callback( $args) {

			$order_statuses = array_merge(
				array('' => esc_html__('Select order status...', 'piy-online')),
				wc_get_order_statuses()
			);

			?>
			<select name="po_order_status" id="po_order_status" class="fields-lenght" >
				<?php 
				foreach ($order_statuses as $key => $status) {
					?>
					 <option value="<?php echo esc_attr($key); ?>" <?php echo selected($key, esc_attr( get_option('po_order_status'))); ?>><?php echo esc_attr($status); ?></option> 
					<?php
				}
				?>
			</select>
			<p class="description"><?php esc_html_e(current($args), 'piy-online'); ?></p>
			<?php
		}

		public function po_cron_time_callback( $args) {  

			?>
			<input type="number" name="po_cron_time" id="po_cron_time" class="fields-lenght" placeholder="<?php esc_html_e('Enter time in seconds', 'piy-online'); ?>" value="<?php esc_html_e( get_option('po_cron_time') ); ?>" />
			<p class="description"><?php esc_html_e(current($args), 'piy-online'); ?></p>
			<?php      
		}


		/**
		 * 
		 * General Configuration
		 * 
		 */


		public function gen_config_sec_cb() { 
			?>
			<h2><?php echo esc_html__('Configure PIY Online', 'piy-online'); ?></h2>
			<p><?php echo esc_html__('The settings below can be overridden on product level, category level and tag level.', 'piy-online'); ?> </p>
			<?php 
		}

		public function po_geb_emojis_cb( $args) { 
			?>
			<input type="checkbox" name="po_gen_emojis" id="po_gen_emojis" class="fields-lenght" value="yes" <?php echo checked('yes', get_option('po_gen_emojis') ); ?>
				/>
			<p class="description po_gen_emojis"> <?php echo esc_attr( current($args) ); ?> </p> 
			<?php      
		}

		public function po_gen_template_cb() {  

			$temp_fonts = wp_cache_get('po_template_fonts');
			
			if (false === $temp_fonts) {
				
				$request 	= new PO_Request();
				$temp_fonts = $request->po_get_temp_fonts();

				wp_cache_set( 'po_template_fonts', $temp_fonts, '', HOUR_IN_SECONDS );
			}

			$template_save = get_option('po_gen_template');
			

			?>
			<select id="po_gen_template" name="po_gen_template">
				<?php 
				foreach ($temp_fonts['templates'] as $key => $template) {
					?>
					 <option value="<?php echo esc_attr($key); ?>" <?php echo selected($key, $template_save); ?>><?php echo esc_html__($template, 'piy-online'); ?> </option> 
					<?php
				} 
				?>
			</select>

			<p class="description"><?php echo esc_html__('The default ribbon template.', 'piy-online'); ?></p>
			<?php
		}

		public function po_gen_font_cb() {  
			
			$temp_fonts = wp_cache_get('po_template_fonts');
			
			if (false === $temp_fonts) {
				
				$request 	= new PO_Request();
				$temp_fonts = $request->po_get_temp_fonts();

				wp_cache_set( 'po_template_fonts', $temp_fonts, '', HOUR_IN_SECONDS );
			}

			$font_save = get_option('po_gen_font');

			?>
			<select id="po_gen_font" name="po_gen_font">
				<?php 
				foreach ($temp_fonts['fonts'] as $key => $font) {
					?>
					 <option value="<?php echo esc_attr($key); ?>" <?php echo selected($key, $font_save); ?>><?php echo esc_html__($font, 'piy-online'); ?> </option> 
					<?php
				} 
				?>
			</select>

			<p class="description"><?php echo esc_html__('The default ribbon font.', 'piy-online'); ?></p>
			<?php
		}


		public function po_gen_products_cb() {  
			?>
			<select multiple="multiple" class="po_gen_products" name="po_gen_products[]" id="po_gen_products" data-placeholder='<?php esc_html_e('Select products...', 'piy-online' ); ?>' tabindex="-1">
				<?php

				if (!empty(get_option('po_gen_products'))) {
					$spec_pro = get_option('po_gen_products');
				} else {
					$spec_pro = array();
				}

				foreach ($spec_pro as $pro_id) {
					$product = wc_get_product($pro_id);
					?>
					<option value="<?php echo esc_attr( $pro_id ); ?>" <?php echo selected(true, true, false); ?>> <?php echo esc_html( wp_strip_all_tags( $product->get_formatted_name() ) ); ?> </option>
					<?php
				}
				?>
			</select>
			<p class="description"><?php esc_html_e('Apply the default template and font to the selected products.', 'piy-online'); ?></p>
			<?php
		}

		public function po_gen_categories_callback() {  
			
			$terms    = get_terms( array('taxonomy' => 'product_cat', 'hide_empty' => false) );
			$spec_cat = array();

			if (!empty(get_option('po_gen_categories'))) {
				$spec_cat = (array) get_option('po_gen_categories');
			}
			?>
			<select multiple="multiple" class="po_gen_categories" name="po_gen_categories[]" id="po_gen_categories" data-placeholder='<?php esc_html_e('Select categories...', 'piy-online' ); ?>' tabindex="-1">
				<?php
				
				foreach ($terms as $term) {

					?>
					<option value="<?php echo esc_attr( $term->term_id ); ?>" 
						<?php echo in_array($term->term_id, $spec_cat) ? 'selected' : ''; ?> ><?php echo esc_html__( $term->name, 'piy-online' ); ?>
					</option>
					<?php
				}
				?>

			</select>
			<p class="description"><?php esc_html_e('Apply the default template and font to the selected product categories.', 'piy-online'); ?></p>
			<?php				
		}

		public function po_gen_tags_callback() {  
			
			$tags     = get_terms( array('taxonomy' => 'product_tag', 'hide_empty' => false) );
			$spec_cat = array();

			if (!empty(get_option('po_gen_tags'))) {
				$spec_cat = (array) get_option('po_gen_tags');
			}
			?>
			<select multiple="multiple" class="po_gen_tags" name="po_gen_tags[]" id="po_gen_tags" data-placeholder='<?php esc_html_e('Select tags...', 'piy-online' ); ?>' tabindex="-1">
				<?php
				
				foreach ($tags as $tag) {

					?>
					<option value="<?php echo esc_attr( $tag->term_id ); ?>" 
						<?php echo in_array($tag->term_id, $spec_cat) ? 'selected' : ''; ?> ><?php echo esc_html__( $tag->name, 'piy-online' ); ?>
					</option>
					<?php
				}
				?>

			</select>
			<p class="description"><?php esc_html_e('Apply the default template and font to the selected product tags.', 'piy-online'); ?></p>
			<?php				
		}

		public function po_gen_attributes_callback() {
			
			$attributes = wc_get_attribute_taxonomies();

			$attrs = array();
			
			if (!empty(get_option('po_gen_attributes'))) {
				$attrs = (array) get_option('po_gen_attributes');
			}

			?>
			<select multiple="multiple" class="po_gen_attributes" name="po_gen_attributes[]" id="po_gen_attributes" data-placeholder='<?php esc_html_e('Select attributes...', 'piy-online' ); ?>' tabindex="-1">
			<?php

			foreach ( $attributes as $attribute_obj ) {
				?>
				<option value="<?php echo esc_attr( wc_attribute_taxonomy_name( $attribute_obj->attribute_name ) ); ?>" 
					<?php echo in_array(wc_attribute_taxonomy_name($attribute_obj->attribute_name), $attrs) ? 'selected' : ''; ?> ><?php echo esc_html__( $attribute_obj->attribute_label, 'piy-online' ); ?>
				</option>
				<?php
			}
	
			?>

			</select>
			<p class="description"><?php esc_html_e('Apply the default template and font to the selected product attributes.', 'piy-online'); ?></p>
			<?php			
		}

		public function po_gen_price_callback() {
			
			?>
			<input type="text" name="po_gen_price" id="po_gen_price" value="<?php echo esc_attr( get_option('po_gen_price')); ?>" >
			<p class="description po_gen_price"><?php esc_html_e('Leave this field empty to offer personalised ribbons for free.', 'piy-online'); ?> </p>
			<?php
		}

		public function po_gen_text_callback() {
			
			?>
			<input type="text" name="po_gen_text" id="po_gen_text" value="<?php echo esc_attr( get_option('po_gen_text')); ?>" >
			<p class="description po_gen_text"><?php esc_html_e('Change the text for the text next to the checkbox on the product page. You can use the {price} variable to display the price.', 'piy-online'); ?> </p>
			<?php
		}

		public function po_add_bulk_order($actions)	{
			
			$actions['push-to-piy'] = esc_html__('Push to PIY Dashboard', 'piy-online');
			return $actions;
		}

		public function po_push_orders($redirectTo, $action, $ids) {
			
			if ('push-to-piy' == $action) {
				
				foreach ($ids as $id) {

					if (!empty(get_post_meta((int) $id, 'piy_order_id', true))) {
						continue;
					}

					$front = new PO_Front();
					$front->po_create_piy_order(wc_get_order($id));
				}
			}
			return $redirectTo;
		}

	}

	new PO_Admin();
}