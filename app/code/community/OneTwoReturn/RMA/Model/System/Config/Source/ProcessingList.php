<?php
class OneTwoReturn_RMA_Model_System_Config_Source_ProcessingList
{
  public function toOptionArray()
  {
    return array(
      array('value' => 'internal', 'label' => Mage::helper('adminhtml')->__('Internal')),
      array('value' => 'external', 'label' => Mage::helper('adminhtml')->__('External'))
    );
  }
}