<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Pilibaba_Pilipay_Helper_Request extends Pilibaba_Pilipay_Helper_Data {
    //put your code here
    public function __construct(){
        parent::__construct();
    }
    public function arrangePostData(){
        $_quote = $this->getOrder();
        $billingAddress = $_quote->getBillingAddress();
        $shippingAddress = $_quote->getShippingAddress();
        $newObjData= array(
            "region_id"=>$this->getWarehouseData('isoStateCode'), 
            "region"=>$this->getWarehouseData('state'), 
            "postcode"=>$this->getWarehouseData('zipcode'), 
            "lastname"=>$this->getWarehouseData('lastName'), 
            "street"=>$this->getWarehouseData('address'), 
            "city"=>$this->getWarehouseData('city'), 
            "telephone"=>$this->getWarehouseData('tel'), 
            "country_id"=>$this->getWarehouseData('iso2CountryCode'), 
            "firstname"=>$this->getWarehouseData('firstName'), 
        );
        $billingAddress->addData($newObjData)->save();
        $shippingAddress->addData($newObjData)->save();
        
        $order_quote_id = $_quote->getIncrementId();
        $orderGrandAmount = (float)$_quote->getGrandTotal();
        $orderShipping = (float)$_quote->getShippingInclTax(); // including tax
        $orderTotal = $orderGrandAmount; // including tax
        $pageUrl = $this->getCheckoutPageUrl();
        $serverUrl = $this->getSuccessPageUrl();
        $products = array();
        $i = 0;
        $totalTaxOfProducts = 0;
        foreach($_quote->getAllVisibleItems() as $item) {
            $_item = array();
            $_product = $item->getProduct();
            $_item['productId']  = $_product->getId();
            $_item['name']  = $_product->getName();
//            $_item['price']  = (int)($_product->getFinalPrice()*100);
            $_item['price']  = (int)($item->getPriceInclTax()*100); // including tax (in cents)
            $totalTaxOfProducts += ($item->getPriceInclTax() - $item->getPrice());
            $_item['quantity'] = (int)$item->getQtyOrdered();
            $attr = $item->getProductOptions()['attributes_info'];
            $item_attr = array();
            foreach($attr as $v){
                $item_attr[$v['label']] = $v['value'];
            }
            $_item['attr'] = json_encode($item_attr);
            $_item['productURL'] = $_product->getProductUrl();
            $_item['pictureURL'] = $_product->getImageUrl();
            $_item['weight'] = $item->getWeight()?(int)$item->getWeight():0;
            $products[$i] = $_item;
            unset($_item);
            $i++;
        }
        $defaultConfigs = $this->getDefaultConfigParams();
        if($defaultConfigs){
            
            //if (function_exists('date_default_timezone_set'))
            //{
             // date_default_timezone_set('Asia/Shanghai');
            //}
            
            $totalTax = $_quote->getTaxAmount() - $_quote->getShippingTaxAmount() - $totalTaxOfProducts;
            $totalTax = max($totalTax, 0);
            $goods_parameter_obj = $products;
            $goods_parameter_str = urlencode(json_encode($goods_parameter_obj));
            
           // $bilingAddrObj= array("fax"=>$billingData->getFax(), "region"=>$billingData->getRegion(), "postcode"=>$billingData->getPostcode(), "lastname"=>$billingData->getLastname(), "street"=>$billingData->getStreet(), "city"=>$billingData->getCity(), "email"=>$billingData->getEmail(), "telephone"=>$billingData->getTelephone(), "country_id"=>$billingData->getCountryId(), "firstname"=>$billingData->getFirstname(), "prefix"=>$billingData->getPrefix(), "middlename"=>$billingData->getMiddlename(), "suffix"=>$billingData->getSuffix(), "company"=>$billingData->getCompany());
           // $shippingAddrObj = array("fax"=>$shippingData->getFax(), "region"=>$shippingData->getRegion(), "postcode"=>$shippingData->getPostcode(), "lastname"=>$shippingData->getLastname(), "street"=>$shippingData->getStreet(), "city"=>$shippingData->getCity(), "email"=>$shippingData->getEmail(), "telephone"=>$shippingData->getTelephone(), "country_id"=>$shippingData->getCountryId(), "firstname"=>$shippingData->getFirstname(), "prefix"=>$shippingData->getPrefix(), "middlename"=>$shippingData->getMiddlename(), "suffix"=>$shippingData->getSuffix(), "company"=>$shippingData->getCompany());
            
            //$shippingAddrObj_str = urlencode(json_encode($shippingAddrObj));
            //$bilingAddrObj_str = urlencode(json_encode($bilingAddrObj));
            
            
            $defaultConfigs['orderNo'] = $order_quote_id;
            $defaultConfigs['orderAmount'] = (int)($orderTotal*100);
            $defaultConfigs['shipper'] = (int)($orderShipping*100);
            
            $defaultConfigs['orderTime'] = date("Y-m-d h:i:s", time());
            $defaultConfigs['tax'] = (int)($totalTax*100);
            $defaultConfigs['sendTime'] = date("Y-m-d h:i:s", time());
            $defaultConfigs["pageUrl"]	= $pageUrl;
            $defaultConfigs["serverUrl"] = $serverUrl;
            $defaultConfigs["goodsList"] = $goods_parameter_str;
            //$defaultConfigs["billing_address"]= $bilingAddrObj_str;
           // $defaultConfigs["shipping_address"]= $shippingAddrObj_str;
            
        }
       // var_dump($defaultConfigs);
        return $defaultConfigs;
    }
    public function generateFormAsHtml(){
        $model = Mage::getModel('pilipay/pilipay');
        $parameter = $this->arrangePostData();
        //var_dump($parameter); die();
        $html = $model->buildRequestForm($parameter,"post", "submit");
        return $html;
    }
    
}
