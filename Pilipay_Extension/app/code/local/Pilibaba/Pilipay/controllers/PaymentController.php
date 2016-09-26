<?php
 class Pilibaba_Pilipay_PaymentController extends Mage_Core_Controller_Front_Action {
	// The redirect action is triggered when someone places an order
    private $_lastRealOrderId;

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
        /*
		header('Content-Type: application/xml; charset=utf-8');
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n<root>\r\n<result>1</result>\r\n<redirecturl>".$varRedirectUrl."</redirecturl>\r\n</root>";
        */
        echo '<result>'.$varResult.'</result><redirecturl>'.$varRedirectUrl.'</redirecturl>';       
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