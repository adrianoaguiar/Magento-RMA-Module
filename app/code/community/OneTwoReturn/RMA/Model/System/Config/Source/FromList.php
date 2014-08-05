<?php
class OneTwoReturn_RMA_Model_System_Config_Source_FromList
{
  public function toOptionArray()
  {
    return array(
      array('value' => 'ordercreate', 'label' => Mage::helper('adminhtml')->__('Order creation')),
      array('value' => 'firstshipment', 'label' => Mage::helper('adminhtml')->__('First shipment')),
      array('value' => 'lastshipment', 'label' => Mage::helper('adminhtml')->__('Latest shipment'))
      
    );
  }
}