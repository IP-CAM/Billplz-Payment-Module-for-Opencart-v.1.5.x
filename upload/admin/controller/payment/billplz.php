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

    private $error = array();

    public function index()
    {
        $this->load->language('payment/billplz');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
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

        $this->data['billplz_api_key'] = $this->language->get('billplz_api_key');
        $this->data['billplz_collection_id'] = $this->language->get('billplz_collection_id');
        $this->data['billplz_x_signature'] = $this->language->get('billplz_x_signature');
        $this->data['entry_host'] = $this->language->get('entry_host');
        $this->data['entry_minlimit'] = $this->language->get('entry_minlimit');
        $this->data['entry_completed_status'] = $this->language->get('entry_completed_status');
        $this->data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
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

        if (isset($this->error['api_key'])) {
            $this->data['error_api_key'] = $this->error['api_key'];
        } else {
            $this->data['error_api_key'] = '';
        }

        if (isset($this->error['collection_id'])) {
            $this->data['error_collection_id'] = $this->error['collection_id'];
        } else {
            $this->data['error_collection_id'] = '';
        }

        if (isset($this->error['x_signature'])) {
            $this->data['error_x_signature'] = $this->error['x_signature'];
        } else {
            $this->data['error_x_signature'] = '';
        }

        $this->data['breadcrumbs'] = array();

        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => false,
        );

        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_payment'),
            'href' => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: ',
        );

        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('payment/billplz', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: ',
        );

        $this->data['action'] = $this->url->link('payment/billplz', 'token=' . $this->session->data['token'], 'SSL');

        $this->data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');

        if (isset($this->request->post['billplz_api_key_value'])) {
            $this->data['billplz_api_key_value'] = $this->request->post['billplz_api_key_value'];
        } else {
            $this->data['billplz_api_key_value'] = $this->config->get('billplz_api_key_value');
        }

        if (isset($this->request->post['billplz_collection_id_value'])) {
            $this->data['billplz_collection_id_value'] = $this->request->post['billplz_collection_id_value'];
        } else {
            $this->data['billplz_collection_id_value'] = $this->config->get('billplz_collection_id_value');
        }

        if (isset($this->request->post['billplz_x_signature_value'])) {
            $this->data['billplz_x_signature_value'] = $this->request->post['billplz_x_signature_value'];
        } else {
            $this->data['billplz_x_signature_value'] = $this->config->get('billplz_x_signature_value');
        }

        if (isset($this->request->post['billplz_minlimit'])) {
            $this->data['billplz_minlimit'] = $this->request->post['billplz_minlimit'];
        } else {
            $this->data['billplz_minlimit'] = $this->config->get('billplz_minlimit');
        }

        if (isset($this->request->post['billplz_completed_status_id'])) {
            $this->data['billplz_completed_status_id'] = $this->request->post['billplz_completed_status_id'];
        } else {
            $this->data['billplz_completed_status_id'] = $this->config->get('billplz_completed_status_id');
        }

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

        $this->load->model('localisation/order_status');

        $this->data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        if (isset($this->request->post['billplz_geo_zone_id'])) {
            $this->data['billplz_geo_zone_id'] = $this->request->post['billplz_geo_zone_id'];
        } else {
            $this->data['billplz_geo_zone_id'] = $this->config->get('billplz_geo_zone_id');
        }

        $this->load->model('localisation/geo_zone');
        $this->data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        $this->layout = 'common/layout';
        $this->template = 'payment/billplz.tpl';
        $this->children = array(
            'common/header',
            'common/footer',
        );

        $this->response->setOutput($this->render());
    }

    private function validate()
    {
        if (!$this->user->hasPermission('modify', 'payment/billplz')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['billplz_api_key_value']) {
            $this->error['api_key'] = $this->language->get('error_api_key');
        }

        if (!$this->request->post['billplz_collection_id_value']) {
            $this->error['collection_id'] = $this->language->get('error_collection_id');
        }

        if (!$this->request->post['billplz_x_signature_value']) {
            $this->error['x_signature'] = $this->language->get('error_x_signature');
        }

        return !$this->error;
    }

}
