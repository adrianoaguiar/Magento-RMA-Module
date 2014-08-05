<?php

class OneTwoReturn_RMA_Model_Mysql4_Rstatus extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('rma/rstatus', 'rma_status_code');
    }  
}