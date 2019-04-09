<?php

/**
 * Billplz OpenCart Plugin
 *
 * @package Payment Gateway
 * @author Billplz Sdn Bhd
 * @version 3.3.1
 */
class ModelPaymentBillplz extends Model
{

    public function getMethod($address, $total)
    {

        $this->load->language('payment/billplz');

        if ($this->config->get('billplz_status')) {

            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int) $this->config->get('billplz_geo_zone_id') . "' AND country_id = '" . (int) $address['country_id'] . "' AND (zone_id = '" . (int) $address['zone_id'] . "' OR zone_id = '0')");

            if ($total < $this->config->get('billplz_minlimit') && $this->config->get('billplz_minlimit') > 0) {
                $status = false;
            } else if (!$this->config->get('billplz_geo_zone_id')) {
                $status = true;
            } elseif ($query->num_rows) {
                $status = true;
            } else {
                $status = false;
            }
        } else {
            $status = false;
        }

        $method_data = array();

        if ($status) {
            $method_data = array(
                'code' => 'billplz',
                'title' => $this->language->get('text_title'),
                'sort_order' => $this->config->get('billplz_sort_order'),
            );
        }

        return $method_data;
    }

}
