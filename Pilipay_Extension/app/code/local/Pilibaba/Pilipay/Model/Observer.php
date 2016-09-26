<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Pilibaba_Pilipay_Model_Observer {
    //put your code here
    public function updateTrackNumber($observer){
        try{
            $order = $observer->getEvent()->getShipment()->getOrder();
            $shipment = $observer->getEvent()->getShipment();

            $trackingNumbers = array();
            foreach ($shipment->getAllTracks() as $track) {
                $trackingNumbers[] = $track->getNumber();
            }

            $newTrackNumber = $trackingNumbers[count($trackingNumbers)-1];

            $order = Mage::getModel('sales/order')->load($order->getId());
            $orderIncrementId = $order->getIncrementId();

            $helper = Mage::helper('pilipay');

            $defParams = $helper->getDefaultConfigParams();
            $trackUrl  = $helper->getUpdateTrackUrl();
            $array = array("merchantNo"=>$defParams['merchantNO'],'orderNo'=>$orderIncrementId,'logisticsNo'=>$newTrackNumber);
			var_dump($array);
            //$trackUrl = "http://www.pilibaba.com/pilipay/updateTrackNo";
            try{
               $cRes =  $helper->curlHit($trackUrl,$array);
            } catch (Exception $ex) {

            }
           // var_dump($cRes);
            //echo "tes";
            //exit;
			
        }catch(Exception $e){
            
        }
        return $this;
    }
}
