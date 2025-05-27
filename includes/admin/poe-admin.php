<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('POE_Admin')) {
    class POE_Admin
    {

        public function __construct()
        {

            // Add Custom tab icon
            add_action('admin_head', array($this, 'poe_tab_icon'), 20);

            add_action('admin_enqueue_scripts', array($this, 'poe_admin_scripts'), 20);

            /**
             *
             * PIY Online for PRODUCTS
             *
             */

            // Add PIY Online Tab for product edit page
            add_filter('woocommerce_product_data_tabs', array($this, 'poe_data_tab'), 20);

            // Create fields for PIY Online tab
            add_filter('woocommerce_product_data_panels', array($this, 'poe_data_tab_fields'), 20);

            // Save PIY Online Product Data
            add_action('woocommerce_process_product_meta', array($this, 'poe_save_data_tabs'), 20);


            /**
             *
             * PIY Online for CATEGORIES
             *
             */

            // Add PIY Online for CATEGORIES
            add_action('product_cat_add_form_fields', array($this, 'poe_new_category_fields'), 20);

            add_action('product_cat_edit_form_fields', array($this, 'poe_edit_category'), 20);

            add_action('edited_product_cat', array($this, 'poe_save_category_fields'), 20, 1);

            add_action('create_product_cat', array($this, 'poe_save_category_fields'), 20, 1);

            /**
             *
             * PIY Online for TAGS
             *
             */

            // Add PIY Online for TAGS
            add_action('product_tag_add_form_fields', array($this, 'poe_new_category_fields'), 20);

            add_action('product_tag_edit_form_fields', array($this, 'poe_edit_category'), 20);

            add_action('edited_product_tag', array($this, 'poe_save_category_fields'), 20, 1);

            add_action('create_product_tag', array($this, 'poe_save_category_fields'), 20, 1);

            /**
             *
             * PIY Online for GENERAL
             *
             */

            // Add PIY Online for Woo Submenu
            add_action('admin_menu', array($this, 'poe_add_submenu'), 20 );

            add_action('admin_init', array($this, 'poe_submenu_tabs'), 20 );
        }

        public function poe_tab_icon()
        {
            ?>
            <style>
                #woocommerce-product-data ul.wc-tabs li.po-data-tab_options a:before {
                    font-family: WooCommerce;
                    content: '\e004';
                }
            </style>
            <?php
        }

        public function poe_admin_scripts()
        {

            $in_footer = true;
            wp_enqueue_style('poe-admin', plugins_url('../../assets/css/poe_admin.css', __FILE__), false, '1.0.0');

            wp_enqueue_script('poe-admin', plugins_url('../../assets/js/poe_admin.js', __FILE__), array('jquery'), '1.0.0', $in_footer);

            $info = array(
                'admin_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('poe-ajax-nonce'),
            );
            wp_localize_script('poe-admin', 'info', $info);
        }


        /**
         *
         * PRODUCTS PIY ONLINE
         *
         */


        //PRODUCT DATA TAB
        public function poe_data_tab($tabs)
        {

            $tabs['poe-data-tab'] = array(
                'label' => esc_html__('PIY Online Lite', 'piy-online-lite'),
                'target' => 'poe_tab',
                'priority' => 80,
            );
            return $tabs;
        }

        public function poe_data_tab_fields($post_id)
        {
            global $post;
            wp_nonce_field('poe-pro-nonce', 'poe_pro_nonce');
            ?>

            <div id="po_tab" class="panel wc-metaboxes-wrapper woocommerce_options_panel">
                <div class='options_group'>
                    <h4 class="data-fields-margin-left"> <?php esc_html_e('PIY Online Lite', 'piy-online-lite'); ?> </h4>
                    <?php
                    woocommerce_wp_checkbox(
                        array(
                            'id' => 'po_pro_enable',
                            'label' => esc_html__('Activate PIY Online Lite', 'piy-online-lite'),
                            'description' => esc_html__('Whether or not Activate PIY Online for this product to override general settings.', 'piy-online-lite'),
                            'default' => '0',
                            'desc_tip' => true,
                            'class' => 'checkbox',
                        )
                    );

                    woocommerce_wp_checkbox(
                        array(
                            'id' => 'po_pro_enable_emoji_picker',
                            'label' => esc_html__('Enable emoji picker', 'piy-online-lite'),
                            'description' => esc_html__('Whether or not enable customers to add emojis.', 'piy-online-lite'),
                            'default' => '0',
                            'desc_tip' => true,
                            'class' => 'checkbox',
                        )
                    );


                    woocommerce_wp_text_input(
                        array(
                            'id' => 'po_pro_section_title',
                            'label' => esc_html__('Section title', 'piy-online-lite'),
                            'description' => esc_html__('This text will be displayed above the ribbon text field. {price} variable will be replaced with the price.', 'piy-online-lite'),
                            'default' => '',
                            'desc_tip' => true,
                            'class' => 'short',
                        )
                    );

                    woocommerce_wp_text_input(
                        array(
                            'id' => 'po_pro_price',
                            'label' => esc_html__('Ribbon price', 'piy-online-lite'),
                            'description' => esc_html__('Leave this field empty to offer personalised ribbons for free.', 'piy-online-lite'),
                            'default' => '',
                            'desc_tip' => true,
                            'class' => 'price short',
                            'data_type' => 'price'
                        )
                    );

                    woocommerce_wp_text_input(
                        array(
                            'id' => 'po_pro_character_limit',
                            'label' => esc_html__('Ribbon text character limit', 'piy-online-lite'),
                            'description' => esc_html__('Set the maximum number of characters for the ribbon text.', 'piy-online-lite'),
                            'default' => '',
                            'desc_tip' => true,
                            'class' => 'short',
                        )
                    );

                    woocommerce_wp_text_input(
                        array(
                            'id' => 'po_pro_button_text',
                            'label' => esc_html__('Button text', 'piy-online-lite'),
                            'description' => esc_html__('This text will be displayed on the button. {price} variable will be replaced with the price.', 'piy-online-lite'),
                            'default' => '',
                            'desc_tip' => true,
                            'class' => 'short',
                        )
                    );
                    ?>
                </div>
            </div>
            <?php
        }

        public function poe_save_data_tabs($post_id) {
            error_log('POE: Attempting to save data for post_id: ' . $post_id);

            if (!isset($_POST['poe_pro_nonce'])) {
                error_log('POE: Nonce not set in POST data');
                return;
            }

            if (!wp_verify_nonce(sanitize_text_field($_POST['poe_pro_nonce']), 'poe-pro-nonce')) {
                error_log('POE: Nonce verification failed');
                die('PIY-Online Product Nonce Verification Failed');
            }

            error_log('POE: Nonce verified, processing fields...');

            $fields = [
                'po_pro_enable' => 'checkbox',
                'po_pro_enable_emoji_picker' => 'checkbox',
                'po_pro_price' => 'price',
                'po_pro_section_title' => 'text',
                'po_pro_character_limit' => 'text',
                'po_pro_button_text' => 'text'
            ];

            foreach ($fields as $field => $type) {
                $value = '';

                if ($type === 'checkbox') {
                    $value = isset($_POST[$field]) ? 'yes' : 'no';
                } else {
                    $value = isset($_POST[$field]) ? sanitize_text_field($_POST[$field]) : '';

                    if ($field === 'po_pro_price') {
                        $value = str_replace(',', '.', $value);
                        if (!is_numeric($value)) {
                            $value = '';
                        }
                    }
                }

                update_post_meta($post_id, $field, $value);
                error_log("POE: Updated $field with value: " . $value);
            }

            error_log('POE: Data saved successfully for post_id: ' . $post_id);
        }

        /**
         *
         * CATEGORIES/TAGS PIY ONLINE
         *
         */


        // Create New Category
        public function poe_new_category_fields()
        {
            wp_nonce_field('poe-cat-nonce', 'poe_cat_nonce');

            ?>
            <h3><?php esc_html_e('PIY Online Lite', 'piy-online-lite'); ?></h3>
            <div class="form-field term-po-enable-wrap">
                <label for="po_cat_enable"><?php esc_html_e('Activate PIY Online Lite', 'piy-online-lite'); ?></label>
                <input type="checkbox" name="po_cat_enable" id="po_cat_enable" value="yes">
                <p class="po-enable-description"><?php esc_html_e('Activate PIY Online Lite for Category', 'piy-online-lite'); ?></p>
            </div>

            <div class="form-field term-po-emojis-wrap">
                <label for="po_cat_emojis"><?php esc_html_e('Allow Emojis Picker', 'piy-online-lite'); ?></label>
                <input type="checkbox" name="po_cat_emojis" id="po_cat_emojis" value="yes">
                <p class="po-emojis-description"><?php esc_html_e('Allow emojis picker', 'piy-online-lite'); ?></p>
            </div>

            <div class="form-field term-po-section-title-wrap">
                <label for="po_cat_section_title"><?php esc_html_e('Section title', 'piy-online-lite'); ?></label>
                <input type="text" name="po_cat_section_title" id="po_cat_section_title">
                <p class="po-section-title-description"><?php esc_html_e('This text will be displayed above the ribbon text field. {price} variable will be replaced with the price.', 'piy-online-lite'); ?></p>
            </div>

            <div class="form-field term-po-price-wrap">
                <label for="po_cat_price"><?php esc_html_e('Ribbon price', 'piy-online-lite'); ?></label>
                <input type="text" class="price short wc_input_price" name="po_cat_price" id="po_cat_price">
                <p class="po-price-description"><?php esc_html_e('Leave this field empty to offer personalised ribbons for free.', 'piy-online-lite'); ?></p>
            </div>

            <div class="form-field term-po-character-limit-wrap">
                <label for="po_cat_character_limit"><?php esc_html_e('Ribbon text character limit', 'piy-online-lite'); ?></label>
                <input type="text" name="po_cat_character_limit" id="po_cat_character_limit">
                <p class="po-character-limit-description"><?php esc_html_e('Set the maximum number of characters for the ribbon text.', 'piy-online-lite'); ?></p>
            </div>

            <div class="form-field term-po-button-text-wrap">
                <label for="po_cat_button_text"><?php esc_html_e('Button text', 'piy-online-lite'); ?></label>
                <input type="text" name="po_cat_button_text" id="po_cat_button_text">
                <p class="po-button-text-description"><?php esc_html_e('This text will be displayed on the button. {price} variable will be replaced with the price.', 'piy-online-lite'); ?></p>
            </div>

            <?php
        }

        //Category Edit
        public function poe_edit_category($term)
        {
            $term_id = $term->term_id;

            $po_cat_enable = get_term_meta($term_id, 'po_cat_enable', true);

            $po_cat_emojis = get_term_meta($term_id, 'po_cat_emojis', true);

            $po_cat_section_title = get_term_meta($term_id, 'po_cat_section_title', true);

            $po_cat_price = get_term_meta($term_id, 'po_cat_price', true);

            $po_cat_character_limit = get_term_meta($term_id, 'po_cat_character_limit', true);

            $po_cat_button_text = get_term_meta($term_id, 'po_cat_button_text', true);

            wp_nonce_field('poe-cat-nonce', 'poe_cat_nonce');

            ?>
            <tr class="form-field">
                <th colspan=2 scope="row" valign="top">
                    <h3><?php esc_html_e('PIY Online Lite', 'piy-online-lite'); ?></h3>
                </th>
            </tr>

            <!-- For PIY Online -->
            <tr class="form-field term-po-enable-wrap">
                <th scope="row" valign="top">
                    <label for="po_cat_enable"><?php esc_html_e('Activate PIY Online Lite', 'piy-online-lite'); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="po_cat_enable" id="po_cat_enable"
                           value='yes' <?php echo checked('yes', $po_cat_enable); ?> >
                    <p class="description"
                       id="po-enable-description"><?php esc_html_e('Activate PIY Online Lite', 'piy-online-lite'); ?></p>
                </td>
            </tr>

            <tr class="form-field term-po-emojis-wrap">
                <th scope="row" valign="top">
                    <label for="po_cat_emojis"><?php esc_html_e('Allow Emojis Picker', 'piy-online-lite'); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="po_cat_emojis" id="po_cat_emojis"
                           value='yes' <?php echo checked('yes', $po_cat_emojis); ?> >
                    <p class="description"><?php esc_html_e('Allow/Disallow Emojis for ribbon text.', 'piy-online-lite'); ?></p>
                </td>
            </tr>

            <tr class="form-field term-po-section-title-wrap">
                <th scope="row" valign="top">
                    <label for="po_cat_section_title"><?php esc_html_e('Section title', 'piy-online-lite'); ?></label>
                </th>
                <td>
                    <input type="text" name="po_cat_section_title" id="po_cat_section_title"
                           value="<?php echo esc_html($po_cat_section_title); ?>">
                    <p class="description"><?php esc_html_e('This text will be displayed above the ribbon text field. {price} variable will be replaced with the price.', 'piy-online-lite'); ?></p>
                </td>
            </tr>

            <tr class="form-field term-po-price-wrap">
                <th scope="row" valign="top">
                    <label for="po_cat_price"><?php esc_html_e('Ribbon price', 'piy-online-lite'); ?></label>
                </th>
                <td>
                    <input type="text" class="price short wc_input_price" name="po_cat_price" id="po_cat_price"
                           value="<?php echo esc_attr($po_cat_price); ?>">
                    <p class="description"><?php esc_html_e('Leave this field empty to offer personalised ribbons for free.', 'piy-online-lite'); ?></p>
                </td>
            </tr>

            <tr class="form-field term-po-character-limit-wrap">
                <th scope="row" valign="top">
                    <label for="po_cat_character_limit"><?php esc_html_e('Ribbon text character limit', 'piy-online-lite'); ?></label>
                </th>
                <td>
                    <input type="text" name="po_cat_character_limit" id="po_cat_character_limit"
                           value="<?php echo esc_html($po_cat_character_limit); ?>">
                    <p class="description"><?php esc_html_e('Set the maximum number of characters for the ribbon text.', 'piy-online-lite'); ?></p>
                </td>
            </tr>

            <tr class="form-field term-po-button-text-wrap">
                <th scope="row" valign="top">
                    <label for="po_cat_button_text"><?php esc_html_e('Button text', 'piy-online-lite'); ?></label>
                </th>
                <td>
                    <input type="text" name="po_cat_button_text" id="po_cat_button_text"
                           value="<?php echo esc_html($po_cat_button_text); ?>">
                    <p class="description"><?php esc_html_e('This text will be displayed on the button. {price} variable will be replaced with the price.', 'piy-online-lite'); ?></p>
                </td>
            </tr>
            <?php
        }

        // Save Category PIY Online
        public function poe_save_category_fields($term_id)
        {

            if (isset($_POST['poe_cat_nonce'])) {
                if (!wp_verify_nonce(sanitize_text_field($_POST['poe_cat_nonce']), 'poe-cat-nonce')) {
                    die('PIY-Online Category Verification Failed');
                }
            }

            $po_cat_enable = filter_input(INPUT_POST, 'po_cat_enable');
            $po_cat_emojis = filter_input(INPUT_POST, 'po_cat_emojis');
            $po_cat_section_title = filter_input(INPUT_POST, 'po_cat_section_title');
            $po_cat_price = filter_input(INPUT_POST, 'po_cat_price');
            $po_cat_character_limit = filter_input(INPUT_POST, 'po_cat_character_limit');
            $po_cat_button_text = filter_input(INPUT_POST, 'po_cat_button_text');


            update_term_meta($term_id, 'po_cat_enable', $po_cat_enable);
            update_term_meta($term_id, 'po_cat_emojis', $po_cat_emojis);
            update_term_meta($term_id, 'po_cat_section_title', $po_cat_section_title);
            update_term_meta($term_id, 'po_cat_price', $po_cat_price);
            update_term_meta($term_id, 'po_cat_character_limit', $po_cat_character_limit);
            update_term_meta($term_id, 'po_cat_button_text', $po_cat_button_text);

        }

        /**
         *
         * GENERAL PIY ONLINE
         *
         */


        // ADD PIY Online SUBMENU
        public function poe_add_submenu() {

            add_submenu_page(
                'woocommerce', // parent name
                esc_html__( 'PIY Online Lite', 'piy-online-lite' ), // Page Title
                esc_html__( 'PIY Online Lite', 'piy-online-lite' ), // Menu Title
                'manage_options', // capabilities
                'piy-online-lite-settings', // page slug
                array($this, 'poe_submenu_content') // callback
            );
        }

        public function poe_submenu_content() {
            ?>
            <div class="wrap piyonline">
                <h1><?php echo esc_html__('PIY Online Lite Settings', 'piy-online-lite'); ?></h1>
                <?php settings_errors(); ?>

                <form method="post" action="options.php" id="save_options_form">
                    <?php
                    settings_fields('gen_config');
                    do_settings_sections('gen_config_page');
                    submit_button(esc_html__('Save configuration', 'piy-online-lite'), 'primary', 'po_save_configs');
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

        public function poe_submenu_tabs() {

           /**
             *
             * General Configuration
             *
             */

            add_settings_section(
                'gen_config_sec', // ID used to identify this section and with which to register options
                esc_html__('General Configuration', 'piy-online-lite'),  // Title to be displayed on the administration page
                array($this, 'gen_config_sec_cb'), // Callback used to render the description of the section
                'gen_config_page'      // Page on which to add this section of options
            );

            add_settings_field (
                'po_gen_emojis', // ID used to identify the field throughout the theme
                esc_html__('Enable emoji picker', 'piy-online-lite'), // The label to the left of the option interface element
                array($this, 'poe_geb_emojis_cb'),   // The name of the function responsible for rendering the option interface
                'gen_config_page', // The page on which this option will be displayed
                'gen_config_sec', // The name of the section to which this field belongs
                array(
                    esc_html__('Allow customers to select emojis in the ribbon text field.', 'piy-online-lite'),
                )
            );
            register_setting(
                'gen_config',
                'po_gen_emojis'
            );

            add_settings_field (
                'po_gen_button_enable', // ID used to identify the field throughout the theme
                esc_html__('Enable button', 'piy-online-lite'), // The label to the left of the option interface element
                array($this, 'poe_gen_button_enable_cb'),   // The name of the function responsible for rendering the option interface
                'gen_config_page', // The page on which this option will be displayed
                'gen_config_sec', // The name of the section to which this field belongs
                array(
                    esc_html__('Enable/Disable the button on the ribbon.', 'piy-online-lite'),
                )
            );
            register_setting(
                'gen_config',
                'po_gen_button_enable'
            );

            add_settings_field (
                'po_gen_price', // ID used to identify the field throughout the theme
                esc_html__('Ribbon price', 'piy-online-lite'), // The label to the left of the option interface element
                array($this, 'poe_gen_price_callback'),   // The name of the function responsible for rendering the option interface
                'gen_config_page', // The page on which this option will be displayed
                'gen_config_sec' // The name of the section to which this field belongs
            );
            register_setting(
                'gen_config',
                'po_gen_price'
            );

            add_settings_field (
                'po_gen_text', // ID used to identify the field throughout the theme
                esc_html__('Custom label', 'piy-online-lite'), // The label to the left of the option interface element
                array($this, 'poe_gen_text_callback'),   // The name of the function responsible for rendering the option interface
                'gen_config_page', // The page on which this option will be displayed
                'gen_config_sec' // The name of the section to which this field belongs
            );
            register_setting(
                'gen_config',
                'po_gen_text'
            );

            add_settings_field (
                'po_gen_character_limit', // ID used to identify the field throughout the theme
                esc_html__('Ribbon text character limit', 'piy-online-lite'), // The label to the left of the option interface element
                array($this, 'poe_gen_character_limit_callback'),   // The name of the function responsible for rendering the option interface
                'gen_config_page', // The page on which this option will be displayed
                'gen_config_sec' // The name of the section to which this field belongs
            );
            register_setting(
                'gen_config',
                'po_gen_character_limit'
            );

            add_settings_field (
                'po_gen_button_text', // ID used to identify the field throughout the theme
                esc_html__('Button text', 'piy-online-lite'), // The label to the left of the option interface element
                array($this, 'poe_gen_button_text_callback'),   // The name of the function responsible for rendering the option interface
                'gen_config_page', // The page on which this option will be displayed
                'gen_config_sec' // The name of the section to which this field belongs
            );
            register_setting(
                'gen_config',
                'po_gen_button_text'
            );

            add_settings_field (
                'po_gen_button_text_color', // ID used to identify the field throughout the theme
                esc_html__('Button text color', 'piy-online-lite'), // The label to the left of the option interface element
                array($this, 'poe_gen_button_text_color_callback'),   // The name of the function responsible for rendering the option interface
                'gen_config_page', // The page on which this option will be displayed
                'gen_config_sec' // The name of the section to which this field belongs
            );
            register_setting(
                'gen_config',
                'po_gen_button_text_color'
            );

            add_settings_field (
                'po_gen_button_color', // ID used to identify the field throughout the theme
                esc_html__('Button background color', 'piy-online-lite'), // The label to the left of the option interface element
                array($this, 'poe_gen_button_color_callback'),   // The name of the function responsible for rendering the option interface
                'gen_config_page', // The page on which this option will be displayed
                'gen_config_sec' // The name of the section to which this field belongs
            );
            register_setting(
                'gen_config',
                'po_gen_button_color'
            );
        }


        /**
         *
         * General Configuration
         *
         */
        public function gen_config_sec_cb() {
            ?>
            <h2><?php echo esc_html__('Configure PIY Online Lite', 'piy-online-lite'); ?></h2>
            <p><?php echo esc_html__('The settings below can be overridden on product level, category level and tag level.', 'piy-online-lite'); ?> </p>
            <?php
        }

        public function poe_geb_emojis_cb($args) {
            ?>
            <input type="checkbox" name="po_gen_emojis" id="po_gen_emojis" class="fields-lenght" value="yes" <?php echo checked('yes', get_option('po_gen_emojis') ); ?>
            />
            <p class="description po_gen_emojis"> <?php echo esc_attr( current($args) ); ?> </p>
            <?php
        }

        public function poe_gen_button_enable_cb($args) {
            ?>
            <input type="checkbox" name="po_gen_button_enable" id="po_gen_button_enable" class="fields-lenght" value="yes" <?php echo checked('yes', get_option('po_gen_button_enable') ); ?>
            />
            <p class="description po_gen_button_enable"> <?php echo esc_attr( current($args) ); ?> </p>
            <?php
        }

        public function poe_gen_price_callback() {

            ?>
            <input type="text" name="po_gen_price" id="po_gen_price" value="<?php echo esc_attr( get_option('po_gen_price')); ?>" >
            <p class="description po_gen_price"><?php esc_html_e('Leave this field empty to offer personalised ribbons for free.', 'piy-online-lite'); ?> </p>
            <?php
        }

        public function poe_gen_text_callback() {

            ?>
            <input type="text" name="po_gen_text" id="po_gen_text" value="<?php echo esc_attr( get_option('po_gen_text')); ?>" >
            <p class="description po_gen_text"><?php esc_html_e('Change the text for the text next to the checkbox on the product page. You can use the {price} variable to display the price.', 'piy-online-lite'); ?> </p>
            <?php
        }

        public function poe_gen_character_limit_callback()
        {
            ?>
            <input type="text" name="po_gen_character_limit" id="po_gen_character_limit" value="<?php echo esc_attr( get_option('po_gen_character_limit')); ?>" >
            <p class="description po_gen_character_limit"><?php esc_html_e('Set the maximum number of characters for the ribbon text.', 'piy-online-lite'); ?> </p>
            <?php
        }

        public function poe_gen_button_text_callback() {

            ?>
            <input type="text" name="po_gen_button_text" id="po_gen_button_text" value="<?php echo esc_attr( get_option('po_gen_button_text')); ?>" >
            <p class="description po_gen_button_text"><?php esc_html_e('Change the text for the button on the product page. You can use the {price} variable to display the price.', 'piy-online-lite'); ?> </p>
            <?php
        }

        public function poe_gen_button_color_callback() {

            ?>
            <input type="text" name="po_gen_button_color" id="po_gen_button_color" value="<?php echo esc_attr( get_option('po_gen_button_color')); ?>" >
            <p class="description po_gen_button_color"><?php esc_html_e('Change the color of the button on the product page.', 'piy-online-lite'); ?> </p>
            <?php
        }

        public function poe_gen_button_text_color_callback() {

            ?>
            <input type="text" name="po_gen_button_text_color" id="po_gen_button_text_color" value="<?php echo esc_attr( get_option('po_gen_button_text_color')); ?>" >
            <p class="description po_gen_button_text_color"><?php esc_html_e('Change the text color of the button on the product page.', 'piy-online-lite'); ?> </p>
            <?php
        }
    }

    new POE_Admin();
}
