<?php

class OneTwoReturn_RMA_Model_Mysql4_Raddress extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('rma/raddress', 'rma_address_id');
    }  
}