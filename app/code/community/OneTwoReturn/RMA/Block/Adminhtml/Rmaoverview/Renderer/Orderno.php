<?php
class OneTwoReturn_RMA_Block_Adminhtml_Rmaoverview_Renderer_Orderno extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $data =  $row->getData();
		$url = $this->getUrl('adminhtml/sales_order/view', array('order_id' => $data['rma_order_entity_id']));
		return '<a href="'.$url.'">'.$data['rma_order_increment_id'].'</a>';
    }
}
?>