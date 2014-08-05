<?php
class OneTwoReturn_RMA_Block_Adminhtml_Rmaoverview_View_Table extends Mage_Adminhtml_Block_Sales_Order_Abstract
{
    /**
     * Init class
     */
    public function __construct()
    {  
        parent::__construct();
    }  
	
	protected function _beforeToHtml()
    {
        if (!$this->getParentBlock()) {
            Mage::throwException(Mage::helper('adminhtml')->__('Invalid parent block for this block'));
        }
        $this->setOrder($this->getParentBlock()->getOrder());
        parent::_beforeToHtml();
    }
	
	public function getRma()
    {
        return Mage::registry('OneTwoReturn_RMA');
    }
     
	public function getOrder()
    {
        return Mage::registry('current_order');
    }
	
	public function getSource()
    {
        return $this->getOrder();
    }

	public function getItemsCollection()
    {

    	$_order = Mage::getModel('sales/order')->load($this->getOrder()->getEntityId());
		$allproducts = $_order->getItemsCollection();
		$ritems = Mage::getModel('rma/ritems')->getCollection()->addFieldToFilter('rma_items_rma_id', $this->getRma()->getRmaId())->getData();
		foreach($allproducts as $key=>$items)
		{
			$idata = $items->getData ();
			foreach($ritems as $ritem)
			{
				if((!isset($idata['parent_item_id']) || empty($idata['parent_item_id'])) && $ritem['rma_items_order_item_id']==$idata['item_id'])
				{
					$products[$key]=$items;
				}
			}
			
		}
        return $products;
    }
	

}