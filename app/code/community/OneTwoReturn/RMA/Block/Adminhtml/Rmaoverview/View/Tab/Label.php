<?php
class OneTwoReturn_RMA_Block_Adminhtml_Rmaoverview_View_Tab_Label extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Init class
     */
    public function __construct()
    {
        parent::__construct();
		$this->setTemplate('onetworeturn_rma/rmaoverview/view/tab/label.phtml');
		
    }  
	
	public function getRma()
    {
        return Mage::registry('OneTwoReturn_RMA');
    }

}