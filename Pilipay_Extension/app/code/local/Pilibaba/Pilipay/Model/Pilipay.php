<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Pilibaba_Pilipay_Model_Pilipay extends Pilibaba_Pilipay_Model_Pilipay_Abstract {
    //put your code here
    var $pilipay_config;
    /**
     *Pilipay gateway
     */
    var $pilipay_gateway_new;

    function __construct(){
            $helper = Mage::helper('pilipay');
            $this->pilipay_config = $helper->getDefaultConfigParams();
            $this->pilipay_gateway_new = $helper->getGatewayUrl();
    }
    
    function pilipaySubmit($pilipay_config) {
    	$this->__construct($pilipay_config);
    }
	
    /**
     * build signature result
     * @param $para_sort
     * return merchantNo+orderNo+orderAmount+sendTime sign result
     */
    function buildRequestMysign($para_sort) {
    $prestr = $para_sort['merchantNO'].$para_sort['orderNo'].$para_sort['orderAmount'].$para_sort['sendTime'];
    
            $mysign = "";
            switch (strtoupper(trim($this->pilipay_config['signType']))) {
                    case "MD5" :
                            $mysign = $this->md5Sign($prestr, $this->pilipay_config['appSecrect']);
                            break;
                    default :
                            $mysign = "";
            }
            //var_dump($mysign);
            return $mysign;
    }

    /**
     * Build give pilipay's request parameter array
     * @param $para_temp parameter before build
     * @return 
     */
    function buildRequestPara($para_temp) {
            //remove null and sign paramter
            $para_filter = $this->paraFilter($para_temp);

            $para_sort = $para_filter;

            //build sign result
            $mysign = $this->buildRequestMysign($para_sort);
            $para_sort['signMsg'] = $mysign;
            $para_sort['signType'] = strtoupper(trim($this->pilipay_config['signType']));
		
            return $para_sort;
    }
	
    /**
     * Create request with html form（default）
     * @param $para_temp request parameter array
     * @param $method submit method：post or get
     * @param $button_name submit button display value
     * @return 
     */
    function buildRequestForm($para_temp, $method, $button_name) {
            //Request paramter array
            $helper = Mage::helper('pilipay');
            $para = $this->buildRequestPara($para_temp);
            //var_dump($para); die();
            $sHtml = "<img src='{$helper->getPaymentMarkImageUrl()}' alt='pilipay' style='float:left; display:inline-block;' />"."<h2>Please wait we are redirecting to payment</h2>"
                    . "<form id='pilipaysubmit' name='pilipaysubmit' action='".$this->pilipay_gateway_new."' method='".$method."'>";
            while (list ($key, $val) = each ($para)) {
                if($key=="appSecrect")
                    continue;

                $sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
            }

            //submit button don't have name attribute
            $sHtml = $sHtml."<input type='submit' value='submit' style='display:none;'></form>";
            $sHtml = $sHtml."<script>document.forms['pilipaysubmit'].submit();</script>";

            return $sHtml;
    }	
    
    function buildResponseForm($flag,$url){
        $sHtml = "<form id='pilipayResponse' name='pilipaysubmit' action='".$this->pilipay_gateway_new."' method='post'>";
        $sHtml.= "<input type='hidden' name='result' value='".$flag."'/>";
        $sHtml.= "<input type='hidden' name='redirecturl' value='".$url."'/>";
        $sHtml = $sHtml."<input type='submit' value='submit' style='display:none;'></form>";
        $sHtml = $sHtml."<script>document.forms['pilipayResponse'].submit();</script>";
        return $sHtml;
    }
    
}
