<?php
class OneTwoReturn_RMA_Helper_Data extends Mage_Core_Helper_Abstract
{
	public $config;
	public $api;
	private $debug = false;
	
    public $allowedOrderStatus = array('complete', 'rma_created', 'rma_complete', 'closed', 'rma_cancelled','rma_received',);//, 'pending');
	public $returnTypes = array('view'=>'View and exchange','warranty'=>'Warranty');
	
	
	public function __construct()
    {
		$this->debug=Mage::getStoreConfig('rma/returnoptions/debugmode');
	}
		
	public function setSessionData($key, $data)
	{
		if($this->debug) Mage::log('Set Sessiondata: ' . $key);
		
		$sessionData = Mage::getSingleton('core/session')->getData('12return');
		$sessionData[$key] = $data;
		Mage::getSingleton('core/session')->setData('12return', $sessionData);
	}
	
	public function addSessionData($key, $datakey, $data)
	{
		$sessionData = Mage::getSingleton('core/session')->getData('12return');
		$sessionData[$key][$datakey] = $data;
		
		if($this->debug)Mage::log('Adding Sessiondata: ' . $key);
		
		Mage::getSingleton('core/session')->setData('12return', $sessionData);
	}
	
	public function getSession($key)
	{
		return $this->getSessionData($key);
	}
	
	public function getSessionData($key)
	{
		$sessionData = Mage::getSingleton('core/session')->getData('12return');

		if(!empty($sessionData[$key]))
			return $sessionData[$key];
		else
			return false;
	}

	public function getStatusLabel($status)
	{
		$data = Mage::getModel('rma/rstatus')->getCollection()->addFieldToFilter('rma_status_code', $status)->getFirstItem()->getData();
		return $data['rma_status_label'];
	}

	public function getRmasByCustomer($id)
	{
		return Mage::getModel('rma/rma')->getCollection()->addFieldToFilter('rma_customer_id', $id);
	}

	public function getRmasByOrder($orderno)
	{
		return Mage::getModel('rma/rma')->getCollection()->addFieldToFilter('rma_order_increment_id', $orderno);
	}
	
	public function getRmaItemsByOrderItem($orderitem)
	{
		return Mage::getModel('rma/ritems')->getCollection()->addFieldToFilter('rma_items_order_item_id', $orderitem);
	}

	public function changeRmaStatus($rma,$newstatus='rma_complete',$order=false)
	{
		if($order==false)$order = Mage::getModel('sales/order')->load($rma->getRmaOrderEntityId());
		
		if($newstatus=='rma_complete')$action='completed'; else $action='cancelled';
		
		$notify=Mage::getStoreConfig('rma/communication/'.$action.'_notify');
		$visible=Mage::getStoreConfig('rma/communication/'.$action.'_view');
        $comment=str_replace('{RMAREF}',$rma->getRmaReference(),str_replace('{LINK_STATUS}','<a target="_blank" href="'.Mage::getStoreConfig('rma/advanced/statusurl').'" >'.Mage::getStoreConfig('rma/advanced/statusurl').'</a>',Mage::getStoreConfig('rma/communication/'.$action.'_message')));
		
		
		$order->addStatusHistoryComment($comment,$newstatus)
		->setIsVisibleOnFront($visible)
		->setIsCustomerNotified($notify);
		$order->save();
		$order->sendOrderUpdateEmail($notify, $comment);
		
		$rma->setData('rma_status_code',$newstatus)->save();
		$rma->setData('rma_updatedate',date('Y-m-d H:i:s'))->save();
		return true;
	}
	
	public function changeRmaItemStatus($item,$order,$newstatus='rma_complete',$returned=0,$tocredit=0,$tostock=0)
	{
		
		if($newstatus=='rma_complete')
		{
			$returning 	= 0;
			$returned	= $item->getData('rma_items_qty_returned')+$returned;
			$tocredit	= $item->getData('rma_items_qty_tocredit')+$tocredit;
			$tostock	= $item->getData('rma_items_qty_tostock')+$tostock;
		} else {
			$returning	= 0;
			$returned	= 0;
			$tocredit	= 0;
			$tostock	= 0;
		}
		
		foreach($order->getAllItems() as $key=>$orderitem)
		{
			if($orderitem->getItemId()==$item->getRmaItemsOrderItemId())
			{
				$orderitem->setData('qty_returning', $orderitem->getData('qty_returning')-$item->getData('rma_items_qty_returning'));
				if($newstatus=='rma_complete')$orderitem->setData('qty_returned', $orderitem->getData('qty_returned')+$returned);
				$orderitem->save();
			}
		}
		
		$data['rma_items_rma_status_code']  = $newstatus;
		$data['rma_items_qty_returned'] 	= $returned;
		$data['rma_items_qty_returning']	= $returning;
		$data['rma_items_qty_tocredit'] 	= $tocredit;
		$data['rma_items_qty_tostock']		= $tostock;
		

		$id 	= $item->getData('rma_items_id');
		$model 	= Mage::getModel('rma/ritems')->load($id)->addData($data);
		$model->setId($id)->save();
		return true;
	}

	public function noRoute(){
		
		$this->getResponse()->setHeader('HTTP/1.1','404 Not Found');
	    $this->getResponse()->setHeader('Status','404 File not found');
	
	    $pageId = Mage::getStoreConfig(Mage_Cms_Helper_Page::XML_PATH_NO_ROUTE_PAGE);
		
	    if (!Mage::helper('cms/page')->renderPage($this, $pageId)) {
	        $this->_forward('defaultNoRoute');
	    }
	}
	
	public function getStatusLink($rmaref)
	{
		$url = trim(Mage::getStoreConfig('rma/advanced/statusurl'));
		if(!empty($url) && $url!= ' ') return '<a href="'.str_replace("{RMAREF}",$rmaref,$url).'" target="_blank" title="View status portal RMA #'.$rmaref.'"> '.$rmaref.' </a>';
		else return $rmaref; 
	}

	public function getImageUrl($url)
	{
		if(!empty($url))
		{
			$parts = explode("=",$url);
			$file = end($parts);
			$magento_vardir =  Mage::getBaseDir('media') . DS ;
			$imageFile = $magento_vardir.'rma/'.$file;
			
			if(file_exists($imageFile) &&!empty($file))
			{
				$img = $file;
			} else {
				$img = 'noimage.png';
			}
		} else {
			 $img='noimage.png';
		}
		return Mage::getBaseUrl('media') .'rma/'.$img;
	}
}
	