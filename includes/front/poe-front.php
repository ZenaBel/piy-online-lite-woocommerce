<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('POE_Front')) {

    class POE_Front
    {

        public function __construct()
        {
            add_action('wp_enqueue_scripts', array($this, 'poe_front_scripts'));

            add_action('woocommerce_cart_calculate_fees', array($this, 'poe_add_cart_item_fee'), 20);

            add_action('woocommerce_cart_contents', array($this, 'poe_show_fields'), 20);

            add_action('woocommerce_thankyou', array($this, 'poe_clear_ribbon_session_data'));

            add_action('woocommerce_before_checkout_form', array($this, 'poe_add_ribbon_coupon_block'), 20);
        }

        public function poe_front_scripts()
        {
            wp_enqueue_style('poe-front', plugins_url('../../assets/css/poe_front.css', __FILE__), false, '1.0');

            wp_enqueue_script('poe-front', plugins_url('../../assets/js/poe_front.js', __FILE__), array('jquery'), '1.0', false);

            wp_register_script('emojimart', 'https://cdn.jsdelivr.net/npm/emoji-mart@latest/dist/browser.js', array('jquery'), '3.0', true);
            wp_enqueue_script('emojimart');

            $info = array(
                'admin_url' => admin_url('admin-ajax.php'),
                'poe_nonce' => wp_create_nonce('poe-ajax-nonce'),
            );

            wp_localize_script('poe-front', 'info', $info);
        }

        public function poe_show_fields()
        {
            $emoji_picker = get_option('po_gen_emojis', true);
            $label_text = get_option('po_gen_text', __('Personalised ribbon', 'piy-online-lite'));
            $ribbon_price = get_option('po_gen_price');
            $character_limit = get_option('po_gen_character_limit');
            $button_text = get_option('po_gen_button_text');
            $button_color = get_option('po_gen_button_color');
            $button_text_color = get_option('po_gen_button_text_color');

            if ($ribbon_price > 0) {
                $button_text = str_replace('{price}', strip_tags(wc_price($ribbon_price)), $button_text);
                $label_text = str_replace('{price}', strip_tags(wc_price($ribbon_price)), $label_text);
            } else {
                $button_text = str_replace('{price}', 'free', $button_text);
                $label_text = str_replace('{price}', 'free', $label_text);
            }

            ?>
            <tr>
                <td class="product-custom-text" colspan="6">
                    <div class="poe-ribbon">
                        <label class="label-personalised-ribbon" for="poe_personalised_ribbon"><?php esc_html_e($label_text, 'piy-online-lite'); ?>
                            <input type="text"
                                   name="poe_personalised_ribbon"
                                   id="po_text"
                                   class="emoji-field input-text"
                                   data-emoji-picker="<?php echo esc_attr($emoji_picker); ?>"
                                   autocomplete="off"
                                   <?php if ($character_limit) : ?>maxlength="<?php echo esc_attr($character_limit); ?>"<?php endif; ?>
                                   placeholder="<?php esc_attr_e('Enter the message to be printed onto the ribbon…', 'piy-online-lite'); ?>"
                            >
                        </label>
                        <?php if ('yes' == $emoji_picker) : ?>
                            <div id="emoji-picker"></div>
                        <?php endif; ?>
                        <button type="submit"
                                id="poe_submit"
                                class="button-personalised-ribbon"
                                name="poe_personalised_ribbon"
                                value="<?php esc_attr_e($button_text, 'piy-online-lite'); ?>"
                                style="background-color: <?php echo esc_attr($button_color); ?>; color: <?php echo esc_attr($button_text_color); ?>;"
                        >
                            <?php esc_html_e($button_text, 'piy-online-lite'); ?>
                        </button>
                        <button type="button"
                                id="poe_remove_ribbon"
                                class="button-remove-ribbon"
                                style="background-color: #ff0000; color: #ffffff; margin-left: 10px;">
                            <?php esc_html_e('Remove', 'piy-online-lite'); ?>
                        </button>
                    </div>
                    <div class="ribbon-error-message" style="display: none; color: red;">
                        <?php esc_html_e('Please enter a message for the ribbon.', 'piy-online-lite'); ?>
                    </div>
                </td>
            </tr>
            <?php
        }


        public function poe_add_ribbon_coupon_block()
        {
            $emoji_picker = get_option('po_gen_emojis', true);
            $label_text = get_option('po_gen_text', __('Personalised ribbon', 'piy-online-lite'));
            $ribbon_price = get_option('po_gen_price');
            $character_limit = get_option('po_gen_character_limit');
            $button_text = get_option('po_gen_button_text');
            $button_color = get_option('po_gen_button_color');
            $button_text_color = get_option('po_gen_button_text_color');

            if ($ribbon_price > 0) {
                $button_text = str_replace('{price}', strip_tags(wc_price($ribbon_price)), $button_text);
                $label_text = str_replace('{price}', strip_tags(wc_price($ribbon_price)), $label_text);
            } else {
                $button_text = str_replace('{price}', 'free', $button_text);
                $label_text = str_replace('{price}', 'free', $label_text);
            }

            ?>
            <div class="ribbon-coupon-block">
                <div class="ribbon-toggle">
                    <a href="#" class="show-ribbon-field">
                        <?php esc_html_e('Add a personalised ribbon? Click here', 'piy-online-lite'); ?>
                    </a>
                </div>

                <div class="ribbon-field" style="display: none;">
                    <label for="ribbon_text_input" class="label-personalised-ribbon">
                        <?php esc_html_e($label_text, 'piy-online-lite'); ?>
                        <input type="text"
                               id="ribbon_text_input"
                               placeholder="<?php esc_attr_e('Enter the message to be printed onto the ribbon…', 'piy-online-lite'); ?>"
                               class="emoji-field input-text"
                               data-emoji-picker="<?php echo esc_attr($emoji_picker); ?>"
                               autocomplete="off"
                               <?php if ($character_limit) : ?>maxlength="<?php echo esc_attr($character_limit); ?>"<?php endif; ?>
                        />
                    </label>
                    <?php if ('yes' == $emoji_picker) : ?>
                        <div id="emoji-picker"></div>
                    <?php endif; ?>
                    <button type="button" id="add_ribbon_btn" class="button" style="background-color: <?php echo esc_attr($button_color); ?>; color: <?php echo esc_attr($button_text_color); ?>;">
                        <?php esc_html_e($button_text, 'piy-online-lite'); ?>
                    </button>
                    <button type="button" id="remove_ribbon_btn" class="button">
                        <?php esc_html_e('Remove', 'piy-online-lite'); ?>
                    </button>
                </div>
                <div class="ribbon-error-message" style="display: none; color: red;">
                    <?php esc_html_e('Please enter a message for the ribbon.', 'piy-online-lite'); ?>
                </div>
            </div>
            <?php
        }

        public function poe_clear_ribbon_session_data() {
            if (WC()->session) {
                WC()->session->__unset('chosen_ribbon');
                WC()->session->__unset('ribbon_text');
            }
        }

        public function poe_add_cart_item_fee($cart)
        {
            if (WC()->session->get('chosen_ribbon')) {
                $price = (float) get_option('po_gen_price');
                $ribbon_text = WC()->session->get('ribbon_text', '');

                $fee_id = 'personalised_ribbon_fee';

                if ($ribbon_text) {
                    $fee_name = $price > 0
                        ? sprintf(__('Personalised ribbon: "%s"', 'piy-online-lite'), $ribbon_text)
                        : sprintf(__('Personalised ribbon: "%s" (free)', 'piy-online-lite'), $ribbon_text);
                } else {
                    $fee_name = $price > 0
                        ? __('Personalised ribbon', 'piy-online-lite')
                        : __('Personalised ribbon (free)', 'piy-online-lite');
                }

                // Додаємо плату навіть якщо вона 0, щоб показувалося "free"
                $cart->add_fee($fee_name, $price, true, '');
                WC()->session->set('ribbon_fee_id', $fee_id);
            }
        }
    }

    new POE_Front();
}
