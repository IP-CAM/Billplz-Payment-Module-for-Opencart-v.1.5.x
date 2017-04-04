<?php
/**
 * Billplz OpenCart Plugin
 * 
 * @package Payment Gateway
 * @author Wanzul-Hosting.com <sales@wanzul-hosting.com>
 * @version 1.5.0
 */
 
class ControllerPaymentBillplz extends Controller {
    
    private $error = array(); 

    public function index() {
        $this->load->language('payment/billplz');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $this->model_setting_setting->editSetting('billplz', $this->request->post);				
            $this->session->data['success'] = $this->language->get('text_success');
            $this->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
        }
        
        $this->data['heading_title'] = $this->language->get('heading_title');

        $this->data['text_enabled'] = $this->language->get('text_enabled');
        $this->data['text_disabled'] = $this->language->get('text_disabled');
        $this->data['text_all_zones'] = $this->language->get('text_all_zones');
        $this->data['text_yes'] = $this->language->get('text_yes');
        $this->data['text_no'] = $this->language->get('text_no');

        $this->data['entry_merchantid'] = $this->language->get('entry_merchantid');
        $this->data['entry_verifykey'] = $this->language->get('entry_verifykey');
		$this->data['entry_host'] = 'Production/Sandbox Mode';
		//$this->data['entry_minlimit'] = 'Minimum Limit';
		$this->data['entry_delivery'] = 'Notification';
        $this->data['entry_order_status'] = $this->language->get('entry_order_status');
        $this->data['entry_pending_status'] = $this->language->get('entry_pending_status');
        $this->data['entry_success_status'] = $this->language->get('entry_success_status');
        $this->data['entry_failed_status'] = $this->language->get('entry_failed_status');	
        $this->data['entry_status'] = $this->language->get('entry_status');
        $this->data['entry_sort_order'] = $this->language->get('entry_sort_order');

        $this->data['button_save'] = $this->language->get('button_save');
        $this->data['button_cancel'] = $this->language->get('button_cancel');

        $this->data['tab_general'] = $this->language->get('tab_general');

        if (isset($this->error['warning'])) {
            $this->data['error_warning'] = $this->error['warning'];
        } else {
            $this->data['error_warning'] = '';
        }

        if (isset($this->error['account'])) {
            $this->data['error_merchantid'] = $this->error['account'];
        } else {
            $this->data['error_merchantid'] = '';
        }	

        if (isset($this->error['secret'])) {
            $this->data['error_verifykey'] = $this->error['secret'];
        } else {
            $this->data['error_verifykey'] = '';
        }

        $this->data['breadcrumbs'] = array();

        $this->data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_home'),
            'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),       		
            'separator' => false
        );

        $this->data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_payment'),
            'href'      => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
        );

        $this->data['breadcrumbs'][] = array(
            'text'      => $this->language->get('heading_title'),
            'href'      => $this->url->link('payment/billplz', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
        );

        $this->data['action'] = $this->url->link('payment/billplz', 'token=' . $this->session->data['token'], 'SSL');

        $this->data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');

        if (isset($this->request->post['billplz_merchantid'])) {
            $this->data['billplz_merchantid'] = $this->request->post['billplz_merchantid'];
        } else {
            $this->data['billplz_merchantid'] = $this->config->get('billplz_merchantid');
        }

        if (isset($this->request->post['billplz_verifykey'])) {
            $this->data['billplz_verifykey'] = $this->request->post['billplz_verifykey'];
        } else {
            $this->data['billplz_verifykey'] = $this->config->get('billplz_verifykey');
        }

		if (isset($this->request->post['billplz_sandbox'])) {
            $this->data['billplz_sandbox'] = $this->request->post['billplz_sandbox'];
        } else {
            $this->data['billplz_sandbox'] = $this->config->get('billplz_sandbox');
        }
		
		if (isset($this->request->post['billplz_delivery'])) {
            $this->data['billplz_delivery'] = $this->request->post['billplz_delivery'];
        } else {
            $this->data['billplz_delivery'] = $this->config->get('billplz_delivery');
        }
		
		//if (isset($this->request->post['billplz_minlimit'])) {
        //    $this->data['billplz_minlimit'] = $this->request->post['billplz_minlimit'];
        //} else {
        //    $this->data['billplz_minlimit'] = $this->config->get('billplz_minlimit');
        //}

        if (isset($this->request->post['billplz_order_status_id'])) {
            $this->data['billplz_order_status_id'] = $this->request->post['billplz_order_status_id'];
        } else {
            $this->data['billplz_order_status_id'] = $this->config->get('billplz_order_status_id'); 
        }

        if (isset($this->request->post['billplz_pending_status_id'])) {
            $this->data['billplz_pending_status_id'] = $this->request->post['billplz_pending_status_id'];
        } else {
            $this->data['billplz_pending_status_id'] = $this->config->get('billplz_pending_status_id');
        }

        if (isset($this->request->post['billplz_success_status_id'])) {
            $this->data['billplz_success_status_id'] = $this->request->post['billplz_success_status_id'];
        } else {
            $this->data['billplz_success_status_id'] = $this->config->get('billplz_success_status_id');
        }

        if (isset($this->request->post['billplz_failed_status_id'])) {
            $this->data['billplz_failed_status_id'] = $this->request->post['billplz_failed_status_id'];
        } else {
            $this->data['billplz_failed_status_id'] = $this->config->get('billplz_failed_status_id');
        }

        $this->load->model('localisation/order_status');

        $this->data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        if (isset($this->request->post['billplz_status'])) {
            $this->data['billplz_status'] = $this->request->post['billplz_status'];
        } else {
            $this->data['billplz_status'] = $this->config->get('billplz_status');
        }

        if (isset($this->request->post['billplz_sort_order'])) {
            $this->data['billplz_sort_order'] = $this->request->post['billplz_sort_order'];
        } else {
            $this->data['billplz_sort_order'] = $this->config->get('billplz_sort_order');
        }

        $this->layout = 'common/layout';
        $this->template = 'payment/billplz.tpl';
        $this->children = array(
            'common/header',
            'common/footer',
        );

        $this->response->setOutput($this->render());
    }

    private function validate() {
        if (!$this->user->hasPermission('modify', 'payment/billplz')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['billplz_merchantid']) {
            $this->error['account'] = $this->language->get('error_merchantid');
        }

        if (!$this->request->post['billplz_verifykey']) {
            $this->error['secret'] = $this->language->get('error_verifykey');
        }
		//Akan datang, masukkan code untuk verify API Key
		//Dan Collection ID Disini
        if (!$this->error) {
            return true;
        } else {
            return false;
        }	
    }
}
?>
