<?php
class OneTwoReturn_RMA_Block_Adminhtml_Rmaoverview_View_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Init class
     */
    public function __construct()
    {
		
    	$this->setId('rma_view');  
        parent::__construct();
		$this->setTemplate('rma/rmaoverview/view/form.phtml');
		
    }  
	
	protected function _beforeToHtml()
    {
        if (!$this->getParentBlock()) {
            Mage::throwException(Mage::helper('adminhtml')->__('Invalid parent block for this block.'));
        }
        $this->setOrder($this->getParentBlock()->getOrder());


        parent::_beforeToHtml();
    }
     
	
	public function getRma()
    {
        return Mage::registry('OneTwoReturn_RMA');
    }


}