<?php
 
/**
* Our test CC module adapter
*/
class J2t_Payplug_Model_PaymentMethod extends Mage_Payment_Model_Method_Abstract
{
    /**
    * unique internal payment method identifier
    *
    * @var string [a-z0-9_]
    */
    protected $_code = 'j2tpayplug';
    protected $_isInitializeNeeded      = true;
    protected $_formBlockType = 'j2tpayplug/form';
    protected $_infoBlockType = 'j2tpayplug/info';
    /**
     * Is this payment method a gateway (online auth/charge) ?
     */
    protected $_isGateway               = true;
 
    /**
     * Can authorize online?
     */
    protected $_canAuthorize            = false;
 
    /**
     * Can capture funds online?
     */
    protected $_canCapture              = false;
 
    /**
     * Can capture partial amounts online?
     */
    protected $_canCapturePartial       = false;
 
    /**
     * Can refund online?
     */
    protected $_canRefund               = false;
 
    /**
     * Can void transactions online?
     */
    protected $_canVoid                 = false;
 
    /**
     * Can use this payment method in administration panel?
     */
    protected $_canUseInternal          = false;
 
    /**
     * Can show this payment method as an option on checkout payment page?
     */
    protected $_canUseCheckout          = true;
 
    /**
     * Is this payment method suitable for multi-shipping checkout?
     */
    protected $_canUseForMultishipping  = false;
 
    /**
     * Can save credit card information for future processing?
     */
    protected $_canSaveCc = false;
 
    /**
     * Here you will need to implement authorize, capture and void public methods
     *
     * @see examples of transaction specific public methods such as
     * authorize, capture and void in Mage_Paygate_Model_Authorizenet
     */
    
    public function canUseForCurrency($currencyCode)
    {
        return Mage::getStoreConfig('payment/j2tpayplug/currencies', $this->getQuote()->getStoreId()) == $currencyCode;
        //return $this->getConfig()->isCurrencyCodeSupported($currencyCode);
    }
    
    public function getSession()
    {
        return Mage::getSingleton('paypal/session');
    }

    /**
     * Get checkout session namespace
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Get current quote
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }
    
    protected function _getOrder()
    {   
        return $this->_order;
    }
    
    public function initialize($paymentAction, $stateObject)
    {
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();
        
        $state = Mage::getStoreConfig('payment/j2tpayplug/new_order_status', $order->getStoreId());
        
        //$state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        $stateObject->setState($state);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);
    }
    
    public function getPayplugCheckoutRedirection()
    {
        $orderIncrementId = $this->getCheckout()->getLastRealOrderId();
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        
        $url_payment= Mage::getStoreConfig('payment/j2tpayplug/module_url', $this->getQuote()->getStoreId());
        
        $params = array(
            'amount'        =>  ($order->getGrandTotal()*100),
            'custom_data'   =>  $order->getStoreId(),
            'origin'        =>  'Magento  '.Mage::getVersion().' module '.Mage::getConfig()->getModuleConfig("J2t_Payplug")->version,
            'currency'      =>  $order->getOrderCurrencyCode(),
            'ipn_url'       =>  Mage::getUrl('j2tpayplug/payment/ipn'),
            'cancel_url'    =>  Mage::getUrl('j2tpayplug/payment/cancel'),
            'return_url'    =>  Mage::getUrl('checkout/onepage/success'),
            'email'         =>  $order->getCustomerEmail(),
            'firstname'     =>  $order->getCustomerFirstname(),
            'lastname'      =>  $order->getCustomerLastname(),
            //'order'         =>  $this->getQuote()->getId(),
            'order'         =>  $orderIncrementId,
            'customer'      =>  $order->getCustomerId()
        );
        
        
        $url_params = http_build_query($params);
        $privatekey = Mage::getStoreConfig('payment/j2tpayplug/private_key', $this->getQuote()->getStoreId());
        openssl_sign($url_params, $signature, $privatekey, $signature_alg = OPENSSL_ALGO_SHA1);
        $url_param_base_encode = base64_encode($url_params);
        $signature = base64_encode($signature);
        $redirect_url = $url_payment."?data=".urlencode($url_param_base_encode)."&sign=".urlencode($signature);
        
        return $redirect_url;
    }
    
    public function isAvailable($quote = null)
    {
        $min = Mage::getStoreConfig('payment/j2tpayplug/min_amount', $quote ? $quote->getStoreId() : null);
        $max = Mage::getStoreConfig('payment/j2tpayplug/max_amount', $quote ? $quote->getStoreId() : null);
        if (parent::isAvailable($quote) && $quote && $quote->getGrandTotal() >= $min && $quote->getGrandTotal() <= $max
                && Mage::getStoreConfig('payment/j2tpayplug/private_key', $quote ? $quote->getStoreId() : null)
                && Mage::getStoreConfig('payment/j2tpayplug/public_key', $quote ? $quote->getStoreId() : null)
                && Mage::getStoreConfig('payment/j2tpayplug/module_url', $quote ? $quote->getStoreId() : null)
                && Mage::getStoreConfig('payment/j2tpayplug/currencies', $quote ? $quote->getStoreId() : null)
                        ) {
            return true;
        }
        return false;
    }
    
    
    
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('j2tpayplug/payment/redirect', array('_secure' => true));
    }
    
}