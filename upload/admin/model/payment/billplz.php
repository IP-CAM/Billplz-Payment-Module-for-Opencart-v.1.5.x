<?php
class ModelPaymentBillplz extends Model {

  public function install() {
    $this->db->query("
      CREATE TABLE `" . DB_PREFIX . "billplz_bill` (
        `billplz_bill_id` INT(11) NOT NULL AUTO_INCREMENT,
        `slug` varchar(255) NOT NULL,
        `order_id` varchar(255) NOT NULL,
        `paid` INT(1) DEFAULT 0 NOT NULL,
        UNIQUE (slug),
        KEY `order_id` (`order_id`),
        PRIMARY KEY `billplz_bill_id` (`billplz_bill_id`)
      ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
    ");
  }

  public function uninstall() {
    $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "billplz_bill`;");
  }
}