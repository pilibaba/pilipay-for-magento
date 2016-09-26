<?php
class Pilibaba_Pilipay_Model_System_Config_Source_Currency
{

    public $WAREHOUSE_CURRENCY_PATH = 'http://www.pilibaba.com/pilipay/getCurrency';

    /**
     * query all currency
     * @return array
     */
    public function queryAll($currencyListUrl = null)
    {
        $result = file_get_contents($currencyListUrl?$currencyListUrl:$this->WAREHOUSE_CURRENCY_PATH);
        if (empty($result)){ return array(); }
        $array = json_decode($result, true);
        return $array;
    }

    /*
        格式化货币类型
    */
    public function currencyListFormat()
    {

        $currencyList = $this->queryAll();
        $newCurrencyList = array();
        foreach ($currencyList as $value) {
             $newCurrencyList[] = array(
                    'value'    => $value,
                    'label'  => $value
                );
        }
        return $newCurrencyList;
    }

    public function toOptionArray()
    {
        return $this->currencyListFormat();
    }

}
