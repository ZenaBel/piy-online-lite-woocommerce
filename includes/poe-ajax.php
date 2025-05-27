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

    public function poe_submit()
    {
        if (isset($_POST['add_ribbon'])) {
            WC()->session->set('chosen_ribbon', (bool) $_POST['add_ribbon']);

            wc_add_notice(__('Personalised ribbon added!', 'piy-online-lite'), 'success');
        }

        if (isset($_POST['ribbon_text'])) {
            WC()->session->set('ribbon_text', sanitize_text_field($_POST['ribbon_text']));
        }

        wp_die();
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
