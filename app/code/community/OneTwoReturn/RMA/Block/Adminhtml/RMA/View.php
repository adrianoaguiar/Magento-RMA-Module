<?php

class OneTwoReturn_RMA_Block_Adminhtml_RMA_View extends Mage_Adminhtml_Block_Sales_Order_View
{
    protected function  _prepareLayout()
    {        
        // -- add extra buttons to order
        $allowedOrder = $this->getOrder()->getData();
        $websiteId = Mage::getModel('core/store')->load($allowedOrder['store_id'])->getWebsiteId();
          
        if(Mage::app()->getWebsite($websiteId)->getConfig('rma/returnoptions/pluginenabled') && in_array($allowedOrder["status"], Mage::helper('rma')->allowedOrderStatus))
        {
            $params2['order_id'] = $this->getOrderId();
            
            if (Mage::app()->getWebsite($websiteId)->getConfig('rma/view/selectiverma')=='perorder' &&  Mage::app()->getWebsite($websiteId)->getConfig('rma/view/enabled'))
            {                
                if($allowedOrder['selective_rma']!='true')
                {
                    $message = $this->__('Are you sure you want allow Exchange RMA on this order?');
                    $this->_addButton('order_allow_rma', array(
                        'label'     => $this->__('Allow Exchange RMA'),
                        'onclick'   => "confirmSetLocation('{$message}', '". Mage::helper("adminhtml")->getUrl('rma/adminhtml_rma/allow/',$params2) ."')", 
                    ), 0, 11);
                } 
                else 
                {
                    $message = $this->__('Are you sure you want disallow Exchange RMA on this order?');
                    $this->_addButton('order_disallow_rma', array(
                        'label'     => $this->__('Disallow Exchange RMA'),
                        'onclick'   => "confirmSetLocation('{$message}', '". Mage::helper("adminhtml")->getUrl('rma/adminhtml_rma/disallow/',$params2) ."')",
                    ), 0, 11);
                }
                    
                         
            }

            if (Mage::app()->getWebsite($websiteId)->getConfig('rma/warranty/selectiverma')=='perorder' &&  Mage::app()->getWebsite($websiteId)->getConfig('rma/warranty/enabled'))
            {
                if($allowedOrder['selective_rma_warranty']!='true')
                {
                    $message = $this->__('Are you sure you want allow Warranty RMA on this order?');
                    $this->_addButton('order_allow_rma_warranty', array(
                        'label'     => $this->__('Allow Warranty RMA'),
                        'onclick'   => "confirmSetLocation('{$message}', '". Mage::helper("adminhtml")->getUrl('rma/adminhtml_rma/allowwarranty/',$params2) ."')", 
                    ), 0, 12);  
                }
                else
                {
                    $message = $this->__('Are you sure you want disallow Warranty RMA on this order?');
                    $this->_addButton('order_disallow_rma_warranty', array(
                        'label'     => $this->__('Disallow Warranty RMA'),
                        'onclick'   => "confirmSetLocation('{$message}', '". Mage::helper("adminhtml")->getUrl('rma/adminhtml_rma/disallowwarranty/',$params2) ."')",
                    ), 0, 12);                       
                }
            }
                
        }

        return parent::_prepareLayout();
    }
    
}
