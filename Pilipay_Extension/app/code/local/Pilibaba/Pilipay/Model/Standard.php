<?php
class Pilibaba_Pilipay_Model_Standard extends Mage_Payment_Model_Method_Abstract {

	protected $_code = 'pilipay';
	protected $_formBlockType = 'pilipay/standard_form';
	protected $_isInitializeNeeded      = true;
	protected $_canUseInternal          = true;
	protected $_canUseForMultishipping  = false;
	protected $_canAuthorize            = false;
    protected $_canCapture              = false;
    protected $_canCapturePartial       = false;
    protected $_canCaptureOnce          = false;

	public function getOrderPlaceRedirectUrl() {
		return Mage::getUrl('pilipay/payment/redirect', array('_secure' => true));
	}
}