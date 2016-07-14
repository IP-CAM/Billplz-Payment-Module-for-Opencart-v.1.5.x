<?php
/**
 * Billplz OpenCart Plugin
 * 
 * @package Payment Gateway
 * @author Wanzul-Hosting.com <sales@wanzul-hosting.com>
 * @version 2.0
 */
class ControllerPaymentBillplz extends Controller
{
	private function get_domain_forwebmaster(){
		return substr($_SERVER['HTTP_HOST'],0,3) == 'www' ? substr($_SERVER['HTTP_HOST'],4) : $_SERVER['HTTP_HOST'];
	}
	public function index()
	{
		$data['button_confirm'] = $this->language->get('button_confirm');
		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		//action as a jump url
		//$data['country'] = $order_info['payment_iso_code_2'];
		//$data['currency'] = $order_info['currency_code'];
		$products = $this->cart->getProducts();
		foreach ($products as $product) {
			$data['prod_desc'][] = $product['name'] . " x " . $product['quantity'];
		}
		//$data['lang'] = $this->session->data['language'];
		//$data['returnurl'] = $this->url->link('payment/billplz/return_ipn', '', 'SSL');
		//***************************************
		//$billplz_url = parse_url(HTTP_SERVER);
		
		//Delivery Notification
		$delivery = $this->config->get('billplz_delivery');
		$deliver = $delivery > 0 ? true : false;

		if ( $delivery == "2" ){
			$orderInfoEmail = null;
		}
		elseif (isset($order_info['email'])) {
			if ($order_info['email']==''){
				$orderInfoEmail = "webmaster@" . self::get_domain_forwebmaster();
			}
			else {
				$orderInfoEmail = $order_info['email'];
			}
		}
		else {
			$orderInfoEmail = "webmaster@" . self::get_domain_forwebmaster();
		}
		
		if ( $delivery == "1" ){
			$custTel = null;
		}
		elseif (isset($order_info['telephone'])){
			if ($order_info['telephone']==''){
				$custTel = '60121234567';
			}
			else {
				$custTel = $order_info['telephone'];
				//number intelligence
				$custTel2   = substr($order_info['telephone'], 0, 1);
				if ($custTel2 == '+') {
					$custTel3 = substr($order_info['telephone'], 1, 1);
					if ($custTel3 != '6')
						$custTel = "+6" . $order_info['telephone'];
					} else if ($custTel2 == '6') {
					} else {
						if ($custTel != '')
							$custTel = "+6" . $order_info['telephone'];
				}
				//number intelligence
			}
		}
		else {
			$custTel = '60121234567';
		}
		
		if (isset($order_info['payment_firstname'])){
			if ($order_info['payment_firstname']==''){
				$firstName = 'Name';
			}
			else {
				$firstName = $order_info['payment_firstname'];
			}			
		}
		else {
			$firstName = 'Name';
		}
		
		if (isset($order_info['payment_lastname'])){
			if ($order_info['payment_lastname']==''){
				$lastName = 'Name';
			}
			else {
				$lastName = $order_info['payment_lastname'];
			}			
		}
		else {
			$lastName = 'Name';
		}
		
		$returnurl   = $this->url->link('payment/billplz/return_ipn', '', 'SSL');
		$callbackurl = $this->url->link('payment/billplz/callback_ipn', '', 'SSL');
		$amount      = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
		$data        = array(
			'collection_id' => $this->config->get('billplz_vkey'),
			'email' => $orderInfoEmail,
			'description' => substr("Order " . $this->session->data['order_id'] . " - ".implode($data['prod_desc']), 0, 199),
			'mobile' => $custTel,
			'name' => $firstName . ' ' . $lastName,
			'reference_1_label' => "ID",
			'reference_1' => $this->session->data['order_id'],
			'deliver' => $deliver,
			'amount' => $amount * 100,
			'callback_url' => $callbackurl,
			'redirect_url' => $returnurl
		);
		//***************************************
		$process     = curl_init($this->config->get('billplz_sandbox'));
		curl_setopt($process, CURLOPT_HEADER, 0);
		curl_setopt($process, CURLOPT_USERPWD, $this->config->get('billplz_mid') . ":");
		curl_setopt($process, CURLOPT_TIMEOUT, 30);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($process, CURLOPT_POSTFIELDS, http_build_query($data));
		$return = curl_exec($process);
		curl_close($process);
		$arr            = json_decode($return, true);
		$data['action'] = isset($arr['url']) ? $arr['url'] : null;
		if (isset($arr['error'])){
			unset($data['mobile']);
			$process = curl_init($this->config->get('billplz_sandbox'));
			curl_setopt($process, CURLOPT_HEADER, 0);
			curl_setopt($process, CURLOPT_USERPWD, $this->config->get('billplz_mid') . ":");
			curl_setopt($process, CURLOPT_TIMEOUT, 30);
			curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($process, CURLOPT_POSTFIELDS, http_build_query($data));
			$return = curl_exec($process);
			curl_close($process);
			$arr            = json_decode($return, true);
			$data['action'] = isset($arr['url']) ? $arr['url'] : "http://facebook.com/billplzplugin";
		}
		//***************************************
		$version_oc     = substr(VERSION, 0, 3);
		if ($version_oc == "2.2") {
			if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/billplz.tpl')) {
				return $this->load->view($this->config->get('config_template') . '/template/payment/billplz.tpl', $data);
			} else {
				return $this->load->view('payment/billplz.tpl', $data);
			}
		} else {
			if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/billplz.tpl')) {
				return $this->load->view($this->config->get('config_template') . '/template/payment/billplz.tpl', $data);
			} else {
				return $this->load->view('default/template/payment/billplz.tpl', $data);
			}
		}
	}
	public function return_ipn()
	{
		$this->load->model('checkout/order');
		$tranID  = $_GET['billplz']['id'];
		//***************************************
		$process = curl_init($this->config->get('billplz_sandbox') . $tranID);
		curl_setopt($process, CURLOPT_HEADER, 0);
		curl_setopt($process, CURLOPT_USERPWD, $this->config->get('billplz_mid') . ":");
		curl_setopt($process, CURLOPT_TIMEOUT, 30);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
		$return = curl_exec($process);
		curl_close($process);
		$arr = json_decode($return, true);
		if (isset($arr['error']))
			exit("Hacking Attempt!");
		//***************************************
		$orderid         = $arr['reference_1'];
		$status          = $arr['paid'];
		$amount          = $arr['amount'];
		$paydate         = $arr['paid_at'];
		$order_info      = $this->model_checkout_order->getOrder($orderid); // orderid
		$order_status_id = $this->config->get('config_order_status_id');
		if ($status) {
			$order_status_id = $this->config->get('billplz_completed_status_id');
		} elseif (!$status) {
			$order_status_id = $this->config->get('billplz_failed_status_id');
		}
		//else {
		//	$order_status_id = $this->config->get('billplz_pending_status_id');
		//}
		if (!$order_info['order_status_id']) {
			$this->model_checkout_order->addOrderHistory($orderid, $order_status_id, "Reredirect: " . $paydate . " URL:" . $arr['url'], false);
		} else {
			$this->model_checkout_order->addOrderHistory($orderid, $order_status_id, "Reredirect: " . $paydate . " URL:" . $arr['url'], false);
		}
		echo '<html>' . "\n";
		echo '<head>' . "\n";
		if ($status)
			echo '  <meta http-equiv="Refresh" content="0; url=' . $this->url->link('checkout/success') . '">' . "\n";
		else
			echo '  <meta http-equiv="Refresh" content="0; url=' . $this->url->link('checkout/checkout') . '">' . "\n";
		echo '</head>' . "\n";
		echo '<body>' . "\n";
		if ($status)
			echo '  <p>Please follow <a href="' . $this->url->link('checkout/success') . '">link</a>!</p>' . "\n";
		else
			echo '  <p>Please follow <a href="' . $this->url->link('checkout/checkout') . '">link</a>!</p>' . "\n";
		echo '</body>' . "\n";
		echo '</html>' . "\n";
		exit();
	}
	/*****************************************************
	 * Callback with IPN(Instant Payment Notification)
	 ******************************************************/
	public function callback_ipn()
	{
		$this->load->model('checkout/order');
		$tranID  = $_POST['id'];
		//***************************************
		$process = curl_init($this->config->get('billplz_sandbox') . $tranID);
		curl_setopt($process, CURLOPT_HEADER, 0);
		curl_setopt($process, CURLOPT_USERPWD, $this->config->get('billplz_mid') . ":");
		curl_setopt($process, CURLOPT_TIMEOUT, 30);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
		$return = curl_exec($process);
		curl_close($process);
		$arr = json_decode($return, true);
		if (isset($arr['error']))
			exit("Hacking Attempt!");
		//***************************************
		$orderid         = $arr['reference_1'];
		$status          = $arr['paid'];
		$amount          = $arr['amount'];
		$paydate         = $arr['paid_at'];
		$order_info      = $this->model_checkout_order->getOrder($orderid); // orderid
		$order_status_id = $this->config->get('config_order_status_id');
		if ($status) {
			$order_status_id = $this->config->get('billplz_completed_status_id');
		} elseif (!$status) {
			$order_status_id = $this->config->get('billplz_failed_status_id');
		}
		//else {
		//	$order_status_id = $this->config->get('billplz_pending_status_id');
		//}
		if (!$order_info['order_status_id']) {
			$this->model_checkout_order->addOrderHistory($orderid, $order_status_id, "Callback: " . $paydate . " URL:" . $arr['url'], false);
		} else {
			$this->model_checkout_order->addOrderHistory($orderid, $order_status_id, "Callback: " . $paydate . "URL:" . $arr['url'], false);
		}
	}
}
