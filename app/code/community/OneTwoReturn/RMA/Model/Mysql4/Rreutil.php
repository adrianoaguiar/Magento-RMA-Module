<?php

class OneTwoReturn_RMA_Model_Mysql4_Rreutil extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('rma/rreutil', 'rma_reutil_id');
    }  
}