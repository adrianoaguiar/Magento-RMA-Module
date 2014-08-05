<?php

class OneTwoReturn_RMA_Model_Mysql4_Ritems extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('rma/ritems', 'rma_items_id');
    }  
}