<?php
if (!defined('ABSPATH'))
	exit;

/**
 * WC AZPay Lite Boleto
 * Payment Gateway
 *
 * Provides a boleto payment method,
 * integrated with AZPay Gateway.
 *
 * @class 		WC_AZPay_Lite_Boleto
 * @extends		WC_Payment_Gateway
 * @author 		Gabriel Guerreiro (gabrielguerreiro.com)
 */

class WC_AZPay_Lite_Boleto extends WC_Payment_Gateway {

	public function __construct() {

		$this->id           = 'azpay_lite_boleto';
		$this->icon         = null;
		$this->has_fields   = true;
		$this->method_title = 'AZPay Lite - Boleto Bancário';
		$this->title = 'Boleto Bancário';

		$this->init_form_fields();
		$this->init_settings();

		foreach ($this->settings as $setting_key => $value) {
	        $this->$setting_key = $value;
	    }

		add_action('admin_notices', array($this, 'admin_notices'));
		add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));

		if (is_admin()) {
			wp_enqueue_style('azpay', plugins_url('assets/css/style.css', plugin_dir_path(__FILE__)));

	        if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));
	        	add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'save_boleto_config'));
            } else {
                add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
	        	add_action('woocommerce_update_options_payment_gateways', array(&$this, 'save_boleto_config'));
            }
	    }

	}

	/**
	 * Displays notifications when the admin has something wrong with the configuration.
	 *
	 * @return void
	 */
	public function admin_notices() {
		if (is_admin()) {
			if (get_woocommerce_currency() != 'BRL')
				add_action('admin_notices', array($this, 'currency_not_supported_message'));
		}
	}


	/**
	 * Admin Panel Options.
	 *
	 * @return string Admin form.
	 */
	public function admin_options() {

		// Generate the HTML For the settings form
		echo '<table class="azpay-form-admin">';
			$this->generate_settings_html();
		echo '</table>';
	}

	/**
	 * Start Gateway Settings Form Fields.
	 *
	 * @return void
	 */
	public function init_form_fields() {

		$fields = array(

			'azpay_lite_title' => array(
				'title' => 'Woocommerce AZPay Lite - Boleto Bancário',
				'type'  => 'title'
			),

			'enabled' => array(
				'title'   => 'Habilitar/Desabilitar',
				'type'    => 'checkbox',
				'default' => 'yes'
			),
			'boleto_form_title' => array(
				'title'       => 'Título',
				'type'        => 'text',
				'description' => 'Título da forma de pagamento que aparece para o usuário',
				'desc_tip'    => true,
			),

			'boleto_form_description' => array(
				'title'       => 'Descrição',
				'type'        => 'textarea',
				'description' => 'Descrição da forma de pagamento que aparece para o usuário',
				'desc_tip'    => true,
			),

			// AZPay Config
			'config' => array(
				'title' => 'Configurando AZPay',
				'type'  => 'title'
			),
			'merchant_id' => array(
				'title'       => 'Merchant ID',
				'type'        => 'text',
				'description' => 'ID da sua conta no AZPay',
				'desc_tip'    => true,
			),
			'merchant_key' => array(
				'title'       => 'Merchant Key',
				'type'        => 'text',
				'description' => 'Chave da sua conta no AZPay',
				'desc_tip'    => true,
			),
			'auto_capture' => array(
				'title'       => 'Captura Automática',
				'type'        => 'checkbox',
				'default'     => 'no',
				'description' => 'Essa opção ativa a mudança automática do status do pedido, a partir das respostas do AZPay',				
			),

			// Boleto Config
			'boleto_config' => array(
				'title' => 'Configurando Boleto Bancário',
				'type'  => 'title'
			),
			'boleto_acquirer' => array(
				'title'       => 'Banco',
				'type'        => 'select',
				'desc_tip'    => true,
				'description' => 'Selecione o banco',
				'default'     => '0',
				'options'     => array(
					'10' => 'Bradesco (sem registro)',
					'18' => 'BradescoNET',
					'11' => 'Itaú (sem registro)',
					'20' => 'Itaú Shopline',
					'12' => 'Banco do Brasil',
					'13' => 'Banco Santander',
					'14' => 'Caixa (sem registro)',
					'15' => 'Caixa (Sinco)',
					'16' => 'Caixa (SIGCB)',
					'17' => 'HSBC',
				)
			),
			'boleto_discount' => array(
				'title'       => 'Desconto (%)',
				'type'        => 'number',
				'description' => 'Desconto aplicado no pagamento com boleto.',
				'desc_tip'    => true,
			),
			'boleto_validate' => array(
				'title'       => 'Vencimento (dias)',
				'type'        => 'number',
				'description' => 'Data de vencimento em dias, a partir do momento da compra.',
				'desc_tip'    => true,
			),
			'boleto_instructions' => array(
				'title'       => 'Instruções',
				'type'        => 'textarea',
				'description' => 'Instrções que irão ser adicionadas ao boleto',
				'desc_tip'    => true,
			),

		);

		$this->form_fields = $fields;
	}


	public function currency_not_supported_message() {
		echo '<div class="error"><p><strong>AZPay Lite - Boleto Bancário</strong>: Moeda não aceita</p></div>';
	}


	/**
	 * Save the configuration
	 * @return [type] [void]
	 */
	public function save_boleto_config() {

		if (isset($_POST['woocommerce_azpay_boleto']))
			update_option('woocommerce_azpay_boleto', json_encode($this->filterData($_POST['woocommerce_azpay_boleto'])));

	}


	/**
	 * Process that use AZPay SDK
	 * @param  [type] $order_id [description]
	 * @return [type]           [description]
	 */
	public function process_payment($order_id) {

		global $woocommerce, $wpdb;

		 try {

		 	$customer_order = new WC_Order($order_id);

			// Calculate total
			$total = ($this->boleto_discount == 0) ? $customer_order->order_total : number_format($this->apply_discount($customer_order->order_total), 2, '.', '');

			// Set new total value
			$customer_order->set_total($total);

			$az_pay = new AZPay($this->merchant_id, $this->merchant_key);
			$az_pay->curl_timeout = 60;
			$az_pay->config_order['reference'] = $order_id;
			$az_pay->config_order['totalAmount'] = str_replace('.', '', $total);
			$az_pay->config_options['urlReturn'] = esc_url( home_url( '/azpay' ) );

			$az_pay->config_boleto['acquirer'] = $this->boleto_acquirer;
			$az_pay->config_boleto['expire'] = date('Y-m-d', strtotime('+ '.$this->boleto_validate.' days'));
			$az_pay->config_boleto['nrDocument'] = str_pad($order_id, 9, '0', STR_PAD_LEFT);
			$az_pay->config_boleto['amount'] = str_replace('.', '', $total);
			$az_pay->config_boleto['instructions'] = $this->boleto_instructions;

			$az_pay->config_billing['customerIdentity'] = $customer_order->user_id;
			$az_pay->config_billing['name'] = $customer_order->billing_first_name . ' ' . $customer_order->billing_last_name;
			$az_pay->config_billing['address'] = $customer_order->billing_address_1;
			$az_pay->config_billing['city'] = $customer_order->billing_city;
			$az_pay->config_billing['state'] = $customer_order->billing_state;
			$az_pay->config_billing['postalCode'] = $customer_order->billing_postcode;
			$az_pay->config_billing['country'] = $customer_order->billing_country;
			$az_pay->config_billing['phone'] = $customer_order->billing_phone;
			$az_pay->config_billing['email'] = $customer_order->billing_email;

			// XML to log
			$xml_log = clone $az_pay;
			$xml_log->merchant['id'] = NULL;
			$xml_log->merchant['key'] = NULL;

			// Log XML
			$azpay_log = $wpdb->prefix.'azpay_log';
			$wpdb->insert(
				$azpay_log,
				array(
					'datetime' => current_time('mysql'),
					'keylog' => 'BOLETO_XML',
					'orderid' => $order_id,
					'content' => $xml_log->boleto()->getXml(),
				)
			);

			// Execute
			$az_pay->boleto()->execute();

			// Response
			$gateway_response = $az_pay->response();

			if (empty($gateway_response))
				throw new Exception('Problemas ao obter resposta sobre pagamento.');

			if ($gateway_response->status != Config::$STATUS['GENERATED']) {
				$error = $az_pay->responseError();
				throw new Exception('Pagamento não Autorizado: ' . $error['error_message'], 1);
			}

			$customer_order->add_order_note('Aguardando pagamento do Boleto. AZPay TID: ' . $gateway_response->transactionId);
			$customer_order->add_order_note('Link do Boleto: ' . $gateway_response->processor->Boleto->details->urlBoleto);
			$customer_order->update_status('pending', 'Aguardando pagamento do boleto');

			$woocommerce->cart->empty_cart();

			// Log Response
			$wpdb->insert(
				$azpay_log,
				array(
					'datetime' => current_time('mysql'),
					'keylog' => 'BOLETO_PAYMENT',
					'orderid' => $order_id,
					'content' => json_encode($gateway_response),
				)
			);

			// Return thankyou redirect.
			return array(
				'result'   => 'success',
				'redirect' => $url = $this->get_return_url($customer_order)
			);

		 } catch (Exception $e) {

		 	// Error = 0 from SDK
			if ($e->getCode() == 0) {
				$error = $az_pay->responseError();
				$message = $error['error_message'] . ' (' . $error['error_code'] . ' - ' . $error['error_moreInfo'] . ')';
			} else {
				$message = $e->getMessage();
			}
			
			$this->add_error($message);

			// Log Error
			$wpdb->insert( 
				$azpay_log, 
				array( 
					'datetime' => current_time('mysql'),
					'keylog' => 'BOLETO_ERROR',
					'orderid' => $order_id,
					'content' => json_encode($error),
				) 
			);

			$response = array(
				'result'   => 'fail',
				'redirect' => ''
			);
		}

		
	}


	/**
	 * Payment fields.
	 *
	 * @return string
	 */
	public function payment_fields() {
		global $woocommerce;

		$cart_total = 0;
		if (defined('WC_VERSION') && version_compare(WC_VERSION, '2.1', '>=')) {
			$order_id = absint(get_query_var('order-pay'));
		} else {
			$order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
		}

		if ($order_id > 0) {
			$order = new WC_Order($order_id);
			$cart_total = (float) $order->get_total();
		} elseif ($woocommerce->cart->total > 0) {
			$cart_total = (float) $woocommerce->cart->total;
		}

		echo $this->description;

		$html_path = apply_filters('wc_azpay_boleto_form', plugin_dir_path(__FILE__) . 'views/html-form-boleto.php');

		if (file_exists($html_path))
			include_once($html_path);

	}


	/**
	 * Thank you page
	 *
	 * @return string
	 */
	public function thankyou_page($order_id) {
		global $woocommerce;
		global $wpdb;

		$azpay_log = $wpdb->prefix.'azpay_log';
		$log = $wpdb->get_row("SELECT * FROM $azpay_log WHERE orderid = $order_id AND keylog = 'BOLETO_PAYMENT'");
		$json_azpay = json_decode($log->content);

		$order = new WC_Order($order_id);
		if ( defined('WC_VERSION') && version_compare(WC_VERSION, '2.1', '>=') ) {
			$order_url = $order->get_view_order_url();
		} else {
			$order_url = add_query_arg( 'order', $order_id, get_permalink( woocommerce_get_page_id( 'view_order' ) ) );
		}

		if ($order->status == 'on-hold')
			echo '<div class="woocommerce-info">Clique aqui para pagar: <a href="' . $json_azpay->processor->Boleto->details->urlBoleto . '" target="_blank">Link do Boleto</a></div>';

	}


	/**
	 * Return the AZPay TID
	 * @param  [type] $oder_id [description]
	 * @return [type]          [description]
	 */
	public static function get_tid($order_id) {
		global $wpdb;

		$azpay_log = $wpdb->prefix.'azpay_log';
		$log = $wpdb->get_row("SELECT * FROM $azpay_log WHERE orderid = $order_id AND keylog = 'BOLETO_PAYMENT'");
		$json_azpay = json_decode($log->content);

		return $json_azpay->transactionId;
	}


	/**
	 * Return Boleto's URL
	 * @param  [type] $order_id [description]
	 * @return [type]           [description]
	 */
	public static function get_boleto_url($order_id) {
		global $wpdb;

		$azpay_log = $wpdb->prefix.'azpay_log';
		$log = $wpdb->get_row("SELECT * FROM $azpay_log WHERE orderid = $order_id AND keylog = 'BOLETO_PAYMENT'");
		$json_azpay = json_decode($log->content);

		return $json_azpay->processor->Boleto->details->urlBoleto;
	}


	/**
	 * Get the value from discount
	 * @param  [type] $cart_total [Value cart]
	 * @return [type]             [description]
	 */
	public function get_discount($cart_total) {
		return ($cart_total * $this->boleto_discount) / 100;
	}

	/**
	 * Apply the discount
	 * @param  [type] $cart_total [description]
	 * @return [type]             [description]
	 */
	public function apply_discount($cart_total) {
		return $cart_total - $this->get_discount($cart_total);
	}


	/**
	 * Add an error
	 * @param [type] $message [description]
	 */
	public function add_error($message) {
		global $woocommerce;

		if (function_exists('wc_add_notice')) {
			wc_add_notice($message, 'error');
		} else {
			$woocommerce->add_error($message);
		}
	}


}