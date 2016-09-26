<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Notify
 *
 * @author KumarM-IIT
 */
class Pilibaba_Pilipay_Model_Pilipay_Notify extends Pilibaba_Pilipay_Model_Pilipay_Abstract {
    //put your code here
    var $pilipay_config;

    public function __construct($pilipay_config){
        if(!$pilipay_config or empty($pilipay_config)){
            $helper = Mage::helper('pilipay');
          
            $pilipay_config = $helper->getDefaultConfigParams();
        }
        $this->pilipay_config = $pilipay_config;
    }
    
    public function pilipayNotify($pilipay_config) {
    	$this->__construct($pilipay_config);
    }
    /**
     * check notify_url is legal message from pilipay
     * @return verify result
     */
    public function verifyNotify($params){
                    
        if(empty($params)) {//check post array is null
                return false;
        }else {
                //build signature result
                $isSign = $this->getSignVerify($params, $params["signMsg"]);
                
                //get pilipay result
                $responseTxt = 0;
                if (! empty($params["payResult"])) {$responseTxt = $params["payResult"]; }

                //verfify
                if ($responseTxt==10 && $isSign) {
                        return $params["dealId"];
                } else {
                        return false;
                }
        }
    }
	
    /**
     * 
     * @param $para_temp 
     * @param $sign 
     * @return 
     */
    public function getSignVerify($para_temp, $sign) {
            $para_filter =  $this->paraFilter($para_temp);
            $prestr =  $para_filter['merchantNO'].$para_filter['orderNo'].$para_filter['orderAmount'].$para_filter['sendTime'];
            $isSgin = false;
           
            switch (strtoupper(trim($this->pilipay_config['signType']))) {
                    case "MD5" :
                            $isSgin = $this->md5Verify($prestr, $sign, $this->pilipay_config['appSecrect']);
                            break;
                    default :
                            $isSgin = false;
            }

            return $isSgin;
    }
}
