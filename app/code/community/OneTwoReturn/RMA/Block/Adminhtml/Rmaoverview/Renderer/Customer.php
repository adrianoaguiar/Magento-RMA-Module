<?php
class OneTwoReturn_RMA_Block_Adminhtml_Rmaoverview_Renderer_Customer extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $data =  $row->getData();
		$order = Mage::getModel('sales/order')->load($data['rma_order_entity_id']);
		
		$customer=$order->getData();
		$url = $this->getUrl('adminhtml/customer/edit', array('id' => $customer['customer_id']));
		if($customer['customer_is_guest']==1)
		{
			return $order->getCustomerName();
		} else {
			return '<a href="'.$url.'">'.$order->getCustomerName().'</a>';
		}
    }
}
?>