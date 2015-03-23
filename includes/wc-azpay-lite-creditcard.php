<?php
/**
 * WC AZPay Gateway
 *
 */

class WC_AZPay_Lite_Creditcard extends WC_Payment_Gateway {

	public $creditcard_config = null;
	
	public function __construct() {

		include_once(plugin_dir_path(__FILE__) . '../vendors/azpay-php-sdk/azpay.php');

		$this->id           = 'azpay_lite_creditcard';
		$this->icon         = null;
		$this->has_fields   = true;
		$this->method_title = 'AZPay Lite - Cartão de Crédito';
		$this->title = 'Cartão de Crédito';		

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
	        	add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'save_creditcard_config'));
            } else {
                add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
	        	add_action('woocommerce_update_options_payment_gateways', array(&$this, 'save_creditcard_config'));
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
			echo '<a href="http://www.azpay.com.br" target="_blank" class="ad-image"><img src="'.plugins_url('/assets/img/ad.png', plugin_dir_path( __FILE__ )).'" /></a>';
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
				'title' => 'Woocommerce AZPay Lite - Cartão de Crédito',
				'type'  => 'title'
			),
			'enabled' => array(
				'title'   => 'Habilitar/Desabilitar',
				'type'    => 'checkbox',
				'default' => 'yes'
			),

			'creditcard_form_title' => array(
				'title'       => 'Título',
				'type'        => 'text',
				'description' => 'Título da forma de pagamento que aparece para o usuário',
				'desc_tip'    => true,
			),

			'creditcard_form_description' => array(
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

			// Creditcard
			'creditcard_title' => array(
				'title' => 'Configurando bandeiras e operadoras',
				'type'  => 'title'
			),

			// Visa
			'visa_title' => array(
				'title' => 'Visa',
				'type'  => 'title'
			),
			'visa_acquirer' => array(
				'title'       => 'Operadora / Adquirente',
				'type'        => 'select',
				'desc_tip'    => true,
				'description' => 'Configurar bandeira Visa',
				'default'     => '0',
				'options'     => array(
					'0' => 'Bandeira Desabilitada',
					'1' => 'Cielo - Buy Page Loja',
					'2' => 'Cielo - Buy Page Cielo',
					'3' => 'Redecard - Komerci WebService',
					'4' => 'Redecard - Komerci Integrado',
					'6' => 'Elavon'
				)
			),
			'visa_parcel_min' => array(
				'title'       => 'Parcela mínima',
				'type'        => 'text',
				'description' => 'Valor mínimo aceito para parcelamento com esta bandeira',
				'desc_tip'    => true,
			),
			'visa_parcels' => array(
				'title'       => 'Máximo de parcelas',
				'type'        => 'select',
				'desc_tip'    => true,
				'description' => 'Selecione o máximo de parcelas aceitas por esta bandeira',
				'default'     => '0',
				'options'     => array(
					'1' => '1x',
					'2' => '2x',
					'3' => '3x',
					'4' => '4x',
					'5' => '5x',
					'6' => '6x',
					'7' => '7x',
					'8' => '8x',
					'9' => '9x',
					'10' => '10x',
					'11' => '11x',
					'12' => '12x',
				)
			),

			// Mastercard
			'mastercard_title' => array(
				'title' => 'Mastercard',
				'type'  => 'title'
			),
			'mastercard_acquirer' => array(
				'title'       => 'Operadora / Adquirente',
				'type'        => 'select',
				'desc_tip'    => true,
				'description' => 'Configurar bandeira Mastercard',
				'default'     => '0',
				'options'     => array(
					'0' => 'Bandeira Desabilitada',
					'1' => 'Cielo - Buy Page Loja',
					'2' => 'Cielo - Buy Page Cielo',
					'3' => 'Redecard - Komerci WebService',
					'4' => 'Redecard - Komerci Integrado',
					'6' => 'Elavon'
				)
			),
			'mastercard_parcel_min' => array(
				'title'       => 'Parcela mínima',
				'type'        => 'text',
				'description' => 'Valor mínimo aceito para parcelamento com esta bandeira',
				'desc_tip'    => true,
			),
			'mastercard_parcels' => array(
				'title'       => 'Máximo de parcelas',
				'type'        => 'select',
				'desc_tip'    => true,
				'description' => 'Selecione o máximo de parcelas aceitas por esta bandeira',
				'default'     => '0',
				'options'     => array(
					'1' => '1x',
					'2' => '2x',
					'3' => '3x',
					'4' => '4x',
					'5' => '5x',
					'6' => '6x',
					'7' => '7x',
					'8' => '8x',
					'9' => '9x',
					'10' => '10x',
					'11' => '11x',
					'12' => '12x',
				)
			),

			// Amex
			'amex_title' => array(
				'title' => 'Amex',
				'type'  => 'title'
			),
			'amex_acquirer' => array(
				'title'       => 'Operadora / Adquirente',
				'type'        => 'select',
				'desc_tip'    => true,
				'description' => 'Configurar bandeira Amex',
				'default'     => '0',
				'options'     => array(
					'0' => 'Bandeira Desabilitada',
					'1' => 'Cielo - Buy Page Loja',
					'2' => 'Cielo - Buy Page Cielo',
				)
			),
			'amex_parcel_min' => array(
				'title'       => 'Parcela mínima',
				'type'        => 'text',
				'description' => 'Valor mínimo aceito para parcelamento com esta bandeira',
				'desc_tip'    => true,
			),
			'amex_parcels' => array(
				'title'       => 'Máximo de parcelas',
				'type'        => 'select',
				'desc_tip'    => true,
				'description' => 'Selecione o máximo de parcelas aceitas por esta bandeira',
				'default'     => '0',
				'options'     => array(
					'1' => '1x',
					'2' => '2x',
					'3' => '3x',
					'4' => '4x',
					'5' => '5x',
					'6' => '6x',
					'7' => '7x',
					'8' => '8x',
					'9' => '9x',
					'10' => '10x',
					'11' => '11x',
					'12' => '12x',
				)
			),

			// Diners
			'diners_title' => array(
				'title' => 'Diners',
				'type'  => 'title'
			),
			'diners_acquirer' => array(
				'title'       => 'Operadora / Adquirente',
				'type'        => 'select',
				'desc_tip'    => true,
				'description' => 'Configurar bandeira Diners',
				'default'     => '0',
				'options'     => array(
					'0' => 'Bandeira Desabilitada',
					'1' => 'Cielo - Buy Page Loja',
					'2' => 'Cielo - Buy Page Cielo',
				)
			),
			'diners_parcel_min' => array(
				'title'       => 'Parcela mínima',
				'type'        => 'text',
				'description' => 'Valor mínimo aceito para parcelamento com esta bandeira',
				'desc_tip'    => true,
			),
			'diners_parcels' => array(
				'title'       => 'Máximo de parcelas',
				'type'        => 'select',
				'desc_tip'    => true,
				'description' => 'Selecione o máximo de parcelas aceitas por esta bandeira',
				'default'     => '0',
				'options'     => array(
					'1' => '1x',
					'2' => '2x',
					'3' => '3x',
					'4' => '4x',
					'5' => '5x',
					'6' => '6x',
					'7' => '7x',
					'8' => '8x',
					'9' => '9x',
					'10' => '10x',
					'11' => '11x',
					'12' => '12x',
				)
			),

			// Discover
			'discover_title' => array(
				'title' => 'Discover',
				'type'  => 'title'
			),
			'discover_acquirer' => array(
				'title'       => 'Operadora / Adquirente',
				'type'        => 'select',
				'desc_tip'    => true,
				'description' => 'Configurar bandeira Discover',
				'default'     => '0',
				'options'     => array(
					'0' => 'Bandeira Desabilitada',
					'1' => 'Cielo - Buy Page Loja',
					'2' => 'Cielo - Buy Page Cielo',
				)
			),
			'discover_parcel_min' => array(
				'title'       => 'Parcela mínima',
				'type'        => 'text',
				'description' => 'Valor mínimo aceito para parcelamento com esta bandeira',
				'desc_tip'    => true,
			),
			'discover_parcels' => array(
				'title'       => 'Máximo de parcelas',
				'type'        => 'select',
				'desc_tip'    => true,
				'description' => 'Selecione o máximo de parcelas aceitas por esta bandeira',
				'default'     => '0',
				'options'     => array(
					'1' => '1x',
					'2' => '2x',
					'3' => '3x',
					'4' => '4x',
					'5' => '5x',
					'6' => '6x',
					'7' => '7x',
					'8' => '8x',
					'9' => '9x',
					'10' => '10x',
					'11' => '11x',
					'12' => '12x',
				)
			),

			// ELO
			'elo_title' => array(
				'title' => 'ELO',
				'type'  => 'title'
			),
			'elo_acquirer' => array(
				'title'       => 'Operadora / Adquirente',
				'type'        => 'select',
				'desc_tip'    => true,
				'description' => 'Configurar bandeira ELO',
				'default'     => '0',
				'options'     => array(
					'0' => 'Bandeira Desabilitada',
					'1' => 'Cielo - Buy Page Loja',
					'2' => 'Cielo - Buy Page Cielo',
				)
			),
			'elo_parcel_min' => array(
				'title'       => 'Parcela mínima',
				'type'        => 'text',
				'description' => 'Valor mínimo aceito para parcelamento com esta bandeira',
				'desc_tip'    => true,
			),
			'elo_parcels' => array(
				'title'       => 'Máximo de parcelas',
				'type'        => 'select',
				'desc_tip'    => true,
				'description' => 'Selecione o máximo de parcelas aceitas por esta bandeira',
				'default'     => '0',
				'options'     => array(
					'1' => '1x',
					'2' => '2x',
					'3' => '3x',
					'4' => '4x',
					'5' => '5x',
					'6' => '6x',
					'7' => '7x',
					'8' => '8x',
					'9' => '9x',
					'10' => '10x',
					'11' => '11x',
					'12' => '12x',
				)
			),

			// Aura
			'aura_title' => array(
				'title' => 'Aura',
				'type'  => 'title'
			),
			'aura_acquirer' => array(
				'title'       => 'Operadora / Adquirente',
				'type'        => 'select',
				'desc_tip'    => true,
				'description' => 'Configurar bandeira Aura',
				'default'     => '0',
				'options'     => array(
					'0' => 'Bandeira Desabilitada',
					'1' => 'Cielo - Buy Page Loja',
					'2' => 'Cielo - Buy Page Cielo',
				)
			),
			'aura_parcel_min' => array(
				'title'       => 'Parcela mínima',
				'type'        => 'text',
				'description' => 'Valor mínimo aceito para parcelamento com esta bandeira',
				'desc_tip'    => true,
			),
			'aura_parcels' => array(
				'title'       => 'Máximo de parcelas',
				'type'        => 'select',
				'desc_tip'    => true,
				'description' => 'Selecione o máximo de parcelas aceitas por esta bandeira',
				'default'     => '0',
				'options'     => array(
					'1' => '1x',
					'2' => '2x',
					'3' => '3x',
					'4' => '4x',
					'5' => '5x',
					'6' => '6x',
					'7' => '7x',
					'8' => '8x',
					'9' => '9x',
					'10' => '10x',
					'11' => '11x',
					'12' => '12x',
				)
			),

			// JCB
			'jcb_title' => array(
				'title' => 'JCB',
				'type'  => 'title'
			),
			'jcb_acquirer' => array(
				'title'       => 'Operadora / Adquirente',
				'type'        => 'select',
				'desc_tip'    => true,
				'description' => 'Configurar bandeira JCB',
				'default'     => '0',
				'options'     => array(
					'0' => 'Bandeira Desabilitada',
					'1' => 'Cielo - Buy Page Loja',
					'2' => 'Cielo - Buy Page Cielo',
				)
			),
			'jcb_parcel_min' => array(
				'title'       => 'Parcela mínima',
				'type'        => 'text',
				'description' => 'Valor mínimo aceito para parcelamento com esta bandeira',
				'desc_tip'    => true,
			),
			'jcb_parcels' => array(
				'title'       => 'Máximo de parcelas',
				'type'        => 'select',
				'desc_tip'    => true,
				'description' => 'Selecione o máximo de parcelas aceitas por esta bandeira',
				'default'     => '0',
				'options'     => array(
					'1' => '1x',
					'2' => '2x',
					'3' => '3x',
					'4' => '4x',
					'5' => '5x',
					'6' => '6x',
					'7' => '7x',
					'8' => '8x',
					'9' => '9x',
					'10' => '10x',
					'11' => '11x',
					'12' => '12x',
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
		
		global $woocommerce;		

		try {

			$customer_order = new WC_Order($order_id);

			$flag = $_POST['azpaylte_cc_form_flag'];
			$name = $_POST['azpaylte_cc_form_name'];
			$number = $_POST['azpaylte_cc_form_number'];
			$parcels = $_POST['azpaylte_cc_form_parcel'];
			$validate = explode('/', $_POST['azpaylte_cc_form_validate']);
			$cvv = $_POST['azpaylte_cc_form_cvv'];

			$parcel_value = ceil($customer_order->order_total / $parcels);

			// Check value of parcel
			if ($parcel_value < $this->{$flag.'_parcel_min'})
				throw new Exception('Valor da parcela inválido.');

			// Check quantity of parcels
			if ($parcels > $this->{$flag.'_parcels'})
				throw new Exception('Quantidade inválida de parcelas.');				

			$az_pay = new AZPay($this->merchant_id, $this->merchant_key);
			$az_pay->config_order['reference'] = $order_id;
			//$az_pay->config_order['totalAmount'] = 1000;
			$az_pay->config_order['totalAmount'] = $customer_order->order_total;

			$az_pay->config_card_payments['amount'] = $customer_order->order_total;
			//$az_pay->config_card_payments['amount'] = 1000;

			$acquirer = $flag . '_acquirer';
			$az_pay->config_card_payments['acquirer'] = $this->$acquirer;
			$az_pay->config_card_payments['method'] = ($parcels == '1') ? 1 : 2;
			$az_pay->config_card_payments['flag'] = $flag;
			$az_pay->config_card_payments['numberOfPayments'] = $parcels;
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

			$az_pay->sale();

			// Se houver erro ao executar o CURL
			// ou não retornar 200
			if ($az_pay->error == true)
				throw new Exception('Erro de comunicação, tente novamente.');

			$gateway_response = $az_pay->response();

			if ($gateway_response == null) 
				throw new Exception("Problemas ao obter resposta sobre pagamento.");

			if ($gateway_response->status != Config::$RESPONSE['APPROVED'])
				throw new Exception("Pagamento não Autorizado - Mensagem: {$gateway_response->result->error->details} - Erro: {$gateway_response->result->error->code})");

			$customer_order->add_order_note("Pagamento relizado com sucesso. AZPay TID: {$gateway_response->transactionId}");
			$customer_order->payment_complete();		
			$woocommerce->cart->empty_cart();			
			
			$response = array(
				'result'   => 'success',
				'redirect' => $url = $this->get_return_url($customer_order)
			);			

		} catch (Exception $e) {
			$this->add_error($e->getMessage());
			$response = array(
				'result'   => 'fail',
				'redirect' => ''
			);
		}

		return $response;
	}

	
	public function currency_not_supported_message() {
		echo '<div class="error"><p><strong>AZPay Lite - Cartão de Crédito</strong>: Moeda não aceita</p></div>';
	}


	/**
	 * Save the configuration of Creditcards
	 * @return [type] [description]
	 */
	public function save_creditcard_config() {

		if (isset($_POST['woocommerce_azpay_lite_creditcard']))
			update_option('woocommerce_azpay_lite_creditcard', json_encode($this->filterData($_POST['woocommerce_azpay_lite_creditcard'])));
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
		
		$html_path = apply_filters('wc_azpay_creditcard_form', plugin_dir_path(__FILE__) . 'views/html-form-creditcard.php');

		if (file_exists($html_path))
			include_once($html_path);
	}


	/**
	 * Validate cart total to show parcels
	 * @param  [type] $cart_total [description]
	 * @param  [type] $flag       [description]
	 * @return [type]             [description]
	 */
	public function validate_parcel($cart_total, $flag) {

		$flag = $flag . '_parcel_min';

		if ($cart_total >= $this->$flag)
			return true;
		
		return false;
	}


	/**
	 * Return the quantity of parcels that are accepted
	 * @param  [type] $flag [description]
	 * @return [type]       [description]
	 */
	public function parcel_qnt($cart_total, $flag) {
		
		// Max os parcels accepted by this flag
		$flag_parcels = $flag . '_parcels';
		$flag_parcel_min = $flag . '_parcel_min';

		$parcels = ceil($cart_total / $this->$flag_parcel_min);

		if ($parcels > $this->$flag_parcels)
			return $this->$flag_parcels;

		return $parcels;
	}


	/**
	 * Return a select field with accepted parcels
	 * @param  [type] $cart_total [description]
	 * @param  [type] $flag       [description]
	 * @return [type]             [description]
	 */
	public function get_select_parcel_html($cart_total, $flag) {

		// Max os parcels accepted by this flag
		$parcels = $flag . '_parcels';
		$parcel_min = $flag . '_parcel_min';
		$max_parcels = $this->$flag;

		$html = '<select name="azpaylte_cc_form_parcel" id="azpaylte_cc_form_parcel">';
        
        for($i = 1; $i <= $this->$parcels ; $i++) {

        	$value_parcel = $cart_total / $i;
        	if ($value_parcel >= $this->$parcel_min) {
        		$html .= '<option value="'.$i.'">'.$i.'x</option>';
        	}        	
        }

        $html = '</select>';

        return $html;
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
