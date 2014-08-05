<?php
class OneTwoReturn_RMA_Model_System_Config_Source_VatList
{
  public function toOptionArray()
  {
    return array(
      array('value' => 'inc', 'label' => Mage::helper('adminhtml')->__('Always Including tax')),
      array('value' => 'ex', 'label' => Mage::helper('adminhtml')->__('Original price'))
    );
  }
}