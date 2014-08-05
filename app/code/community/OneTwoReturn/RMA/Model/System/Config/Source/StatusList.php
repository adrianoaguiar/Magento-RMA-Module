<?php
class OneTwoReturn_RMA_Model_System_Config_Source_StatusList
{
  public function toOptionArray()
  {
  	foreach(Mage::getModel('sales/order_status')->getResourceCollection()->getData() as $s)$data[]=array('value' => $s['status'], 'label' => $s['label']);
    return $data;
  }
}