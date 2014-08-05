<?php

class OneTwoReturn_RMA_Adminhtml_RmaoverviewController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->_initAction();
        $this->renderLayout();
    }  
     
    public function newAction()
    {  
        $this->_forward('view');
    }  
	
	public function editAction()
    {  
        $this->_forward('view');
    }  
	
	public function viewAction()
    {
		$order = $this->_initOrder();
		$rma = $this->_initRma();

		$this->_title($this->__('RMA: ').$rma->getRmaReference());
		
        $this->_initAction();
        $this->renderLayout();
    }
	
	public function exportCsvAction()
    {
        $fileName   = 'rma'.date("d-m-Y").'.csv';
        $grid       = $this->getLayout()->createBlock('rma/adminhtml_rmaoverview_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getCsvFile());
    }

    /**
     *  Export order grid to Excel XML format
     */
    public function exportExcelAction()
    {
        $fileName   = 'rma'.date("d-m-Y").'.xml';
        $grid       = $this->getLayout()->createBlock('rma/adminhtml_rmaoverview_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getExcelFile($fileName));
    }
	
	public function gridAction()
    {
        $this->loadLayout(false);
        $this->renderLayout();
    }
	
	public function labelAction()
    {
    	$order = $this->_initOrder();
		$rma = $this->_initRma();
        $this->loadLayout(false);
        $this->renderLayout();
    }
	
	public function statusAction()
    {
    	$order = $this->_initOrder();
		$rma = $this->_initRma();
        $this->loadLayout(false);
        $this->renderLayout();
    }
	
	protected function _initOrder()
    {
        $id = $this->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($id);

        if (!$order->getId()) {
            $this->_getSession()->addError($this->__('This order no longer exists.'));
            $this->_redirect('*/*/');
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            return false;
        }
        Mage::register('sales_order', $order);
        Mage::register('current_order', $order);
        return $order;
    }
	
	protected function _initRma()
    {
        $id = $this->getRequest()->getParam('rma_id');
        $rma = Mage::getModel('rma/rma')->load($id);

        if (!$rma->getId()) {
            $this->_getSession()->addError($this->__('This rma no longer exists.'));
            $this->_redirect('*/*/');
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            return false;
        }
        Mage::register('OneTwoReturn_RMA', $rma);
        return $rma;
    }

    /**
     * Initialize action
     *
     * Here, we set the breadcrumbs and the active menu
     *
     * @return Mage_Adminhtml_Controller_Action
     */
    protected function _initAction()
    {
        $this->loadLayout()
            // Make the active menu match the menu config nodes (without 'children' inbetween)
            ->_setActiveMenu('sales/menurma')
            ->_title($this->__('Sales'))->_title($this->__('RMA Overview'))
            ->_addBreadcrumb($this->__('RMA'), $this->__('RMA'));
        
        return $this;
    }

}