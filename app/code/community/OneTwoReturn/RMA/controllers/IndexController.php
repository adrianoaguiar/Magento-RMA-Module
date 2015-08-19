<?php 
class OneTwoReturn_RMA_IndexController extends Mage_Core_Controller_Front_Action
{
   public function indexAction ()
   {
   		Mage::app()->getResponse()->setRedirect(Mage::getUrl("12return/form/index"));
   }
   protected function _isAllowed()
   {
       return true;
   }
}
