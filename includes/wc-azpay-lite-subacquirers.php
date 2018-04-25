<?php
if (!defined('ABSPATH'))
	exit;

/**
 * WC AZPay Lite Subacquirers
 * Payment Gateway
 *
 * Provides a subacquirer payment method,
 * integrated with AZPay Gateway.
 *
 * @class 		WC_AZPay_Lite_Subacquirer
 * @extends		WC_Payment_Gateway
 */

class WC_AZPay_Lite_Subacquirer extends WC_Payment_Gateway {

	public $subacquirer_config = null;

	public function __construct() {

		$this->id           = 'azpay_lite_subacquirer';
		$this->icon         = null;
		$this->has_fields   = true;
		$this->method_title = 'AZPay - Intermediadores';
		$this->title = 'Intermediadores';

		$this->init_form_fields();
		$this->init_settings();

	    foreach ($this->settings as $setting_key => $value) {
	        $this->$setting_key = $value;
	    }

		add_action('admin_notices', array($this, 'admin_notices'));

		if (is_admin()) {

			wp_enqueue_style('azpay-lite', plugins_url('assets/css/style.css', plugin_dir_path(__FILE__)));

	        if (version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));
	        	add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'save_subacquirer_config'));
            } else {
                add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
	        	add_action('woocommerce_update_options_payment_gateways', array(&$this, 'save_subacquirer_config'));
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

		// Generate the HTML For the settings form.
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

			// AZPay Config
			'azpay_lite_title' => array(
				'title' => 'Woocommerce AZPay Lite - Intermediadores',
				'type'  => 'title'
			),
			'enabled' => array(
				'title'   => 'Habilitar/Desabilitar',
				'type'    => 'checkbox',
				'default' => 'yes'
			),

			'subacquirer_form_title' => array(
				'title'       => 'Título',
				'type'        => 'text',
				'description' => 'Título da forma de pagamento que aparece para o usuário',
				'desc_tip'    => true,
			),

			'subacquirer_form_description' => array(
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
            'in_production' => array(
				'title'       => 'Em Produção',
				'type'        => 'checkbox',
				'default'     => 'no',
				'description' => 'Utilizar o ambiente de produção da AZPay. Se desmarcada, será utilizado o ambiente de homologação da AZPay.',
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
			// Subacquirer
            'subacquirer_title' => array(
				'title' => 'Configurando intermediadores',
				'type'  => 'title'
			),
            'pagseguro' => array(
				'title'       => 'PagSeguro',
				'type'        => 'select',
				'desc_tip'    => true,
				'default'     => '0',
				'options'     => array(
					'0' => 'Desabilitado',
                    '1' => 'PagSeguro',
                    '2' => 'PagSeguro Checkout'
				)
			),
            'paypal' => array(
				'title'       => 'PayPal',
				'type'        => 'select',
				'desc_tip'    => true,
				'default'     => '0',
				'options'     => array(
					'0' => 'Desabilitado',
                    '1' => 'Habilitado'
				)
			),

		);

		$this->form_fields = $fields;
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

			$flag = $_POST['azpaylte_dc_form_flag'];
			$name = $_POST['azpaylte_dc_form_name'];
			$number = $_POST['azpaylte_dc_form_number'];
			$validate = explode('/', $_POST['azpaylte_dc_form_validate']);
			$cvv = $_POST['azpaylte_dc_form_cvv'];

			$az_pay = new AZPay($this->merchant_id, $this->merchant_key);
			$az_pay->curl_timeout = 60;
			$az_pay->config_order['reference'] = $order_id;
			$az_pay->config_order['totalAmount'] = $customer_order->order_total;
			$az_pay->config_options['urlReturn'] = esc_url( home_url( '/azpay' ) );

			$az_pay->config_card_payments['amount'] = $customer_order->order_total;

			$acquirer = $flag . '_acquirer';
			$az_pay->config_card_payments['acquirer'] = $this->$acquirer;
			$az_pay->config_card_payments['method'] = 4;
			$az_pay->config_card_payments['flag'] = $flag;
			$az_pay->config_card_payments['numberOfPayments'] = 1;
			$az_pay->config_card_payments['cardHolder'] = $name;
			$az_pay->config_card_payments['cardNumber'] = $number;
			$az_pay->config_card_payments['cardSecurityCode'] = $cvv;
			$az_pay->config_card_payments['cardExpirationDate'] = $validate[1].$validate[0];

			$az_pay->config_billing['customerIdentity'] = $customer_order->user_id;
			$az_pay->config_billing['name'] = $customer_order->billing_first_name . ' ' . $customer_order->billing_last_name;
			$az_pay->config_billing['address'] = $customer_order->billing_address_1;
			$az_pay->config_billing['city'] = $customer_order->billing_city;
			$az_pay->config_billing['state'] = $customer_order->billing_state;
			$az_pay->config_billing['postalCode'] = $customer_order->billing_postcode;
			$az_pay->config_billing['country'] = $customer_order->billing_country;
			$az_pay->config_billing['phone'] = $customer_order->billing_phone;
			$az_pay->config_billing['email'] = $customer_order->billing_email;

			$payment_method_config = get_option( 'woocommerce_azpay_lite_subacquirer_settings' );

            if (!isset($payment_method_config['in_production']) || empty($payment_method_config['in_production']) || $payment_method_config['in_production'] == 'no') {
                $az_pay->in_production = false; 
            } else {
                $az_pay->in_production = true;
            }


			// XML to log
			$xml_log = clone $az_pay;
			$xml_log->merchant['id'] = NULL;
			$xml_log->merchant['key'] = NULL;
			$xml_log->config_card_payments['cardNumber'] = preg_replace('/[0-9]/', 'X', $xml_log->config_card_payments['cardNumber']);
			$xml_log->config_card_payments['cardSecurityCode'] = preg_replace('/[0-9]/', 'X', $xml_log->config_card_payments['cardSecurityCode']);

			// Log XML
			$azpay_log = $wpdb->prefix.'azpay_log';
			$wpdb->insert(
				$azpay_log,
				array(
					'datetime' => current_time('mysql'),
					'keylog' => 'SALE_XML',
					'orderid' => $order_id,
					'content' => $xml_log->sale()->getXml(),
				)
			);

			// Execute authorize
			$az_pay->authorize()->execute();

			$gateway_response = $az_pay->response();

			if ($gateway_response == null)
				throw new Exception('Problemas ao obter resposta sobre pagamento.');

			if ($gateway_response->status != Config::$STATUS['AUTHORIZED']) {
				throw new Exception(Config::$STATUS_MESSAGES[(int)$gateway_response->status]['title'], 1);
			}

			$customer_order->add_order_note("Pagamento autorizado pela operadora. AZPay TID: {$gateway_response->transactionId}");
			$customer_order->payment_complete();
			$customer_order->update_status('on-hold', 'Aguardando pagamento');

			$woocommerce->cart->empty_cart();

			// Log Response
			$wpdb->insert(
				$azpay_log,
				array(
					'datetime' => current_time('mysql'),
					'keylog' => 'SALE_RESPONSE',
					'orderid' => $order_id,
					'content' => json_encode($gateway_response),
				)
			);

			$response = array(
				'result'   => 'success',
				'redirect' => $url = $this->get_return_url($customer_order)
			);

		} catch (Exception $e) {

			$code = $az_pay->getCurlErrorCode();

		 	// cURL error (Timeout)
			if ($e instanceof AZPay_Curl_Exception && $code == 28) {

				$customer_order->update_status('processing', 'Aguardando confirmação de pagamento');
				$woocommerce->cart->empty_cart();

				$response = array(
					'result'   => 'success',
					'redirect' => $url = $this->get_return_url($customer_order)
				);

			} else {

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
						'keylog' => 'SALE_ERROR',
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

		return $response;
	}


	public function currency_not_supported_message() {
		echo '<div class="error"><p><strong>AZPay Lite - Intermediadores</strong>: Moeda não aceita</p></div>';
	}


	/**
	 * Save the configuration of Debitcards
	 * @return [type] [description]
	 */
	public function save_Subacquirer_config() {

		if (isset($_POST['woocommerce_azpay_lite_subacquirer']))
			update_option('woocommerce_azpay_lite_subacquirer', json_encode($this->filterData($_POST['woocommerce_azpay_lite_subacquirer'])));
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
			$order      = new WC_Order($order_id);
			$cart_total = (float) $order->get_total();
		} elseif ($woocommerce->cart->total > 0) {
			$cart_total = (float) $woocommerce->cart->total;
		}

		echo $this->description;

		$html_path = apply_filters('wc_azpay_subacquirer_form', plugin_dir_path(__FILE__) . 'views/html-form-subacquirer.php');

		if (file_exists($html_path))
			include_once($html_path);
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
