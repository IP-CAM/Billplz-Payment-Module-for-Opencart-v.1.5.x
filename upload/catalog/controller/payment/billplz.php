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
	public function index()
	{
		$data['button_confirm'] = $this->language->get('button_confirm');
		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		//action as a jump url
		//number intelligence
		$custTel    = $order_info['telephone'];
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
		$returnurl   = $this->url->link('payment/billplz/return_ipn', '', 'SSL');
		$callbackurl = $this->url->link('payment/billplz/callback_ipn', '', 'SSL');
		$amount      = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
		$data        = array(
			'collection_id' => $this->config->get('billplz_vkey'),
			'email' => $order_info['email'],
			'description' => substr("Order " . $this->session->data['order_id'] . " - ".implode($data['prod_desc']), 0, 199),
			'mobile' => $custTel,
			'name' => $order_info['payment_firstname'] . ' ' . $order_info['payment_lastname'],
			'reference_1_label' => "ID",
			'reference_1' => $this->session->data['order_id'],
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
		if (isset($arr['error']))
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
		$data['action'] = isset($arr['url']) ? $arr['url'] : "http://wanzul-hosting.com";
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
