<?php

class OneTwoReturn_RMA_Model_Rreutil extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
    	parent::_construct();
        $this->_init('rma/rreutil');
    }  
}