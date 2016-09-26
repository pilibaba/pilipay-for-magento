<?php
class Pilibaba_Pilipay_Block_Shortcut extends Mage_Checkout_Block_Onepage
{
    public function __construct(){
    	
    }

    public function addWareHouseAddress($template){
    	$block = new Pilibaba_Pilipay_Block_Warehouse($template);
    	$this->append($block);
    //	var_dump($this->getSteps());
    }

    public function getSteps(){

    	$steps = parent::getSteps();
    	return $steps;
    }
}
