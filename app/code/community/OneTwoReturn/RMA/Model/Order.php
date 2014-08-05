<?php

class OneTwoReturn_RMA_Model_Order extends Varien_Object
{
	public $helper;
	
	public function __construct()
	{
		$this->helper = Mage::helper('rma');
	}
	
	public function getOrders($type)
	{
		// -- get customer & orders
        $customer = $this->helper->getCustomer();            
        
		$this->helper->activateReturnType($type);
			
		// -- TODO? only get orders newer than today - max days from settings
    	$orders = $this->helper->getAllOrderIdsByCustomerId($customer->getId());
			// die("1");
		if(is_array($orders) && !empty($orders))
			return $orders;
		else		
			return null;
	}
    
    public function isValidOrder($type, $ordernr, $postcode)
    {        
        $this->helper->activateReturnType($type);
        $order = Mage::getModel('sales/order')->loadByIncrementId($ordernr);
        if(isset($order) && !empty($order))
        {            
            if($this->helper->checkOrderDate($order))
            {
                // -- permission for RMA is set per order                
                if($this->helper->config['selectiverma'] == 'perorder')
                {
                    $shipping = $order->getShippingAddress();
                    if(!empty($postcode) && strtoupper(str_replace(" ","",trim($postcode))) == strtoupper(str_replace(" ","",trim($shipping->getPostcode()))))
                    {
                        // -- is permission for RMA set on this order?
                        if(($type == "view" && $order->getData('selective_rma') == "true") || ($type == "warranty" && $order->getData('selective_rma_warranty') == "true"))
                        {
                            // -- valid order!
                            return true;
                        }
                        else 
                        {
                            // -- valid order but no permission for RMA
                            Mage::getSingleton('customer/session')->addError($this->helper->__('Order can not be returned'));
                            return false;                            
                        }
                    }
                    else 
                    {       
                        // -- postcode entered is different from postcode in order
                        Mage::getSingleton('customer/session')->addError($this->helper->__('Postcode does not match'));
                        return false;
                    }                       
                } 
                else 
                {
                    // -- all RMA on orders are allowed 
                    $shipping = $order->getShippingAddress();
                    if(!empty($postcode) && strtoupper(str_replace(" ","",trim($postcode))) == strtoupper(str_replace(" ","",trim($shipping->getPostcode()))))
                    {
                        // -- entered postcode is same as orderpostcode, order must be valid
                        return true;
                    }
                    else
                    {   
                        // -- postcode entered is different from postcode in order
                        Mage::getSingleton('customer/session')->addError($this->helper->__('Postcode does not match'));
                        return false;
                    }
                }
            }
            else 
            {
                // -- order can't be returned anymore
                //$this->__('Order can not be returned')
                Mage::getSingleton('customer/session')->addError($this->helper->__('Order can not be returned'));
                return false;    
            }
        }
        else 
        {
            // -- ordernumber invalid
            Mage::getSingleton('customer/session')->addError($this->helper->__('Ordernumber invalid'));
            return false;    
        }
    }
    
    public function checkValidOrder($order, $postcode = '', $selective = false)
    {
        
        //$order = $this->getOrderData($orderId);
        
        if(!empty($order))
        {
            $shippingAddress = $order->getShippingAddress();
            if(isset($postcode) && !empty($postcode))          
                $postcode = strtoupper(str_replace(" ","",trim($postcode)));
            else
            {
                $postcode = strtoupper(str_replace(" ","",trim($order->getShippingAddress()->getData('postcode'))));
            }
            
            if(!empty($postcode) && $postcode == strtoupper(str_replace(" ","",trim($order->getShippingAddress()->getData('postcode')))))
            {
                if($selective)
                {   
                    $rmaInfo = $this->helper->getSessionData('rmainfo');
                    
                    if($rmaInfo["type"] == 'view')
                    {
                        $selectiveRma = $order->getData('selective_rma');
                        if(isset($selectiveRma) && $selectiveRma == 'true')
                        {
                            return true;
                        } else {
                            if(!$silent)
                                Mage::getSingleton('customer/session')->addError($this->translate('orderNotValidForRma'));
                            return false;
                        }
                        return false; 
                    } else {
                        
                        $selectiveRma = $order->getData('selective_rma_warranty');
                        
                        if(isset($selectiveRma) && $selectiveRma == true)
                        {
                            return true;
                        } else 
                        {
                            if(!$silent)
                                Mage::getSingleton('customer/session')->addError($this->translate('orderNotValidForRma'));
                            return false;
                        }
                        return false; 
                    }
                } 
                else 
                    return true;
                   
                   
                   
            } 
            else
            {                        
                if(!$silent)
                    Mage::getSingleton('customer/session')->addError($this->translate('postcodeDoesNotMatch'));
                return false;
            }
                // }else {
                    // if(!$silent)Mage::getSingleton('customer/session')->addError($this->translate('orderCanNotBeReturned')); //Order valt buiten aantal mogelijke dagen om te retourneren, dus toon error.
// 
                    // return false;
                // }
                }
        // } else {
            // if(!$silent)Mage::getSingleton('customer/session')->addError($this->translate('orderDoesNotExists')); //Order bestaat niet, dus toon error.
// 
            // return false;
        // }
    }
	
	public function getOrderItems($order)
	{
		if(isset($order) && !empty($order))
		{
			// -- TODO: coresix
			$allproducts = $order->getAllVisibleItems();
			
			$returnValid=false;
			if(isset($allproducts) && is_array($allproducts) && !empty($allproducts) )
			{
				//$i = 0;
				$productsArray = array();
				foreach($allproducts as $index => $_orderItem)
				{
					//$product = Mage::getModel('catalog/product')->load($_product->getId());
					// -- TODO: create array with required options only, complete products not needed
					// -- Seems not possible, need productsanyway. 
					// $productData = array();
					// $productData['product_id'] = $product->getId();
					// $productData['url'] = $product->getProductUrl();
					
					$productsArray[] = $_orderItem;//$_product;
					//$i++; 
				}
				
				return $productsArray;
			}
		}
		else
		{
			return null;	
		}
	}
    
    public function getOrderData($orderId,$addsession=true) //Haal alle relevante data van het order object op en plaats deze in een var
    {
        // die("hallo");
        //$this->thisOrder=$orderData= Mage::getModel('sales/order')->loadByIncrementId($orderId)->getData();
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId)->getData();
        // $orderSession['data'] = $this->thisOrder;
        // $orderSession['orderId'] = $orderId;
        // if($addsession)$this->helper->addToSession('Order',$orderSession);
        return $order;
    }
    
	public function getProductData($_product)
	{
		// TODO core6
		$productData = array();
		
		// if($config['coresix']!='coresix')
                // $price = $_product->getPrice(); 
            // else 
                // $price =$_product['value'] ;
		$productData['price'] = $_product->getPrice();
		
		// if($config['coresix']!='coresix')
                // $qnty = round($_product->getQtyOrdered());
            // else
                // $qnty = $_product['qtyordered'];
		$productData['qnty'] = round($_product->getQtyOrdered());
		
		// if($config['coresix']!='coresix')
                // $totals = $_product->getRowTotalInclTax(); 
            // else 
                // $totals = $price * $qnty;
		$productData['totals'] = Mage::helper('core')->currency($_product->getRowTotalInclTax()); 
		
		// if($config['coresix']!='coresix')
                // $allOptions = $_product->getData('product_options'); 
            // else 
                // $allOptions='';
		$productData['alloptions'] = $_product->getData('product_options');
		
		// -- TODO get RMA status
		
		
		return $productData;
	}

    
}

/* EOF */