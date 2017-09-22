<?php
/**
 * PayEx module for Magento
 * Created by Ravindra Pratap Singh, ravisinghengg@gmail.com
 *
 **/

class Mage_Payex_Block_Standard_Failed extends Mage_Core_Block_Template
{
	public function __construct()
    {
        parent::__construct();
        
        $this->setTemplate('payex/standard/failed.phtml');
	}
}
