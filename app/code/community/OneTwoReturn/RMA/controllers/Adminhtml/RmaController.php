<?php
/*
 * Custom order view for RMA orders
 */
require_once 'Mage/Adminhtml/controllers/Sales/OrderController.php';

class OneTwoReturn_RMA_Adminhtml_RmaController extends Mage_Adminhtml_Controller_Action
{
    private $params;
    private $order;
	
	private function setVar()
	{
		$this->params = $this->getRequest()->getParams();
		if(!isset($this->params['order_id']) || empty($this->params['order_id']))
		{
			Mage::getSingleton('core/session')->addError($this->__("Invalid order id"));
			
			return Mage::app()->getResponse()->setRedirect("/adminhtml/sales_order/index/");
		}
        $this->order = Mage::getModel('sales/order')->load($this->params['order_id']);
	}

    
    private function redirect()
    {
        $prefix = Mage::getConfig()->getNode('admin/routers/adminhtml/args/frontName');
        Mage::app()->getResponse()->setRedirect("/".$prefix."/sales_order/view/order_id/" . $this->params['order_id']);
    }
    
    public function AllowAction()
    {
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/rma_allow'))
        {
        	$this->setVar();
			$this->order->setData('selective_rma', 'true')->save();
			
            try
            {
                $this->order->save();
                $message = "Allowed Exchange RMA on this order.";
            }
            catch(Exception $e)
            {
                $message = "Failed to allow Exchange RMA on this order: " . $e->getMessage();
            }            
            Mage::getSingleton('core/session')->addSuccess($this->__($message));
        }
        $this->redirect();
    }
    
    public function DisallowAction()
    {
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/rma_allow'))
        {
        	$this->setVar();
            $this->order->setData('selective_rma', 'false');
            try
            {
                $this->order->save();
                $message = "Disallowed Exchange RMA on this order.";
            }
            catch(Exception $e)
            {
                $message = "Failed to disallow Exchange RMA on this order: " . $e->getMessage();
            }            
            Mage::getSingleton('core/session')->addSuccess($this->__($message));
        }
        $this->redirect();
    }
    
    public function AllowwarrantyAction()
    {
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/rma_warranty_allow'))
        {
        	$this->setVar();
            $this->order->setData('selective_rma_warranty', 'true');
            
            try
            {
                $this->order->save();
                $message = "Allowed Warranty RMA on this order.";
            }
            catch(Exception $e)
            {
                $message = "Failed to allow Warranty RMA on this order: " . $e->getMessage();
            }            
            Mage::getSingleton('core/session')->addSuccess($this->__($message));
        }   
        $this->redirect();     
    }
    
    public function DisallowwarrantyAction()
    {
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/rma_warranty_allow'))
        {
        	$this->setVar();
            $this->order->setData('selective_rma_warranty', 'false');
            
            try
            {
                $this->order->save();
                $message = "Disallowed Warranty RMA on this order.";
            }
            catch(Exception $e)
            {
                $message = "Failed to disallow Warranty RMA on this order: " . $e->getMessage();
            }            
            Mage::getSingleton('core/session')->addSuccess($this->__($message));
        }
        $this->redirect();
    }
}

/* EOF */