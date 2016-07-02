<?php
/**
 * Billplz OpenCart Plugin
 * 
 * @package Payment Gateway
 * @author Wanzul-Hosting.com <sales@wanzul-hosting.com>
 * @version 2.0
 */
//mid as apikey vkey as collection id
class ControllerPaymentBillplz extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('payment/billplz');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('billplz', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
		}

		$data['heading_title'] = $this->language->get('heading_title');
		
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_all_zones'] = $this->language->get('text_all_zones');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');

		$data['entry_mid'] = $this->language->get('entry_mid');
		$data['entry_vkey'] = $this->language->get('entry_vkey');
		$data['entry_host'] = 'Production/Sandbox Mode';
		$data['entry_minlimit'] = 'Minimum Limit';
		$data['entry_delivery'] = 'Notification';
		$data['entry_order_status'] = $this->language->get('entry_order_status');
		$data['entry_completed_status'] = $this->language->get('entry_completed_status');
		$data['entry_pending_status'] = $this->language->get('entry_pending_status');
		$data['entry_failed_status'] = $this->language->get('entry_failed_status');
		$data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');

		$data['help_vkey'] = $this->language->get('help_vkey');
		$data['help_minlimit'] = 'Set total minimum limit to enable Billplz';

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['mid'])) {
			$data['error_mid'] = $this->error['mid'];
		} else {
			$data['error_mid'] = '';
		}

		if (isset($this->error['vkey'])) {
			$data['error_vkey'] = $this->error['vkey'];
		} else {
			$data['error_vkey'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_payment'),
			'href' => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('payment/billplz', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['action'] = $this->url->link('payment/billplz', 'token=' . $this->session->data['token'], 'SSL');

		$data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');

		if (isset($this->request->post['billplz_mid'])) {
			$data['billplz_mid'] = $this->request->post['billplz_mid'];
		} else {
			$data['billplz_mid'] = $this->config->get('billplz_mid');
		}

		if (isset($this->request->post['billplz_vkey'])) {
			$data['billplz_vkey'] = $this->request->post['billplz_vkey'];
		} else {
			$data['billplz_vkey'] = $this->config->get('billplz_vkey');
		}
		
		if (isset($this->request->post['billplz_sandbox'])) {
			$data['billplz_sandbox'] = $this->request->post['billplz_sandbox'];
		} else {
			$data['billplz_sandbox'] = $this->config->get('billplz_sandbox');
		}
		
		if (isset($this->request->post['billplz_delivery'])) {
			$data['billplz_delivery'] = $this->request->post['billplz_delivery'];
		} else {
			$data['billplz_delivery'] = $this->config->get('billplz_delivery');
		}
		
		if (isset($this->request->post['billplz_minlimit'])) {
			$data['billplz_minlimit'] = $this->request->post['billplz_minlimit'];
		} else {
			$data['billplz_minlimit'] = $this->config->get('billplz_minlimit');
		}

		if (isset($this->request->post['billplz_order_status_id'])) {
			$data['billplz_order_status_id'] = $this->request->post['billplz_order_status_id'];
		} else {
			$data['billplz_order_status_id'] = $this->config->get('billplz_order_status_id');
		}
		
		if (isset($this->request->post['billplz_completed_status_id'])) {
			$data['billplz_completed_status_id'] = $this->request->post['billplz_completed_status_id'];
		} else {
			$data['billplz_completed_status_id'] = $this->config->get('billplz_completed_status_id');
		}
		
		if (isset($this->request->post['billplz_pending_status_id'])) {
			$data['billplz_pending_status_id'] = $this->request->post['billplz_pending_status_id'];
		} else {
			$data['billplz_pending_status_id'] = $this->config->get('billplz_pending_status_id');
		}
		
		if (isset($this->request->post['billplz_failed_status_id'])) {
			$data['billplz_failed_status_id'] = $this->request->post['billplz_failed_status_id'];
		} else {
			$data['billplz_failed_status_id'] = $this->config->get('billplz_failed_status_id');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['billplz_geo_zone_id'])) {
			$data['billplz_geo_zone_id'] = $this->request->post['billplz_geo_zone_id'];
		} else {
			$data['billplz_geo_zone_id'] = $this->config->get('billplz_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['billplz_status'])) {
			$data['billplz_status'] = $this->request->post['billplz_status'];
		} else {
			$data['billplz_status'] = $this->config->get('billplz_status');
		}

		if (isset($this->request->post['billplz_sort_order'])) {
			$data['billplz_sort_order'] = $this->request->post['billplz_sort_order'];
		} else {
			$data['billplz_sort_order'] = $this->config->get('billplz_sort_order');
		}
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('payment/billplz.tpl', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'payment/billplz')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['billplz_mid']) {
			$this->error['mid'] = $this->language->get('error_mid');
		}

		if (!$this->request->post['billplz_vkey']) {
			$this->error['vkey'] = $this->language->get('error_vkey');
		}

		return !$this->error;
	}
}
