<?php

// DIR_APPLICATION to ensure vqmod cache find the correct path
require_once DIR_APPLICATION .'/controller/payment/billplz-api.php';
require_once DIR_APPLICATION .'/controller/payment/billplz-connect.php';

class ControllerPaymentBillplz extends Controller
{
    protected function index()
    {
        $this->language->load('payment/billplz');
        
        $this->data = array(
            'button_confirm' => $this->language->get('button_confirm'),
            'is_sandbox' => $this->config->get('billplz_is_sandbox_value'),
            'text_is_sandbox' => $this->language->get('text_is_sandbox'),
            'action' => $this->url->link('payment/billplz/proceed', '', true),
        );

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/billplz.tpl')) {
            $this->template = $this->config->get('config_template') . '/template/payment/billplz.tpl';
        } else {
            $this->template = 'default/template/payment/billplz.tpl';
        }

        $this->render();
    }

    public function proceed()
    {
        $is_sandbox = $this->config->get('billplz_is_sandbox_value');
        $api_key = $this->config->get('billplz_api_key_value');
        $collection_id = $this->config->get('billplz_collection_id_value');

        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);

        $products = $this->cart->getProducts();
        foreach ($products as $product) {
            $data['prod_desc'][] = $product['name'] . " x " . $product['quantity'];
        }

        $parameter = array(
            'collection_id' => trim($collection_id),
            'email' => trim($order_info['email']),
            'mobile' => trim($order_info['telephone']),
            'name' => trim($order_info['payment_firstname'] . ' ' . $order_info['payment_lastname']),
            'amount' => strval($amount * 100),
            'callback_url' => $this->url->link('payment/billplz/callback_url', '', 'SSL'),
            'description' => substr("Order " . $this->session->data['order_id'] . " - " . implode($data['prod_desc']), 0, 200),
        );
        if (empty($parameter['mobile']) && empty($parameter['email'])) {
            $parameter['email'] = 'noreply@billplz.com';
        }
        if (empty($parameter['name'])) {
            $parameter['name'] = 'Payer Name Unavailable';
        }

        $optional = array(
            'redirect_url' => $this->url->link('payment/billplz/redirect_url', '', true),
            'reference_1_label' => 'ID',
            'reference_1' => $order_info['order_id'],
        );

        $connect = new BillplzConnect($api_key);
        $connect->setStaging($is_sandbox);
        $billplz = new BillplzApi($connect);

        list($rheader, $rbody) = $billplz->toArray($billplz->createBill($parameter, $optional));

        $this->load->model('payment/billplz');

        /*
        Display error if bills failed to create
        */

        if ($rheader !== 200) {
            $this->model_payment_billplz->logger($rbody);
            exit(print_r($rbody, true));
        }

        $bill_id = $rbody['id'];

        if (!$this->model_payment_billplz->insertBill($order_info['order_id'], $bill_id)){
            $this->model_payment_billplz->logger('Unexpected error. Duplicate bill id.');
            exit('Unexpected error. Duplicate bill id.');
        }

        if (!$order_info['order_status_id']) {
            $this->model_checkout_order->confirm($order_info['order_id'], $this->config->get('billplz_pending_status_id'), "Status: Pending. Bill ID: $bill_id", false);
        } else {
            $this->model_checkout_order->update($order_info['order_id'], $this->config->get('billplz_pending_status_id'), "Status: Pending. Bill ID: $bill_id", false);
        }

        $this->cart->clear();

        header('Location: ' . $rbody['url']);
    }

    public function redirect_url()
    {
        $this->load->model('payment/billplz');

        try {
           $data = BillplzConnect::getXSignature($this->config->get('billplz_x_signature_value'));
        } catch (Exception $e){
            $this->model_payment_billplz->logger('XSignature redirect error. ' . $e->getMessage());
            exit($e->getMessage());
        }

        if (!$data['paid']){
            $this->redirect($this->url->link('checkout/checkout'));
        }

        $bill_id = $data['id'];
        $bill_info = $this->model_payment_billplz->getBill($bill_id);
        $order_id = $bill_info['order_id'];

        $this->load->model('checkout/order');

        $billplz_completed_status_id = $this->config->get('billplz_completed_status_id');
        $billplz_pending_status_id = $this->config->get('billplz_pending_status_id');

        $order_info = $this->model_checkout_order->getOrder($order_id);

        if ($order_info['order_status_id'] == $billplz_pending_status_id && !$bill_info['paid']) {
            if ($this->model_payment_billplz->markBillPaid($order_id, $bill_id)){
                $this->model_checkout_order->update($order_id, $billplz_completed_status_id, "Status: Paid. Bill ID: $bill_id. Method: Redirect " , true);
            }
        }

        $this->redirect($this->url->link('checkout/success'));    
    }

    public function callback_url()
    {
        if ($_POST['paid'] === 'false'){
            exit;
        }

        $this->load->model('payment/billplz');

        try {
           $data = BillplzConnect::getXSignature($this->config->get('billplz_x_signature_value'));
        } catch (Exception $e){
            $this->model_payment_billplz->logger('XSignature callback error. ' . $e->getMessage());
            exit;
        }

        $bill_id = $data['id'];
        $bill_info = $this->model_payment_billplz->getBill($bill_id);
        $order_id = $bill_info['order_id'];

        $this->load->model('checkout/order');
        
        $billplz_completed_status_id = $this->config->get('billplz_completed_status_id');
        $billplz_pending_status_id = $this->config->get('billplz_pending_status_id');

        $order_info = $this->model_checkout_order->getOrder($order_id);

        if ($order_info['order_status_id'] == $billplz_pending_status_id && !$bill_info['paid']) {
            if ($this->model_payment_billplz->markBillPaid($order_id, $bill_id)){
                $this->model_checkout_order->update($order_id, $billplz_completed_status_id, "Status: Paid. Bill ID: $bill_id. Method: Callback " , true);
            }
        }

        exit('Callback Success');
    }
}