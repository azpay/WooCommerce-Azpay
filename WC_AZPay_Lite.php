<?php
/**
 * Plugin Name: WooCommerce AZPay Lite
 * Plugin URI: http://www.azpay.com.br
 * Description: WooCommerce AZPay is a plugin to integrate the WooCommerce with AZPay, a brazilian payment gateway
 * Author: Gabriel Guerreiro
 * Author URI: http://www.gabrielguerreiro.com
 * Version: 1.2.6
 * License: GNU General Public License v2.0
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH'))
	exit;

/**
 * Check if WooCommerce is installed
 */
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))))
	return;

/**
 * If do not exists class WC_AZPay_Lite
 */
if(!class_exists('WC_AZPay_Lite')):

class WC_AZPay_Lite {

	/**
	 * Version of the plugin
	 *
	 */
	const VERSION = '1.2.6';

	/**
	 * Instance of this class
	 *
	 */
	protected static $instance = null;


	public function __construct() {

		if (class_exists('WC_Payment_Gateway')) {

			include_once(plugin_dir_path(__FILE__) . './vendors/azpay-php-sdk/azpay.php');
			include_once 'includes/wc-azpay-lite-creditcard.php';
			include_once 'includes/wc-azpay-lite-boleto.php';

			add_filter('woocommerce_payment_gateways', array($this, 'load_gateway'));

			/**
			 * My account item order link pay
			 */
			add_filter('woocommerce_my_account_my_orders_actions', array($this, 'order_link_pay'), 10, 2);

			/**
			 * Execute tasks based on AZPay returns
			 */
			add_action('init', array($this, 'azpay_callback'));

		} else {
			add_action('admin_notices', array($this, 'plugin_missing'));
		}

	}


	/**
	 * Return an instance of this class.
	 *
	 * @return [WC_AZPay_Lite object]
	 */
	public static function get_instance() {
		if (self::$instance == null)
			self::$instance = new self;

		return self::$instance;
	}


	/**
	 * Join the AZPay gateway at the array
	 *
	 * @param  [type]
	 * @return [type]
	 */
	public function load_gateway($current_gateways) {
		$current_gateways[] = 'WC_AZPay_Lite_Creditcard';
		$current_gateways[] = 'WC_AZPay_Lite_Boleto';
		return $current_gateways;
	}


	public function plugin_missing() {
		echo '<div class="error"><p>Please, install the Woocommerce AZPay Lite plugin.</p></div>';
	}



	/**
	 * Activate the plugin
	 * Create Log table
	 *
	 * @return [type]
	 */
	public static function activate() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'azpay_log';

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
				  id INT(11) NOT NULL AUTO_INCREMENT,
				  datetime DATETIME NOT NULL,
				  keylog VARCHAR(45) NOT NULL,
				  content TEXT NOT NULL,
				  orderid INT(11) NOT NULL,
				  PRIMARY KEY  (id)
				);";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta($sql);

		add_rewrite_endpoint('azpay', EP_PERMALINK | EP_ROOT);
		flush_rewrite_rules();
	}

	/**
	 * Deactivate the plugin
	 * @return [type]
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}


	/**
	 * My Account - link to order pay
	 *
	 * @param  [type] $actions [Links]
	 * @param  [type] $order   [Order]
	 * @return [type]          [description]
	 */
	public function order_link_pay($actions, $order) {
		global $wpdb;

		if ($order->status == 'on-hold') {

			$azpay_log = $wpdb->prefix.'azpay_log';
			$log = $wpdb->get_row("SELECT * FROM $azpay_log WHERE orderid = {$order->id}");

			if (!empty($log)) {

				$json_azpay = json_decode($log->content);

				if ($log->keylog == 'BOLETO_PAYMENT') {
					$actions['azpay-boleto-link']['name'] = 'Pagar';
					$actions['azpay-boleto-link']['url'] = $json_azpay->processor->Boleto->details->urlBoleto;
				}
			}
		}

		return $actions;
	}


	/**
	 * Execute tasks based on AZPay callback
	 *
	 * @return void
	 */
	public function azpay_callback() {

		// if URL ends with 'azpay'
		if (preg_match('/azpay(\/|)\?/', $_SERVER['REQUEST_URI']) && isset($_GET['TransactionID']) && !empty($_GET['TransactionID'])) {

			if (
				!isset($_POST['TID']) ||
				!isset($_POST['order_reference']) ||
				!isset($_POST['customers_id']) ||
				!isset($_POST['status'])
			   )
			return false;

			global $wpdb;

			$callback = array(
				'tid' 			  => $_POST['TID'],
				'order_reference' => $_POST['order_reference'],
				'customers_id' 	  => $_POST['customers_id'],
				'status' 		  => $_POST['status'],
			);

			// get Order
			$order = new WC_Order($callback['order_reference']);

			// if not exists this Order or payment method is not Boleto
			// return false
			if (empty($order))
				return false;

			// get payment method
			$payment_method = get_post_meta( $callback['order_reference'], '_payment_method', true );
			$payment_method_config = get_option( 'woocommerce_'.$payment_method.'_settings' );
			
			if (!isset($payment_method_config['auto_capture']) || empty($payment_method_config['auto_capture']) || $payment_method_config['auto_capture'] == 'no')
				return false;						

			// Changes by status
			switch ($callback['status']) {

				case Config::$STATUS['APPROVED']:
					$order->update_status($payment_method_config['azpaystatus_approved'], 'Pagamento confirmado. AZPay TID: ' . $callback['tid']);
					break;

				case Config::$STATUS['CAPTURING']:
					$order->update_status($payment_method_config['azpaystatus_capturing'], 'Aguardando confirmação de pagamento. AZPay TID: ' . $callback['tid']);
					break;

				case Config::$STATUS['CANCELLED']:
					$order->update_status($payment_method_config['azpaystatus_cancelled'], 'Pagamento cancelado. AZPay TID: ' . $callback['tid']);
					break;

				case Config::$STATUS['UNAUTHENTICATED']:
					$order->update_status($payment_method_config['azpaystatus_unauthenticated'], 'Falha no pagamento, cartão não autenticado. AZPay TID: ' . $callback['tid']);
					break;

				case Config::$STATUS['UNAUTHORIZED']:
					$order->update_status($payment_method_config['azpaystatus_unauthorized'], 'Falha no pagamento, transação não autorizada pela operadora. AZPay TID: ' . $callback['tid']);
					break;

				case Config::$STATUS['UNAPPROVED']:
					$order->update_status($payment_method_config['azpaystatus_unapproved'], 'Transação não capturada, tente novamente pelo painel do AZPay. AZPay TID: ' . $callback['tid']);
					break;
			}

			// register log
			$azpay_log = $wpdb->prefix.'azpay_log';
			$wpdb->insert(
				$azpay_log,
				array(
					'datetime' => current_time('mysql'),
					'keylog' => 'PAYMENT_CALLBACK',
					'orderid' => $callback['order_reference'],
					'content' => json_encode($callback),
				)
			);

			die(0);
		}
	}

}


/**
 * Activation and deactivation
 */
register_activation_hook(__FILE__,array('WC_AZPay_Lite','activate'));
register_deactivation_hook(__FILE__,array('WC_AZPay_Lite','deactivate'));


/**
 * Initialize
 */
add_action('plugins_loaded', array('WC_AZPay_Lite', 'get_instance'), 0);

endif;

function plugin_assets() {
	return plugin_dir_url(__FILE__).'assets/';
}

?>