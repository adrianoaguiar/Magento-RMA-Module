<?php
class OneTwoReturn_RMA_Model_System_Config_Source_ExchangeList
{
  public function toOptionArray()
  {
    return array(
      array('value' => false, 'label' => Mage::helper('adminhtml')->__('none')),
      array('value' => 1, 'label' => Mage::helper('adminhtml')->__('Voucher')),
      array('value' => 2, 'label' => Mage::helper('adminhtml')->__('Manual'))
    );
  }
}