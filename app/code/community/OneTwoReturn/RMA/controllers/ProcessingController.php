<?php 
// IP VALIDATION CHECK
class OneTwoReturn_RMA_ProcessingController extends Mage_Core_Controller_Front_Action
{
   private $postData 		= false;
   private $logname			= '12Return_API.log';
   private $returnMethods	= array('STK'=>'re-stock',''=>'warranty');
   private $statusses 		= array('E'=>'rma_cancelled','C'=>'rma_complete');
   
   const	CON_SUCCESS 	= "resultcode=0";
   const	CON_ERROR		= "resultcode=1";
   const	CON_FATAL	 	= "resultcode=2";
   
   public function indexAction ()
   {
		if(Mage::getStoreConfig('rma/returnoptions/processing')=='internal')die(self::CON_FATAL);
   		$this->postdata = file_get_contents("php://input"); 
		if(!empty($this->postdata) && $this->postdata!=false) die($this->processMessage());
		else die(self::CON_FATAL); 
   }
   
   protected function _initOrder($orderNo)
   {
        $order = Mage::getModel('sales/order')->loadByIncrementID($orderNo);

        if (!$order->getId()) {
            Mage::log("ERROR EXTERNAL PROCESSING: Cannot find Order" . $orderNo, null, $this->logname, 1);
            return false;
        }
        Mage::register('sales_order', $order);
        Mage::register('current_order', $order);
        return true;
    }

	protected function _initRma($rmaRef)
    {
    	
		$rma=Mage::getModel('rma/rma')->getCollection()->addFieldToFilter('rma_reference',$rmaRef)->getFirstItem();
		if (!$rma->getRmaReference()) {
            Mage::log("ERROR EXTERNAL PROCESSING: Cannot find RMA" . $rmaRef, null, $this->logname, 1);
            return false;
        }
        Mage::register('OneTwoReturn_RMA', $rma);
        return true;
    }
	
	
	public function getOrder()
    {
        return Mage::registry('sales_order');
    }
	
	public function getRma()
    {
        return Mage::registry('OneTwoReturn_RMA');
    }	
   
    private function processMessage()
    {
   		if(Mage::getStoreConfig('rma/returnoptions/debugmode'))Mage::log("DEBUG: XML EXTERNAL PROCESS REQUEST " . $this->postdata, null, $this->logname, 1);
   		$request = Mage::helper('rma/api')->getDataFromResponse($this->postdata);
		
		if(	!isset($request['processedmsg']['rmano']) || empty($request['processedmsg']['rmano']) ||
			!isset($request['processedmsg']['orderno']) || empty($request['processedmsg']['orderno']) ||
			!isset($request['processedmsg']['products']) || empty($request['processedmsg']['products']) ||
			!isset($request['processedmsg']['products']) || empty($request['processedmsg']['status'])
		)
		{
			Mage::log("ERROR EXTERNAL PROCESSING: Message is invalid, missing madatory fields!\n".print_r($request,true), null, $this->logname, 1);
			return self::CON_FATAL;
		} 
		
		if(isset($request['processedmsg']['products']) && !isset($request['processedmsg']['products']['product'][0]))
		{
			$temp = $request['processedmsg']['products']['product'];
			$request['processedmsg']['products']['product']='';
			$request['processedmsg']['products']['product'][0]=$temp;
		}

		if($this->_initRma($request['processedmsg']['rmano']))
		{
			if($this->getRma()->getRmaStatusCode()=='rma_complete' || $this->getRma()->getRmaStatusCode()=='rma_cancelled')
			{
				Mage::log("ERROR EXTERNAL PROCESSING: RMA is allready in final state!".$this->getRma()->getRmaReference(), null, $this->logname, 1);
				return self::CON_ERROR;
			}
			if($this->_initOrder($request['processedmsg']['orderno']))
			{
				if($this->processProducts($request['processedmsg']['products']['product'],$this->statusses[$request['processedmsg']['status']]))
				{
					return self::CON_SUCCESS; 
				} else {
					return self::CON_ERROR; 
				}
			}
		}

		return self::CON_ERROR; 
    }

	private function processProducts($products,$newstatus)
	{
		$rmaitems = Mage::getModel('rma/ritems')->getCollection()->addFieldToFilter('rma_items_rma_id', $this->getRma()->getRmaId());
		
		foreach($products as $product)
		{
			foreach($rmaitems as $item)
			{
				if($product['lineno']==$item->getRmaItemsLineno() && $product['productid']==$item->getRmaItemsProductModel())
				{
					if(!isset($temp[$product['lineno']]))
					{
						$temp[$product['lineno']]['item']=$item;
						$temp[$product['lineno']]['received']=0;
						$temp[$product['lineno']]['tocredit']=0;
						$temp[$product['lineno']]['tostock']=0;
						$temp[$product['lineno']]['productid']=$product['productid'];
					}
					
					$temp[$product['lineno']]['received']=$temp[$product['lineno']]['received']+$product['received'];
					$temp[$product['lineno']]['tocredit']=$temp[$product['lineno']]['tocredit']+$product['tocredit'];
					$temp[$product['lineno']]['tostock']=$temp[$product['lineno']]['tostock']+$product['tostock'];
				}
			}
		}

		foreach($temp as $lines)
		{
			if(Mage::getStoreConfig('rma/returnoptions/debugmode'))Mage::log("DEBUG: XML EXTERNAL PROCESSING PRODUCT: " .$lines['productid'].'--'.$lines['received'].'--'.$lines['tostock'].'--'.$lines['tocredit'], null, $this->logname, 1);
			Mage::helper('rma')->changeRmaItemStatus($lines['item'],$this->getOrder(),$newstatus,$lines['received'],$lines['tocredit'],$lines['tostock']);
		}
		

		return Mage::helper('rma')->changeRmaStatus($this->getRma(),$newstatus,$this->getOrder());
	}
    protected function _isAllowed()
   {
       return true;
   }
}
