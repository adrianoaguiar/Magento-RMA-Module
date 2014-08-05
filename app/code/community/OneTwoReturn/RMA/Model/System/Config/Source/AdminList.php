<?php
class OneTwoReturn_RMA_Model_System_Config_Source_AdminList
{
  public function toOptionArray()
  {
    return array(
      array('value' => 'user', 'label' => Mage::helper('adminhtml')->__('User account')),
      array('value' => 'order', 'label' => Mage::helper('adminhtml')->__('Order number and postal'))
    );
  }
}