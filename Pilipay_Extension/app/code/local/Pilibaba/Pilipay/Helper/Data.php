<?php
class Pilibaba_Pilipay_Helper_Data extends Mage_Core_Helper_Abstract
{
    private $_errrosList;
    public $storeId;
    public $wesiteId;
    public $WAREHOUSE_ADDRESS_PATH = 'http://www.pilibaba.com/pilipay/getAddressList';

    public function __construct() {
        $this->_errrosList = array(
            800090001=>"Error merchants submitted commodity list",
            800090002=>"Merchants submit commodity list is empty",
            800010001=>"Merchant number can not be empty",
            800020001=>"Merchant number key cannot be empty",
            800020002=>"Merchants order number does not exist or is empty",
            800030001=>"Can't order amount of merchants is empty",
            800040001=>"Currency type cannot be empty",
            800040002=>"Currency type does not exist",
            800010002=>"Merchant number does not exist",
            800080001=>"Merchant signature error",
            999999999=>"System error"
        );
        $this->storeId  = Mage::app()->getStore()->getStoreId();
        $this->wesiteId = Mage::app()->getStore()->getWebsiteId();
    }

    public function getActive(){
        try{
            return Mage::getStoreConfig('payment/pilipay/active', $this->storeId);
        } catch (Exception $ex) {
           return false;
        }
    }
    
    public function getErrorList(){
        return $this->_errrosList;
    }
    public function getError($error_code){
        return isset($this->_errrosList[$error_code])? $this->_errrosList[$error_code]: false;
    }
    public function getDefaultConfigParams(){
        try{
            return array(
                'merchantNO'    => Mage::getStoreConfig('payment/pilipay/merhcantno', $this->storeId) ,
                //'key'=> Mage::getStoreConfig('payment/pilipay/second', $this->storeId) ,
                'appSecrect'    => Mage::getStoreConfig('payment/pilipay/appSecret', $this->storeId),
                'signType'      => strtoupper('MD5'),
                'serverUrl'     => $this->getSuccessPageUrl(),
                'pageUrl'       => Mage::getStoreConfig('payment/pilipay/checkoutUrl', $this->storeId),
                'currencyType'  => Mage::getStoreConfig('payment/pilipay/currencyType', $this->storeId)
             );
        } catch (Exception $ex) {
           return false;
        }
    }
    public function getUpdateTrackUrl(){
       try{
            return Mage::getStoreConfig('payment/pilipay/track_url', $this->storeId);
        } catch (Exception $ex) {
           return false;
        }
    }

    private function removeLastSlash($string) {
        
        if(substr($string, -1) == '/') {
            $string = substr($string, 0, -1);
        }
        
        return $string;
    }

    public function getWarehouseInfo(){
        try{
            return Mage::getStoreConfig('payment/pilipay/warehouseInfo', $this->storeId);
        } catch (Exception $ex) {
            return false;
        }
    }

    public function getShippingMethod(){
        try{
            return Mage::getStoreConfig('payment/pilipay/shippingMethid', $this->storeId);
        } catch (Exception $ex) {
            return false;
        }
    }

    public function getGatewayUrl(){
        try{
            return Mage::getStoreConfig('payment/pilipay/gateway_url', $this->storeId);
        } catch (Exception $ex) {
            return false;
        }
    }

    public function getCheckoutPageUrl(){
       try{
            return $this->removeLastSlash(Mage::getStoreConfig('payment/pilipay/checkoutUrl', $this->storeId));
        } catch (Exception $ex) {
            return false;
        }
    }
    public function getSuccessPageUrl(){
       try{
            return $this->removeLastSlash(Mage::getUrl('pilipay/payment/response', array('_secure'=>true)));
        } catch (Exception $ex) {
            return false;
        }
    }

    public function getCurrrentOrder(){
        return Mage::getSingleton('checkout/session')->getQuote();
    }
    
    /**
     * Return checkout session instance
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Return order instance
     *
     * @return Mage_Sales_Model_Order|null
     */
    protected function _getOrder()
    {
         $orderIncrementId = $this->_getCheckout()->getLastRealOrderId();
         return Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
         
    }
    public function getOrder(){
        return $this->_getOrder();
    }
    
    public function curlHit($url,$data=array()){
        //foreach($data as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
        //rtrim($fields_string, '&');
        
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_FAILONERROR, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);  
        curl_setopt($curl, CURLOPT_HEADER, false); 
        curl_setopt($curl,CURLOPT_POST, count($data));
        curl_setopt($curl,CURLOPT_POSTFIELDS, $data);
        $result = curl_exec($curl); 
        curl_close($curl); 
        return $result;
    }

    /**
     * query all warehouse addresses
     * @return array
     */
    public function queryAll($addressListUrl = null)
    {
        $result = file_get_contents($addressListUrl?$addressListUrl:$this->WAREHOUSE_ADDRESS_PATH);
        if (empty($result)){ return array(); }
        $array = json_decode($result, true);
        return $array;
    }
       
    public function getPaymentMarkImageUrl(){
        return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)."pilipay/".Mage::getStoreConfig('payment/pilipay/logoImg', $this->storeId);
    }

    public function getPaymentMarkWhatIsPilipayUrl(){
        return Mage::getStoreConfig('payment/pilipay/whatIsUrl', $this->storeId);
    }

    public function getPaymentWhatIsText(){
        return Mage::getStoreConfig('payment/pilipay/whatIsText', $this->storeId);
    }

    public function getWarehouseAddressBy($val,$k = 'id')
    {
        $addressList = $this->queryAll();
        foreach ($addressList as $key => $value) {
            if( $value[$k] == $val ){ return $value; }
        }

        return null;
    }

    public function getCurrentAddress(){
        try{
            return $this->getWarehouseAddressBy($this->getWarehouseInfo());
        } catch (Exception $ex) {
            return false;
        }
    }

    public function getWarehouseData($field = 'id'){
        try{
            $currentAddress = $this->getCurrentAddress();
            if($field == 'isoStateCode'){
                $stateId = Mage::getModel('directory/region')->loadByCode($currentAddress['isoStateCode'],$currentAddress['iso2CountryCode'])->getId();
                return $stateId?$stateId:null;
            }
            return $currentAddress[$field];
        } catch (Exception $ex) {
            return false;
        }
    }

}