<?php 
/**
 * Billplz OpenCart Plugin
 * 
 * @package Payment Gateway
 * @author Wanzul-Hosting.com <sales@wanzul-hosting.com>
 * @version 1.5.0
 */

class ModelPaymentBillplz extends Model {
    
    public function getMethod($address) {
		if ($this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false) < $this->config->get('billplz_minlimit')){
			return null;
		}
        $this->load->language('payment/billplz');

        if ($this->config->get('billplz_status')) {
            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('billplz_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

            if (!$this->config->get('billplz_geo_zone_id')) {
                $status = TRUE;
            } elseif ($query->num_rows) {
                $status = TRUE;
            } else {
                $status = FALSE;
            }	
      	} else {
            $status = FALSE;
        }
		
        $method_data = array();
	
        if ($status) {  
            $method_data = array( 
                'code'       => 'billplz',
                'title'      => $this->language->get('text_title'),
                'sort_order' => $this->config->get('billplz_sort_order')
                );
    	}
   
    	return $method_data;
    }
}
?>
