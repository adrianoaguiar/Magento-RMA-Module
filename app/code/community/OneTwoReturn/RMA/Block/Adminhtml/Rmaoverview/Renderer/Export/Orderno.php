<?php
class OneTwoReturn_RMA_Block_Adminhtml_Rmaoverview_Renderer_Export_Orderno extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $data =  $row->getData();
		return $data['rma_order_increment_id'];
    }
}
?>