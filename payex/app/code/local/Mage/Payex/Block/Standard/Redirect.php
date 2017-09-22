<?php
/**
 * PayEx module for Magento
 * Created by Ravindra Pratap Singh, ravisinghengg@gmail.com
 *
 **/


class Mage_Payex_Block_Standard_Redirect extends Mage_Core_Block_Template
{
    public function __construct()
    {
        parent::__construct();
        
        $standard = Mage::getModel('payex/standard');
        $this->setTemplate('payex/standard/redirect_standardwindow.phtml');
        $standard->manualInstall();
        
        //
        // Save the order into the payex_order_status table
        //
		$read = Mage::getSingleton('core/resource')->getConnection('core_read');
    	$row = $read->fetchRow("select * from payex_order_status where orderid = " . $standard->getCheckout()->getLastRealOrderId());
		if ($row == null) {
			$write = Mage::getSingleton('core/resource')->getConnection('core_write');
			$write->insert('payex_order_status', Array('orderid'=>$standard->getCheckout()->getLastRealOrderId()));
		}
    }
}
