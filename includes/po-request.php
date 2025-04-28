<?php
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Send Requests 
 */
class PO_Request {
	
	public $base_url = 'https://piyribbons.com/dashboard/api';

	public function po_get_headers() {
		$key = empty(get_option('po_api_key')) ? '7|TjUM1uKToHAg0MdXa0ArFkGqczEJoOJtPYW5dLes' : get_option('po_api_key');

		// Log the API key (masked for security)
		$this->po_debug_console('API Key', substr($key, 0, 5) . '...');

		return array(
			'Authorization' => 'Bearer ' . $key,
			'Content-Type' => 'application/json'
		);
	}

	public function po_get_temp_fonts() {
		$templates[''] = esc_html__('Select Template', 'piy-online');
		$fonts['']     = esc_html__('Select Font', 'piy-online');

		$templateResponse = wp_safe_remote_get(
			$this->base_url . '/templates',
			array(
				'headers' => $this->po_get_headers()
			)
		);

		$this->po_debug_console('Template Response', $templateResponse);

		$fontsResponse = wp_safe_remote_get(
			$this->base_url . '/fonts',
			array(
				'headers' => $this->po_get_headers()
			)
		);

		$this->po_debug_console('Font Response', $fontsResponse);

		if (!is_wp_error($templateResponse)) {

			$tbody = wp_remote_retrieve_body($templateResponse);
			$tcode = wp_remote_retrieve_response_code($templateResponse);

			$this->po_debug_console('Template Response Body', $tbody);
			$this->po_debug_console('Template Response Code', $tcode);

			if (200 == $tcode) {
				$templatesD = (array)json_decode($tbody, ARRAY_A);

				foreach ($templatesD as $template) {
					$templates[$template['id']] = esc_html__($template['name'], 'piy-online');
				}
			}
		}
		
		if (!is_wp_error($fontsResponse)) {

			$fbody = wp_remote_retrieve_body($fontsResponse);
			$fcode = wp_remote_retrieve_response_code($fontsResponse);

			$this->po_debug_console('Font Response Body', $fbody);
			$this->po_debug_console('Font Response Code', $fcode);

			if (200 == $fcode) {
				$fontsD = (array)json_decode($fbody, ARRAY_A);

				foreach ($fontsD as $font) {
					if (1 == $font['id']) {
						continue;
					}
					
					$fonts[$font['id']] = $font['family'];
				}
			}
		}

		$this->po_debug_console('Templates and Fonts', array('templates' => $templates, 'fonts' => $fonts));

		return array('templates' => $templates, 'fonts' => $fonts);
	}

	public function po_get_fonts() {
		$response = wp_safe_remote_get(
			$this->base_url . '/fonts',
			array(
				'headers' => $this->po_get_headers()
			)
		);

		$this->po_debug_console('Fonts Response', $response);

		return $response;
	}

	public function piy_send_payload($payload, $endpoint, $method) {
		$this->po_debug_console('Payload', $payload);
		$this->po_debug_console('Endpoint', $endpoint);
		$this->po_debug_console('Method', $method);

		$response = wp_remote_request(
			$this->base_url . $endpoint, 
			array(
				'method' 	=> $method,
				'headers' 	=> $this->po_get_headers(),
				'body' 		=> wp_json_encode($payload)
			)
		);

		$this->po_debug_console('Response', $response);

		return $response;
	}

	private function po_debug_console($label, $data) {
		$script = "
			console.log('%c{$label}:', 'color: green; font-weight: bold;', " . json_encode($data) . ");
		";

		add_action('wp_footer', function() use ($script) {
			echo "<script>{$script}</script>";
		});
	}
}

new PO_Request();
