<?php
/**
 * PayEx module for Magento
 * Created by Ravindra Pratap Singh, ravisinghengg@gmail.com
 *
 **/


$installer = $this;

$installer->startSetup();

$installer->run("

		delete from {$installer->getTable('core_resource')} where code = 'payex_setup';
		
		CREATE TABLE if not exists `payex_order_status` (
  	`orderid` VARCHAR(45) NOT NULL,
  	`orderRef` VARCHAR(45) NOT NULL,
	`transactionNumber` VARCHAR(45) NOT NULL,
	`transactionRef` VARCHAR(45) NOT NULL,
  	`status` INTEGER UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = unpaid, 1 = paid',
  	`date` VARCHAR(45) NOT NULL
		);
		
    ");


$installer->endSetup();

