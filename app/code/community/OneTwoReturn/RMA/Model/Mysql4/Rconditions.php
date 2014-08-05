<?php

class OneTwoReturn_RMA_Model_Mysql4_Rconditions extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('rma/rconditions', 'rma_conditions_id');
    }  
}