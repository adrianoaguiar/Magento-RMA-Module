<?php
/*
 * Custom order view for RMA orders
 */

class OneTwoReturn_RMA_Adminhtml_RmaprocessController extends Mage_Adminhtml_Controller_Action
{
    private $params;
    private $rma; 
	private $statusses = array('rma_cancelled'=>'E','rma_complete'=>'C');
	
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
	
	public function getOrder()
    {
        return Mage::registry('sales_order');
    }
	
	public function getRma()
    {
        return Mage::registry('OneTwoReturn_RMA');
    }

    private function redirect()
    {
        $prefix = Mage::getConfig()->getNode('admin/routers/adminhtml/args/frontName');
        Mage::app()->getResponse()->setRedirect(Mage::helper('adminhtml')->getUrl("12return/adminhtml_rmaoverview/view/",array("rma_id"=>$this->getRma()->getRmaId(), 'order_id' =>$this->getRma()->getRmaOrderEntityId())));
    }
	
	private function ajaxRedirect()
    {
        $prefix = Mage::getConfig()->getNode('admin/routers/adminhtml/args/frontName');
    	echo json_encode(array('ajaxExpired'=>true,'ajaxRedirect'=>Mage::helper('adminhtml')->getUrl("12return/adminhtml_rmaoverview/view/",array("rma_id"=>$this->getRma()->getRmaId(), 'order_id' =>$this->getRma()->getRmaOrderEntityId()))));
		exit;
    }
	
	public function CheckAction()
    {
    	$this->_initRma();
		$this->_initOrder();
    	$this->callTaskCompletionApi('QC','rma_complete');
        $this->ajaxRedirect();
    }
	
	public function CloseAction()
    {
        $this->_initRma();
		$this->_initOrder();
    	$this->callTaskCompletionApi('QC','rma_cancelled');
        $this->ajaxRedirect();
    }
	
	private function callTaskCompletionApi($taskccode='QC',$newstatus='rma_complete')
	{
		$prodXml='';
		$rmaitems = Mage::getModel('rma/ritems')->getCollection()->addFieldToFilter('rma_items_rma_id', $this->getRma()->getRmaId());
		if($newstatus=='rma_complete')
		{
			$action = 'rmacheck';$msg = 'checked';
			$note='Check from Magento';
			$trc='OK';
			
			foreach($rmaitems as $item)
			{
				if(isset($_POST['returned'][$item->getRmaItemsOrderItemId()]) && is_numeric($_POST['returned'][$item->getRmaItemsOrderItemId()]) && $newstatus=='rma_complete')
				{
					$num = $_POST['returned'][$item->getRmaItemsOrderItemId()]; 
				} else $num=0;
				
				if($num>$item->getRmaItemsQtyReturning())
				{
					return $this->_getSession()->addError('You have exceeded the limit for returning products to receive');
				}
				
				for ($i = 1; $i <= $item->getRmaItemsQtyReturning(); $i++)
				{
					if($i<=$num)$rec=1; else $rec=0;
					$prodXml.='<product><lineno>'.$item->getRmaItemsLineno().'</lineno><productid>'.$item->getRmaItemsProductModel().'</productid><quantity>1.00</quantity><received>'.$rec.'</received><tostock>0</tostock><tocredit>0</tocredit></product>';
				}
			
			}
			$prodXml ='<products>'.$prodXml.'</products>';
		} elseif($newstatus=='rma_cancelled') {
			$action = 'rmaremove';$msg = 'removed';
			$note='Cancel from Magento';
			$trc='PRD01';
		}

		$xml='<'.$action.'><taskreasoncode>'.$trc.'</taskreasoncode><notes>'.$note.'</notes><extreference></extreference>'.$prodXml.'</'.$action.'>';	
		$data = http_build_query(array( 
                'key' => Mage::getStoreConfig('rma/'.$this->getRma()->getRmaContext().'/key'),
                'contextcode' => Mage::getStoreConfig('rma/'.$this->getRma()->getRmaContext().'/context'),
                'rmaref' => $this->getRma()->getRmaReference(),
                'taskcode' => $taskccode,   
                'xml' =>$xml
        	));

		$response = Mage::helper('rma/api')->APIRequest('process',$action,$data);  
		    
        if($response==false) $this->_getSession()->addError('RMA #'.$this->getRma()->getRmaReference().' is not '.$msg); else 
        {
        	$this->_getSession()->addSuccess('RMA #'.$this->getRma()->getRmaReference().' is '.$msg);
			foreach($rmaitems as $item)
			{
				if(isset($_POST['returned'][$item->getRmaItemsOrderItemId()]) && is_numeric($_POST['returned'][$item->getRmaItemsOrderItemId()]))$returned = $_POST['returned'][$item->getRmaItemsOrderItemId()]; else $returned=0;
				Mage::helper('rma')->changeRmaItemStatus($item,$this->getOrder(),$newstatus,$returned);
			}
			Mage::helper('rma')->changeRmaStatus($this->getRma(),$newstatus,$this->getOrder());
		}
	}

    protected function _isAllowed(){
      return Mage::getSingleton('admin/session')->isAllowed('admin/sales/menurma/rma_allow_check_cancel');
    }



}

/* EOF */
