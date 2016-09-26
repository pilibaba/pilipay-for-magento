<?php
class Pilibaba_Pilipay_Model_System_Config_Source_ShippingMethod
{

    /**
     * query all shipping method
     * @return array
     */
    public function queryAll()
    {
        $storeId=Mage::app()->getStore()->getId();
        return Mage::getStoreConfig('carriers', $storeId);
    }

    /**
     * query all shipping method
     * @return array
     */
    public function queryAllActive()
    {
        $storeId=Mage::app()->getStore()->getId();
        $results = Mage::getStoreConfig('carriers', $storeId);
        foreach ($results as $key => $value) {
            if(!$value['active']){ unset($results[$key]); }
        }
        return $results;
    }

    public function shippingMethodFormat()
    {

        $addresses = $this->queryAllActive();
        $newAddresses = array();
        foreach ($addresses as $key => $value) {
             $newAddresses[] = array(
                    'value'    => $key,
                    'label'  => $value['title']
                );
        }
        return $newAddresses;
    }

    public function toOptionArray()
    {
        return $this->shippingMethodFormat();
    }

}
