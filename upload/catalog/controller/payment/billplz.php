<?php

/**
 * Billplz OpenCart Plugin
 *
 * @package Payment Gateway
 * @author Billplz Sdn Bhd
 * @version 3.3.1
 */
class ControllerPaymentBillplz extends Controller
{

    protected function index()
    {
        $this->language->load('payment/billplz');
        $this->data = array(
            'button_confirm' => $this->language->get('button_confirm'),
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
        $api_key = trim($this->config->get('billplz_api_key_value'));
        $collection_id = trim($this->config->get('billplz_collection_id_value'));

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
            'callback_url' => $this->url->link('payment/billplz/callback_ipn', '', 'SSL'),
            'description' => substr("Order " . $this->session->data['order_id'] . " - " . implode($data['prod_desc']), 0, 200),
        );
        if (empty($parameter['mobile']) && empty($parameter['email'])) {
            $parameter['email'] = 'noreply@billplz.com';
        }
        if (empty($parameter['name'])) {
            $parameter['name'] = 'Payer Name Unavailable';
        }

        $optional = array(
            'redirect_url' => $this->url->link('payment/billplz/return_ipn', '', true),
            'reference_1_label' => 'ID',
            'reference_1' => $order_info['order_id'],
        );

        $connect = new BillplzConnect(trim($api_key));
        $connect->detectMode();
        $billplz = new BillplzApi($connect);
        list($rheader, $rbody) = $billplz->toArray($billplz->createBill($parameter, $optional));
        if ($rheader !== 200) {
            throw new Exception(print_r($rbody, true));
            exit;
        }
        $this->cart->clear();

        header('Location: ' . $rbody['url']);
    }

    public function return_ipn()
    {
        $x_signature = trim($this->config->get('billplz_x_signature_value'));

        try {
            $data = BillplzConnect::getXSignature($x_signature);
        } catch (Exception $e) {
            header('HTTP/1.1 403 X Signature matching failed', true, 403);
            exit();
        }

        if ($data['paid']) {
            $goTo = $this->url->link('checkout/success');
        } else {
            $goTo = $this->url->link('checkout/checkout');
        }

        if (!headers_sent()) {
            header('Location: ' . $goTo);
        } else {
            echo "If you are not redirected, please click <a href=" . '"' . $goTo . '"' . " target='_self'>Here</a><br />"
                . "<script>location.href = '" . $goTo . "'</script>";
        }
        exit;
    }

    public function callback_ipn()
    {
        $this->load->model('checkout/order');

        $api_key = trim($this->config->get('billplz_api_key_value'));
        $x_signature = trim($this->config->get('billplz_x_signature_value'));

        try {
            $data = BillplzConnect::getXSignature($x_signature);
        } catch (Exception $e) {
            header('HTTP/1.1 403 X Signature matching failed', true, 403);
            exit();
        }

        if (!$data['paid']) {
            exit;
        }

        $connect = new BillplzConnect($api_key);
        $connect->detectMode();
        $billplz = new BillplzApi($connect);
        list($rheader, $rbody) = $billplz->toArray($billplz->getBill($data['id']));

        $orderid = $rbody['reference_1'];
        $paydate = $data['paid_at'];
        $order_info = $this->model_checkout_order->getOrder($orderid); // orderid
        $order_status_id = $this->config->get('billplz_completed_status_id');
        if ($order_status_id != $order_info['order_status_id']) {
            $comment = "Callback: " . $paydate . " URL:" . $rbody['url'];
            $this->model_checkout_order->confirm($orderid, $order_status_id, $comment, true);
        }

        exit('Callback Success');
    }
}

class BillplzConnect
{
    private $api_key;
    private $x_signature_key;
    private $collection_id;
    private $process;
    public $is_production;
    public $detect_mode = false;
    public $url;
    public $webhook_rank;
    public $header;
    const TIMEOUT = 10; //10 Seconds
    const PRODUCTION_URL = 'https://www.billplz.com/api/';
    const STAGING_URL = 'https://billplz-staging.herokuapp.com/api/';
    public function __construct($api_key)
    {
        $this->api_key = $api_key;
        $this->process = curl_init();
        $this->header = $api_key . ':';
        curl_setopt($this->process, CURLOPT_HEADER, 0);
        curl_setopt($this->process, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->process, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($this->process, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($this->process, CURLOPT_TIMEOUT, self::TIMEOUT);
        curl_setopt($this->process, CURLOPT_USERPWD, $this->header);
    }
    public function setMode($is_production = false)
    {
        $this->is_production = $is_production;
        if ($is_production) {
            $this->url = self::PRODUCTION_URL;
        } else {
            $this->url = self::STAGING_URL;
        }
    }
    public function detectMode()
    {
        $this->url = self::PRODUCTION_URL;
        $this->detect_mode = true;
        return $this;
    }
    public function getWebhookRank()
    {
        $url = $this->url . 'v4/webhook_rank';
        curl_setopt($this->process, CURLOPT_URL, $url);
        curl_setopt($this->process, CURLOPT_POST, 0);
        $body = curl_exec($this->process);
        $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
        $return = array($header, $body);

        return $return;
    }
    public function getCollectionIndex($parameter = array())
    {
        $url = $this->url . 'v4/collections?' . http_build_query($parameter);
        curl_setopt($this->process, CURLOPT_URL, $url);
        curl_setopt($this->process, CURLOPT_POST, 0);
        $body = curl_exec($this->process);
        $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
        $return = array($header, $body);

        return $return;
    }
    public function createCollection($title, $optional = array())
    {
        $url = $this->url . 'v4/collections';
        $body = http_build_query(['title' => $title]);
        if (isset($optional['split_header'])) {
            $split_header = http_build_query(array('split_header' => $optional['split_header']));
        }
        $split_payments = [];
        if (isset($optional['split_payments'])) {
            foreach ($optional['split_payments'] as $param) {
                $split_payments[] = http_build_query($param);
            }
        }
        if (!empty($split_payments)) {
            $body .= '&' . implode('&', $split_payments);
            if (!empty($split_header)) {
                $body .= '&' . $split_header;
            }
        }
        curl_setopt($this->process, CURLOPT_URL, $url);
        curl_setopt($this->process, CURLOPT_POSTFIELDS, $body);
        $body = curl_exec($this->process);
        $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
        $return = array($header, $body);

        return $return;
    }
    public function createOpenCollection($parameter, $optional = array())
    {
        $url = $this->url . 'v4/open_collections';
        $body = http_build_query($parameter);
        if (isset($optional['split_header'])) {
            $split_header = http_build_query(array('split_header' => $optional['split_header']));
        }
        $split_payments = [];
        if (isset($optional['split_payments'])) {
            foreach ($optional['split_payments'] as $param) {
                $split_payments[] = http_build_query($param);
            }
        }
        if (!empty($split_payments)) {
            unset($optional['split_payments']);
            $body .= '&' . implode('&', $split_payments);
            if (!empty($split_header)) {
                unset($optional['split_header']);
                $body .= '&' . $split_header;
            }
        }
        if (!empty($optional)) {
            $body .= '&' . http_build_query($optional);
        }
        curl_setopt($this->process, CURLOPT_URL, $url);
        curl_setopt($this->process, CURLOPT_POSTFIELDS, $body);
        $body = curl_exec($this->process);
        $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
        $return = array($header, $body);

        return $return;
    }
    public function getCollection($id)
    {
        $url = $this->url . 'v4/collections/' . $id;
        curl_setopt($this->process, CURLOPT_URL, $url);
        curl_setopt($this->process, CURLOPT_POST, 0);
        $body = curl_exec($this->process);
        $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
        $return = array($header, $body);

        return $return;
    }
    public function getOpenCollection($id)
    {
        $url = $this->url . 'v4/open_collections/' . $id;

        curl_setopt($this->process, CURLOPT_URL, $url);
        curl_setopt($this->process, CURLOPT_POST, 0);
        $body = curl_exec($this->process);
        $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
        $return = array($header, $body);

        return $return;
    }
    public function getOpenCollectionIndex($parameter = array())
    {
        $url = $this->url . 'v4/open_collections?' . http_build_query($parameter);
        curl_setopt($this->process, CURLOPT_URL, $url);
        curl_setopt($this->process, CURLOPT_POST, 0);
        $body = curl_exec($this->process);
        $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
        $return = array($header, $body);

        return $return;
    }
    public function createMPICollection($title)
    {
        $url = $this->url . 'v4/mass_payment_instruction_collections';
        $data = ['title' => $title];
        curl_setopt($this->process, CURLOPT_URL, $url);
        curl_setopt($this->process, CURLOPT_POSTFIELDS, http_build_query($data));
        $body = curl_exec($this->process);
        $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
        $return = array($header, $body);

        return $return;
    }
    public function getMPICollection($id)
    {
        $url = $this->url . 'v4/mass_payment_instruction_collections/' . $id;

        curl_setopt($this->process, CURLOPT_URL, $url);
        curl_setopt($this->process, CURLOPT_POST, 0);
        $body = curl_exec($this->process);
        $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
        $return = array($header, $body);

        return $return;
    }
    public function createMPI($parameter, $optional = array())
    {
        $url = $this->url . 'v4/mass_payment_instructions';
        //if (sizeof($parameter) !== sizeof($optional) && !empty($optional)){
        //    throw new \Exception('Optional parameter size is not match with Required parameter');
        //}
        $data = array_merge($parameter, $optional);
        curl_setopt($this->process, CURLOPT_URL, $url);
        curl_setopt($this->process, CURLOPT_POSTFIELDS, http_build_query($data));
        $body = curl_exec($this->process);
        $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
        $return = array($header, $body);

        return $return;
    }
    public function getMPI($id)
    {
        $url = $this->url . 'v4/mass_payment_instructions/' . $id;

        curl_setopt($this->process, CURLOPT_URL, $url);
        curl_setopt($this->process, CURLOPT_POST, 0);
        $body = curl_exec($this->process);
        $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
        $return = array($header, $body);

        return $return;
    }
    public static function getXSignature($x_signature_key)
    {
        $signingString = '';
        if (isset($_GET['billplz']['id']) && isset($_GET['billplz']['paid_at']) && isset($_GET['billplz']['paid']) && isset($_GET['billplz']['x_signature'])) {
            $data = array(
                'id' => $_GET['billplz']['id'],
                'paid_at' => $_GET['billplz']['paid_at'],
                'paid' => $_GET['billplz']['paid'],
                'x_signature' => $_GET['billplz']['x_signature'],
            );
            $type = 'redirect';
        } elseif (isset($_POST['x_signature'])) {
            $data = array(
                'amount' => isset($_POST['amount']) ? $_POST['amount'] : '',
                'collection_id' => isset($_POST['collection_id']) ? $_POST['collection_id'] : '',
                'due_at' => isset($_POST['due_at']) ? $_POST['due_at'] : '',
                'email' => isset($_POST['email']) ? $_POST['email'] : '',
                'id' => isset($_POST['id']) ? $_POST['id'] : '',
                'mobile' => isset($_POST['mobile']) ? $_POST['mobile'] : '',
                'name' => isset($_POST['name']) ? $_POST['name'] : '',
                'paid_amount' => isset($_POST['paid_amount']) ? $_POST['paid_amount'] : '',
                'paid_at' => isset($_POST['paid_at']) ? $_POST['paid_at'] : '',
                'paid' => isset($_POST['paid']) ? $_POST['paid'] : '',
                'state' => isset($_POST['state']) ? $_POST['state'] : '',
                'url' => isset($_POST['url']) ? $_POST['url'] : '',
                'x_signature' => isset($_POST['x_signature']) ? $_POST['x_signature'] : '',
            );
            $type = 'callback';
        } else {
            return false;
        }
        foreach ($data as $key => $value) {
            if (isset($_GET['billplz']['id'])) {
                $signingString .= 'billplz' . $key . $value;
            } else {
                $signingString .= $key . $value;
            }
            if (($key === 'url' && isset($_POST['x_signature'])) || ($key === 'paid' && isset($_GET['billplz']['id']))) {
                break;
            } else {
                $signingString .= '|';
            }
        }
        /*
         * Convert paid status to boolean
         */
        $data['paid'] = $data['paid'] === 'true' ? true : false;
        $signedString = hash_hmac('sha256', $signingString, $x_signature_key);
        if ($data['x_signature'] === $signedString) {
            $data['type'] = $type;
            return $data;
        }
        throw new \Exception('X Signature Calculation Mismatch!');
    }
    public function deactivateCollection($title, $option = 'deactivate')
    {
        $url = $this->url . 'v3/collections/' . $title . '/' . $option;
        $data = ['title' => $title];
        curl_setopt($this->process, CURLOPT_URL, $url);
        curl_setopt($this->process, CURLOPT_POST, 1);
        curl_setopt($this->process, CURLOPT_POSTFIELDS, http_build_query(array()));
        $body = curl_exec($this->process);
        $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
        $return = array($header, $body);

        return $return;
    }
    public function createBill($parameter, $optional = array())
    {
        $url = $this->url . 'v3/bills';
        //if (sizeof($parameter) !== sizeof($optional) && !empty($optional)){
        //    throw new \Exception('Optional parameter size is not match with Required parameter');
        //}
        $data = array_merge($parameter, $optional);
        curl_setopt($this->process, CURLOPT_URL, $url);
        curl_setopt($this->process, CURLOPT_POSTFIELDS, http_build_query($data));
        $body = curl_exec($this->process);
        $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
        $return = array($header, $body);

        return $return;
    }
    public function getBill($id)
    {
        $url = $this->url . 'v3/bills/' . $id;
        curl_setopt($this->process, CURLOPT_URL, $url);
        curl_setopt($this->process, CURLOPT_POST, 0);
        $body = curl_exec($this->process);
        $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
        $return = array($header, $body);

        return $return;
    }
    public function deleteBill($id)
    {
        $url = $this->url . 'v3/bills/' . $id;
        curl_setopt($this->process, CURLOPT_URL, $url);
        curl_setopt($this->process, CURLOPT_CUSTOMREQUEST, "DELETE");
        $body = curl_exec($this->process);
        $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
        $return = array($header, $body);

        return $return;
    }
    public function bankAccountCheck($id)
    {
        $url = $this->url . 'v3/check/bank_account_number/' . $id;

        curl_setopt($this->process, CURLOPT_URL, $url);
        curl_setopt($this->process, CURLOPT_POST, 0);
        $body = curl_exec($this->process);
        $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
        $return = array($header, $body);

        return $return;
    }
    public function getPaymentMethodIndex($id)
    {
        $url = $this->url . 'v3/collections/' . $id . '/payment_methods';

        curl_setopt($this->process, CURLOPT_URL, $url);
        curl_setopt($this->process, CURLOPT_POST, 0);
        $body = curl_exec($this->process);
        $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
        $return = array($header, $body);

        return $return;
    }
    public function getTransactionIndex($id, $parameter)
    {
        $url = $this->url . 'v3/bills/' . $id . '/transactions?' . http_build_query($parameter);
        curl_setopt($this->process, CURLOPT_URL, $url);
        curl_setopt($this->process, CURLOPT_POST, 0);
        $body = curl_exec($this->process);
        $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
        $return = array($header, $body);

        return $return;
    }
    public function updatePaymentMethod($parameter)
    {
        if (!isset($parameter['collection_id'])) {
            throw new \Exception('Collection ID is not passed on updatePaymethodMethod');
        }
        $url = $this->url . 'v3/collections/' . $parameter['collection_id'] . '/payment_methods';
        unset($parameter['collection_id']);
        $data = $parameter;
        $header = $this->header;
        $body = [];
        foreach ($data['payment_methods'] as $param) {
            $body[] = http_build_query($param);
        }
        curl_setopt($this->process, CURLOPT_URL, $url);
        curl_setopt($this->process, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->process, CURLOPT_POSTFIELDS, implode('&', $body));
        $body = curl_exec($this->process);
        $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
        $return = array($header, $body);

        return $return;
    }
    public function getBankAccountIndex($parameter)
    {
        if (!is_array($parameter['account_numbers'])) {
            throw new \Exception('Not valid account numbers.');
        }
        $parameter = http_build_query($parameter);
        $parameter = preg_replace('/%5B[0-9]+%5D/simU', '%5B%5D', $parameter);
        $url = $this->url . 'v3/bank_verification_services?' . $parameter;
        curl_setopt($this->process, CURLOPT_URL, $url);
        curl_setopt($this->process, CURLOPT_POST, 0);
        $body = curl_exec($this->process);
        $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
        $return = array($header, $body);

        return $return;
    }
    public function getBankAccount($id)
    {
        $url = $this->url . 'v3/bank_verification_services/' . $id;

        curl_setopt($this->process, CURLOPT_URL, $url);
        curl_setopt($this->process, CURLOPT_POST, 0);
        $body = curl_exec($this->process);
        $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
        $return = array($header, $body);

        return $return;
    }
    public function createBankAccount($parameter)
    {
        $url = $this->url . 'v3/bank_verification_services';
        curl_setopt($this->process, CURLOPT_URL, $url);
        curl_setopt($this->process, CURLOPT_POSTFIELDS, http_build_query($parameter));
        $body = curl_exec($this->process);
        $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
        $return = array($header, $body);
        return $return;
    }
    public function getFpxBanks()
    {
        $url = $this->url . 'v3/fpx_banks';

        curl_setopt($this->process, CURLOPT_URL, $url);
        curl_setopt($this->process, CURLOPT_POST, 0);
        $body = curl_exec($this->process);
        $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
        $return = array($header, $body);

        return $return;
    }
    public function closeConnection()
    {
        curl_close($this->process);
    }
    public function toArray($json)
    {
        return array($json[0], \json_decode($json[1], true));
    }
}
class BillplzApi
{
    private $connect;
    public function __construct($connect)
    {
        $this->connect = $connect;
    }
    public function setConnect($connect)
    {
        $this->connect = $connect;
    }
    /**
     * This method is to change the URL to staging if failed to authenticate with production.
     * It will recall the method that has executed previously to perform it in staging.
     */
    private function detectMode($method_name, $response, $parameter = '', $optional = '', $extra = '')
    {
        if ($response[0] === 401 && $this->connect->detect_mode) {
            $this->connect->detect_mode = false;
            $this->connect->setMode(false);
            if (!empty($extra)) {
                return $this->{$method_name}($parameter, $optional, $extra);
            } elseif (!empty($optional)) {
                return $this->{$method_name}($parameter, $optional);
            } elseif (!empty($parameter)) {
                return $this->{$method_name}($parameter);
            } else {
                return $this->{$method_name}();
            }
        }
        return false;
    }
    public function getCollectionIndex($parameter = array())
    {
        $response = $this->connect->getCollectionIndex($parameter);
        if ($detect_mode = $this->detectMode(__FUNCTION__, $response, $parameter)) {
            return $detect_mode;
        }
        return $response;
    }
    public function createCollection($parameter, $optional = array())
    {
        $response = $this->connect->createCollection($parameter, $optional);
        if ($detect_mode = $this->detectMode(__FUNCTION__, $response, $parameter, $optional)) {
            return $detect_mode;
        }
        return $response;
    }
    public function getCollection($parameter)
    {
        $response = $this->connect->getCollection($parameter);
        if ($detect_mode = $this->detectMode(__FUNCTION__, $response, $parameter)) {
            return $detect_mode;
        }
        return $response;
    }
    public function createOpenCollection($parameter, $optional = array())
    {
        $parameter['title'] = substr($parameter['title'], 0, 49);
        $parameter['description'] = substr($parameter['description'], 0, 199);
        if (intval($parameter['amount']) > 999999999) {
            throw new \Exception("Amount Invalid. Too big");
        }
        $response = $this->connect->createOpenCollection($parameter, $optional);
        if ($detect_mode = $this->detectMode(__FUNCTION__, $response, $parameter, $optional)) {
            return $detect_mode;
        }
        return $response;
    }
    public function getOpenCollection($parameter)
    {
        $response = $this->connect->getOpenCollection($parameter);
        if ($detect_mode = $this->detectMode(__FUNCTION__, $response, $parameter)) {
            return $detect_mode;
        }
        return $response;
    }
    public function getOpenCollectionIndex($parameter = array())
    {
        $response = $this->connect->getOpenCollectionIndex($parameter);
        if ($detect_mode = $this->detectMode(__FUNCTION__, $response, $parameter)) {
            return $detect_mode;
        }
        return $response;
    }
    public function createMPICollection($parameter)
    {
        $response = $this->connect->createMPICollection($parameter);
        if ($detect_mode = $this->detectMode(__FUNCTION__, $response, $parameter)) {
            return $detect_mode;
        }
        return $response;
    }
    public function getMPICollection($parameter)
    {
        $response = $this->connect->getMPICollection($parameter);
        if ($detect_mode = $this->detectMode(__FUNCTION__, $response, $parameter)) {
            return $detect_mode;
        }
        return $response;
    }
    public function createMPI($parameter, $optional = array())
    {
        $response = $this->connect->createMPI($parameter, $optional);
        if ($detect_mode = $this->detectMode(__FUNCTION__, $response, $parameter, $optional)) {
            return $detect_mode;
        }
        return $response;
    }
    public function getMPI($parameter)
    {
        $response = $this->connect->getMPI($parameter);
        if ($detect_mode = $this->detectMode(__FUNCTION__, $response, $parameter)) {
            return $detect_mode;
        }
        return $response;
    }
    public function deactivateCollection($parameter)
    {
        $response = $this->connect->deactivateCollection($parameter);
        if ($detect_mode = $this->detectMode(__FUNCTION__, $response, $parameter)) {
            return $detect_mode;
        }
        return $response;
    }
    public function activateCollection($parameter)
    {
        $response = $this->connect->deactivateCollection($parameter, 'activate');
        if ($detect_mode = $this->detectMode(__FUNCTION__, $response, $parameter)) {
            return $detect_mode;
        }
        return $response;
    }
    public function createBill($parameter, $optional = array(), $sendCopy = '')
    {
        /* Email or Mobile must be set */
        if (empty($parameter['email']) && empty($parameter['mobile'])) {
            throw new \Exception("Email or Mobile must be set!");
        }
        /* Manipulate Deliver features to allow Email/SMS Only copy */
        if ($sendCopy === '0') {
            $optional['deliver'] = 'false';
        } elseif ($sendCopy === '1' && !empty($parameter['email'])) {
            $optional['deliver'] = 'true';
            unset($parameter['mobile']);
        } elseif ($sendCopy === '2' && !empty($parameter['mobile'])) {
            $optional['deliver'] = 'true';
            unset($parameter['email']);
        } elseif ($sendCopy === '3') {
            $optional['deliver'] = 'true';
        }
        /* Validate Mobile Number first */
        if (!empty($parameter['mobile'])) {
            /* Strip all unwanted character */
            $parameter['mobile'] = preg_replace('/[^0-9]/', '', $parameter['mobile']);
            /* Add '6' if applicable */
            $parameter['mobile'] = $parameter['mobile'][0] === '0' ? '6' . $parameter['mobile'] : $parameter['mobile'];
            /* If the number doesn't have valid formatting, reject it */
            /* The ONLY valid format '<1 Number>' + <10 Numbers> or '<1 Number>' + <11 Numbers> */
            /* Example: '60141234567' or '601412345678' */
            if (!preg_match('/^[0-9]{11,12}$/', $parameter['mobile'], $m)) {
                $parameter['mobile'] = '';
            }
        }
        /* Create Bills */
        $bill = $this->connect->createBill($parameter, $optional);
        if ($bill[0] === 200) {
            return $bill;
        }
        /* Determine if the API Key is belong to Staging */
        if ($detect_mode = $this->detectMode(__FUNCTION__, $bill, $parameter, $optional, $sendCopy)) {
            return $detect_mode;
        }
        /* Check if Failed caused by wrong Collection ID */
        $collection = $this->toArray($this->getCollection($parameter['collection_id']));
        /* If doesn't exists or belong to another merchant */
        /* + In-case the collection id is an empty */
        if ($collection[0] === 404 || $collection[0] === 401 || empty($parameter['collection_id'])) {
            /* Get All Active & Inactive Collection List */
            $collectionIndexActive = $this->toArray($this->getCollectionIndex(array('page' => '1', 'status' => 'active')));
            $collectionIndexInactive = $this->toArray($this->getCollectionIndex(array('page' => '1', 'status' => 'inactive')));
            /* If Active Collection not available but Inactive Collection is available */
            if (empty($collectionIndexActive[1]['collections']) && !empty($collectionIndexInactive[1]['collections'])) {
                /* Use inactive collection */
                $parameter['collection_id'] = $collectionIndexInactive[1]['collections'][0]['id'];
            }
            /* If there is Active Collection */
            elseif (!empty($collectionIndexActive[1]['collections'])) {
                $parameter['collection_id'] = $collectionIndexActive[1]['collections'][0]['id'];
            }
            /* If there is no Active and Inactive Collection */
            else {
                $collection = $this->toArray($this->createCollection('Payment for Purchase'));
                $parameter['collection_id'] = $collection[1]['id'];
            }
        } else {
            return $bill;
        }
        /* Create Bills */
        return $this->connect->createBill($parameter, $optional);
    }
    public function deleteBill($parameter)
    {
        $response = $this->connect->deleteBill($parameter);
        if ($detect_mode = $this->detectMode(__FUNCTION__, $response, $parameter)) {
            return $detect_mode;
        }
        return $response;
    }
    public function getBill($parameter)
    {
        $response = $this->connect->getBill($parameter);
        if ($detect_mode = $this->detectMode(__FUNCTION__, $response, $parameter)) {
            return $detect_mode;
        }
        return $response;
    }
    public function bankAccountCheck($parameter)
    {
        $response = $this->connect->bankAccountCheck($parameter);
        if ($detect_mode = $this->detectMode(__FUNCTION__, $response, $parameter)) {
            return $detect_mode;
        }
        return $response;
    }
    public function getTransactionIndex($id, $parameter = array('page' => '1'))
    {
        $response = $this->connect->getTransactionIndex($id, $parameter);
        if ($detect_mode = $this->detectMode(__FUNCTION__, $response, $id, $parameter)) {
            return $detect_mode;
        }
        return $response;
    }
    public function getPaymentMethodIndex($parameter)
    {
        $response = $this->connect->getPaymentMethodIndex($parameter);
        if ($detect_mode = $this->detectMode(__FUNCTION__, $response, $parameter)) {
            return $detect_mode;
        }
        return $response;
    }
    public function updatePaymentMethod($parameter)
    {
        $response = $this->connect->updatePaymentMethod($parameter);
        if ($detect_mode = $this->detectMode(__FUNCTION__, $response, $parameter)) {
            return $detect_mode;
        }
        return $response;
    }
    public function getBankAccountIndex($parameter = array('account_numbers' => array('0', '1')))
    {
        $response = $this->connect->getBankAccountIndex($parameter);
        if ($detect_mode = $this->detectMode(__FUNCTION__, $response, $parameter)) {
            return $detect_mode;
        }
        return $response;
    }
    public function getBankAccount($parameter)
    {
        $response = $this->connect->getBankAccount($parameter);
        if ($detect_mode = $this->detectMode(__FUNCTION__, $response, $parameter)) {
            return $detect_mode;
        }
        return $response;
    }
    public function createBankAccount($parameter)
    {
        $response = $this->connect->createBankAccount($parameter);
        if ($detect_mode = $this->detectMode(__FUNCTION__, $response, $parameter)) {
            return $detect_mode;
        }
        return $response;
    }
    public function bypassBillplzPage($bill)
    {
        $bills = \json_decode($bill, true);
        if ($bills['reference_1_label'] !== 'Bank Code') {
            return \json_encode($bill);
        }
        $fpxBanks = $this->toArray($this->getFpxBanks());
        if ($fpxBanks[0] !== 200) {
            return \json_encode($bill);
        }
        $found = false;
        foreach ($fpxBanks[1]['banks'] as $bank) {
            if ($bank['name'] === $bills['reference_1']) {
                if ($bank['active']) {
                    $found = true;
                    break;
                }
                return \json_encode($bill);
            }
        }
        if ($found) {
            $bills['url'] .= '?auto_submit=true';
        }
        return json_encode($bills);
    }
    public function getFpxBanks()
    {
        $response = $this->connect->getFpxBanks();
        if ($detect_mode = $this->detectMode(__FUNCTION__, $response)) {
            return $detect_mode;
        }
        return $response;
    }
    public function toArray($json)
    {
        return $this->connect->toArray($json);
    }
}
