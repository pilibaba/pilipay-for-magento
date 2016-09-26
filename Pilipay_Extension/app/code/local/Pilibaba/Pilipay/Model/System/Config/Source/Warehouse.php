<?php
class Pilibaba_Pilipay_Model_System_Config_Source_Warehouse
{
    public $WAREHOUSE_ADDRESS_PATH = 'http://pre.pilibaba.com/pilipay/getAddressList';

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

    /*
        格式化地址
    */
    public function addressFormat()
    {

        $addresses = $this->queryAll();
        $newAddresses = array();
        foreach ($addresses as $key => $value) {
             $newAddresses[] = array(
                    'value'    => $value['id'],
                    'label'  => $value['state'].' '.$value['city'].' '.$value['address'].' / '.$value['country']
                );
        }
        return $newAddresses;
    }

    public function toOptionArray()
    {
        return $this->addressFormat();
    }

}
