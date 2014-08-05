<?php
class OneTwoReturn_RMA_Model_Mysql4_Ritems_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
    	parent::_construct();
        $this->_init('rma/ritems');
    }  
}