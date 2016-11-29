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

    var_dump($order_id);exit;

  }

}
