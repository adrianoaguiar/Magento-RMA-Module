<?php
class OneTwoReturn_RMA_Block_Adminhtml_Rmaoverview extends Mage_Adminhtml_Block_Widget_Grid_Container
{
	
	protected $_addButtonLabel = 'Add New RMA';
	
    public function __construct()
	{
		$this->_controller = 'adminhtml_rmaoverview';
        $this->_blockGroup = 'rma';
        $this->_headerText = $this->__("RMAs");
        parent::__construct();
		$this->removeButton('add');
		
    }
	
	protected function _prepareLayout()
    {
       return parent::_prepareLayout();
    }
	
	
	
}