<?php
/**
 * J2t_Payplug
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@j2t-design.com so we can send you a copy immediately.
 *
 * @category   Magento extension
 * @package    J2t_Payplug
 * @copyright  Copyright (c) 2011 J2T DESIGN. (http://www.j2t-design.net)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class J2t_Payplug_PaymentController extends Mage_Core_Controller_Front_Action
{
    const PAYMENT_STATUS_PAID = 0;
    const PAYMENT_STATUS_REFUND = 4;
    const PAYMENT_STATUS_CANCEL = 2;
    
    public function redirectAction()
    {
        $payplug = Mage::getModel('j2tpayplug/paymentMethod');
        $url = $payplug->getPayplugCheckoutRedirection();
        $this->_redirectUrl($url);
    }
    
    
    protected function _createInvoice($order)
    {
        if (!$order->canInvoice()) {
            return;
        }
        /*$invoice = $order->prepareInvoice();
        $invoice->register()->capture();
        $order->addRelatedObject($invoice);*/
        
        ////////////////
        
        $invoice = $order->prepareInvoice();
        if (!$invoice->getTotalQty()) {
            Mage::throwException(Mage::helper('j2tpayplug')->__('Cannot create an invoice without products.'));
        }

        $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
        $invoice->register();
        /*$transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($invoice)
            ->addObject($invoice->getOrder());

        $transactionSave->save();*/
        $order->addRelatedObject($invoice);
    }
    
    public function ipnAction()
    {
        $headers = array();
        foreach ($_SERVER as $name => $value){
            if(substr($name, 0, 5) == 'HTTP_'){
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$name] = $value;
            } else if($name == "CONTENT_TYPE") {
                $headers["Content-Type"] = $value;
            } else if($name == "CONTENT_LENGTH") {
                $headers["Content-Length"] = $value;
            } else{
                $headers[$name]=$value;
            }
        }
        $headers = array_change_key_case($headers, CASE_UPPER);
        if(!isset($headers['PAYPLUG-SIGNATURE'])){
            header($_SERVER['SERVER_PROTOCOL'] . ' 403 Signature not provided', true, 403);
            die;
        }
        
        $signature = base64_decode($headers['PAYPLUG-SIGNATURE']);
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);
        
        $status = $data['status'];
        if($status == self::PAYMENT_STATUS_PAID || $status == self::PAYMENT_STATUS_REFUND || $status == self::PAYMENT_STATUS_CANCEL){
            // Check signature
            $publicKey = Mage::getStoreConfig('payment/j2tpayplug/public_key', $data['custom_data']);
            $checkSignature = openssl_verify($body , $signature, $publicKey, OPENSSL_ALGO_SHA1);
            if($checkSignature == 1){
                $bool_sign = true;
            } else if($checkSignature == 0){
                echo Mage::helper('j2tpayplug')->__('Invalid signature');
                header($_SERVER['SERVER_PROTOCOL'] . ' 403 Invalid signature', true, 403);
                die;
            } else{
                echo Mage::helper('j2tpayplug')->__('Error while checking signature');
                header($_SERVER['SERVER_PROTOCOL'] . ' 500 Error while checking signature', true, 500);
                die;
            }

            if($data && $bool_sign){
                $order = Mage::getModel('sales/order')->loadByIncrementId($data['order']);
                if($orderId = $order->getId()){
                    // If status paid
                    if($status == self::PAYMENT_STATUS_PAID) {
                        // If order state is already paid by payplug
                        if($order->getState() == Mage::getStoreConfig('payment/j2tpayplug/complete_order_status', $data['custom_data'])){
                            // Order is already marked as paid - return http 200 OK
                        }
                        // If order state is payment in progress by payplug
                        elseif($order->getState() == Mage::getStoreConfig('payment/j2tpayplug/new_order_status', $data['custom_data'])){
                            /*$order->setState(Mage::getStoreConfig('payment/j2tpayplug/complete_order_status', $data['custom_data']));
                            $order->setStatus(Mage::getStoreConfig('payment/j2tpayplug/complete_order_status', $data['custom_data']));
                            $order->addStatusHistoryComment(Mage::helper('j2tpayplug')->__('Payment has been captured by Payment Gateway. Transaction id: %s', $data['id_transaction']));
                            $order->save();*/
                            
                            $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, Mage::helper('j2tpayplug')->__('Payment has been captured by Payment Gateway. Transaction id: %s', $data['id_transaction']));
                            // save transaction ID
                            $order->getPayment()->setLastTransId($data['id_transaction']);
                            // send new order email
                            $order->sendNewOrderEmail();
                            $order->setEmailSent(true);
                            
                            if (Mage::getStoreConfig('payment/j2tpayplug/invoice', $data['custom_data'])){
                                $this->_createInvoice($order);
                            }
                            
                            $order->save();
                            
                            /*if (Mage::getStoreConfig('payment/j2tpayplug/invoice', $data['custom_data'])){
                                //generate invoice
                                try {
                                    
                                    if(!$order->canInvoice())
                                    {
                                        Mage::throwException(Mage::helper('j2tpayplug')->__('Cannot create an invoice.'));
                                    }
                                    $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
                                    if (!$invoice->getTotalQty()) {
                                        Mage::throwException(Mage::helper('j2tpayplug')->__('Cannot create an invoice without products.'));
                                    }

                                    $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
                                    $invoice->register();
                                    $transactionSave = Mage::getModel('core/resource_transaction')
                                        ->addObject($invoice)
                                        ->addObject($invoice->getOrder());

                                    $transactionSave->save();
                                }
                                catch (Mage_Core_Exception $e) {

                                }
                            }*/
                            
                        }
                    } // If status refund
                    else if($status == self::PAYMENT_STATUS_CANCEL){
                        $order->cancel();
                        $order->addStatusToHistory(Mage_Sales_Model_Order::STATE_CANCELED, Mage::helper('j2tpayplug')->__('Payment canceled by Payment Gateway. Transaction id: %s', $data['id_transaction']));
                        $order->save();
                    }
                    else if($status == self::PAYMENT_STATUS_REFUND){
                        $invoices = array();
                        foreach ($order->getInvoiceCollection() as $invoice) {
                            if ($invoice->canRefund()) {
                                $invoices[] = $invoice;
                            }
                        }
                        $service = Mage::getModel('sales/service_order', $order);
                        foreach ($invoices as $invoice) {
                            $creditmemo = $service->prepareInvoiceCreditmemo($invoice);
                            $creditmemo->refund();
                            $creditmemo->getInvoice()->save();
                            $creditmemo->save();
                        }
                        //if (!sizeof($invoices)){
                            $order->setState(Mage::getStoreConfig('payment/j2tpayplug/cancel_order_status', $data['custom_data']));
                            $order->setStatus(Mage::getStoreConfig('payment/j2tpayplug/cancel_order_status', $data['custom_data']));
                            $order->addStatusToHistory(Mage_Sales_Model_Order::STATE_CLOSED, Mage::helper('j2tpayplug')->__('Payment refunded by Payment Gateway. Transaction id: %s', $data['id_transaction']));
                            //$order->addStatusHistoryComment(Mage::helper('j2tpayplug')->__('Payment refunded by Payment Gateway. Transaction id: %s', $data['id_transaction']));
                            $order->save();
                        //}
                    }
                }
            } else{
                echo Mage::helper('j2tpayplug')->__('Error: missing or wrong parameters.');
                header($_SERVER['SERVER_PROTOCOL'] . ' 400 Missing or wrong parameters', true, 400);
                die;
            }
        }
        
    }
    
    protected function _getSession()
    {
        return Mage::getSingleton('checkout/session');
    }
    
    public function cancelAction()
    {
        //redirect to cart and error message payment has been declined
        $this->_getSession()->addError(Mage::helper('j2tpayplug')->escapeHtml(Mage::helper('j2tpayplug')->__('There has been a problem during the payment.')));
        $this->_redirect('checkout/cart');
    }
    
}
