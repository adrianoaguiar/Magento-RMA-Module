<?php

class OneTwoReturn_RMA_Helper_Api extends Mage_Core_Helper_Abstract
{
    private $helper;
    const 	VERSION	        = 'MAGPL1.1';
	private $TIMEOUT			= 15;  
	const 	PREFIXURL		= '/o2r/osc/REST/';  
	private $logname		= '12Return_API.log';
	
    private $curl           = false;     
	private $debug			= true;       
	private $config			= false;   
	private $port			= false;
	                   
    
    public function __construct()
    {
        $server = Mage::getStoreConfig('rma/returnoptions/serverhost');
        
        $this->servers = array();
        $this->servers['production']['url'] = Mage::getStoreConfig('rma/advanced/productionurl');
        $this->servers['production']['port'] = Mage::getStoreConfig('rma/advanced/productionport');
        $this->servers['test']['url'] = Mage::getStoreConfig('rma/advanced/testurl');
        $this->servers['test']['port'] = Mage::getStoreConfig('rma/advanced/testport');
	
		$this->port=$this->servers[$server]['port'];
        $this->config['serverUrl'] 	= $this->servers[$server]['url'].':'.$this->servers[$server]['port'].self::PREFIXURL;
		$this->debug=Mage::getStoreConfig('rma/returnoptions/debugmode');
	}
	
	public function APIRequest($scope,$action,$data)
    {
    	if($scope=='process')
		{
			$url = $action;
		} elseif($scope=='creation') {
			$config='';
			$parent='';
			$url='RMA/New/';
			$xml = $this->prepareXml($data, $config, $parent);
			$data = array_merge($xml, $data);
        	$data = 'xml='.urlencode($this->buildRequestXML($data));  //Maak een XML structuur van de lokale sessie
		} elseif($scope=='context') {
        	$url='RMA/context/';
  			if($action == 'status')
			{
				$url .='Customer/'.$data['customer'].'/RMA';
				
	            if(!empty($data['orderno']))
	                $url.='/'.$data['orderno'];
	            if(!empty($data['orderline']))
	                $url.='/'.$data['orderline'];
	 
	            $data = 'key='.$config['key'].'&appversion='.self::VERSION.'&selectiondays='.$config['orderDays'];
			} 
			elseif($action=='countries')
	        {
	            $url .= '/parameters?key='.$config['key'];
	            $data = 'key='.$config['key'].'&appversion='.self::VERSION; 
	        }
        
		}
		return $this->execCurl($this->config['serverUrl'].$url,$data);
    }
	
	
	private function setSessionValue($key, $value)
	{
		Mage::getSingleton('customer/session')->setData($key, $value);
	}
	
	private function getSessionValue($key)
	{
		return Mage::getSingleton('customer/session')->getData($key);
	}

    private function execCurl($url,$xml=false)
    {
        if($this->curl!=false) $this->closeCurl();  
		      
        //$url.='?ts='.time();    
        $this->curl = curl_init($url);
        $this->setCurlOptions();
		
		if($xml!=false)curl_setopt($this->curl,CURLOPT_POSTFIELDS,$xml); else $xml='';//plaats de XML in de post fields
		$exec = curl_exec($this->curl); //Voer de Curl actie uit	

        $curl_errno = curl_errno($this->curl); //Controleren op errors
		if($exec==false ||  $curl_errno > 0) 
        {
            $curl_error = curl_error($this->curl); // Er is een error, dus vraag error bericht op.
            Mage::log("ERROR: URL: " . $url, null, $this->logname, 1);
			Mage::log("ERROR: Curl error: " . $curl_error, null, $this->logname, 1);
			Mage::log("ERROR: XML POST: " . urldecode($xml), null, $this->logname, 1);
			$return=false;
        } else { 
            $code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
            if($code!='200')
            {
                Mage::log("ERROR: URL " . $url, null, $this->logname, 1);
				Mage::log("ERROR: Status error code: " . $code, null, $this->logname, 1);
				Mage::log("ERROR: XML POST: " . urldecode($xml), null, $this->logname, 1);
				$return = false;
            } else {
            	if($this->debug)		Mage::log("DEBUG: 12return request url: " . $url, null, $this->logname, 1);
				if($this->debug)		Mage::log("DEBUG: 12return request xml: " . urldecode($xml), null, $this->logname, 1);
				if($this->debug)		Mage::log("DEBUG: 12return response: " . $exec, null, $this->logname, 1);
	            $response = $this->processResponse($exec);
	            
	            if(empty($response['response']) || trim($response['response']) == 'No more data to read from socket')
	            {
	                Mage::log("ERROR: No more data to read from socket" . $url, null, $this->logname, 1);
	                $return = false;
	            } 
	            else 
	            {
	                $return = $this->getDataFromResponse($response['response']);
					$errors=$this->checkForResponseErrors($return);
					if($errors!=false)
					{
						Mage::log("ERROR: URL: " . $url, null, $this->logname, 1);
						Mage::log("ERROR: XML POST: " . urldecode($xml), null, $this->logname, 1);
						Mage::log("ERROR: 12return error: " . $errors, null, $this->logname, 1);
						if($this->debug)		Mage::log("ERROR: 12return response: " . $exec, null, $this->logname, 1);
						$return=false;
						Mage::getSingleton('core/session')->addError($errors); 
					}
				}
            }
        }
		$this->closeCurl();
        return $return;
    }

	private function setCurlOptions()
	{
		curl_setopt($this->curl,CURLOPT_RETURNTRANSFER,1);  //Vang de response van de server af
        curl_setopt($this->curl,CURLOPT_AUTOREFERER,1); // This make sure will follow redirects
        curl_setopt($this->curl,CURLOPT_FAILONERROR,1);
        curl_setopt($this->curl,CURLOPT_HEADER,1);  // THis verbose option for extracting the headers
        curl_setopt($this->curl,CURLOPT_POST,1);        // Set POST method on
        curl_setopt($this->curl,CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded')); //Set Content type
        curl_setopt($this->curl,CURLOPT_FORBID_REUSE, 1);     //to force the connection to explicitly close when it has finished processing, and not be pooled for reuse
        curl_setopt($this->curl,CURLOPT_FRESH_CONNECT, 1);    //to force the use of a new connection instead of a cached one.
        curl_setopt($this->curl,CURLOPT_CONNECTTIMEOUT, $this->TIMEOUT);      //The number of seconds to wait while trying to connect. Use 0 to wait indefinitely.
        curl_setopt($this->curl,CURLOPT_TIMEOUT, $this->TIMEOUT);
        curl_setopt($this->curl,CURLOPT_PORT, $this->port); 
        curl_setopt($this->curl,CURLOPT_CUSTOMREQUEST, "POST");  
        curl_setopt($this->curl, CURLOPT_USERAGENT, "curl/7.23.1 (x86_64-unknown-linux-gnu) libcurl/7.23.1 OpenSSL/0.9.8b zlib/1.2.3");
        if($this->debug)
             curl_setopt($this->curl,CURLOPT_VERBOSE, true);  
        else 
            curl_setopt($this->curl,CURLOPT_VERBOSE, false); 
        curl_setopt($this->curl,CURLOPT_SSL_VERIFYHOST, false);   //Zet op 2 in production
        curl_setopt($this->curl,CURLOPT_SSL_VERIFYPEER, false);   //Zet op true in production
	}
    
    public function closeCurl()
    {
        curl_close($this->curl);
        $this->curl = false;        
    }
    	
    

	/*
	 * Process response XML data
	 */
	private function processResponse($response)
    {
        // -- is response empty?
        if($response == null or strlen($response) < 1) 
        { 
            return '';
        }
        
		// -- split header and body
        $parts  = explode("\n\r",$response);
		 
        if(preg_match('@HTTP/1.[0-1] 100 Continue@',$parts[0])) 
        {
            for($i=1;$i<count($parts);$i++)  $parts[$i - 1] = trim($parts[$i]);
            unset($parts[count($parts) - 1]);
        }
        
        preg_match("@Content-Type: ([a-zA-Z0-9-]+/?[a-zA-Z0-9-]*)@",$parts[0],$reg);// This extract the content type
        $return['content-type'] = $reg[1];
        
        preg_match("@HTTP/1.[0-1] ([0-9]{3}) ([a-zA-Z ]+)@",$parts[0],$reg); // This extracts the response header Code and Message
        $return['code'] = $reg[1];
        $return['message'] = $reg[2];
        $return['response'] = "";
        
        for($i=1;$i<count($parts);$i++) {//This make sure that exploded response get back togheter
            if($i > 1) $return['response']  .= "\n\r";
            $return['response']  .= $parts[$i];
        }
        
        return $return;
    }

    public function getDataFromResponse($XML)
    {
        $XML = trim($XML);
        $XML = new SimpleXMLElement($XML);
        $array = $this->objectToArray($XML);
        if(is_array($array) && !empty($array))
            return $array;
        else
            return false;
    }

	public function objectToArray($object)
    {
        if( !is_object( $object ) && !is_array( $object ) )
        {
            return $object;
        }
        if( is_object( $object ) )
        {
            $object = get_object_vars( $object );
        }
        return array_map( array($this, "objectToArray"), $object );
    }
	
	private function checkForResponseErrors($array)
    {
    	$errormsg='';
        if(isset($array['status']['statuscode']) && $array['status']['statuscode']=='E')
        {
            if(isset($array['status']['errors']['error'][0]))
            {
                foreach($array['status']['errors']['error'] as $error) $errormsg.=$error['errorcode'].' - '.$error['errordesc']; // Toon error bericht
            } else {
                foreach($array['status']['errors'] as $error) $errormsg.=$error['errorcode'].' - '.$error['errordesc']; // Toon error bericht
            }
        } 
        if(isset($array['result']['returncode']) && $array['result']['returncode']=='E')
        {
            if(isset($array['result']['errors']['error'][0]))
            {
                foreach($array['result']['errors']['error'] as $error) $errormsg.=$error['errorcode'].' - '.$error['errordesc']; // Toon error bericht
            } else {
                if(isset($array['result']['errors'][0]))foreach($array['result']['errors'] as $error) $errormsg.=$error['errorcode'].' - '.$error['errordesc']; // Toon error bericht
            }
        }
        if(isset($array['errors']['error']) && $array['result']['returncode']=='E')
		{
			if(isset($array['errors']['error'][0]))
            {
                foreach($array['result']['errors']['error'] as $error) $errormsg.=$error['errorcode'].' - '.$error['errordesc']; // Toon error bericht
            } else {
            	$errormsg.=$array['errors']['error']['errorcode'].' - '.$array['errors']['error']['errordesc'];
            }
		}
		if(empty($errormsg))return false; else return $errormsg;
    }


	   // -- commentaar van de vorige bouwer: //////////////////////////////////Moet ik nog maken, controleerd of value geld is en niet te lang, anders trancute
    private function validateValue($value,$type='AN',$maxLenght='10',$decimal='10') 
    {
        if($type == 'N')
            $value = round($value,$decimal);
        
        if(strlen($value)>$maxLenght)
        {
            return $this->truncateString($value,$maxLenght);
        }
        else 
        {
            return $value;
        }
    }
    
    private function truncateString($text,$num) 
    { 
        return preg_replace('/\s+?(\S+)?$/', '', substr($text, 0, $num));
    } 
    
    private function createNodeChild($data,$xml)
    {
        foreach($data as $node=>$nodeData)
        {
            if(is_array($nodeData)&& !empty($nodeData))
            {
                $multiChild = true;
                foreach($nodeData as $key=>$value)
                    if(!is_numeric($key))
                        $multiChild = false;
                
                if($multiChild)
                {
                    foreach($nodeData as $childData)
                    {
                        $child = $xml->addChild($node, '');
                        $this->createNodeChild($childData,$child);
                    }
                } 
                else 
                {
                    $child = $xml->addChild($node, '');
                    $this->createNodeChild($nodeData,$child);
                }
            } 
            elseif(!empty($nodeData) && !is_array($nodeData)) 
                $xml->addChild($node, $this->cleanXmlValue($nodeData,$node));
        }
        return true;
    }
    
    private function cleanXmlValue($value,$node='')
    {        
        $value = htmlentities ($value,ENT_COMPAT ,'UTF-8');
        $value=$this->xmlentities($value);
        $value = trim($value);
        return $value;
    }
    
    private function xmlentities($string) {
        return str_replace(array("<", ">", "\"", "'", "&"),
            array("&lt;", "&gt;", "&quot;", "&apos;", "&amp;"), $string);
    }





























	public function prepareXml($data, $config, $parent)
    {
    	
        $xmlData = array();
        
        if(!isset($xmlData['context']))
            $xmlData['context'] = $config['context'];
        
        if(!isset($xmlData['key']))
            $xmlData['key'] = $config['key'];
        
        if(!isset($xmlData['appversion']))
            $xmlData['appversion'] = self::VERSION;
        
        if(!isset($xmlData['language']))
            $xmlData['language'] = Mage::app()->getLocale()->getLocaleCode(); //$config['language'];
        
        // if(!isset($xmlData['conversationhandle']))
        $xmlData['conversationhandle'] = $this->getSessionValue('conversationhandle');
		//$xmlData['conversationhandle'] ='14286';
        
        $rmaInfo = Mage::helper('rmareturn')->getSessionData('rmainfo');
        //$orderSession = $this->getSession('order');  
        
        if(!empty($rmaInfo['ordernumber']))  
            $xmlData["ownerreference"] = $rmaInfo["ordernumber"];
        
        $xmlData[$parent] = $data;
        
        return $xmlData;
    }

    private function buildRequestXML($data)
    {
        unset($data['status']);
		
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?> <rmarequest></rmarequest>');
        if(!empty($data))
        {
            foreach($data as $node=>$nodeData)
            {
                if(!empty($node))
                {
                    if(is_array($nodeData)&& !empty($nodeData))
                    {
                        $child = $xml->addChild($node, '');
                        $this->createNodeChild($nodeData,$child);
                    } elseif(!empty($nodeData) && !is_array($nodeData)) $xml->addChild($node, $this->cleanXmlValue($nodeData,$node));
                }
            }
			
            return $xml->asXML();
        } else {
            $this->abort('091');
        }
    }

    public function createProductDataset($products, $ordernumber)
    {
        $order = Mage::getModel('sales/order')->loadByIncrementId($ordernumber)->getData();

        $i = 0;
        
        // -- workaround if going back in the process. Model of products has changed to 12return model so data is different
        if(isset($products["product"]))
        {
            // var_dump($products);
            $tempproducts = array();
            foreach($products["product"] as $product)
            {
                $tempproducts[Mage::getModel('catalog/product')->loadByAttribute('sku',$product["model"])->getId()] = $product["qty"];
            }
            $products = $tempproducts;
        }
                                                                                                                                                                                                                                                                            
        foreach($products as $id => $qty)
        {
            //var_dump($id . " => " . $qty);
            
            // -- TODO: coresix
            // if($config['coresix']!='coresix')
            // {
            	$product = Mage::getModel('catalog/product')->load($id);         
                $productData = Mage::getModel('catalog/product')->load($id)->getData();                

                // $desc = $productData['name'];
                            // }
            // } else {
                // $_data=$_product;
                // $desc=$_data['productdesc'];
                // $custom='';
            // }
            
            // -- vaag, geen idee wat hier gebeurd
            $error      = '';
            if(isset($productData['type']))
                $productsSet['product'][$i]['type'] = $this->validateValue($productData['type'],'AN','20');
            
            if(isset($productData['brand']))
                $productsSet['product'][$i]['brand'] = $this->validateValue($productData['brand'],'AN','50');
            elseif(isset($productData['manufacturer']))
                $productsSet['product'][$i]['brand'] = $this->validateValue($productData['manufacturer'],'AN','50');
            else 
                $productsSet['product'][$i]['brand']='Unknown';
            
           	if(isset($product) && !is_null($product->getSku()))
                $productsSet['product'][$i]['model']=$this->validateValue($product->getSku(),'AN','26');
			elseif(isset($productData['model']))
				$productsSet['product'][$i]['model'] = $this->validateValue($productData['model'],'AN','26');
            else 
                $error .= 'model ';
            
            if(isset($productData['name']) && !empty($productData['name']))
                $productsSet['product'][$i]['productdesc'] = $this->validateValue($productData['name'],'AN','240');
            else 
                $productsSet['product'][$i]['productdesc'] = 'Omschrijving';
            
            $productsSet['product'][$i]['qty'] = $qty;
            
            if(isset($productData['lenght']))
                $productsSet['product'][$i]['lenght'] = $this->validateValue($productData['lenght'],'N','4');
            
            if(isset($productData['width']))
                $productsSet['product'][$i]['width'] = $this->validateValue($productData['width'],'N','4');
            
            if(isset($productData['height']))
                $productsSet['product'][$i]['height'] = $this->validateValue($productData['height'],'N','4');
            
            if(isset($productData['weight']))
                $productsSet['product'][$i]['weight'] = $this->validateValue($productData['weight'],'N','9','3');
            
            // -- TODO: core6
            // if($this->helper->config['coresix'] != 'coresix')
            // var_dump($productData); die();
                // $productsSet['product'][$i]['value'] = $this->validateValue(number_format($productData['price'], 2),'N','7','2'); 
                $productsSet['product'][$i]['value'] = $this->validateValue(number_format($product->getPrice(), 2),'N','7','2');
            // else 
                // $productsSet['product'][$id]['value'] = $this->validateValue(number_format($productData['value'], 2),'N','7','2');  
            
            // var_dump($order["base_currency_code"]);
            if(isset($order['base_currency_code']))
                $productsSet['product'][$i]['ccy'] = $this->validateValue($order['base_currency_code'],'AN','5');
            elseif(isset($productData['ccy']))
                $productsSet['product'][$i]['ccy'] = $_data['ccy'];
            else 
                $_data['ccy'] = 'EUR';
            
            if(isset($_data['color']))$productsSet['product'][$d]['color']=$this->validateValue($_data['color'],'AN','64');
            elseif(isset($_data['colour']))$productsSet['product'][$d]['color']=$this->validateValue($_data['colour'],'AN','64'); 
             
            if(isset($productData['size']))
                $productsSet['product'][$i]['size']=$this->validateValue($productData['size'],'AN','64');
            
            if(isset($productData['flavour']))
                $productsSet['product'][$i]['flavour']=$this->validateValue($productData['flavour'],'AN','64');

            $productsSet['product'][$i]['orderno'] = $ordernumber;
            $productsSet['product'][$i]['lineno'] = $i+1;
            
            $i++;
        }
        if(empty($error))
        {
            return $productsSet;
        } else {
            return $error;
        }
    }

    public function createKlantDataset($answers, $customer_id)
    {        
        $customer['identifier']      = $this->validateValue($customer_id,'AN', '255');
        $customer['company']         = $this->validateValue($answers['bedrijf'],'AN', '40');
        $customer['title']           = '';
        $customer['name']            = $this->validateValue($answers['naam'],'AN', '40');
        $customer['houseno']         = $this->validateValue($answers['huisnummer'],'AN', '25');
        $customer['postal']          = $this->validateValue($answers['postcode'],'AN', '12');
        $customer['country']         = $this->validateValue($answers['land'],'AN', '2');
        $customer['phone']           = $this->validateValue($answers['telefoon'],'AN', '15');
        $customer['fax']             = $this->validateValue($answers['fax'],'AN', '15');
        $customer['mobile']          = $this->validateValue($answers['mobiel'],'AN', '15');
        $customer['email']           = $this->validateValue($answers['email'],'AN', '80');
        $customer['city']            = $this->validateValue($answers['stad'],'AN', '35');
        $customer['street']          = $this->validateValue($answers['adres'],'AN', '50');
        // $this->resetSession('xml');
        // $this->addToSession('xml',$xml);
        return $customer;
    }

    
 
}

/* EOF */
