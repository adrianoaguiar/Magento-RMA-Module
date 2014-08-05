<?php

class OneTwoReturn_RMA_Model_Mysql4_Rma extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('rma/rma', 'rma_id');
    }  
}