<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
 
class Pilibaba_Pilipay_Model_Pilipay_Abstract extends Mage_Core_Model_Abstract {
    //put your code here
    /**
    * remove array null and sign parameter
    * @param $para parameter array
    * return 
    */
    public function paraFilter($para) {
           $para_filter = array();
           while (list ($key, $val) = each ($para)) {
                   if($key == "sign" || $key == "signType" || $val == "") continue;
                   else	$para_filter[$key] = $para[$key];
           }
           return $para_filter;
    }
    /**
    * signature
    * @param $prestr 
    * @param $key merchant key
    * return signature result
    */
    public function md5Sign($prestr, $key) {
           $prestr = $prestr . $key;
           //var_dump($prestr);
           return md5($prestr);
    }

    /**
    * verify signature
    * @param $prestr signature before
    * @param $sign signature result
    * @param $key merchant key
    * return verify result
    */
    public function md5Verify($prestr, $sign, $key) {
           $prestr = $prestr . $key;
           $mysgin = md5($prestr);
           
           if($mysgin == $sign) {
                   return true;
           }
           else {
                   return false;
           }
    }
}
