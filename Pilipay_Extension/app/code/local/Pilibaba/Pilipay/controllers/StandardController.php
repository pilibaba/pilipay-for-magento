<?php
 class Pilibaba_Pilipay_StandardController extends Mage_Core_Controller_Front_Action {
    // The redirect action is triggered when someone places an order
    protected $_customer;
    protected $_checkout;
    protected $_quote;
    protected $_r = false;


    protected function _isAllowedGuestCheckout(){
        return Mage::helper('checkout')->isAllowedGuestCheckout($this->getQuote());
    }

    //check if allow guest checkout and do something
    protected function _checkGuestCheckout(){
        if(!$this->_isAllowedGuestCheckout() && !$this->isCustomerLoggedIn())
           $this->_redirect('customer/account/login');
        return;
    }

    protected function _getStoreId(){
        return Mage::app()->getStore()->getId();
    }

    /**
     * Get logged in customer
     *
     * @return Mage_Customer_Model_Customer
     */
    public function getCustomer(){
        if (empty($this->_customer)) {
            $this->_customer = Mage::getSingleton('customer/session')->getCustomer();
        }
        return $this->_customer;
    }

    public function isCustomerLoggedIn(){
        return Mage::getSingleton('customer/session')->isLoggedIn();
    }

    public function getOnepage(){
        return Mage::getSingleton('checkout/type_onepage');
    }

    /**
     * Retrieve checkout session model
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout(){
        if (empty($this->_checkout)) {
            $this->_checkout = Mage::getSingleton('checkout/session');
        }
        return $this->_checkout;
    }

    /**
     * Retrieve sales quote model
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote(){
        if (empty($this->_quote)) {
            $this->_quote = $this->getCheckout()->getQuote();
        }
        return $this->_quote;
    }

    public function indexAction(){
        $this->_redirect('checkout/cart');
        return;
    }

    //mini top cart checkout
    public function minipayAction(){
        if(!$this->_isAllowedGuestCheckout() && !$this->isCustomerLoggedIn()){
            $this->_redirect('customer/account/login');
            return;
        }
        if (!Mage::helper('checkout')->canOnepageCheckout()) {
            Mage::getSingleton('checkout/session')->addError($this->__('The onepage checkout is disabled.'));
            $this->_redirect('checkout/cart');
            return;
        }
        if (!Mage::helper('pilipay')->getActive()) {
            Mage::getSingleton('checkout/session')->addError($this->__('The pilipay payment is disabled.'));
            $this->_redirect('checkout/cart');
            return;
        }
        $quote = $this->getOnepage()->getQuote();
        if (!$quote->hasItems() || $quote->getHasError()) {
            $this->_redirect('checkout/cart');
            return;
        }
        if (!$quote->validateMinimumAmount()) {
            $error = Mage::getStoreConfig('sales/minimum_order/error_message') ?
                Mage::getStoreConfig('sales/minimum_order/error_message') :
                Mage::helper('checkout')->__('Subtotal must exceed minimum order amount');

            Mage::getSingleton('checkout/session')->addError($error);
            $this->_redirect('checkout/cart');
            return;
        }
        Mage::getSingleton('checkout/session')->setCartWasUpdated(false);
        Mage::getSingleton('customer/session')->setBeforeAuthUrl(Mage::getUrl('*/*/*', array('_secure' => true)));
    //    $this->getOnepage()->initCheckout();
        $this->_initLayoutMessages('customer/session');
        if($this->saveAddress())
            $this->_redirect('pilipay/standard/rdt');
        return $this;
    }

    //checkout
    public function payAction(){
        if(!$this->_isAllowedGuestCheckout() && !$this->isCustomerLoggedIn()){
            $this->_redirect('customer/account/login');
            return;
        }
        if (!Mage::helper('checkout')->canOnepageCheckout()) {
            Mage::getSingleton('checkout/session')->addError($this->__('The onepage checkout is disabled.'));
            $this->_redirect('checkout/cart');
            return;
        }
        if (!Mage::helper('pilipay')->getActive()) {
            Mage::getSingleton('checkout/session')->addError($this->__('The pilipay payment is disabled.'));
            $this->_redirect('checkout/cart');
            return;
        }
        if($this->saveAddress())
            $this->_redirect('pilipay/standard/rdt');
        return $this;
    }

    //init cart
    protected function _initCart(){

        $cart = Mage::getSingleton('checkout/cart');
        if ($cart->getQuote()->getItemsCount()) {
            $cart->init();
            $cart->save();
        }
        Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
        return $this;
    }

    //save order and redirect url
    public function rdtAction(){
        if(!$this->_r) $this->_redirect('checkout/cart');
        $this->_saveShippingMethod2()
             ->_savePayment()
             ->_saveOrder();
        $this->_r = false;
        if($redirecturl = $this->getOnepage()->getCheckout()->getRedirectUrl())
            Mage::app()->getResponse()->setRedirect($redirecturl)->sendResponse();
    }

    public function saveAddress(){
        $this->_initCart()
             ->_saveBilling()
             ->_saveShipping();
        $this->_r = true;
        return $this;
    }

    /*
        create new Order
        @return $order
    */
    protected function _saveOrder(){
        /*$order = Mage::getModel('sales/order');

        //base information
        $order->setStoreId($this->_getStoreId());

        //var_dump($this->getQuote());die;
        //quote information
        $order->setQuoteId($this->getQuote()->getId());

        //customer information
        $order->setCustomer($this->getCustomer());
        $order->setCustomerIsGuest($this->isCustomerLoggedIn()?1:0);

        //payment information
        $orderPayment = Mage::getModel('sales/order_payment');
        $orderPayment->setStoreId($storeId)
             ->setCustomerPaymentId(0)
             ->setMethod('pilipay')
             ->setPo_number(' – ');
        $order->setPayment($orderPayment);

        //address information
        $order->setBillingAddress($this->_createAddress('billing'));
        $order->setShippingAddress($this->_createAddress());

        $order->save();*/
        $data = array('method' => 'pilipay');
        $this->getOnepage()->getQuote()->getPayment()->importData($data);
        $this->getOnepage()->saveOrder();
        $this->_saveQuote();
        return $this;
    }

    protected function _saveBilling(){
        // address data
        $data= array(
            "region_id"=>$this->_getWarehouseData('isoStateCode'),
            "region"=>$this->_getWarehouseData('state'),
            "postcode"=>$this->_getWarehouseData('zipcode'),
            "lastname"=>$this->_getWarehouseData('lastName'),
            "street"=>array($this->_getWarehouseData('address')),
            "city"=>$this->_getWarehouseData('city'),
            "telephone"=>$this->_getWarehouseData('tel'),
            "country_id"=>$this->_getWarehouseData('iso2CountryCode'),
            "firstname"=>$this->_getWarehouseData('firstName'),
        );
        $this->getOnepage()->saveBilling($data, '');
    //    $this->getOnepage()->getQuote()->collectTotals()->save();   
    //    $this->getOnepage()->getQuote()->collectTotals()->save();
    //    $this->getOnepage()->getQuote()->getShippingAddress()->setCollectShippingRates(true);
        return $this;
    }

    //save billing address and shipping address
    protected function _saveShipping(){
        // address data
        $data= array(
            "region_id"=>$this->_getWarehouseData('isoStateCode'),
            "region"=>$this->_getWarehouseData('state'),
            "postcode"=>$this->_getWarehouseData('zipcode'),
            "lastname"=>$this->_getWarehouseData('lastName'),
            "street"=>array($this->_getWarehouseData('address')),
            "city"=>$this->_getWarehouseData('city'),
            "telephone"=>$this->_getWarehouseData('tel'),
            "country_id"=>$this->_getWarehouseData('iso2CountryCode'),
            "firstname"=>$this->_getWarehouseData('firstName'),
        );
        $this->getOnepage()->saveShipping($data, '');
    //    $this->getOnepage()->getQuote()->collectTotals()->save();
    //    $this->getOnepage()->getQuote()->getShippingAddress()->collectTotals();
    //    $this->getOnepage()->getQuote()->collectTotals()->save();
    //    $this->getOnepage()->getQuote()->getShippingAddress()->setCollectShippingRates(true);
        return $this;
        /*$address = Mage::getModel('sales/order_address')
            ->setStoreId($this->_getStoreId())
            ->setAddressType($type)
            ->setFirstname($this->_getWarehouseDate('firstName'))
            ->setLastname($this->_getWarehouseDate('lastName'))
            ->setStreet($this->_getWarehouseDate('address'))
            ->setCity($this->_getWarehouseDate('city'))
            ->setCountryId($this->_getWarehouseDate('iso2CountryCode'))
            ->setRegion($this->_getWarehouseDate('state'))
            ->setRegionId($this->_getWarehouseDate('isoStateCode'))
            ->setPostcode($this->_getWarehouseDate('zipcode'))
            ->setTelephone($this->_getWarehouseDate('tel'));
        if($type == "shipping")
            $address->setShippingMethod('flatrate_flatrate');

        return $address;*/
    }


    //save shipping method
    protected function _saveShippingMethod(){
    //  $shippingRates = $this->getOnepage()->getQuote()->getShippingAddress()->getShippingRatesCollection();
        $shippingRates = $this->getOnepage()->getQuote()->getShippingAddress()->getAllShippingRates();
    //    var_dump($shippingRates);die;
        $shippingMethod = 'freeshipping_freeshipping';
        foreach ($shippingRates as $rate) {
            if ($rate->getCarrier() == Mage::helper('pilipay')->getShippingMethod()) {
                $shippingMethod = $rate->getCode();
            }
        }
        $result = $this->getOnepage()->saveShippingMethod($shippingMethod);
        if(!$result){
            Mage::dispatchEvent(
                'checkout_controller_onepage_save_shipping_method',
                 array(
                      'request' => $this->getRequest(),
                      'quote'   => $this->getOnepage()->getQuote()));
            $this->getOnepage()->getQuote()->collectTotals();
        }
        $this->getOnepage()->getQuote()->collectTotals()->save();
        return $this;
    }


    protected function _saveShippingMethod2(){
        if (!$this->getOnepage()->getQuote()->getIsVirtual() && $shippingAddress = $this->getOnepage()->getQuote()->getShippingAddress()) {
            $shiipingMethod = Mage::getModel('sales/quote_address_rate')->load(Mage::helper('pilipay')->getShippingMethod(),"carrier");
            $shippingMethodCode = 'freeshipping_freeshipping';
            if($shiipingMethod->getId()) $shippingMethodCode = $shiipingMethod->getCode();
            if ($shippingMethodCode != $shippingAddress->getShippingMethod()) {
                $shippingAddress->setShippingMethod($shippingMethodCode)->setCollectShippingRates(true);
                $this->getOnepage()->getQuote()->collectTotals()->save();
            }
        }
        return $this;
    }

    //save payment
    protected function _savePayment(){
        $this->getOnepage()->savePayment(array("method"=>"pilipay"));
        return $this;
    }

    //quote save
    protected function _saveQuote(){
        $this->getOnepage()->getQuote()->save();
        return $this;
    }

    protected function _getWarehouseData($field = 'id'){
        return Mage::helper('pilipay')->getWarehouseData($field);
    }

    public function redirectAction() {
        $helper = Mage::helper('pilipay/request');
        echo $helper->generateFormAsHtml();
    }
    
    // The response action is triggered when your gateway sends back a response after processing the customer's payment
    public function responseAction() {

        $varRedirectUrl="";
        $varResult = 0;
            
        /* if($this->getRequest()->getParams()) {  */
           
          
            //if($this->getRequest()->getParams()) {
                
                
            /*
            /* Your gateway's code to make sure the reponse you
            /* just got is from the gatway and not from some weirdo.
            /* This generally has some checksum or other checks,
            /* and is provided by the gateway.
            /* For now, we assume that the gateway's response is valid
            */
            //$helper = Mage::helper('pilipay');
                        
                    
        $pilipayNotify = Mage::getModel('pilipay/pilipay_notify');
    
        $verify_result = $pilipayNotify->verifyNotify($this->getRequest()->getParams());
                         
        $para_filter =  $pilipayNotify->paraFilter($this->getRequest()->getParams());
        //$validated = true;
        $orderId = $para_filter['orderNo']; // Generally sent by gateway
        $this->_lastRealOrderId = $orderId;
                        
        //var_dump($verify_result); die();
        if(!empty($verify_result)) {
                                
            // Payment was successful, so update the order's state, send order email and move to the success page
            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($orderId);
            if($order->getId()){
                //if($this->getRequest()->getParam('trade_status') and $this->getRequest()->getParam('trade_status')=="TRADE_SUCCESS"){
                $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, 'Gateway has authorized the payment.');
                // }
                $order->sendNewOrderEmail();
                $order->setEmailSent(true);
                // add invoice
                $invoice = $order->prepareInvoice();
                $invoice->register();
                if (method_exists($invoice, 'sendEmail')){
                    $invoice->sendEmail();
                }

                $order->addRelatedObject($invoice);

                $order->save();

                Mage::getSingleton('checkout/session')->unsQuoteId();
                                                                        //setting last order id, so that can show info in success page
                Mage::getSingleton('checkout/session')->setLastOrderId($order->getId());
                $varResult = 1;
                $varRedirectUrl = Mage::getUrl('checkout/onepage/success', array('order_id'=>$orderId,'_secure'=>true));
            }else{
                $varResult = 1;
                $varRedirectUrl = Mage::getUrl('checkout/onepage/failure', array('order_id'=>$orderId,'_secure'=>true));
            }
        }else{
            // There is a problem in the response we got
            $this->cancelAction();
            $varResult = 1;
            $varRedirectUrl = Mage::getUrl('checkout/onepage/failure', array('order_id'=>$orderId,'_secure'=>true));
        }
    
                //either xml way 
        header('Content-Type: application/xml; charset=utf-8');
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n<root>\r\n<result>1</result>\r\n<redirecturl>".$varRedirectUrl."</redirecturl>\r\n</root>";
               
        /*
         * Or html way as they said
         * echo '<result>'.$varResult.'</result><redirecturl>'.$varRedirectUrl.'</redirecturl>';
        */
        die();
                
    }
    
    // The cancel action is triggered when an order is to be cancelled
    public function cancelAction() {
        $lastRealOrderId = $this->_lastRealOrderId;
        if ($lastRealOrderId) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($lastRealOrderId);
            if($order->getId()) {
                // Flag the order as 'cancelled' and save it
                $order->cancel()->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true, 'Gateway has declined the payment.')->save();
            }
        }
    }
}