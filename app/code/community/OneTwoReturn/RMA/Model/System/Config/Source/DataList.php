<?php
class OneTwoReturn_RMA_Model_System_Config_Source_DataList
{
  public function toOptionArray()
  {
    return array(
      array('value' => 'magento', 'label' => Mage::helper('adminhtml')->__('Internal')),
      array('value' => 'coresix', 'label' => Mage::helper('adminhtml')->__('External'))
    );
  }
}