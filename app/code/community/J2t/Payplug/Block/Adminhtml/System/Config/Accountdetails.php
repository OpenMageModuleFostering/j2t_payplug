<?php

class J2t_Payplug_Block_Adminhtml_System_Config_Accountdetails extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        if (strlen($code = Mage::getSingleton('adminhtml/config_data')->getStore())) // store level
        {
            $store_id = Mage::getModel('core/store')->load($code)->getId();
        }
        elseif (strlen($code = Mage::getSingleton('adminhtml/config_data')->getWebsite())) // website level
        {
            $website_id = Mage::getModel('core/website')->load($code)->getId();
            $store_id = Mage::app()->getWebsite($website_id)->getDefaultStore()->getId();
        }
        else // default level
        {
            $store_id = 0;
        }

        $currency = Mage::getStoreConfig('payment/j2tpayplug/currencies', $store_id);
        $return_val = array();
        
        if (Mage::getStoreConfig('payment/j2tpayplug/user', $store_id) && Mage::getStoreConfig('payment/j2tpayplug/min_amount', $store_id) 
                && Mage::getStoreConfig('payment/j2tpayplug/max_amount', $store_id) && $currency){
            $return_val[] = Mage::helper('j2tpayplug')->__('User: %s', Mage::getStoreConfig('payment/j2tpayplug/user', $store_id));
            $return_val[] = Mage::helper('j2tpayplug')->__('Min allowed amount: %s %s', Mage::getStoreConfig('payment/j2tpayplug/min_amount', $store_id), $currency);
            $return_val[] = Mage::helper('j2tpayplug')->__('Max allowed amount: %s %s', Mage::getStoreConfig('payment/j2tpayplug/max_amount', $store_id), $currency);
            $return_val[] = Mage::helper('j2tpayplug')->__('Currency: %s', $currency);
        } else {
            $return_val[] = Mage::helper('j2tpayplug')->__('Not configured yet');
        }
        
        return implode("<br />", $return_val);
    }
}

