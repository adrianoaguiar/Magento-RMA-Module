<?php

class OneTwoReturn_RMA_Block_Adminhtml_Sales_Order_Creditmemo_Create_Items extends Mage_Adminhtml_Block_Sales_Order_Creditmemo_Create_Items
{
    
    public function getUpdateUrl()
    {
           return $this->getUrl('*/*/updateQty', array(
                    'order_id'=>$this->getCreditmemo()->getOrderId(),
                    'rma_id'=>$this->getRequest()->getParam('rma_id', null),
                    'invoice_id'=>$this->getRequest()->getParam('invoice_id', null),
            ));
    }
        
         
}
