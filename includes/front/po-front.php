<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('PO_Front')) {

    class PO_Front
    {

        public function __construct()
        {

            add_action('wp_enqueue_scripts', array($this, 'po_front_scripts'));

            add_action('woocommerce_before_add_to_cart_button', array($this, 'po_show_fields'), 20);

            add_filter('woocommerce_add_cart_item_data', array($this, 'po_add_cart_item_meta_data'), 20, 4);

            add_filter('woocommerce_get_item_data', array($this, 'po_get_cart_item_data'), 20, 2);

            add_action('woocommerce_new_order_item', array($this, 'po_add_order_item_meta'), 20, 3);

            add_action('woocommerce_checkout_create_order_line_item', array($this, 'po_template_order_item_meta_data'), 20, 4);

            add_filter('woocommerce_order_item_display_meta_key', array($this, 'po_wc_order_item_display_meta_key'), 20, 3);

            add_action('woocommerce_cart_calculate_fees', array($this, 'po_add_cart_item_fee'), 20);

//            add_filter('woocommerce_product_single_add_to_cart_text', array($this, 'custom_add_to_cart_text_with_price'), 20);

//            add_filter('woocommerce_product_add_to_cart_text', array($this, 'custom_add_to_cart_text_with_price'), 20);
        }

        public function po_front_scripts()
        {

            wp_enqueue_style('po-front', plugins_url('../../assets/css/po_front.css', __FILE__), false, '1.0');

            wp_enqueue_script('po-front', plugins_url('../../assets/js/po_front.js', __FILE__), array('jquery'), '1.0', false);

            wp_register_script('emojimart', 'https://cdn.jsdelivr.net/npm/emoji-mart@latest/dist/browser.js', array('jquery'), '3.0', true);
            wp_enqueue_script('emojimart');

            $info = array(
                'admin_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('po-ajax-nonce'),
            );

            wp_localize_script('po-front', 'info', $info);
        }

        public function po_get_applied_from($product_id)
        {


            $product = wc_get_product($product_id);

            if (!is_a($product, 'WC_Product')) {
                return;
            }

            $display = false;
            $enable_by = '';
            $term_id = 0;
            $price = 0;
            $section_title = esc_html__('Add a personalised satin ribbon for {price}', 'piy-online-lite');
            $character_limit = null;
            $button_text = esc_html__('Add to cart ({price})', 'piy-online-lite');


            $enable = get_post_meta($product_id, 'po_pro_enable', true);

            if ('yes' == $enable) {
                $enable_by = 'product';
                $price = get_post_meta($product_id, 'po_pro_price', true);
                $section_title = get_post_meta($product_id, 'po_pro_section_title', true);
                $character_limit = get_post_meta($product_id, 'po_pro_character_limit', true);
                $button_text = get_post_meta($product_id, 'po_pro_button_text', true);
                $display = true;
            } else {

                $terms = wp_get_post_terms($product_id, array('product_cat', 'product_tag'));

                foreach ($terms as $term) {

                    if ('product_cat' == $term->taxonomy) {
                        $enable_by = 'category';
                        $enable = get_term_meta($term->term_id, 'po_cat_enable', true);
                        $price = get_term_meta($term->term_id, 'po_cat_price', true);
                        $section_title = get_term_meta($term->term_id, 'po_cat_section_title', true);
                        $character_limit = get_term_meta($term->term_id, 'po_cat_character_limit', true);
                        $button_text = get_term_meta($term->term_id, 'po_cat_button_text', true);
                    } elseif ('product_tag' == $term->taxonomy) {

                        $enable_by = 'tag';
                        $enable = get_term_meta($term->term_id, 'po_tag_enable', true);
                        $price = get_term_meta($term->term_id, 'po_tag_price', true);
                        $section_title = get_term_meta($term->term_id, 'po_tag_section_title', true);
                        $character_limit = get_term_meta($term->term_id, 'po_tag_character_limit', true);
                        $button_text = get_term_meta($term->term_id, 'po_tag_button_text', true);
                    }

                    if ('yes' == $enable) {

                        $display = true;
                        $term_id = $term->term_id;
                        break;
                    }
                }


                if ('yes' !== $enable) {
                    $enable = 'yes' == get_option('po_gen_enable') ? get_option('po_gen_enable') : 'no';

                    if ('yes' === $enable) {
                        $enable_by = 'general';
                        $display = $this->po_is_in_gen_configs($product_id);
                        $price = get_option('po_gen_price');

                    }
                }
            }

            return array(
                'enable' => $enable,
                'enable_by' => $enable_by,
                'display' => $display,
                'term_id' => $term_id,
                'product_id' => $product_id,
                'price' => $price,
                'section_title' => $section_title,
                'character_limit' => $character_limit,
                'button_text' => $button_text,
            );
        }

        public function po_show_fields()
        {
            $applied_data = $this->po_get_applied_from(get_the_ID());

            if (!$applied_data['display']) {
                return;
            }

            $term_id = $applied_data['term_id'];
            $enable_by = $applied_data['enable_by'];
            $price = $applied_data['price'];
            $text = $applied_data['section_title'] ?: esc_html__('Add a personalised satin ribbon for {price}', 'piy-online-lite');
            $character_limit = $applied_data['character_limit'];

            if ($price > 0) {
                $text = str_replace('{price}', '<span class="piy-price">' . wc_price($price) . '</span>', $text);
            } else {
                $text = str_replace('{price}', '', $text);
            }


            ob_start();
            ?>
            <div class="po-parent">
                <div style="clear: both;"></div>
                <input type="checkbox" name="po_show_fields" id="po_show_fields" value="yes"
                       <?php if (isset($character_limit) && $character_limit > 0) {
                           echo 'maxlength="' . esc_attr($character_limit) . '"';
                       } ?>
                       data-product_id="<?php echo get_the_ID(); ?>" data-sent="false"
                       data-enable_by="<?php echo esc_attr($enable_by); ?>"
                       data-term='<?php echo esc_attr($term_id); ?>'> <?php echo wp_kses_post($text);?>
                <div class="po-display-fields">

                </div>
            </div>
            <div style="clear: both;"></div>
            <?php
            echo ob_get_clean();
        }

        public function po_is_in_gen_configs($product_id)
        {

            $products = !empty(get_option('po_gen_products')) ? get_option('po_gen_products') : array();
            $categories = get_option('po_gen_categories');
            $tags = get_option('po_gen_tags');
            $attrs = get_option('po_gen_attributes');
            $result = false;

            if (!empty($categories)) {
                $args = array(

                    'post_type' => 'product',

                    'post_status' => 'publish',

                    'fields' => 'ids',

                    'orderby' => 'menu_order',

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

                    'orderby' => 'menu_order',

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
                $result = count(array_intersect($attrs, array_keys($product->get_attributes()))) > 0;
            }

            if (!$result && !empty($products)) {
                $result = in_array($product_id, $products);
            }

            return $result;
        }

        public function po_add_cart_item_meta_data($cart_item_data, $product_id, $variation_id, $quantity)
        {
            $meta_keys = array('po_text', 'po_price');

            foreach ($meta_keys as $key) {
                if (!empty($_POST[$key])) {
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

        public function po_get_cart_item_data($item_data, $cart_item)
        {
            $meta_keys = array(
                'po_text' => esc_html__('Personalised satin ribbon', 'piy-online-lite')
            );

            // Skip child items inside a bundle

            if (function_exists('wc_pb_is_bundled_cart_item')) {
                if (wc_pb_is_bundled_cart_item($cart_item)) {
                    // error_log("⏩ Skipping Ribbon Text & Font for child item: " . $cart_item['data']->get_name());
                    return $item_data; // Do not add ribbon text/font to child products
                }
            }

            foreach ($meta_keys as $key => $name) {
                if (isset($cart_item[$key])) {
                    $value = $cart_item[$key];

                    if (!empty($value)) {
                        $item_data[] = array(
                            'name' => esc_html__($name, 'piy-online-lite'),
                            'value' => esc_html__($value, 'piy-online-lite')
                        );
                    }
                }
            }

            return $item_data;
        }


        public function po_add_order_item_meta($item_id, $item, $order_id)
        {
            $meta_keys = array(
                'po_text' => esc_html__('Ribbon text', 'piy-online-lite')
            );

            // ❌ Fix: Ensure we're only working with product items
            if (!$item instanceof WC_Order_Item_Product) {
                // error_log("⏩ Skipping non-product order item: " . get_class($item));
                return;
            }

            $product = $item->get_product();
            if (!$product) return;

            // ❌ Skip adding meta for child products inside a bundle
            if (function_exists('wc_pb_is_bundled_order_item')) {
                if (wc_pb_is_bundled_order_item($item)) {
                    // error_log("⏩ Skipping Ribbon Meta for child product in order: " . $product->get_name());
                    return;
                }
            }

            foreach ($meta_keys as $key => $name) {
                if (isset($item->legacy_values[$key]) && !empty($item->legacy_values[$key])) {
                    $value = $item->legacy_values[$key];

                    wc_add_order_item_meta($item_id, $name, $value, false);
                    // error_log("✅ Added Ribbon Meta: $name | Value: $value");
                } else {
                    error_log("❌ ERROR: Skipping meta key $name due to missing value.");
                }
            }
        }


        public function po_template_order_item_meta_data($item, $cart_item_key, $values, $order)
        {
            // Skip adding meta for child products inside a bundle
            if (function_exists('wc_pb_is_bundled_cart_item')) {
                if (wc_pb_is_bundled_cart_item($values)) {
                    return; // Do not add ribbon text/font to child order items
                }
            }

            if (!isset($values['po_text'])) {
                return;
            }

            if (isset($values['po_price'])) {
                $item->update_meta_data('_po_price', wc_price($values['po_price']));
            }
        }

        public function po_wc_order_item_display_meta_key($display_key, $meta, $item)
        {
            // ❌ Prevent display of ribbon meta for child products inside a bundle
            if (function_exists('wc_pb_is_bundled_order_item')) {
                if (wc_pb_is_bundled_order_item($item)) {
                    // error_log("⏩ Hiding Ribbon Meta for child product in order display: " . $item->get_name());
                    return $display_key; // Do not modify display for child items
                }
            }

            if ($meta->key === '_po_price' && is_admin()) {
                $display_key = __("Ribbons Price", "woocommerce");
            }

            return $display_key;
        }

        public function po_add_cart_item_fee($cart)
        {
            $ribbon_price = 0;
            $ribbon_count = 0;
            $processed_bundles = []; // To track processed bundles

            foreach ($cart->get_cart() as $cart_item_key => $item) {
                $product = wc_get_product($item['product_id']);
                if (!$product) continue;

                // Skip child items inside a bundle
                if (function_exists('wc_pb_is_bundled_cart_item')) {
                    if (wc_pb_is_bundled_cart_item($item)) {
                        error_log("⏩ Skipping child item inside bundle: " . $product->get_name());
                        continue;
                    }
                }

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

                if ($price <= 0) {
                    continue; // Skip if no ribbon price is set
                }

                // Apply ribbon price only once per bundle
                $ribbon_price += (float)$price * $item['quantity'];
                $ribbon_count += $item['quantity'];

                error_log("✅ Added ribbon fee ({$price}) for: " . $product->get_name());
            }

            if ($ribbon_price > 0) {
                $name = sprintf(esc_html__('%u personalised ribbon(s)', 'piy-online-lite'), $ribbon_count);
                $amount = $ribbon_price;
                $taxable = true;
                $tax_class = '';

                error_log("📡 Adding total ribbon fee: " . $ribbon_price);
                $cart->add_fee($name, $amount, $taxable, $tax_class);
            }
        }

        public function custom_add_to_cart_text_with_price($default_text) {
            global $post;
            if (!is_a($post, 'WC_Product')) {
                return $default_text;
            }
            $applied_data = $this->po_get_applied_from($post->ID);
            if (!$applied_data['display']) {
                return $default_text;
            }

            // Отримуємо кастомний текст кнопки
            $button_text = get_post_meta(get_the_ID(), 'po_pro_button_text', true);

            // Якщо текст не встановлено, повертаємо текст за замовчуванням
            if (empty($button_text)) {
                return $default_text;
            }

            // Обробляємо ціну
            $price = $applied_data['price'];
            if ($price > 0) {
                $price_html = wc_price($price);
                $text = str_replace('{price}', strip_tags($price_html), $button_text);
            } else {
                $text = str_replace('{price}', '', $button_text);
            }

            // Повертаємо результат з підтримкою HTML
            return wp_kses_post($text);
        }
    }

    new PO_Front();
}
