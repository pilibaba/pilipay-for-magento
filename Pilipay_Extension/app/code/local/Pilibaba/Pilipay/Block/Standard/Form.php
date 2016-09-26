<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Pilibaba_Pilipay_Block_Standard_Form extends Mage_Payment_Block_Form
{
    /**
     * Set template and redirect message
     */
    protected function _construct()
    {
        $helper = Mage::helper('pilipay');
        //$this->_config = Mage::getModel('paypal/config')->setMethod($this->getMethodCode());
        $locale = Mage::app()->getLocale();
        $mark = Mage::getConfig()->getBlockClassName('core/template');
        $mark = new $mark;
        $mark->setTemplate('pilipay/mark.phtml')
            ->setPaymentAcceptanceMarkHref($helper->getPaymentMarkWhatIsPilipayUrl())
            ->setPaymentAcceptanceMarkSrc($helper->getPaymentMarkImageUrl())
            ->setPaymentWhatIsText($helper->getPaymentWhatIsText())
        ; // known issue: code above will render only static mark image
        $this->setMethodTitle('') // Output PayPal mark, omit title
            ->setMethodLabelAfterHtml($mark->toHtml())
        ;
        return parent::_construct();
    }
}
