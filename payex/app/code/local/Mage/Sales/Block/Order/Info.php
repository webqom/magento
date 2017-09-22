<?php
/**
 * PayEx module for Magento
 * Created by Ravindra Pratap Singh, ravisinghengg@gmail.com
 *
 **/
class Mage_Sales_Block_Order_Info extends Mage_Core_Block_Template
{
    protected $_links = array();

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('sales/order/info.phtml');
    }

    protected function _prepareLayout()
    {
        if ($headBlock = $this->getLayout()->getBlock('head')) {
            $headBlock->setTitle($this->__('Order # %s', $this->getOrder()->getRealOrderId()));
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
    
    /**
     * Retrieve current order model instance
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return Mage::registry('current_order');
    }

    public function addLink($name, $path, $label)
    {
        $this->_links[$name] = new Varien_Object(array(
            'name' => $name,
            'label' => $label,
            'url' => empty($path) ? '' : Mage::getUrl($path, array('order_id' => $this->getOrder()->getId()))
        ));
        return $this;
    }

    public function getLinks()
    {
        $this->checkLinks();
        return $this->_links;
    }

    private function checkLinks()
    {
        $order = $this->getOrder();
        if (!$order->hasInvoices()) {
        	unset($this->_links['invoice']);
        }
        if (!$order->hasShipments()) {
        	unset($this->_links['shipment']);
        }
        if (!$order->hasCreditmemos()) {
        	unset($this->_links['creditmemo']);
        }
    }

    public function getReorderUrl($order)
    {
        return $this->getUrl('sales/order/reorder', array('order_id' => $order->getId()));
    }

    public function getPrintUrl($order)
    {
        return $this->getUrl('sales/order/print', array('order_id' => $order->getId()));
    }
}
