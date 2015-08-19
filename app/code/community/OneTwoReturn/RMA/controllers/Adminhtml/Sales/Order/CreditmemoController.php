<?php
require_once("Mage/Adminhtml/controllers/Sales/Order/CreditmemoController.php");

class OneTwoReturn_RMA_Adminhtml_Sales_Order_CreditmemoController extends Mage_Adminhtml_Sales_Order_CreditmemoController
{
    private $params;
    private $order;
    private $rma;
    
    private function setVar()
    {
        $this->params = $this->getRequest()->getParams();
        if(!isset($this->params['rma_id']) || empty($this->params['rma_id']))
        {
            return false;
        }
        
        $this->rma = Mage::getModel('rma/rma')->load($this->params['rma_id']);

        if (!$this->rma->getId()) {
            $this->_getSession()->addError($this->__('This rma no longer exists.'));
            Mage::app()->getResponse()->setRedirect(Mage::helper('adminhtml')->getUrl("12return/adminhtml_rmaoverview/"));
            return false;
        }
        
        $this->order = Mage::getModel('sales/order')->load($this->params['order_id']);
        
        return true;
    }
    

    
    
    
    public function saveAction()
    {
        parent::saveAction();
        $data = $this->getRequest()->getPost('creditmemo');
        if(isset($data['create_coupon']) && $data['create_coupon']==1 && Mage::getStoreConfig('rma/view/refund_enabled')==1)
        {
            if($this->setVar()!=false)$this->createCoupon(Mage::registry('current_creditmemo'));
        }
    }
    
    private function createCoupon($creditmemo)
    {
        $visible = Mage::getStoreConfig('rma/view/coupon_view');
        $notify = Mage::getStoreConfig('rma/view/coupon_vnotify');
        $voucherMsg = Mage::getStoreConfig('rma/view/coupon_message');
        $voucherName = Mage::getStoreConfig('rma/view/coupon_name');
        $voucherLength = Mage::getStoreConfig('rma/view/coupon_length');
        
        if(Mage::getStoreConfig('rma/view/refund_free_shipping'))$freeshipping = 1; else $freeshipping = 0; 
        if(Mage::getStoreConfig('rma/view/refund_apply_shipping'))$applyshipping = 1; else $applyshipping = 0; 
        
        $generator = Mage::getModel('salesrule/coupon_codegenerator')->setLength($voucherLength);
        $attempts = 0;
        $coupon = Mage::getModel('salesrule/coupon');
        
        do {
            $voucherCode = $generator->generateCode();
        } while ($coupon->getResource()->exists($voucherCode));
        
        $comment=str_replace('{COUPON_CODE}',$voucherCode,$voucherMsg);
        $name=str_replace('{RMAREF}',$this->rma->getRmaReference(),$voucherName);
        
        $customerGroupIds = Mage::getModel('customer/group')->getCollection()->getAllIds();
        $rule = Mage::getModel('salesrule/rule');
        
        $rule->setName($name)                                                
            ->setDescription('Voucher for RMA #'.$this->rma->getRmaReference())
            ->setFromDate('')
            ->setCouponType(Mage_SalesRule_Model_Rule::COUPON_TYPE_SPECIFIC)
            ->setCouponCode($voucherCode)
            ->setUsesPerCustomer(1)
            ->setUsesPerCoupon(1)
            ->setCustomerGroupIds($customerGroupIds)
            ->setIsActive(1)
            ->setConditionsSerialized('')
            ->setActionsSerialized('')
            ->setStopRulesProcessing(0)
            ->setIsAdvanced(1)
            ->setProductIds('')
            ->setSortOrder(0)
            ->setSimpleAction(Mage_SalesRule_Model_Rule::CART_FIXED_ACTION)
            ->setDiscountAmount($creditmemo->getGrandTotal())
            ->setDiscountQty(1)
            ->setDiscountStep(0)
            ->setSimpleFreeShipping($freeshipping)
            ->setApplyToShipping($applyshipping)
            ->setIsRss(0)
            ->setWebsiteIds(array(Mage::getModel('core/store')->load($this->order->getStoreId())->getWebsiteId()))
            ->setStoreLabels(array($name));
            
            if($rule->save())
            {
                $this->rma->setRmaSalesRuleId((int)$rule->getId()); 
                $this->rma->save();
                    
                $this->_getSession()->addSuccess($this->__('Coupon code is generated and send to the user'));
                $creditmemo->addComment($comment,true,true)->setIsVisibleOnFront(true)->setIsCustomerNotified(true);
                $creditmemo->save();
                $creditmemo->sendUpdateEmail(true, $comment);
            } else {
                $this->_getSession()->addError($this->__('Failed to generate a coupon code'));
            }
    }
    protected function _isAllowed()
   {
       return true;
   }
}
