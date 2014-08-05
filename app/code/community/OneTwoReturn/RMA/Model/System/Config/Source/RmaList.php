<?php
class OneTwoReturn_RMA_Model_System_Config_Source_RmaList
{
  public function toOptionArray()
  {
    return array(
      array('value' => 'perorder', 'label' => Mage::helper('adminhtml')->__('Allow RMA after order validation')),
      array('value' => 'all', 'label' => Mage::helper('adminhtml')->__('Always allow RMA'))
    );
  }
}