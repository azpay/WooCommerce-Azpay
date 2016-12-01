<?php
if (!defined('ABSPATH'))
	exit;

/**
 * WC AZPay Lite Pagcoin
 * Payment Gateway
 *
 * Provides a pagcoin payment method,
 * integrated with AZPay Gateway.
 *
 * @class 		WC_AZPay_Lite_Pagcoin
 * @extends		WC_Payment_Gateway
 */

class WC_AZPay_Lite_Pagcoin extends WC_Payment_Gateway {

  public function __construct() {

    $this->id = 'azpay_lite_pagcoin';
    $this->icon         = null;
		$this->has_fields   = true;
    $this->method_title = 'AZPay Lite - Pagcoin';
		$this->title = 'Pagcoin';

    $this->init_form_fields();
		$this->init_settings();

    foreach ($this->settings as $setting_key => $value) {
        $this->$setting_key = $value;
    }

    if (version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'save_pagcoin_config'));
    } else {
        add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
        add_action('woocommerce_update_options_payment_gateways', array(&$this, 'save_pagcoin_config'));
    }

  }

  /**
	 * Start Gateway Settings Form Fields.
	 *
	 * @return void
	 */
  public function init_form_fields() {

    $fields = array(

			'azpay_lite_title' => array(
				'title' => 'Woocommerce AZPay Lite - Pagcoin',
				'type'  => 'title'
			),
			'enabled' => array(
				'title'   => 'Habilitar/Desabilitar',
				'type'    => 'checkbox',
				'default' => 'no'
			),
			'pagcoin_form_description' => array(
				'title'       => 'Descrição',
				'type'        => 'textarea',
				'description' => 'Descrição da forma de pagamento que aparece para o usuário',
				'desc_tip'    => true,
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

    );

    $this->form_fields = $fields;

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
	 * Save the configuration of Pagcoin
	 * @return [type] [description]
	 */
	public function save_pagcoin_config() {

		if (isset($_POST['woocommerce_azpay_lite_pagcoin']))
			update_option('woocommerce_azpay_lite_pagcoin', json_encode($this->filterData($_POST['woocommerce_azpay_lite_pagcoin'])));

  }

  /**
	 * Payment fields.
	 *
	 * @return string
	 */
	public function payment_fields() {

    echo $this->pagcoin_form_description;

  }

  /**
	 * Process that use AZPay SDK
	 * @param  [type] $order_id [description]
	 * @return [type]           [description]
	 */
	public function process_payment($order_id) {

		global $woocommerce;

		try {

			$customer_order = new WC_Order($order_id);

			$az_pay = new AZPay($this->merchant_id, $this->merchant_key);

			$az_pay->curl_timeout = 60;

			$az_pay->config_order['reference'] = $order_id;
			$az_pay->config_order['totalAmount'] = $customer_order->order_total;

			$az_pay->config_pagcoin_payments['amount'] = $customer_order->order_total;

			$az_pay->config_billing['customerIdentity'] = $customer_order->user_id;
			$az_pay->config_billing['name'] = $customer_order->billing_first_name . ' ' . $customer_order->billing_last_name;
			$az_pay->config_billing['address'] = $customer_order->billing_address_1;
			$az_pay->config_billing['city'] = $customer_order->billing_city;
			$az_pay->config_billing['state'] = $customer_order->billing_state;
			$az_pay->config_billing['postalCode'] = $customer_order->billing_postcode;
			$az_pay->config_billing['country'] = $customer_order->billing_country;
			$az_pay->config_billing['phone'] = $customer_order->billing_phone;
			$az_pay->config_billing['email'] = $customer_order->billing_email;

			$az_pay->config_options['urlReturn'] = esc_url( home_url( '/azpay' ) );

			$az_pay->pagcoin()->execute();

			$gateway_response = $az_pay->response();

			print_r($gateway_response);exit;

		} catch (Exception $e) {

			$error = $az_pay->responseError();
			throw new Exception('Erro ao processar pagamento: ' . $error['error_message'], 1);
			
		}


  }

}
