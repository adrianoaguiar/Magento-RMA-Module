<?php

class OneTwoReturn_RMA_Block_Adminhtml_Rmaoverview_View_Items_Column_Qty extends Mage_Adminhtml_Block_Sales_Items_Column_Qty
{
	public function getRmaItem($item_id)
	{
		return Mage::getModel('rma/ritems')->getCollection()->addFieldToFilter('rma_items_rma_id',$this->getRma()->getRmaId())->addFieldToFilter('rma_items_order_item_id',$item_id)->getFirstItem()->getData();
	}
	
	public function getQtyReturning($_item)
	{
		$rmaitem = $this->getRmaItem($_item->getItemId());
		return $rmaitem['rma_items_qty_returning'];
	}
	
	public function getQtyReturned($_item)
	{
		$rmaitem = $this->getRmaItem($_item->getItemId());
		return $rmaitem['rma_items_qty_returned'];
	}
	
	
	public function getRma()
    {
        return Mage::registry('OneTwoReturn_RMA');
    }
}
