<?php

class ModelPaymentBillplz extends Model
{
    public function getMethod($address, $total)
    {
        $this->load->language('payment/billplz');

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int) $this->config->get('billplz_geo_zone_id') . "' AND country_id = '" . (int) $address['country_id'] . "' AND (zone_id = '" . (int) $address['zone_id'] . "' OR zone_id = '0')");

        if ($this->config->get('billplz_total') > 0 && $this->config->get('billplz_total') > $total) {
            $status = false;
        } elseif (!$this->config->get('billplz_geo_zone_id')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } else {
            $status = false;
        }

        $currencies = array('MYR');

        if (!in_array(strtoupper($this->currency->getCode()), $currencies)) {
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

    public function insertBill($order_id, $slug) {
      $qry = $this->db->query("INSERT INTO `" . DB_PREFIX . "billplz_bill` (`order_id`, `slug`) VALUES ('$order_id', '$slug')");  

      if ($qry) {
        return true;
      }

      $this->logger($qry);
      return false;
    }  

    public function getBill($slug) {
      $qry = $this->db->query("SELECT * FROM `" . DB_PREFIX . "billplz_bill` WHERE `slug` = '" . $slug . "' LIMIT 1");  

      if ($qry->num_rows) {
        return $qry->rows[0];
      }
      return false;
    }  

    public function markBillPaid($order_id, $slug) {
      $qry = $this->db->query("UPDATE `" . DB_PREFIX . "billplz_bill` SET `paid` = '1' WHERE `order_id` = '$order_id' AND `slug` = '$slug' AND `paid` = '0'");  

      if ($qry) {
        return true;
      }
      return false;
    }

    public function logger($message) {
        $log = new Log('billplz.log');
        $log->write(print_r($message, 1));
    }

}
