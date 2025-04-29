<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('PO_Admin')) {
    class PO_Admin
    {

        public function __construct()
        {

            // Add Custom tab icon
            add_action('admin_head', array($this, 'po_tab_icon'), 20);

            add_action('admin_enqueue_scripts', array($this, 'po_admin_scripts'), 20);

            /**
             *
             * PIY Online for PRODUCTS
             *
             */

            // Add PIY Online Tab for product edit page
            add_filter('woocommerce_product_data_tabs', array($this, 'po_data_tab'), 20);

            // Create fields for PIY Online tab
            add_filter('woocommerce_product_data_panels', array($this, 'po_data_tab_fields'), 20);

            // Save PIY Online Product Data
            add_action('woocommerce_process_product_meta', array($this, 'po_save_data_tabs'), 20);


            /**
             *
             * PIY Online for CATEGORIES
             *
             */

            // Add PIY Online for CATEGORIES
            add_action('product_cat_add_form_fields', array($this, 'po_new_category_fields'), 20);

            add_action('product_cat_edit_form_fields', array($this, 'po_edit_category'), 20);

            add_action('edited_product_cat', array($this, 'po_save_category_fields'), 20, 1);

            add_action('create_product_cat', array($this, 'po_save_category_fields'), 20, 1);

            /**
             *
             * PIY Online for TAGS
             *
             */

            // Add PIY Online for TAGS
            add_action('product_tag_add_form_fields', array($this, 'po_new_category_fields'), 20);

            add_action('product_tag_edit_form_fields', array($this, 'po_edit_category'), 20);

            add_action('edited_product_tag', array($this, 'po_save_category_fields'), 20, 1);

            add_action('create_product_tag', array($this, 'po_save_category_fields'), 20, 1);
        }

        public function po_tab_icon()
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

        public function po_admin_scripts()
        {

            $in_footer = true;
            wp_enqueue_style('po-admin', plugins_url('../../assets/css/po_admin.css', __FILE__), false, '1.0.0');

            wp_enqueue_script('po-admin', plugins_url('../../assets/js/po_admin.js', __FILE__), array('jquery'), '1.0.0', $in_footer);

            wp_enqueue_style('select2', plugins_url('../../assets/css/select2.min.css', __FILE__), false, '1.0.0');

            wp_enqueue_script('select2', plugins_url('../../assets/js/select2.min.js', __FILE__), array('jquery'), '1.0.0', $in_footer);

            $info = array(
                'admin_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('po-ajax-nonce'),
            );
            wp_localize_script('po-admin', 'info', $info);
        }


        /**
         *
         * PRODUCTS PIY ONLINE
         *
         */


        //PRODUCT DATA TAB
        public function po_data_tab($tabs)
        {

            $tabs['po-data-tab'] = array(
                'label' => esc_html__('PIY Online Lite', 'piy-online-lite'),
                'target' => 'po_tab',
                'priority' => 80,
            );
            return $tabs;
        }

        public function po_data_tab_fields($post_id)
        {
            global $post;
            wp_nonce_field('po-pro-nonce', 'po_pro_nonce');
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


                    $current_value_section_title = get_post_meta( $post->ID, 'po_pro_section_title', true ) ?: esc_html__('Add a personalised satin ribbon for {price}', 'piy-online-lite');

                    woocommerce_wp_text_input(
                        array(
                            'id' => 'po_pro_section_title',
                            'label' => esc_html__('Section title', 'piy-online-lite'),
                            'description' => esc_html__('This text will be displayed above the ribbon text field. {price} variable will be replaced with the price.', 'piy-online-lite'),
                            'value' => $current_value_section_title,
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

                    $button_text = get_post_meta($post->ID, 'po_pro_button_text', true) ?: esc_html__('Add to cart ({price})', 'piy-online-lite');

                    woocommerce_wp_text_input(
                        array(
                            'id' => 'po_pro_button_text',
                            'label' => esc_html__('Button text', 'piy-online-lite'),
                            'description' => esc_html__('This text will be displayed on the button. {price} variable will be replaced with the price.', 'piy-online-lite'),
                            'value' => $button_text,
                            'desc_tip' => true,
                            'class' => 'short',
                        )
                    );
                    ?>
                </div>
            </div>
            <?php
        }

        public function po_save_data_tabs($post_id)
        {

            if (isset($_POST['po_pro_nonce'])) {
                if (!wp_verify_nonce(sanitize_text_field($_POST['po_pro_nonce']), 'po-pro-nonce')) {
                    die('PIY-Online Product Nonce Verification Failed');
                }
            }

            $po_pro_enable = isset($_POST['po_pro_enable']) ? 'yes' : 'no';
            update_post_meta($post_id, 'po_pro_enable', $po_pro_enable);

            $po_pro_enable_emoji_picker = isset($_POST['po_pro_enable_emoji_picker']) ? 'yes' : 'no';
            update_post_meta($post_id, 'po_pro_enable_emoji_picker', $po_pro_enable_emoji_picker);

            $po_pro_price_raw = isset($_POST['po_pro_price']) ? sanitize_text_field($_POST['po_pro_price']) : '';
            $po_pro_price = str_replace(',', '.', $po_pro_price_raw);
            if (!is_numeric($po_pro_price)) {
                $po_pro_price = '';
            }
            update_post_meta($post_id, 'po_pro_price', $po_pro_price);

            $po_pro_section_title = isset($_POST['po_pro_section_title']) ? sanitize_text_field($_POST['po_pro_section_title']) : '';
            update_post_meta($post_id, 'po_pro_section_title', $po_pro_section_title);

            $po_pro_character_limit = isset($_POST['po_pro_character_limit']) ? sanitize_text_field($_POST['po_pro_character_limit']) : '';
            update_post_meta($post_id, 'po_pro_character_limit', $po_pro_character_limit);

            $po_pro_button_text = isset($_POST['po_pro_button_text']) ? sanitize_text_field($_POST['po_pro_button_text']) : '';
            update_post_meta($post_id, 'po_pro_button_text', $po_pro_button_text);
        }


        /**
         *
         * CATEGORIES/TAGS PIY ONLINE
         *
         */


        // Create New Category
        public function po_new_category_fields()
        {
            wp_nonce_field('po-cat-nonce', 'po_cat_nonce');

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
                <input type="text" name="po_cat_section_title" id="po_cat_section_title" value="<?php echo esc_html__('Add a personalised satin ribbon for {price}', 'piy-online-lite'); ?>">
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
                <input type="text" name="po_cat_button_text" id="po_cat_button_text" value="<?php echo esc_html__('Add to cart ({price})', 'piy-online-lite'); ?>">
                <p class="po-button-text-description"><?php esc_html_e('This text will be displayed on the button. {price} variable will be replaced with the price.', 'piy-online-lite'); ?></p>
            </div>

            <?php
        }

        //Category Edit
        public function po_edit_category($term)
        {
            $term_id = $term->term_id;

            $po_cat_enable = get_term_meta($term_id, 'po_cat_enable', true);

            $po_cat_emojis = get_term_meta($term_id, 'po_cat_emojis', true);

            $po_cat_section_title = get_term_meta($term_id, 'po_cat_section_title', true);

            $po_cat_price = get_term_meta($term_id, 'po_cat_price', true);

            $po_cat_character_limit = get_term_meta($term_id, 'po_cat_character_limit', true);

            $po_cat_button_text = get_term_meta($term_id, 'po_cat_button_text', true);

            wp_nonce_field('po-cat-nonce', 'po_cat_nonce');

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
        public function po_save_category_fields($term_id)
        {

            if (isset($_POST['po_cat_nonce'])) {
                if (!wp_verify_nonce(sanitize_text_field($_POST['po_cat_nonce']), 'po-cat-nonce')) {
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
    }

    new PO_Admin();
}
