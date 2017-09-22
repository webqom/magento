<?php
/**
 * PayEx module for Magento
 * Created by Ravindra Pratap Singh, ravisinghengg@gmail.com
 *
 **/
class Mage_Payex_Model_System_Config_Source_Popup
{

    public function toOptionArray()
    {
        return array(
            array('value'=>1, 'label'=>Mage::helper('adminhtml')->__('Popup is disabled - Redirect (1)')),
            array('value'=>2, 'label'=>Mage::helper('adminhtml')->__('Popup is enabled (2)')),
        );
    }

}
