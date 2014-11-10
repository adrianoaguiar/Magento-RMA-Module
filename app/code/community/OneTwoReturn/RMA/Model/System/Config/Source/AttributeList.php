<?php
class OneTwoReturn_RMA_Model_System_Config_Source_AttributeList
{
  public function toOptionArray()
  {
      $attributes = Mage::getResourceModel('catalog/product_attribute_collection')->getItems();
      $arr = array();
      foreach ($attributes as $attribute){
          $temp['value'] = $attribute->getAttributecode(); 
          $temp['label'] = $attribute->getFrontendLabel(); 
          if(!empty($temp['label']))$arr[$attribute->getFrontendLabel()]= $temp;
      }
    ksort ($arr);
    return $arr;
  }
}