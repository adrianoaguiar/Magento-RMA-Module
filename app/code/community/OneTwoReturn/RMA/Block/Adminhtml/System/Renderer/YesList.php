<?php
class OneTwoReturn_RMA_Block_Adminhtml_System_Renderer_YesList extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $val = $this->_getValue($row);
        $items=$this->getOptions();
        return $items[$val];
    }
    
    public static function getOptions()
    {
        $return['true']='yes';
        $return['false']='no';
        return $return;
    }
}
?>