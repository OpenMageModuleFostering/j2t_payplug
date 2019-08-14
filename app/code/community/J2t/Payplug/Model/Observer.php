<?php

class J2t_Payplug_Model_Observer extends Mage_Core_Model_Session_Abstract
{
    const URL_AUTOCONFIG = 'https://www.payplug.fr/portal/ecommerce/autoconfig';
    const URL_AUTOCONFIG_TEST = 'https://www.payplug.fr/portal/test/ecommerce/autoconfig';
    protected function createCertFile($user, $pass){
        $url = self::URL_AUTOCONFIG;
        if (($groups = Mage::app()->getRequest()->getPost('groups'))) {
            if (isset($groups['j2tpayplug']) && isset($groups['j2tpayplug']['fields'])
                    && isset($groups['j2tpayplug']['fields']['sandbox']) && isset($groups['j2tpayplug']['fields']['sandbox']['value'])
                    && $groups['j2tpayplug']['fields']['sandbox']['value']){
                $url = self::URL_AUTOCONFIG_TEST;
            }
        }
        
        /*$curl = new Varien_Http_Adapter_Curl();
        $curl->setConfig(array(
            //'verifypeer' => strpos($url, 'https://') !== false,
            //'header' => true,
            'timeout' => 35,
        ));

        
        $curl->addOption(CURLOPT_USERPWD, $user.':'.$pass);
        $curl->addOption(CURLOPT_RETURNTRANSFER, true);
        $curl->addOption(CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1  );
        
        $curl->write(Zend_Http_Client::GET, $url, '1.1');
        $answer = $curl->read();
        $errorCurl = $curl->getErrno();
        if ($curl->getErrno() || $curl->getError()) {
            //throw new Exception(Mage::helper('wordpress')->__('CURL (%s): %s', $curl->getErrno(), $curl->getError()));
            $errorCurl = $errorCurl." ".$curl->getError();
        } else {
            $answer = preg_split('/^r?$/m', $answer, 2); 
            $answer = trim($answer[1]);
        }
        
        
        $curl->close();*/
        
        
        ////////////////////////////////////////
        
        $process = curl_init($url);
        //$process = curl_init(self::URL_AUTOCONFIG);
        
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
}

