<?php
/**
 * Billplz OpenCart Plugin
 * 
 * @package Payment Gateway
 * @author Wanzul-Hosting.com <sales@wanzul-hosting.com>
 * @version 1.5.0
 */

class ControllerPaymentBillplz extends Controller {
    
	private function get_domain_forwebmaster(){
		return substr($_SERVER['HTTP_HOST'],0,3) == 'www' ? substr($_SERVER['HTTP_HOST'],4) : $_SERVER['HTTP_HOST'];
	}
    protected function index() {
            $this->data['button_confirm'] = $this->language->get('button_confirm');
            
            $this->load->model('checkout/order');
		
            $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		
            $this->data['amount'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
            //$this->data['orderid'] = $this->session->data['order_id'];
            
			$delivery = $this->config->get('billplz_delivery');
			$deliver = $delivery > 0 ? true : false;
			
			if ( $delivery == "2" ){
				$bill_email = null;
			}
			elseif (isset($order_info['email'])){
				if ($order_info['email']==''){
					$bill_email = "webmaster@" . self::get_domain_forwebmaster();
				}
				else {
					$bill_email = $order_info['email'];
				}
			}
			else {
				$bill_email = "webmaster@" . self::get_domain_forwebmaster();
			}
			
			if ( $delivery == "1" ){
				$bill_mobile = null;
			}
			elseif (isset($order_info['telephone'])){
				if ($order_info['telephone']==''){
					$bill_mobile = '60121234567';
				}
				else {
					$bill_mobile = $order_info['telephone'];
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
				$bill_mobile = '60121234567';
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
			
			//$bill_name = $firstName . ' ' . $lastName;
            //$this->data['bill_email'] = $order_info['email'];
            //$this->data['bill_mobile'] = $order_info['telephone'];
            //$this->data['country'] = $order_info['payment_iso_code_2'];
            //$this->data['currency'] = $order_info['currency_code'];
            
            $products = $this->cart->getProducts();
            foreach ($products as $product) {
                $this->data['prod_desc'][]= $product['name']." x ".$product['quantity'];
            }

            $this->data['order_id'] = $this->session->data['order_id'];
            
            //$this->data['lang'] = $this->session->data['language'];

            $returnurl = $this->url->link('payment/billplz/return_ipn', '', 'SSL');
			$callbackurl = $this->url->link('payment/billplz/callback_ipn', '', 'SSL');
			
			$data        = array(
			'collection_id' => $this->config->get('billplz_verifykey'),
			'email' => $bill_email,
			'description' => substr("Order " . $this->session->data['order_id'] . " - ".implode($this->data['prod_desc']), 0, 199),
			'mobile' => $bill_mobile,
			'name' => $firstName . ' ' . $lastName,
			'reference_1_label' => "ID",
			'reference_1' => $this->session->data['order_id'],
			'deliver' => $deliver,
			'amount' => $this->data['amount'] * 100,
			'callback_url' => $callbackurl,
			'redirect_url' => $returnurl
			);
			
			//***************************************
			$process     = curl_init($this->config->get('billplz_sandbox'));
			curl_setopt($process, CURLOPT_HEADER, 0);
			curl_setopt($process, CURLOPT_USERPWD, $this->config->get('billplz_merchantid') . ":");
			curl_setopt($process, CURLOPT_TIMEOUT, 30);
			curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($process, CURLOPT_POSTFIELDS, http_build_query($data));
			$return = curl_exec($process);
			curl_close($process);
			$arr            = json_decode($return, true);
			$this->data['action'] = isset($arr['url']) ? $arr['url'] : "http://facebook.com/billplzplugin";
			if (isset($arr['error'])){
				unset($data['mobile']);
				$process = curl_init($this->config->get('billplz_sandbox'));
				curl_setopt($process, CURLOPT_HEADER, 0);
				curl_setopt($process, CURLOPT_USERPWD, $this->config->get('billplz_merchantid') . ":");
				curl_setopt($process, CURLOPT_TIMEOUT, 30);
				curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
				curl_setopt($process, CURLOPT_POSTFIELDS, http_build_query($data));
				$return = curl_exec($process);
				curl_close($process);
				$arr            = json_decode($return, true);
				$this->data['action'] = isset($arr['url']) ? $arr['url'] : "http://facebook.com/billplzplugin";
			}
			
			//***************************************
		
            if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/billplz.tpl')) {
                    $this->template = $this->config->get('config_template') . '/template/payment/billplz.tpl';
            } else {
                    $this->template = 'default/template/payment/billplz.tpl';
            }	
		
            $this->render();
    }
	
	/* return url */
    public function return_ipn() {
 
        $this->load->model('checkout/order');
		$tranID  = isset($_GET['billplz']['id']) ? $_GET['billplz']['id'] : 'abc';
        //$verifykey = $this->config->get('billplz_verifykey');

        //***************************************
		$process = curl_init($this->config->get('billplz_sandbox') . $tranID);
		curl_setopt($process, CURLOPT_HEADER, 0);
		curl_setopt($process, CURLOPT_USERPWD, $this->config->get('billplz_merchantid') . ":");
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

        $this->model_checkout_order->confirm($orderid, $this->config->get('billplz_order_status_id'));
        
        if ( $status )  {
            $this->model_checkout_order->update($orderid , $this->config->get('billplz_success_status_id'), "Return: PAID " . $paydate . " URL: " . $arr['url'], false);
            $this->redirect(HTTP_SERVER . 'index.php?route=checkout/success');
        } elseif ( !$status ) {
            $this->model_checkout_order->update($orderid , $this->config->get('billplz_failed_status_id'), "Return: NOT PAID " . $paydate . "URL: " . $arr['url'], false);
            
            $this->data['continue'] = $this->url->link('checkout/cart');

            if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/billplz_fail.tpl')) {
                $this->template = $this->config->get('config_template') . '/template/payment/billplz_fail.tpl';
            } else {
                $this->template = 'default/template/payment/billplz_fail.tpl';
            }
            $this->response->setOutput($this->render());            
        }
    }
     
    /*****************************************************
    * Callback with IPN(Instant Payment Notification)
    ******************************************************/
    public function callback_ipn()   {
        $this->load->model('checkout/order');
		$tranID  = isset($_POST['id']) ? $_POST['id']: 'abc';
        //$verifykey = $this->config->get('billplz_verifykey');

        //***************************************
		$process = curl_init($this->config->get('billplz_sandbox') . $tranID);
		curl_setopt($process, CURLOPT_HEADER, 0);
		curl_setopt($process, CURLOPT_USERPWD, $this->config->get('billplz_merchantid') . ":");
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

            $order_info = $this->model_checkout_order->getOrder($orderid); // orderid
            
	    //Confirm the order If not created yet
	    $this->model_checkout_order->confirm($orderid, $this->config->get('billplz_order_status_id'));
	    
	    if ( $status ) {                
	        $this->model_checkout_order->update($orderid , $this->config->get('billplz_success_status_id'), "Callback: PAID " . $paydate . " URL: " . $arr['url'], false);
	    } elseif ( !$status ) { 
	        $this->model_checkout_order->update($orderid, $this->config->get('billplz_failed_status_id'), "Callback: NOT PAID " . $paydate . "URL: " . $arr['url'], false);
	    }
    }
}
?>
