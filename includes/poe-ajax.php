<?php

if (!defined('ABSPATH')) {
	exit;
}

class POE_Ajax {


	public function __construct() {

        add_action('wp_ajax_poe_submit', array($this, 'poe_submit'), 20);
        add_action('wp_ajax_nopriv_poe_submit', array($this, 'poe_submit'), 20);

        add_action('wp_ajax_poe_remove_ribbon', array($this, 'poe_remove_ribbon'));
        add_action('wp_ajax_nopriv_poe_remove_ribbon', array($this, 'poe_remove_ribbon'));

        add_action('wp_ajax_poe_remove_ribbon_fee', array($this, 'poe_remove_ribbon_fee'));
        add_action('wp_ajax_nopriv_poe_remove_ribbon_fee', array($this, 'poe_remove_ribbon_fee'));
	}

    public function poe_submit() {
        // Перевіряємо, чи запит був відправлений через POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            wp_send_json_error([
                'message' => __('Invalid request method.', 'piy-online-lite')
            ], 405);
        }

        // Перевірка nonce, якщо ви використовуєте його в формі (рекомендується)
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'poe-ajax-nonce')) {
            wp_send_json_error([
                'message' => __('Security check failed. Please refresh the page and try again.', 'piy-online-lite')
            ], 403);
        }

        $ribbon_text = isset($_POST['ribbon_text']) ? sanitize_text_field($_POST['ribbon_text']) : '';

        if (empty($ribbon_text)) {
            wc_add_notice(__('Ribbon text is required.', 'piy-online-lite'), 'error');
            wp_send_json_error(['message' => __('Ribbon text is required.', 'piy-online-lite')]);
        }

        // Отримуємо ліміт символів із налаштувань або дефолтне значення
        $character_limit = intval(get_option('po_gen_character_limit', 50));

        if ($character_limit < 1 || $character_limit > 500) {
            $character_limit = 50; // Запобігаємо аномальним значенням
        }

        if (mb_strlen($ribbon_text) > $character_limit) {
            $error_message = sprintf(
                __('Ribbon text exceeds the character limit of %d characters.', 'piy-online-lite'),
                $character_limit
            );
            wc_add_notice($error_message, 'error');
            wp_send_json_error(['message' => $error_message]);
        }

        // Успішне збереження тексту стрічки в сесію
        WC()->session->set('chosen_ribbon', (bool) $_POST['add_ribbon']);
        WC()->session->set('ribbon_text', $ribbon_text);
        $success_message = sprintf(__('Ribbon text set to: "%s"', 'piy-online-lite'), $ribbon_text);
        wc_add_notice($success_message, 'success');

        wp_send_json_success([
            'message' => $success_message
        ]);
    }

    public function poe_remove_ribbon() {
        if (WC()->session) {
            WC()->session->__unset('chosen_ribbon');
            WC()->session->__unset('ribbon_text');
        }
        wp_die();
    }

    public function poe_remove_ribbon_fee() {
        if (WC()->session) {
            WC()->session->__unset('chosen_ribbon');
            WC()->session->__unset('ribbon_text');
            WC()->session->__unset('ribbon_fee_id');

            wc_add_notice(__('Personalised ribbon removed.', 'piy-online-lite'), 'notice');
        }
        wp_die();
    }
}
new POE_Ajax();
