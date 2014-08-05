<?php
class OneTwoReturn_RMA_Model_System_Config_Source_ServerList
{
  public function toOptionArray()
  {
    return array(
      array('value' => 'production', 'label' => Mage::helper('adminhtml')->__('Production server')),
      array('value' => 'test', 'label' => Mage::helper('adminhtml')->__('Test server'))
    );
  }
}