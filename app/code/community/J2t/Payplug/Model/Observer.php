<?php

class J2t_Payplug_Model_Observer extends Mage_Core_Model_Session_Abstract
{
    const URL_AUTOCONFIG = 'https://www.payplug.fr/portal/ecommerce/autoconfig';
    protected function createCertFile($user, $pass){
        $process = curl_init(self::URL_AUTOCONFIG);
        
        curl_setopt($process, CURLOPT_USERPWD, $user.':'.$pass);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
        //curl_setopt($process, CURLOPT_SSLVERSION, 3);
        $answer = curl_exec($process);
        
        $errorCurl = curl_errno($process);
        
        curl_close($process);
        if($errorCurl == 0) {
            $jsonAnswer = json_decode($answer);
            $authorizationSuccess = false;
            if ($jsonAnswer->status == 200) {
                $authorizationSuccess = true;
                //$payplug_install = new InstallPayplug();
                if (!is_array($jsonAnswer->currencies)){
                    $currencies = implode(json_decode($jsonAnswer->currencies), ';');
                }
                else {
                    $currencies = $jsonAnswer->currencies[0];
                }
                if (($groups = Mage::app()->getRequest()->getPost('groups')) && isset($groups['j2tpayplug']) && isset($groups['j2tpayplug']['fields'])){
                    $groups['j2tpayplug']['fields']['private_key'] = array('value' => $jsonAnswer->yourPrivateKey);
                    $groups['j2tpayplug']['fields']['public_key'] = array('value' => $jsonAnswer->payplugPublicKey);
                    $groups['j2tpayplug']['fields']['module_url'] = array('value' => $jsonAnswer->url);
                    $groups['j2tpayplug']['fields']['min_amount'] = array('value' => $jsonAnswer->amount_min);
                    $groups['j2tpayplug']['fields']['max_amount'] = array('value' => $jsonAnswer->amount_max);
                    $groups['j2tpayplug']['fields']['currencies'] = array('value' => $currencies);
                    Mage::app()->getRequest()->setPost('groups', $groups);
                    
                }
            } else {
                if (($groups = Mage::app()->getRequest()->getPost('groups')) && isset($groups['j2tpayplug']) && isset($groups['j2tpayplug']['fields'])){
                    $groups['j2tpayplug']['fields']['private_key'] = array('value' => "");
                    $groups['j2tpayplug']['fields']['public_key'] = array('value' => "");
                    $groups['j2tpayplug']['fields']['module_url'] = array('value' => "");
                    $groups['j2tpayplug']['fields']['min_amount'] = array('value' => "");
                    $groups['j2tpayplug']['fields']['max_amount'] = array('value' => "");
                    $groups['j2tpayplug']['fields']['currencies'] = array('value' => "");
                    Mage::app()->getRequest()->setPost('groups', $groups);
                }
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('j2tpayplug')->__("Unable to retrieve account details."));
            }
            //$this->assign_context_for_PS_VERSION ('authorizationSuccess', $authorizationSuccess);
        } else {
            if (($groups = Mage::app()->getRequest()->getPost('groups')) && isset($groups['j2tpayplug']) && isset($groups['j2tpayplug']['fields'])){
                $groups['j2tpayplug']['fields']['private_key'] = array('value' => "");
                $groups['j2tpayplug']['fields']['public_key'] = array('value' => "");
                $groups['j2tpayplug']['fields']['module_url'] = array('value' => "");
                $groups['j2tpayplug']['fields']['min_amount'] = array('value' => "");
                $groups['j2tpayplug']['fields']['max_amount'] = array('value' => "");
                $groups['j2tpayplug']['fields']['currencies'] = array('value' => "");
                Mage::app()->getRequest()->setPost('groups', $groups);
            }
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('j2tpayplug')->__("CURL error %s", $errorCurl));
        }
    }
    
    public function saveConfigPayPlug(Varien_Event_Observer $observer)
    {
        
        if (($groups = Mage::app()->getRequest()->getPost('groups')) && isset($groups['j2tpayplug']) 
                && isset($groups['j2tpayplug']['fields']) && isset($groups['j2tpayplug']['fields']['user'])
                && isset($groups['j2tpayplug']['fields']['pwd']) && isset($groups['j2tpayplug']['fields']['user']['inherit'])
                && isset($groups['j2tpayplug']['fields']['pwd']['inherit'])){
            $groups['j2tpayplug']['fields']['private_key'] = array('inherit' => '1');
            $groups['j2tpayplug']['fields']['public_key'] = array('inherit' => '1');
            $groups['j2tpayplug']['fields']['module_url'] = array('inherit' => '1');
            $groups['j2tpayplug']['fields']['min_amount'] = array('inherit' => '1');
            $groups['j2tpayplug']['fields']['max_amount'] = array('inherit' => '1');
            $groups['j2tpayplug']['fields']['currencies'] = array('inherit' => '1');
            Mage::app()->getRequest()->setPost('groups', $groups);
        } if (($groups = Mage::app()->getRequest()->getPost('groups')) && isset($groups['j2tpayplug']) 
                && isset($groups['j2tpayplug']['fields']) && isset($groups['j2tpayplug']['fields']['user'])
                && isset($groups['j2tpayplug']['fields']['pwd']) && (isset($groups['j2tpayplug']['fields']['user']['inherit'])
                || isset($groups['j2tpayplug']['fields']['pwd']['inherit']))){
            throw new Exception( Mage::helper('j2tpayplug')->__("Both username and password must be defined.") );
        } else if (($groups = Mage::app()->getRequest()->getPost('groups')) && isset($groups['j2tpayplug']) 
                && isset($groups['j2tpayplug']['fields']) && isset($groups['j2tpayplug']['fields']['user'])
                && isset($groups['j2tpayplug']['fields']['pwd']) && isset($groups['j2tpayplug']['fields']['user']['value'])
                && isset($groups['j2tpayplug']['fields']['pwd']['value'])
                && ($user = $groups['j2tpayplug']['fields']['user']['value']) && ($pwd = $groups['j2tpayplug']['fields']['pwd']['value'])
                && $pwd != "******"){
            $this->createCertFile($user, $pwd);
        }
    }
    
    public function preDispatch(Varien_Event_Observer $observer)
    {
        if (Mage::getSingleton('admin/session')->isLoggedIn()) {
            $feedModel  = Mage::getModel('j2tpayplug/feed');
            /* @var $feedModel Mage_AdminNotification_Model_Feed */

            $feedModel->checkUpdate();
        }

    }
}

