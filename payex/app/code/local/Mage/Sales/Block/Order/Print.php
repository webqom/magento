<?php
/**
 * PayEx module for Magento
 * Created by Ravindra Pratap Singh, ravisinghengg@gmail.com
 *
 **/

class Mage_Sales_Block_Order_Print extends Mage_Sales_Block_Items_Abstract
{
    protected function _prepareLayout()
    {
        if ($headBlock = $this->getLayout()->getBlock('head')) {
            $headBlock->setTitle($this->__('Print Order # %s', $this->getOrder()->getRealOrderId()));
        }
        $this->setChild(
            'payment_info',
            $this->helper('payment')->getInfoBlock($this->getOrder()->getPayment())
        );
    }

    public function getPaymentInfoHtml()
    {
        //return $this->getChildHtml('payment_info');
        
        $res = $this->getChildHtml('payment_info');
        
	$standard = Mage::getModel('payex/standard');
	$standard->manualInstall();
	
        //
				// Read info directly from the database   	
    		$read = Mage::getSingleton('core/resource')->getConnection('core_read');
    		$row = $read->fetchRow("select * from payex_order_status where orderid = " . $this->getOrder()->getIncrementId());
    		
    		if ($row['status'] == '1') {
	    		//
	    		// Payment has been made to this order
	    		$res .= "<table border='0' width='100%'>";
	    		if ($row['transactionNumber'] != '0') {
					$res .= "<tr><td>" . Mage::helper('payex')->__('PAYEX_LABEL_8') . "</td>";
					$res .= "<td>" . $row['transactionNumber'] . "</td></tr>";
				}
				if ($row['date'] != '0') {
					$res .= "<tr><td>" . Mage::helper('payex')->__('PAYEX_LABEL_9') . "</td>";
					$res .= "<td>" . $row['date'] . "</td></tr>";
				}
	    		$res .= "</table><br>";
	    		
	    	} else {
	    		$res .= "<br>" . Mage::helper('payex')->__('PAYEX_LABEL_11') . "<br>";
	    	}
    	
    	return $res;
    }
    
    public function getOrder()
    {
        return Mage::registry('current_order');
    }

    protected function _prepareItem(Mage_Core_Block_Abstract $renderer)
    {
        $renderer->setPrintStatus(true);

        return parent::_prepareItem($renderer);
    }

}

