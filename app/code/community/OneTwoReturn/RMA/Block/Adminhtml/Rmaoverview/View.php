<?php
class OneTwoReturn_RMA_Block_Adminhtml_Rmaoverview_View extends Mage_Adminhtml_Block_Widget_Form_Container
{
    /**
     * Init class
     */
    public function __construct()
    {
		
		parent::__construct();
        $this->_controller = FALSE;
     	$this->_removeButton('delete');
        $this->_removeButton('reset');
        $this->_removeButton('save');
		
		
		if(Mage::getStoreConfig('rma/returnoptions/processing')=='internal' && $this->getRma()->getRmaStatusCode()=='rma_created')
		{
			if (Mage::getSingleton('admin/session')->isAllowed('sales/menurma/rma_allow_check_cancel')) {
				$message = $this->__('You sure you want to check this RMA?');
		        $this->_addButton('rma_check', array(
		            'label'     => $this->__('Check'),
		            'onclick'   => "submitAndReloadArea($('rmaTable'), '". Mage::helper("adminhtml")->getUrl('12return/adminhtml_rmaprocess/check/', array('rma_id' => $this->getRequest()->getParam('rma_id'),'order_id' => $this->getRequest()->getParam('order_id'))) ."')", 
		        ), 0, 1);
			}
			if (Mage::getSingleton('admin/session')->isAllowed('sales/menurma/rma_allow_check_cancel')) {
				$message = $this->__('You sure you want to cancel this RMA?');
		        $this->_addButton('rma_cancel', array(
		            'label'     => $this->__('Cancel'),
		            'onclick'   => "submitAndReloadArea($('rmaTable'), '". Mage::helper("adminhtml")->getUrl('12return/adminhtml_rmaprocess/close/', array('rma_id' => $this->getRequest()->getParam('rma_id'),'order_id' => $this->getRequest()->getParam('order_id'))) ."')",
		        ), 0, 2);
			}
		}
		if($this->getRma()->getRmaStatusCode()=='rma_complete')
		{
    	        $this->_addButton('rma_credit', array(
    	            'label'     => $this->__('Creditmemo'),
    	            'onclick'   => "window.location.href='". Mage::helper("adminhtml")->getUrl('adminhtml/sales_order_creditmemo/new/', array('order_id' => $this->getRequest()->getParam('order_id'),'rma_id'=>$this->getRequest()->getParam('rma_id'))) ."';",
    	        ), 0, 1);
		}
    }  
     
    /**
     * Get Header text
     *
     * @return string
     */
    public function getHeaderText()
    {
        return $this->__('RMA #'.$this->getRma()->getRmaReference().' | Order #'.$this->getRma()->getRmaOrderIncrementId().' | '.$this->getRma()->getRmaCreatedate()); 
    }  
	
	public function getOrder()
    {
        return Mage::registry('sales_order');
    }
	
	public function getRma()
    {
        return Mage::registry('OneTwoReturn_RMA');
    }
	
	public function getSource()
    {
        return $this->getRma();
    }
	
	public function getBackUrl()
    {
        return $this->getUrl('*/*/');
    }
}