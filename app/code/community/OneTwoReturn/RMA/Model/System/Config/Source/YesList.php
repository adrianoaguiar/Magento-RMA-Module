<?php
class OneTwoReturn_RMA_Model_System_Config_Source_YesList
{
  public function toOptionArray()
  {
    return array(
      array('value' => true, 'label' => Mage::helper('adminhtml')->__('Yes')),
      array('value' => false, 'label' => Mage::helper('adminhtml')->__('No'))
    );
  }
}