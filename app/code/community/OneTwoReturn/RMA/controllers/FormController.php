<?php
class OneTwoReturn_RMA_FormController extends Mage_Core_Controller_Front_Action
{
	
	private $version			= 'MAGPL1.1';
	private $Config	  			= array();
	private $curl				= false;
	private $sessionStarted 	= false;
	private $loggedIn 			= false;	
	private $debug				= false;
	
	private $RESTrma			= '/o2r/osc/REST/RMA/New/';
	private $RESTcontext		= '/o2r/osc/REST/Context/';
	private $returnTypes  		= array('view','warranty');
	public $allowedOrderStatus 	= array('complete', 'rma_created', 'rma_complete', 'closed');//, 'pending');
	
	private $RESTurl			= '';
	private $Contexturl			= '';
	private $RESTport			= '';
	public $labelurl			= '';
	
	private $stackTrace			= array();
	
	private function _initAction()
	{
		$this->loadConfig();
		if($this->Config['pluginenabled']==false)
		{
			$this->noRoute();
			return false;
		} 
		$this->loadLayout(); 
		return true;
	}

	public function indexAction() //Alias voor de authenticate functie, aanroepen dus al men via index binnenkomt.
    {
    	$this->resetSession();
    	if($this->_initAction())$this->redirectTo('select');
    }
	
	public function selectAction() //Alias voor de authenticate functie, aanroepen dus al men via index binnenkomt.
    {
    	$this->resetSession();
    	if($this->_initAction())
		{
			$this->_initLayoutMessages('customer/session');
			if($this->multiplyReturnTypes()) //Bekijken of er meerdere return types actief zijn
			{
				
				//Zoja, laad dan de layout om de bezoeker een keuze te geven.
				
				if ($this->getRequest()->isPost()){
					if(isset($_POST['type']))
					{
						if(!$this->activevateReturnType($_POST['type']))
						{
							Mage::getSingleton('customer/session')->addError($this->translate('noActiveReturnType'));
							$this->noRoute();
							return false;
						} elseif(Mage::getSingleton('customer/session')->isLoggedIn() && $this->Config['useaccountifpossible']==true )$this->redirectTo('selectorder'); else $this->redirectTo('authenticate');
					} else {
						Mage::getSingleton('customer/session')->addError($this->translate('MissingFormField').$this->translate('ReturnType'));
						$this->redirectTo('select');
					}
				}
				
			} else {
				
				if(!$this->activevateReturnType())
				{
					Mage::getSingleton('customer/session')->addError($this->translate('noActiveReturnType'));
					$this->noRoute();
					return false;
				} elseif(Mage::getSingleton('customer/session')->isLoggedIn()&& $this->Config['useaccountifpossible']==true )$this->redirectTo('selectorder'); else $this->redirectTo('authenticate');// Zoniet, stuur de bezoeker dan direct door naar inlog pagina.
			}
			$this->renderLayout();
		}
    }
	

    public function authenticateAction() //Hier laten we de user inloggen via een order id.
    {
    	$this->resetSession('allorders');
		$this->resetSession('tempRMARef');
		$this->resetSession('countryError');
    	if($this->_initAction())
		{
			$this->_initLayoutMessages('customer/session');
			$fieldcheck	= $this->getSession('select');
			if(Mage::getSingleton('customer/session')->isLoggedIn()&& $this->Config['useaccountifpossible']==true )$this->redirectTo('selectorder');
				if($this->Config['loginType']=='order')
				{
					
			    	if ($this->getRequest()->isPost()){ //Wanneer er een post actie is controleren of alles is ingevuld.
			    		if(isset($_POST['ordernumber']) && !empty($_POST['ordernumber']) && !empty($_POST['postcode']))
						{
							$_POST['ordernumber']=trim($_POST['ordernumber']);
							$order = $this->getOrderData($_POST['ordernumber']);
							if(!empty($order))
							{
								if($this->checkOrderDate($_POST['ordernumber'],false))
								{
									
									if($this->checkOrderSelective($_POST['ordernumber'],false))
									{
										
										$thisAddress=$this->getShippingAddressData($_POST['ordernumber']);
										
										$thisAddress['postcode'] = strtoupper(str_replace(" ","",trim($thisAddress['postcode'])));
										$_POST['postcode']= strtoupper(str_replace(" ","",trim($_POST['postcode'])));
										if($thisAddress['postcode']==$_POST['postcode'])	//Controlerne of men via order inlogd, dan postcode valideren.
										{
											$this->getOrderData($_POST['ordernumber']);
											if($this->Config['useaccountifpossible']==true)
												$this->redirectTo('selectorder');	
											else {
												$this->getCustomerData();  
												$this->redirectTo('selectproduct');
											}
											
										} else {
											if(!$silent)Mage::getSingleton('customer/session')->addError($this->translate('postcodeDoesNotMatch'));
											$this->redirectTo('authenticate');	
										}
									}else {
										Mage::getSingleton('customer/session')->addError($this->translate('orderNotValidForRma')); //Order valt buiten aantal mogelijke dagen om te retourneren, dus toon error.
				
										$this->redirectTo('authenticate');				//Gegevens zijn ongeldig, stuur terug naar het login formulier.
									}
								}else {
									Mage::getSingleton('customer/session')->addError($this->translate('orderCanNotBeReturned')); //Order valt buiten aantal mogelijke dagen om te retourneren, dus toon error.
				
									$this->redirectTo('authenticate');				//Gegevens zijn ongeldig, stuur terug naar het login formulier.
								}
							} else {
								Mage::getSingleton('customer/session')->addError($this->translate('orderDoesNotExists')); //Order bestaat niet, dus toon error.
								$this->redirectTo('authenticate');
							}
						} else {									//Niet alles is ingevuld, dus toon error.
					    	Mage::getSingleton('customer/session')->addError($this->translate('notFilledInRequiredInfo'));
							$this->redirectTo('authenticate');				//stuur terug naar het login formulier.
						}
			    	} else {					//Niet ingelogd, dus toon inlog formulier voor order nummer
			    	
			    		$this->resetSession('allorders');$this->resetSession('order');$this->resetSession('customer');
			    	}
				} else {				//Niet ingelogd, dus stuur bezoeker door naar inlog formulier en onthoud huidige URL
					if(!Mage::getSingleton('customer/session')->isLoggedIn()) {
						Mage::getSingleton('customer/session')->setBeforeAuthUrl(Mage::getUrl("12return/form/selectorder", array('_nosid' => true)));
					    Mage::app()->getResponse()->setRedirect(Mage::getUrl("customer/account/login"));
					} else {
						$this->redirectTo('selectorder');		
					}
				}
	
			$this->renderLayout();
			
			return true;
		}
    }

	
    public function selectorderAction() //Dit is de tweede stap wanneer men met een gebruikers account inlogd, men specificeerd hier het order id.
    {
		$this->resetSession('allorders');
		$this->resetSession('tempRMARef');
		$this->resetSession('countryError');
    	$this->_initAction();
		$this->_initLayoutMessages('customer/session');
		$orderSession = $this->getSession('order');		
		$this->resetSession('orderstep');
		
		
    	if($this->checkIfLoggedIn('','',true))
    	{

    		$orderSession = $this->getSession('Order');
			if($this->Config['loginType']=='order' && $this->Config['useaccountifpossible']==true && !Mage::getSingleton('customer/session')->isLoggedIn() && !empty($orderSession) &&isset($orderSession['orderId']) )
    		{
    			$this->getCustomerData();$this->redirectTo('selectproduct'); 
			}
			$this->resetSession('order');$this->resetSession('customer'); 
			$customer = $this->getCustomerData(); //Voor de zekerheid wat sessies resetten en opnieuw aanmaken mochten ze bestaan
			$this->getAllOrderIdsByCustomerId($customer['entity_id']); //Vraag alle geldige orders van de gebruiker op.
    		
    		if ($this->getRequest()->isPost())
    		{
    		 	if(isset($_POST['orderid']) && !empty($_POST['orderid'])) //Controleren of er een order ID is meegegeven
				{
					$this->addToSession('orderstep',$_POST['orderid']);
    		 		if($this->checkValidOrder($_POST['orderid']))
    		 		{
    		 			$this->redirectTo('selectproduct'); 
    		 		} else $this->abort('021'); //Controleren of het een geldige order is, zoja doorsturen, zonee abort.
    		 	} else {
    		 		$this->abort('011');				//Er ontbreekt een order ID, dus abort
    		 	}
    		}
				
    	} else {
    		$this->abort('001');
    	}
		
    	$this->renderLayout();
		
    }

    public function selectproductAction()
    {
    	$this->_initAction();
		$this->_initLayoutMessages('customer/session');
		$this->resetSession('productstep');	
				
    	if($this->checkIfLoggedIn())
    	{
			    		
    		$this->resetSession('tempAnswerPost');
    		$this->resetSession('allproducts');			//Sessie resetten voor de zekerheid

			$customer	= $this->getSession('Customer');
			$order 		= $this->getSession('order');
			
			$tempref = $this->getSession('tempRMARef');
			if(!empty($tempref))
			{
				Mage::getSingleton('customer/session')->addError($this->translate("error-rma-allready-created"));
				$this->redirectTo('index');
			}

			//Haal de huidige geselecteerde order op
    		 if ($this->getRequest()->isPost())			//Controlerne of er een post is.
    		 {
    		 	if(isset($_POST['product']) && !empty($_POST['product']) && is_array($_POST['product'])) 
				{
					$error			= false;
					$_order 		= Mage::getModel('sales/order')->loadByIncrementID($order['orderId']);
					$allproducts 	= $_order->getAllItems();
					$allorders 		= $this->getSession('allorders');
					
					foreach($_POST['product'] as $product)
					{
						if(isset($_POST['aantal'][$product]) && !empty($_POST['aantal'][$product]) && $_POST['aantal'][$product]>=1)
						{
							$lineNo=0;
							foreach($allproducts as $index=>$productData)
							{ 
								$obj = Mage::getModel('catalog/product')->load($productData->getProductId());
								$allOptions = $productData->getData('product_options');
								$options = unserialize($allOptions);
								if(!isset($options['info_buyRequest']['product']) || $productData->getProductId()==$options['info_buyRequest']['product'])
								{
									$lineNo++;
									if($lineNo==$product)
									{
										$prodQnty=0;
										$count=0;
										
										$rmaItems = Mage::helper('rma')->getRmaItemsByOrderItem($productData->getItemId())->getData();
										if(is_array($rmaItems))
										{
											foreach($rmaItems as $ritem)$prodQnty=$count+$ritem['rma_items_qty_returning']+$ritem['rma_items_qty_returned'];		
										} 
										
										$qnty= round($productData->getQtyOrdered());
										$quantity= $qnty - $prodQnty;
										$_product=$productData;
									}
								}
							}
							if($quantity>=$_POST['aantal'][$product]){
								$products[$product]['aantal']=$_POST['aantal'][$product];
								$products[$product]['data']=$_product;
							} else {
								$error=true;
								Mage::getSingleton('customer/session')->addError($this->translate('MaxiumQuantity'));
								$this->redirectTo('selectproduct');				//stuur terug naar het product
							}
							
						} else {
							$error=true;
							Mage::getSingleton('customer/session')->addError($this->translate('MiniumQuantity'));
							$this->redirectTo('selectproduct');				//stuur terug naar het product
						}
					}
					if(!$error && isset($products) && is_array($products))
					{
						$return = $this->createProductDataset($products);    //Prepare de dataset en vorm er een XML van.
						if($return==true)					//Alles is goed gegaan, dus maak een API call.
						{
							if($this->APIRequest('Questions'))
							{
								$this->addToSession('productstep','1');
								$this->redirectTo('retourinformatie');	//Alles is goed dus stuur door naar de volgende stap
							} else {
								$this->redirectTo('selectproduct');				//stuur terug naar het product
							}
						} else {	//Er was een error bij het maken van de xml, toon deze
							Mage::getSingleton('customer/session')->addError($this->translate('missingField').$return);
							$this->redirectTo('selectproduct');				//stuur terug naar het product
						}
					}
				} else {
					Mage::getSingleton('customer/session')->addError($this->translate('SelectAProduct'));
					$this->redirectTo('selectproduct');				//stuur terug naar het product
				}
    		} else {
    			$this->resetSession('xml');
    		}
    	} else {
    		$this->abort('002');
    	}
		
	 	$this->renderLayout();
		
    }

    public function retourinformatieAction()
    {
    	$this->_initAction();
		$this->_initLayoutMessages('customer/session');
		$this->resetSession('infostep');	
		
    	if($this->checkIfLoggedIn())
    	{
    		$tempref = $this->getSession('tempRMARef');
			if(!empty($tempref))
			{
				Mage::getSingleton('customer/session')->addError($this->translate("error-rma-allready-created"));
				$this->redirectTo('index');
			}
			
    		$fieldcheck = $this->getSession('productstep');
			if(!isset($fieldcheck) || empty($fieldcheck))
			{
				Mage::getSingleton('customer/session')->addError($this->translate('SelectAProduct'));
				$this->redirectTo('selectproduct');
			}
				
    		 if ($this->getRequest()->isPost())
    		 {
    		 	$sessionXML 	= $this->getSession('xml');
				$errors=false;
				foreach($sessionXML['questions']['question'] as $question)
				{
					if(isset($_POST[$question['questioncode']]))$t=str_replace(" ","",$_POST[$question['questioncode']]); else $t='';
					if(isset($_POST[$question['questioncode']]) && !empty($t))
					{
						$return = $this->validateAnswer($_POST[$question['questioncode']],$question);
						if($return!=false)$errors=true;
					} else {
						if($question['answer']['optional']=='N')
						{
							$errors=true;
							Mage::getSingleton('customer/session')->addError($this->translate('MissingFormField').$question['questiondesc']);
						}
					}
				}
				$this->resetSession('tempAnswerPost');	
				$this->addToSession('tempAnswerPost',$_POST);	
				if(!$errors)
				{
					$this->createAnswerDataset($_POST);
					$this->addToSession('infostep','1');
					$this->redirectTo('klantgegevens');	
				} else $this->redirectTo('retourinformatie');
    		 }
    	} else {
    		$this->abort('003');
    	}
		
	 	$this->renderLayout();
		
    }

    public function klantgegevensAction()
    {
    	$this->_initAction();
		$this->_initLayoutMessages('customer/session');
		$this->resetSession('servicestep');	
		
		
    	if($this->checkIfLoggedIn())
    	{
    		$tempref = $this->getSession('tempRMARef');
			if(!empty($tempref))
			{
				Mage::getSingleton('customer/session')->addError($this->translate("error-rma-allready-created"));
				$this->redirectTo('index');
			}
			
    		$fieldcheck = $this->getSession('infostep');
			if(!isset($fieldcheck) || empty($fieldcheck))
			{
				Mage::getSingleton('customer/session')->addError($this->translate('MissingFormField').$this->translate('Naam'));
				$this->redirectTo('selectproduct');
			}
    		$count=$this->getSession('allowedCountries');
			$cust=$this->getSession('customer');

    		if(empty($count))$count=$this->countryRequest();
    		 if ($this->getRequest()->isPost())
    		 {
    		 	$sessionXML 	= $this->getSession('xml');
				$errors=false;
				if(!isset($_POST['naam']) || empty($_POST['naam']))
				{
					$errors=true;
					Mage::getSingleton('customer/session')->addError($this->translate('MissingFormField').$this->translate('Naam'));
				}
				if(!isset($_POST['adres']) || empty($_POST['adres']))
				{
					$errors=true;
					Mage::getSingleton('customer/session')->addError($this->translate('MissingFormField').$this->translate('Adres'));
				}
				if(!isset($_POST['huisnummer']) || empty($_POST['huisnummer']))
				{
					$errors=true;
					
					Mage::getSingleton('customer/session')->addError($this->translate('MissingFormField').$this->translate('Huisnummer'));
				}
				if(!is_numeric(substr($_POST['huisnummer'],0,1)))
				{
					$errors=true;
					Mage::getSingleton('customer/session')->addError($this->translate('MissingFormField').$this->translate('Huisnummer'));
				}
				if(!isset($_POST['stad']) || empty($_POST['stad']))
				{
					$errors=true;
					Mage::getSingleton('customer/session')->addError($this->translate('MissingFormField').$this->translate('Stad'));
				} 
				
				if(!isset($_POST['postcode']) || empty($_POST['postcode']))
				{
					$errors=true;
					Mage::getSingleton('customer/session')->addError($this->translate('MissingFormField').$this->translate('Postcode'));
				} else $_POST['postcode']=trim(strtoupper(str_replace(" ","",$_POST['postcode'])));
				if(!isset($_POST['email']) || empty($_POST['email']))
				{
					$errors=true;
					Mage::getSingleton('customer/session')->addError($this->translate('MissingFormField').$this->translate('Email'));
				}
				if(!isset($_POST['land']) || empty($_POST['land']))
				{
					$errors=true;
					Mage::getSingleton('customer/session')->addError($this->translate('MissingFormField').$this->translate('Land'));
				}
				if(!isset($_POST['telefoon']) || empty($_POST['telefoon']))
				{
					$errors=true;
					Mage::getSingleton('customer/session')->addError($this->translate('MissingFormField').$this->translate('Telefoon'));
				}
				$this->resetSession('tempKlantPost');	
				$this->addToSession('tempKlantPost',$_POST);	
				if(!$errors)
				{
					$this->createKlantDataset($_POST);
					if($this->APIRequest('Reutilization'))
					{
						$xml = $this->getSession('xml');
						if(isset($xml['reutilization']['reutilcosts']))
						{
							$this->redirectTo('servicekosten');		//Alles is goed dus stuur door naar de volgende stap	
						} else {
							$this->redirectTo('supplychain');		//Alles is goed dus stuur door naar de volgende stap	
						}
					} else {
						$this->redirectTo('klantgegevens');
					}
				} else $this->redirectTo('klantgegevens');
    		 }
    	} else {
    		$this->abort('004');
    	}
	 	$this->renderLayout();
		
    }
	
	public function servicekostenAction()
    {
    	$this->_initAction();
		$this->_initLayoutMessages('customer/session');
    	if($this->checkIfLoggedIn())
    	{
    		$tempref = $this->getSession('tempRMARef');
			if(!empty($tempref))
			{
				Mage::getSingleton('customer/session')->addError($this->translate("error-rma-allready-created"));
				$this->redirectTo('index');
			}
    		 if ($this->getRequest()->isPost())
    		 {
    		 	if($this->APIRequest('Reutilization')) 
				{
					$this->redirectTo('supplychain');		//Alles is goed dus stuur door naar de volgende stap	
				} else {
					$this->redirectTo('servicekosten');		//stuur terug naar de klantgegevens stap					
				}
    		 }
    	} else {
    		$this->abort('005');
    	}
		
	 	$this->renderLayout();
    }

    public function supplychainAction()
    {
    	$this->_initAction();
		$this->_initLayoutMessages('customer/session');
    	if($this->checkIfLoggedIn())
    	{
    		$tempref = $this->getSession('tempRMARef');
			if(!empty($tempref))
			{
				Mage::getSingleton('customer/session')->addError($this->translate("error-rma-allready-created"));
				$this->redirectTo('index');
			}
    		 $this->resetSession('vervoerder');
    		 if ($this->getRequest()->isPost())
    		 {
    		 	
				foreach($_POST as $post=>$t)
				{
					$parts=explode('-',$post);
					if($parts[0]=='id' && isset($parts[1]) && !empty($parts[1]) && is_numeric($parts[1]))$id=$parts[1];
				}
				if(!isset($id))
				{
					Mage::getSingleton('customer/session')->addError($this->translate('ReturnIdMissing'));
					$this->redirectTo('supplychain');
				} else {
					$this->addToSession('vervoerder',$id);
					$this->createSuplychainDataset($id);
					$this->redirectTo('checkout');
				}
    		 }else{
    		 	
    		 	if($this->APIRequest('ReturnOptions'))
				{
					
					$this->ProcessSupplyChainImages();
				} else {
					Mage::getSingleton('customer/session')->addError($this->translate('ErrorCallingReturnOptions'));
				}
    		 }
    	} else {
    		$this->abort('006');
    	}
		
	 	$this->renderLayout();
		
    }

    public function checkoutAction()
    {
    	$this->_initAction();
		$this->_initLayoutMessages('customer/session');
		if($this->checkIfLoggedIn())
    	{
    		$vervoerder	= $this->getSession('vervoerder');
			if(!isset($vervoerder) || empty($vervoerder))
			{
				Mage::getSingleton('customer/session')->addError($this->translate('noActiveSupply'));
				$this->redirectTo('supplychain');
			} else {
				$tempref = $this->getSession('tempRMARef');
				if(!empty($tempref))
				{
					Mage::getSingleton('customer/session')->addError($this->translate("error-rma-allready-created"));
					$this->redirectTo('index');
				} else {
					if ($this->getRequest()->isPost())
					{
					 	if(!isset($_POST['voorwaarden']) || $_POST['voorwaarden']!='true')
						{
							Mage::getSingleton('customer/session')->addError($this->translate('acceptVoorwaarden'));
							$this->redirectTo('checkout');
						} else {
							
							if($response = $this->APIRequest('Submit'))
							{
								$orderSession = $this->getSession('order');
								$orderXML = $this->getSession('xml');
								if(isset($response["rmareference"]) && isset($response["status"]))
								{
									$this->addToSession('tempRMARef',$response["rmareference"]);
									if($response["status"]["statuscode"] == "O")
									{
										$this->saveLocalRMAData($orderSession,$orderXML);
									} 
								}
								$this->redirectTo('payment');
							} else {
								$this->redirectTo('checkout');
							}
						}
					}
				}
			}
		}else {
    		$this->abort('007');
    	}
	
	 	$this->renderLayout();
		
    }

	
	
	public function paymentAction()
    {
    	$this->_initAction();
    	$this->_initLayoutMessages('customer/session');
    	if($this->checkIfLoggedIn())
    	{
    		 if ($this->getRequest()->isPost())
    		 {
    		 	//Do something with the information;
    		 	//$this->redirectTo('retourinformatie');
    		 	//echo "Het retour proces is afgerond, hier komt later de stap dat de klant wordt doorgestuurd naar de betaalmodule van 12Return";
    		 	//$this->redirectTo('payment');
    		 }
    	} else {
    		$this->abort('009');
    	}
	 	$this->renderLayout();
		
    }
	
	
	private function setCurrentOrder($orderId)
	{
		$tempOrder = $this->getSession('tempOrder');$this->resetSession('tempOrder');

		if(!isset($tempOrder['orders']['order'][0]))
		{
			if($tempOrder['orders']['order']['orderno']==$orderId)$lorder= $tempOrder['orders']['order'];
		}
		elseif(isset($tempOrder['orders']['order'][0]) && count($tempOrder['orders']['order'])>=1)
		{
			foreach($tempOrder['orders']['order'] as $order)
			{
				if($order['orderno']==$orderId)$lorder= $order;
			}
		}

		if(!empty($lorder))
		{
			unset($lorder['customer']);
			$orderSession['data'] = $lorder;
			$orderSession['orderId'] = $lorder['orderno'] ;
			$orderSession['dateCreated'] = $lorder['deliverydate'];
			$orderSession['orderStatus'] = 'completed';
			$this->addToSession('Order',$orderSession);
			return true;
		}

		return false;
	}
	
	private function validateAnswer($post,$opts)
	{
		$error=false;
		if($opts['answer']['datatype']=='DATE')
		{
				
		  $re1='((?:(?:[0-2]?\\d{1})|(?:[3][01]{1}))[-:\\/.](?:[0]?[1-9]|[1][012])[-:\\/.](?:(?:[1]{1}\\d{1}\\d{1}\\d{1})|(?:[2]{1}\\d{3})))(?![\\d])';

		  if ($c=preg_match_all ("/".$re1."/is", $post, $matches))
		  {
		      if(empty($matches[1][0]))
		      {
		      	$error=true;
				Mage::getSingleton('customer/session')->addError($this->translate('WrongSyntax').$opts['questiondesc']);
		      }
		      
		  } else {
		  	$error=true;
			Mage::getSingleton('customer/session')->addError($this->translate('WrongSyntax').$opts['questiondesc']);
		  }
		}elseif($opts['answer']['datatype']=='NUMBER')
		{
			if(isset($opts['answer']['dataprecision'])&&!empty($opts['answer']['dataprecision']))
			{
				$post = round($post,$opts['answer']['dataprecision']);
			}
		}
		
		
		
		if(isset($opts['answer']['datalength'])&&!empty($opts['answer']['datalength'])&&$opts['answer']['datalength']<strlen($post))
		{
			$error=true;
			Mage::getSingleton('customer/session')->addError($this->translate('FieldToLong').$opts['questiondesc']);
		}
		
		return $error;
	}

    private function checkIfLoggedIn($orderId='',$postcode='',$checkmagentouser=false)
    {
		if($this->Config['loginType']=='user') // Controleren welke type inlog is ingesteld
    	{
	    	Mage::getSingleton('customer/session')->setBeforeAuthUrl('/12return/form/selectorder/');  //save requested URL for later redirection
			
			if(!Mage::getSingleton('customer/session')->isLoggedIn()) {  // if not logged in
			    return $this->loggedIn = false; 
			} else {
				
				$orderSession = $this->getSession('order');								//Gebruiker is ingelogd
				
				if(isset($orderSession['orderId']) && !empty($orderSession['orderId'])) //Haal data op wanner orderId bekent is in de sessie
				{
			
						if($this->checkOrderDate($orderSession['orderId']))
						{
							$this->getShippingAddressData($orderSession['orderId']);
							$this->getOrderData($orderSession['orderId']); 
							return $this->loggedIn = true;
						} else {
							return $this->loggedIn = false;
						}
		
				}
				return $this->loggedIn = true;
			}
		} else {
			if($checkmagentouser && Mage::getSingleton('customer/session')->isLoggedIn())return true;
			$orderSession = $this->getSession('Order');
			if(!empty($orderSession) && isset($orderSession['orderId']))
			{
				if($this->checkValidOrder($orderSession['orderId']))return true; else return false;
			} else {
				
				return false;
			}
		}
    }

	private function getShippingAddressData($orderId='')  //Haal het adres uit de order op basis van order id en plaats deze in een var
	{

		$thisCustomer = $this->getSession('Customer');
		if(!empty($orderId))
		{
			$order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
			$address = $order->getShippingAddress();
			
			if(!empty($address) && $address!=false)
			{
				$thisAddress=$address->getData();

				$orderSession['shippingAddress']=$thisAddress;
				$this->addToSession('Customer',$orderSession);
				$this->checkCountries();
				return $thisAddress;
			} else {
				return false;
			}
		} elseif(isset($thisCustomer['default_shipping']) && !empty($thisCustomer['default_shipping']))
		{
			$sessionData = $this->getSession('order');
			$orderId=$sessionData['orderId'];
			$order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
			$address = $order->getShippingAddress();
			if(!Mage::getSingleton('customer/session')->isLoggedIn()) 
			{
				$thisAddress=$order->getAddressById($address->getId())->getData();
			} else {
				$thisAddress = Mage::getSingleton('customer/session')->getCustomer()->getPrimaryShippingAddress()->getData();
			}

			$orderSession['shippingAddress']=$thisAddress;
			$this->addToSession('Customer',$orderSession);
			return $thisAddress;
		} else {

			return false;
		}
	}
	
	private function getOrderData($orderId,$addsession=true) //Haal alle relevante data van het order object op en plaats deze in een var
	{
		$this->thisOrder=$orderData= Mage::getModel('sales/order')->loadByIncrementId($orderId)->getData();
		$orderSession['data'] = $this->thisOrder;
		$orderSession['orderId'] = $orderId;
		if($addsession)$this->addToSession('Order',$orderSession);
		return $this->thisOrder;
	}
	
	private function checkIfOrderIsCompleted($orderId) // Controleren of het betaal proces van de order volledig is afgerond
	{
		$orderData= Mage::getModel('sales/order')->loadByIncrementId($orderId);
		//if($orderData->getStatusLabel()==Mage_Sales_Model_Order::STATE_COMPLETE)return true; else return false;
		$orderSession['orderStatus']='completed';
		$this->addToSession('Order',$orderSession);
		return true; //////////////////////////////////////////////FIX MAKEN
	}
	
	private function getCustomerData($orderId='') //Haal alle klant data van het order object op en plaats deze in een var
	{
		$thisCustomer=$this->getSession('Customer');
		$thisCustomer='';
		
		if(empty($thisCustomer))
		{
			if($this->Config['loginType']=='order' && !Mage::getSingleton('customer/session')->isLoggedIn())
			{
				if(empty($orderId))
				{
					$sessionData = $this->getSession('order');
					$orderId=$sessionData['orderId'];
				}
				
				if(empty($orderId))$this->abort('101');
				$order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
				$userid= $order->getCustomerId();
				
				if(empty($userid))
				{
					$thisCustomer['entity_id']='guest-'.$order['shipping_address_id'] ;
					$thisCustomer['entity_type_id']=1 ;
					$thisCustomer['attribute_set_id']=0 ;
					$thisCustomer['website_id']='' ;
					$thisCustomer['email']= $order['customer_email'] ;
					$thisCustomer['group_id']=1 ;
					$thisCustomer['increment_id']='' ;
					$thisCustomer['store_id']='' ;
					$thisCustomer['created_at']='' ;
					$thisCustomer['updated_at']='' ;
					$thisCustomer['is_active']=1 ;
					$thisCustomer['is_guest']=1 ;
					$thisCustomer['firstname']= $order['customer_firstname'] ;
					$thisCustomer['lastname']= $order['customer_lastname'] ;

				} else {
					$thisCustomer=Mage::getModel('customer/customer')->load($userid)->getData();
				}
				
				$this->addToSession('Customer',$thisCustomer);
				$this->getShippingAddressData();
				
				return $thisCustomer;
			} else {
				
				$session = Mage::getSingleton('customer/session');
				if(!$session->isLoggedIn())$this->abort('102');
				$thisCustomer=Mage::getModel('customer/customer')->load($session->getId())->getData();
				$this->addToSession('Customer',$thisCustomer);
				$this->getShippingAddressData();
				return $thisCustomer;
			}
		} else {
			return false;
		}
	}
	
	private function countryRequest(){
    	$counties = $this->ContextRequest('countries');
		if(!isset($counties['contextinfo']['allowedcountries']))
		{
			Mage::getSingleton('customer/session')->addError($this->translate('errorGettingCountries'));
			return false;
		} else {
			$this->resetSession('allowedCountries');	
			$this->addToSession('allowedCountries',$counties['contextinfo']);	
			return $counties['contextinfo'];
		}
    }
	
	

	private function checkCountries()
	{
		$count=$this->getSession('allowedCountries');
		$erroShowed=$this->getSession('countryError');
    	if(empty($count))$count=$this->countryRequest();

    	//if(!isset($count['allowedcountries']['country']))$this->abort('202');  need to check this
    	
		if(isset($count['allowedcountries']['country']) && !isset($count['allowedcountries']['country'][0]))
		{
			$temp = $count['allowedcountries']['country'];
			$count['allowedcountries']['country']='';
			$count['allowedcountries']['country'][0]=$temp;
		}
		
		$countArray= $count['allowedcountries']['country'];
    	$customer=$this->getSession('Customer');
		if(empty($customer) || !isset($customer['shippingAddress']) || !isset($customer['shippingAddress']['country_id']))return false;
    	$land=$customer['shippingAddress']['country_id'];
    	if(is_array($countArray))
    	{
    		foreach($countArray as $country)if($land==$country['code'] || $country==$land)return true;
    	}
    	if(empty($erroShowed))
    	{
    		$this->addToSession('countryError','1');	
    		Mage::getSingleton('customer/session')->addError($this->translate('notValidCountry'));
		}
    	return false;
	}
	
	private function checkOrderSelective($orderId)
	{
		if($this->Config['selectiverma']== 'perorder')
		{
			$order = $this->getOrderData($orderId);
			return $this->checkValidOrder($order['increment_id'],'',true,$this->Config['selectiverma']);
			
		} else {
			return true;
		}
	}
	
	private function getAllOrderIdsByCustomerId($id)
	{
		$allOrder = Mage::getModel('sales/order')
                                        ->getCollection()
										->addAttributeToFilter('status', array('in' => explode(",",$this->Config['allowstatus'])))
                                        ->addAttributeToFilter('customer_id', $id)
                                        ->getData();
										
		if(!empty($allOrder) && is_array($allOrder))
		{
			
			$currentRmas = Mage::helper('rma')->getRmasByCustomer($id)->getData();
			if(!empty($currentRmas) && is_array($currentRmas))
			{
				foreach($currentRmas as $rma)$ids[$rma['rma_order_increment_id']][]=$rma;
			}
			
			foreach($allOrder as $order)
			{
				if($this->checkValidOrder($order['increment_id'],'',true,$this->Config['selectiverma']))
				{
					if(!isset($ids[$order['increment_id']]))$ids[$order['increment_id']] = 'nostatus';
				} else {
					unset($ids[$order['increment_id']]);
				}
				
			}

			if(isset($ids) && !empty($ids)){
				$this->resetSession('allorders');
				$this->addToSession('allorders',$ids); 
				return $ids;
			} else {
				return false;
			}
		} else {
			
			return false;
		}
	
	}
	
	
	private function checkOrderDate($orderId,$addsession=true) //Controleren of order nog  geldig is voor retourneren
	{
		$order= Mage::getModel('sales/order')->loadByIncrementId($orderId);
		$orderData= $order->getData();
		$orderTime = strtotime($orderData['created_at']);
		$thisDate = date('d-m-Y',$orderTime);
		$orderSession['dateCreated']=$thisDate;
		$orderValidTillTime=0;

		if($addsession){$this->addToSession('Order',$orderSession);}
		$now = time();
		if(!isset($this->Config['orderDays'])||empty($this->Config['orderDays']))$this->Config['orderDays']='14';
		$date = $this->Config['orderDays']*24*60*60;


		if($this->Config['daysfrom']=='ordercreate')
		{
			$orderValidTillTime = $orderTime+$date;
		} else {
			$shipments = $order->getShipmentsCollection()->getData();
			if(is_array($shipments) &&!empty($shipments) && isset($shipments[0]))
			{
				if($this->Config['daysfrom']=='lastshipment')$shipment = end($shipments);
				elseif($this->Config['daysfrom']=='firstshipment')$shipment = current($shipments);
				$orderValidTillTime = strtotime($shipment['created_at'])+$date;
			} else {
				return false;
			}
		}
		if($now<=$orderValidTillTime) return true; else return false;
	}
	
	private function checkValidOrder($orderId,$postcode='',$silent=false,$selective=false)
	{
		$orderId = trim($orderId);
		$postcode = strtoupper(str_replace(" ","",trim($postcode)));
		$order = $this->getOrderData($orderId);
		
		if(!empty($order))
		{
			
			if($this->checkOrderDate($orderId))
			{
				
				$thisAddress=$this->getShippingAddressData($orderId);
				$thisAddress['postcode'] = strtoupper(str_replace(" ","",trim($thisAddress['postcode'])));
				
				if(empty($postcode) || $thisAddress['postcode']==$postcode)	//Controlerne of men via order inlogd, dan postcode valideren.
				{
					if($selective=='perorder')
					{
						if($this->Config['currentType']=='view')
						{
							if(isset($order['selective_rma']) &&$order['selective_rma']=='true')
							{
								return true;
							} else {
								if(!$silent)Mage::getSingleton('customer/session')->addError($this->translate('orderNotValidForRma'));
								return false;
							}
							return false; 
						} else {

							if(isset($order['selective_rma_warranty']) &&$order['selective_rma_warranty']=='true')
							{
								return true;
							} else {
								if(!$silent)Mage::getSingleton('customer/session')->addError($this->translate('orderNotValidForRma'));
								return false;
							}
							return false; 
						}
					} else return true;
				} else {						//Postcode komt niet overeen op order, dus toon error.
					if(!$silent)Mage::getSingleton('customer/session')->addError($this->translate('postcodeDoesNotMatch'));
					return false;
				}
			}else {
				if(!$silent)Mage::getSingleton('customer/session')->addError($this->translate('orderCanNotBeReturned')); //Order valt buiten aantal mogelijke dagen om te retourneren, dus toon error.

				return false;
			}

		} else {
			if(!$silent)Mage::getSingleton('customer/session')->addError($this->translate('orderDoesNotExists')); //Order bestaat niet, dus toon error.

			return false;
		}
	}


	private function createProductDataset($products) /////////////////////////////// ORDER LINE NUMBER ophalen
	{
		$_order		= $this->getSession('order');
		$config = $this->getSession('Config');
		foreach($products as $lineNo=>$data)
		{
			$_product	= $data['data'];
			$_aantal	= $data['aantal'];

			$_data		= $_product->getData();
			$options 	= unserialize($_data['product_options']);
			
			$custom = Mage::getModel('catalog/product')->load($_data['product_id']);
			$desc = $custom->getName();

			$error		= '';
			if(isset($_data['type']))$productsSet['product'][$lineNo]['type']=$this->validateValue($_data['type'],'AN','20');
			
			
			if(isset($_data['model']))$productsSet['product'][$lineNo]['model']=$this->validateValue($_data['model'],'AN','26');
			elseif(isset($_data['sku']))$productsSet['product'][$lineNo]['model']=$this->validateValue($_data['sku'],'AN','26');
			else $error .= 'model ';
			
			if(isset($desc) && !empty($desc))$productsSet['product'][$lineNo]['productdesc']=$this->validateValue($desc,'AN','240');
			else $productsSet['product'][$lineNo]['productdesc']='Omschrijving';
			
			$productsSet['product'][$lineNo]['qty']=$_aantal;
			
			if(isset($_data['lenght']))$productsSet['product'][$lineNo]['lenght']=$this->validateValue($_data['lenght'],'N','4');
			
			if(isset($_data['width']))$productsSet['product'][$lineNo]['width']=$this->validateValue($_data['width'],'N','4');
			
			if(isset($_data['height']))$productsSet['product'][$lineNo]['height']=$this->validateValue($_data['height'],'N','4');
			
			if(isset($_data['weight']))$productsSet['product'][$lineNo]['weight']=$this->validateValue($_data['weight'],'N','9','3');
	
			if($config['pricevat']=='ex')
			{
				$productsSet['product'][$lineNo]['value']=$this->validateValue(number_format($_product->getPrice(), 2),'N','7','2');
			} else {
			 	$productsSet['product'][$lineNo]['value']=$this->validateValue(number_format($_product->getPriceInclTax(), 2),'N','7','2');
			}

			
			if(isset($_order['data']['base_currency_code']))$productsSet['product'][$lineNo]['ccy']=$this->validateValue($_order['data']['base_currency_code'],'AN','5');
			elseif(isset($_data['ccy']))$productsSet['product'][$lineNo]['ccy']=$_data['ccy'];
			else $_data['ccy']='EUR';
			
			if(isset($_data['color']))$productsSet['product'][$d]['color']=$this->validateValue($_data['color'],'AN','64');
			elseif(isset($_data['colour']))$productsSet['product'][$d]['color']=$this->validateValue($_data['colour'],'AN','64'); 
			if(isset($_data['size']))
			if(isset($_data['flavour']))$productsSet['product'][$lineNo]['flavour']=$this->validateValue($_data['flavour'],'AN','64');
			
			if(isset($options['attributes_info']) && is_array($options['attributes_info']))
			{
				foreach($options['attributes_info'] as $attr)
				{
					if(strtolower($attr['label'])=='size')$productsSet['product'][$lineNo]['size']=$this->validateValue($attr['value'],'AN','64');
					elseif(strtolower($attr['label'])=='color' || strtolower($attr['label'])=='colour')$productsSet['product'][$lineNo]['colour']=$this->validateValue($attr['value'],'AN','64');
					elseif(strtolower($attr['label'])=='flavour')$productsSet['product'][$lineNo]['flavour']=$this->validateValue($attr['value'],'AN','64');
					elseif(strtolower($attr['label'])=='manufacturer')$productsSet['product'][$lineNo]['brand']=$this->validateValue($attr['value'],'AN','64');
				
				}
			}
			if(!isset($productsSet['product'][$lineNo]['brand']) || empty($productsSet['product'][$lineNo]['brand']))$productsSet['product'][$lineNo]['brand']='UNKNOWN';
			$productsSet['product'][$lineNo]['orderno']=$_order['orderId'];
			$productsSet['product'][$lineNo]['lineno']=$lineNo;

		}
		if(empty($error))
		{
			$this->addToXMLSession($productsSet,'products');
			return true;
		} else {
			return $error;
		}
	}

	private function createAnswerDataset($answers)
	{
		$xml	= $this->getSession('xml');
		foreach($xml['questions']['question'] as $key=>$data)
		{
			if(isset($answers[$data['questioncode']]))
			{
				$xml['questions']['question'][$key]['questionvalue']=$answers[$data['questioncode']];
			}
		}
		$this->resetSession('xml');
		$this->addToSession('xml',$xml);
		return true;
	}
	
	private function ProcessSupplyChainImages()
	{
		$xml	= $this->getSession('xml');
		foreach($xml['returnoptions']['returnoption'] as $return)
		{
			$parts = explode("=",$return['logourl']);
			$file = end($parts);
			$magento_vardir =  Mage::getBaseDir('media') . DS ;
			$imageFile = $magento_vardir.'rma/'.$file;
			if(!file_exists($imageFile))
			{
				
				$this->downloadFile($return['logourl'],$imageFile);
			}
		}
		return true;
	}
	
	
	
	
	private function createKlantDataset($answers)
	{
		$xml	= $this->getSession('xml');
		$thisCustomer = $this->getSession('Customer');
		$xml['customer']['identifier'] 		= $this->validateValue($thisCustomer['entity_id'],'AN', '255');
		$xml['customer']['company'] 		= $this->validateValue($answers['bedrijf'],'AN', '40');
		//$xml['customer']['title'] 			= 'mr';
		$xml['customer']['name'] 			= $this->validateValue($answers['naam'],'AN', '40');
		$xml['customer']['houseno'] 		= $this->validateValue($answers['huisnummer'],'AN', '25');
		$xml['customer']['postal'] 			= $this->validateValue($answers['postcode'],'AN', '12');
		$xml['customer']['country'] 		= $this->validateValue($answers['land'],'AN', '2');
		$xml['customer']['phone'] 			= $this->validateValue($answers['telefoon'],'AN', '15');
		$xml['customer']['fax'] 			= $this->validateValue($answers['fax'],'AN', '15');
		$xml['customer']['mobile'] 			= $this->validateValue($answers['mobiel'],'AN', '15');
		$xml['customer']['email'] 			= $this->validateValue($answers['email'],'AN', '80');
		$xml['customer']['city'] 			= $this->validateValue($answers['stad'],'AN', '35');
		$xml['customer']['street'] 			= $this->validateValue($answers['adres'],'AN', '50');
		$this->resetSession('xml');
		$this->addToSession('xml',$xml);
		return true;
	}

	private function createSuplychainDataset($id)
	{
		$sessionXML	= $this->getSession('xml');
		$temp = $sessionXML['returnoptions']['returnoption'];
		$sessionXML['returnoptions']['returnoption']='';
		foreach($temp as $returnoption)
		{
			if($returnoption['returnoptionid']==$id)$returnoption['selected']='Y';
			$sessionXML['returnoptions']['returnoption'][]=$returnoption;
		}
		$this->resetSession('xml');
		$this->addToSession('xml',$sessionXML);
		return true;
	}

	private function validateValue($value,$type='AN',$maxLenght='10',$decimal='10')	////////////////////////////////////Moet ik nog maken, controleerd of value geld is en niet te lang, anders trancute
	{
		if($type=='N')$value=round($value,$decimal);
		if(strlen($value)>$maxLenght)
		{
			return $this->truncateString($value,$maxLenght);
		}else {
			return $value;
		}
	}
	private function truncateString($text,$num) { 
		$text = preg_replace('/\s+?(\S+)?$/', '', substr($text, 0, $num));
        return $text; 

    } 

	private function addToXMLSession($xml,$parent)		//Functie om data in een sessie op te slaan, ter voorbereiding van XML
	{
		$sessionXML 	= $this->getSession('xml');
		$sessionConfig	= $this->getSession('config');
		
		/////Bouw de standaard elementen van de XML op
		if(!isset($sessionXML))												$sessionXML										=array();
		if(!isset($sessionXML['context']))									$sessionXML['context']							=$sessionConfig['context'];
		if(!isset($sessionXML['key']))										$sessionXML['key']								=$sessionConfig['key'];
		if(!isset($sessionXML['appversion']))								$sessionXML['appversion']						=$sessionConfig['version'];
		if(!isset($sessionXML['language']))									$sessionXML['language']							=$sessionConfig['language'];
		if(!isset($sessionXML['conversationhandle']))						$sessionXML['conversationhandle']				='';
		
		$orderSession = $this->getSession('order');	 
		if(!empty($orderSession['orderId']))$sessionXML['ownerreference']=$orderSession['orderId']; else unset($sessionXML['ownerreference']);
		if(isset($sessionXML[$parent]))unset($sessionXML[$parent]);
		$sessionXML[$parent]=$xml;
		$this->resetSession('xml');				//Reset sessie voor de zekerheid
		$this->addToSession('xml',$sessionXML);
		return $sessionXML;
	}
	
	private function buildRequestXML()
	{
		$data= $this->getSession('xml');
		unset($data['status']);
		$data['rmareference']='';
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
	
	private function cleanXmlValue($value,$node='')
	{
		
		$value = htmlentities ($value,ENT_COMPAT ,'UTF-8');
		$value=$this->xmlentities($value);
		//$value = urlencode ($value); 
		//$value= "<![CDATA[".$value."]]>";
		$value = trim($value);
		return $value;
	}
	
	
	private function cleanSessionValue(&$value,$key='')
	{
		$value = $this->xmlentities_decode ($value);
		$value = html_entity_decode  ($value,ENT_QUOTES|0 ,'UTF-8');
	}
	
	private function xmlentities($string) {
	    return str_replace(array("<", ">", "\"", "'", "&"),
	        array("&lt;", "&gt;", "&quot;", "&apos;", "&amp;"), $string);
	}

    private function xmlentities_decode($string) {
	    return str_replace(array("&lt;", "&gt;", "&quot;", "&apos;", "&amp;"),
	        array("<", ">", "\"", "'", "&"), $string);
	}
	
	
	private function cleanXML($value)
	{
		$value = trim($value);
		return $value;
	}

	private function createNodeChild($data,$xml)
	{
		foreach($data as $node=>$nodeData)
		{
			if(is_array($nodeData)&& !empty($nodeData))
			{
				$multiChild = true;
				foreach($nodeData as $key=>$value)if(!is_numeric($key))$multiChild=false;
				if($multiChild)
				{
					foreach($nodeData as $childData)
					{
						$child = $xml->addChild($node, '');
						$this->createNodeChild($childData,$child);
					}
				} else {
					$child = $xml->addChild($node, '');
					$this->createNodeChild($nodeData,$child);
					}
			} elseif(!empty($nodeData) && !is_array($nodeData)) $xml->addChild($node, $this->cleanXmlValue($nodeData,$node));
		}
		return true;
	}
	
	private function checkForResponseErrors($array)
	{
		if(isset($array['status']['statuscode']) && $array['status']['statuscode']=='E')
		{
			if(isset($array['status']['errors']['error'][0]))
			{
				foreach($array['status']['errors']['error'] as $error) 
				{
					Mage::getSingleton('customer/session')->addError($error['errorcode'].' - '.$error['errordesc']); // Toon error bericht
				}
			} else {
				foreach($array['status']['errors'] as $error) 
				{
					Mage::getSingleton('customer/session')->addError($error['errorcode'].' - '.$error['errordesc']); // Toon error bericht
				}
			}
			return false;
		} elseif(isset($array['result']['returncode']) && $array['result']['returncode']=='E')
		{
			if(isset($array['result']['errors']['error'][0]))
			{
				foreach($array['result']['errors']['error'] as $error) 
				{
					Mage::getSingleton('customer/session')->addError($error['errorcode'].' - '.$error['errordesc']); // Toon error bericht
				}
			} else {
				foreach($array['result']['errors'] as $error) 
				{
					Mage::getSingleton('customer/session')->addError($error['errorcode'].' - '.$error['errordesc']); // Toon error bericht
				}
			}
			return false;
		} else {
			return true;
		}
	}

	private function cleanIncXml($array)
	{
		 array_walk_recursive($array, array(&$this, "cleanSessionValue"));
		 return $array;
	}
	
	private function isXmlStructureValid($file) {
	    $prev = libxml_use_internal_errors(true);
	    $ret = true;
	    try {
	      $ret= new SimpleXMLElement($file);
	    } catch(Exception $e) {
	      $ret = false;
	    }
	    if(count(libxml_get_errors()) > 0) {
	      // There has been XML errors
	      $ret = false;
	    }
	    // Tidy up.
	    libxml_clear_errors();
	    libxml_use_internal_errors($prev);
	    return $ret;
  	}
	
	private function createSessionFromXML($XMLstring)
	{
		$XMLstring = $this->cleanXML($XMLstring);
		$XML = $this->isXmlStructureValid($XMLstring);
		if(!$XML)
		{
			Mage::getSingleton('customer/session')->addError($this->translate('CurlError').' - '.'Failed to parse XML string: '.$XMLstring);
			return false;
		}
		$array = Mage::helper('rma/api')->objectToArray($XML);
		$array=$this->cleanIncXml($array);
		if($this->checkForResponseErrors($array))
		{
			$this->resetSession('xml');				//Reset sessie voor de zekerheid
			if(isset($array['products']) && !isset($array['products']['product'][0]))
			{
				$temp = $array['products']['product'];
				$array['products']['product']='';
				$array['products']['product'][0]=$temp;
			}
			if(isset($array['returnoptions']) && !isset($array['returnoptions']['returnoption'][0]))
			{
				$temp = $array['returnoptions']['returnoption'];
				$array['returnoptions']['returnoption']='';
				$array['returnoptions']['returnoption'][0]=$temp;
			}
			if(isset($array['reutilization']['reutilcosts']) && !isset($array['reutilization']['reutilcosts']['reutilcost'][0]))
			{
				$temp = $array['reutilization']['reutilcosts']['reutilcost'];
				$array['reutilization']['reutilcosts']['reutilcost']='';
				$array['reutilization']['reutilcosts']['reutilcost'][0]=$temp;
			}

			
			$this->addToSession('xml',$array);
			return $array;
		} else {
			return false;
		}
	}
	
	

	private function APIRequest($action)
	{
		$xml = 'xml='.urlencode($this->buildRequestXML());  //Maak een XML structuur van de lokale sessie
	
		$data= $this->getSession('xml');
		
		$curlResponse = $this->doRequest($xml,$action);
		if($curlResponse!=false) //Maak de Curl request
		{
			$response = $this->processResponse($curlResponse);
			//die($response['response']);
			if(empty($response['response']) || trim($response['response']) == 'No more data to read from socket')
			{
				
				Mage::getSingleton('customer/session')->addError($this->translate('CurlError').' - '.'No more data to read from socket'); // Toon error bericht
				return false;
			} else {
				return $this->createSessionFromXML($response['response']); 
			}
		} else {
			
			return false;
		}
		
	}

	private function ContextRequest($action,$customer='',$orderno='',$orderline='')
	{
		$curlResponse = $this->doContextRequest($action,$customer,$orderno,$orderline);
		if($curlResponse!=false) //Maak de Curl request
		{
			$response = $this->processResponse($curlResponse);
			if(empty($response['response']) || trim($response['response']) == 'No more data to read from socket')
			{
				
				Mage::getSingleton('customer/session')->addError($this->translate('CurlError').' - '.'No more data to read from socket'); // Toon error bericht
				return false;
			} else {
				$XML = $this->cleanXML($response['response']);
				$XML = new SimpleXMLElement($XML);
				
				$array = Mage::helper('rma/api')->objectToArray($XML);
				if($this->checkForResponseErrors($array))return $array; else return false;
			}
			
		} else {
			
			return false;
		}
		
	}


	private function doContextRequest($action,$customer,$orderno,$orderline)
	{
		if($this->curl!=false)$this->closeCurl;
		$configXML 	= $this->getSession('Config');

		if($action=='countries'){
			$url= $this->Contexturl.$configXML['context'].'/parameters?key='.$configXML['key'].'&ts='.time();
			$xml = 'key='.$configXML['key'].'&appversion='.$configXML['version']; 
		}
		elseif($action=='status'){
			$url = $this->Contexturl.$configXML['context'].'/Customer/'.$customer.'/RMA';
			if(!empty($orderno))$url.='/'.$orderno;
			if(!empty($orderline))$url.='/'.$orderline;
			$url .='?ts='.time();
			$xml = 'key='.$configXML['key'].'&appversion='.$configXML['version'].'&selectiondays='.$configXML['orderDays']; 
		} elseif($action=='WhOrder'){
			$url = $this->Contexturl.$configXML['context'].'/Customer/'.$customer.'/WhOrder';
			if(!empty($orderno))$url.='/'.$orderno;
			$url .='?ts='.time();
			$xml = 'key='.$configXML['key'].'&appversion='.$configXML['version'].'&selectiondays='.$configXML['orderDays']; 
		} 
		$time = microtime();
		$time = explode(' ', $time);
		$time = $time[1] + $time[0];
		$start = $time;
		$this->setupCurl($action);
		$this->curl = curl_init($url);
		$this->defaultCurlOpts();

		curl_setopt($this->curl,CURLOPT_POSTFIELDS,$xml);  //plaats de XML in de post fields
        $exec = curl_exec($this->curl); //Voer de Curl actie uit

		$curl_errno = curl_errno($this->curl); //Controleren op errors
		if ($curl_errno > 0) 
		{
			$curl_error = curl_error($this->curl); // Er is een error, dus vraag error bericht op.
			Mage::getSingleton('customer/session')->addError($this->translate('CurlError').$curl_error); // Toon error bericht
			$this->closeCurl(); //Error is opgeslagen dus alles is klaar, sluit curl verbinding af
			return false;
		} else {
			$code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
			if($code!='200')
			{
				Mage::getSingleton('customer/session')->addError($this->translate('CurlError').' - '.$code); // Toon error bericht
				$this->closeCurl(); //Error is opgeslagen dus alles is klaar, sluit curl verbinding af
				return false;
			} else {
				$this->closeCurl(); //Er is geen error dus alles is klaar, sluit curl verbinding af
				$time = microtime();
				$time = explode(' ', $time);
				$time = $time[1] + $time[0];
				$finish = $time;
				$total_time = round(($finish - $start), 4);
				//echo('Page generated in '.$total_time.' seconds. action:'.$action."\n"); 
				
				return $exec;  //Return de response van de request call
			}
		}
	}
	
	private function setupCurl($action)
	{
		if($this->curl!=false)$this->closeCurl();
		$this->curl = curl_init($this->RESTurl.$action.'?ts='.time());
		
		$this->defaultCurlOpts();
		return true;
	}

	private function defaultCurlOpts()
	{
		curl_setopt($this->curl,CURLOPT_RETURNTRANSFER,1);	//Vang de response van de server af
        curl_setopt($this->curl,CURLOPT_AUTOREFERER,1); // This make sure will follow redirects
        curl_setopt($this->curl,CURLOPT_FOLLOWLOCATION,0); // This too
        curl_setopt($this->curl,CURLOPT_FAILONERROR,1);
        curl_setopt($this->curl,CURLOPT_HEADER,1);	// THis verbose option for extracting the headers
		curl_setopt($this->curl,CURLOPT_POST,1);		// Set POST method on
		curl_setopt($this->curl,CURLOPT_HTTPHEADER,array('Content-Type: application/x-www-form-urlencoded')); //Set Content type
		curl_setopt($this->curl,CURLOPT_FORBID_REUSE, 1);	  //to force the connection to explicitly close when it has finished processing, and not be pooled for reuse
		curl_setopt($this->curl,CURLOPT_FRESH_CONNECT, 1);	  //to force the use of a new connection instead of a cached one.
		curl_setopt($this->curl,CURLOPT_CONNECTTIMEOUT, 30);	  //The number of seconds to wait while trying to connect. Use 0 to wait indefinitely.
		curl_setopt($this->curl,CURLOPT_TIMEOUT, 10);
		curl_setopt($this->curl,CURLOPT_PORT, $this->RESTport);	
		curl_setopt($this->curl,CURLOPT_CUSTOMREQUEST, "POST");  
		curl_setopt($this->curl, CURLOPT_USERAGENT, "curl/7.23.1 (x86_64-unknown-linux-gnu) libcurl/7.23.1 OpenSSL/0.9.8b zlib/1.2.3");
		if($this->debug==true)curl_setopt($this->curl,CURLOPT_VERBOSE, true);  else curl_setopt($this->curl,CURLOPT_VERBOSE, false); 
		curl_setopt($this->curl,CURLOPT_SSL_VERIFYHOST, false);   //Zet op 2 in production
		curl_setopt($this->curl,CURLOPT_SSL_VERIFYPEER, false);	  //Zet op true in production

	}

	private function downloadFile($file,$savepath)
	{
		$this->loadConfig();
		$ch = curl_init ($file);
	    curl_setopt($ch, CURLOPT_HEADER, 0);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
		curl_setopt($ch,CURLOPT_FAILONERROR,1);
		curl_setopt($ch,CURLOPT_TIMEOUT, 10);
		curl_setopt($ch,CURLOPT_FOLLOWLOCATION,0);
		curl_setopt($ch,CURLOPT_AUTOREFERER,1); 
		curl_setopt($ch,CURLOPT_PORT, $this->RESTport);	
		
		if($this->debug==true)curl_setopt($ch,CURLOPT_VERBOSE, true);  else curl_setopt($ch,CURLOPT_VERBOSE, false); 
			curl_setopt($ch,CURLOPT_SSL_VERIFYHOST, false);   //Zet op 2 in production
			curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);	  //Zet op true in production
	    $rawdata=curl_exec($ch);
		
		$curl_errno = curl_errno($ch); //Controleren op errors
		
		if ($curl_errno > 0) 
		{
			$curl_error = curl_error($ch); // Er is een error, dus vraag error bericht op.
			Mage::getSingleton('customer/session')->addError($this->translate('CurlError').$curl_error); // Toon error bericht
			curl_close ($ch);//Error is opgeslagen dus alles is klaar, sluit curl verbinding af
			return false;
		} else {
			$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if($code!='200')
			{
				Mage::getSingleton('customer/session')->addError($this->translate('CurlError').' - '.$code); // Toon error bericht
				curl_close ($ch); //Error is opgeslagen dus alles is klaar, sluit curl verbinding af
				return false;
			} else {
				curl_close ($ch);
			    if(file_exists($savepath)){
			        unlink($savepath);
			    }
			    $fp = fopen($savepath,'x');
			    fwrite($fp, $rawdata);
			    fclose($fp);
				return true;  //Return de response van de request call
			}
		}
	    
	}
	
	private function closeCurl()
	{
		curl_close($this->curl);
        $this->curl = null ;
		$this->curl = false ;
        return true ;
	}
	
	private function doRequest($xml,$action)
	{
		$time = microtime();
		$time = explode(' ', $time);
		$time = $time[1] + $time[0];
		$start = $time;
		$this->setupCurl($action);
		curl_setopt($this->curl,CURLOPT_POSTFIELDS,$xml);  //plaats de XML in de post fields
		
        $exec = curl_exec($this->curl); //Voer de Curl actie uit
		$curl_errno = curl_errno($this->curl); //Controleren op errors

		if ($curl_errno > 0) 
		{
			
			$curl_error = curl_error($this->curl); // Er is een error, dus vraag error bericht op.
			
			Mage::getSingleton('customer/session')->addError($this->translate('CurlError').$curl_error); // Toon error bericht
			$this->closeCurl(); //Error is opgeslagen dus alles is klaar, sluit curl verbinding af
			return false;
		} else {
			$code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
			if($code!='200')
			{
				Mage::getSingleton('customer/session')->addError($this->translate('CurlError').' - '.$code); // Toon error bericht
				$this->closeCurl(); //Error is opgeslagen dus alles is klaar, sluit curl verbinding af
				return false;
			} else {
				$this->closeCurl(); //Er is geen error dus alles is klaar, sluit curl verbinding af
				$time = microtime();
				$time = explode(' ', $time);
				$time = $time[1] + $time[0];
				$finish = $time;
				$total_time = round(($finish - $start), 4);
				
				//die('Page generated in '.$total_time.' seconds. action: '.$action."\n"); 
				return $exec;  //Return de response van de request call
			}
		}
	}
	
	private function processResponse($response)
	{
		if($response == null or strlen($response) < 1) { //Controleren of response niet leeg is
            return '';
        }
		
        $parts  = explode("\n\r",$response); // Splits header van body in de response
        if(preg_match('@HTTP/1.[0-1] 100 Continue@',$parts[0])) { // Continue header must be bypass
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

    private function redirectTo($action)
    {
		$url = Mage::getUrl("rma/form/".$action); // might append ___SID parameter
		$url = Mage::getModel('core/url')->sessionUrlVar($url); // process ___SID

		die(header("location: ".$url));
    }
	
	private function translate($key) //Wordt gebruikt voor de vertalingen in de blokken zel.
	{
		//if(isset($this->translations[$key])) return $this->__($this->translations[$key]); else return 'MISSING-'.$key;
		return $this->__($key);
	}
	
	private function multiplyReturnTypes()
	{
		$i=0;
		$this->Config = $this->getSession('Config');
		foreach($this->returnTypes as $returnType)
		{
			$this->Config['returnType'][$returnType]['enabled']= Mage::getStoreConfig('rma/'.$returnType.'/enabled');
			$this->Config['returnType'][$returnType]['key']= Mage::getStoreConfig('rma/'.$returnType.'/key');
			$this->Config['returnType'][$returnType]['context']= Mage::getStoreConfig('rma/'.$returnType.'/context');
			$this->Config['returnType'][$returnType]['selectiverma']= Mage::getStoreConfig('rma/'.$returnType.'/selectiverma');
			$this->Config['returnType'][$returnType]['orderDays']= Mage::getStoreConfig('rma/'.$returnType.'/orderdays');
			$this->Config['returnType'][$returnType]['labelname']= Mage::getStoreConfig('rma/'.$returnType.'/labelname');
			$this->Config['returnType'][$returnType]['shippingdefault']= Mage::getStoreConfig('rma/'.$returnType.'/shippingdefault');
			$this->Config['returnType'][$returnType]['refundshipping']= Mage::getStoreConfig('rma/'.$returnType.'/refundshipping');
			$this->Config['returnType'][$returnType]['allowstatus']= Mage::getStoreConfig('rma/'.$returnType.'/allowstatus');
			$this->Config['returnType'][$returnType]['daysfrom']= Mage::getStoreConfig('rma/'.$returnType.'/daysfrom');
			if($this->Config['returnType'][$returnType]['enabled'])$i++;;
		}
		$this->addToSession('Config',$this->Config);
		
		if($i==0)
		{
			return false;
		} else if($i==1)
		{
			return false;
		}else {
			return true;
		}
		
	}

	private function sanitizePostInput(&$val,$key)
	{
		 $val=filter_var($val, FILTER_SANITIZE_STRING); 
	}
	
	private function loadConfig($params='') 			//Laad de configuratie instellen
	{
		//array_map(array($this,'sanitizePostInput'), $_POST);
		array_walk_recursive($_POST,array($this,"sanitizePostInput")); 
		$this->Config = $this->getSession('Config');
														//Hier komt later een fetch functie om de instellingen uit magento te halen
		$this->Config['loginType']= Mage::getStoreConfig('rma/returnoptions/logintype');		

		
		$this->debug=Mage::getStoreConfig('rma/returnoptions/debugmode');
		$this->Config['debug']=$this->debug;
		
		$this->servers = array();
        $this->servers['production']['url'] = Mage::getStoreConfig('rma/advanced/productionurl');
        $this->servers['production']['port'] = Mage::getStoreConfig('rma/advanced/productionport');
		$this->servers['production']['labelurl'] = 'https://www2.12return.eu/';
        $this->servers['test']['url'] = Mage::getStoreConfig('rma/advanced/testurl');
        $this->servers['test']['port'] = Mage::getStoreConfig('rma/advanced/testport');
		$this->servers['test']['labelurl'] = 'http://www2.test.12return.eu:8080/';
		
		
 		$server = Mage::getStoreConfig('rma/returnoptions/serverhost');
		$this->RESTurl			= $this->servers[$server]['url'].$this->RESTrma;
		$this->Contexturl		= $this->servers[$server]['url'].$this->RESTcontext;
		$this->RESTport			= $this->servers[$server]['port'];
		$this->labelurl			= $this->servers[$server]['labelurl'];
		
		$this->Config['labelurl']=$this->labelurl;
		$this->Config['labelview']=Mage::getStoreConfig('rma/returnoptions/labelview');
		$this->Config['bundledproducts']=Mage::getStoreConfig('rma/returnoptions/bundledproducts');
		$this->Config['rmareference']='';
		$this->Config['ownerreference']='';
		$this->Config['pluginenabled']=Mage::getStoreConfig('rma/returnoptions/pluginenabled');
		$this->Config['pricevat']=Mage::getStoreConfig('rma/returnoptions/pricevat');
		$this->Config['useaccountifpossible']=Mage::getStoreConfig('rma/returnoptions/useaccountifpossible');
		$this->Config['processing']=Mage::getStoreConfig('rma/returnoptions/processing');	
		$this->Config['introtext']=Mage::getStoreConfig('rma/returnoptions/introtext');
		$this->Config['customerreference']='';
		$this->Config['tosurl']= Mage::getStoreConfig('rma/returnoptions/tosurl');
		$this->Config['version']=$this->version;
		$this->Config['language']=Mage::app()->getLocale()->getLocaleCode();
		
		$this->addToSession('Config',$this->Config);		//Sla de instellingen op in een sessie

		return true;
	}

	private function activevateReturnType($returnType='')
	{
		if(empty($returnType))
		{
			foreach($this->returnTypes as $type)if($this->Config['returnType'][$type]['enabled'])$returnType=$type;
		}
		if(empty($returnType)) return false;

		if($this->Config['returnType'][$returnType]['enabled'])
		{
			$this->Config = $this->getSession('Config');
			$this->Config['context']= $this->Config['returnType'][$returnType]['context'];
			$this->Config['key']= $this->Config['returnType'][$returnType]['key'];
			$this->Config['selectiverma']=$this->Config['returnType'][$returnType]['selectiverma'];
			$this->Config['orderDays']=$this->Config['returnType'][$returnType]['orderDays'];
			$this->Config['refundshipping']=$this->Config['returnType'][$returnType]['refundshipping'];
			$this->Config['shippingdefault']=$this->Config['returnType'][$returnType]['shippingdefault'];
			$this->Config['labelname']=$this->Config['returnType'][$returnType]['labelname'];
			$this->Config['currentType']=$returnType;
			$this->Config['allowstatus']=$this->Config['returnType'][$returnType]['allowstatus'];
			$this->Config['daysfrom']=$this->Config['returnType'][$returnType]['daysfrom'];
			$this->addToSession('Config',$this->Config);	
			
			if(empty($this->Config['context']) || empty($this->Config['key']))$this->abort('Invalid context or key.');
			return true;
		} else {
			return false;
		}
	}
	
	
	private function resetSession($key='')				//Functie om data tijdelijk in een sessie op te slaan.
	{
		$key = strtolower($key);
		if(empty($key))Mage::getSingleton('core/session')->setReturnModuleSession(''); else {
			$session = Mage::getSingleton('core/session')->getReturnModuleSession();
			if(isset($session[$key])){$session[$key]=''; unset($session[$key]);}
			Mage::getSingleton('core/session')->setReturnModuleSession('');
			Mage::getSingleton('core/session')->setReturnModuleSession($session);
		}
		return true;
	}
	
	private function saveLocalRMAData($orderSession,$response)
	{
		
		$order = Mage::getModel('sales/order')->loadByIncrementID($orderSession['orderId']);
		$order->setStatus('rma_created', true);
        $order->addStatusHistoryComment('12Return RMA request created.<br /><br /> RMA Reference number:' . $response["rmareference"]."<br /> Check status at: https://status.12return.eu/" . $response["rmareference"].'<br />RMA Label: ' . $response["labellink"]);
		$order->save();
		if(Mage::getSingleton('customer/session')->isLoggedIn()) {
     		$customerData 	= Mage::getSingleton('customer/session')->getCustomer();
			$cid			= $customerData->getId();
		} else $cid=0;
		
		$rmamodel 								= Mage::getSingleton('rma/rma');
		$rma['rma_reference']					= $response['rmareference'];
		$rma['rma_context']						= $this->Config['currentType'];
		$rma['rma_createdate']					= date('Y-m-d H:i:s');
		$rma['rma_updatedate']					= date('Y-m-d H:i:s');
		$rma['rma_order_increment_id']			= $orderSession['orderId'];
		$rma['rma_order_entity_id']				= $order->getEntityId();
		$rma['rma_status_code']					= 'rma_created';
		$rma['rma_language']					= $response['language'];
		$rma['rma_ip']							= Mage::helper('core/http')->getRemoteAddr();
		$rma['rma_customer_id']					= $cid;
		$rma['rma_store_id']					= Mage::app()->getStore()->getStoreId();
		$rma['rma_labellink']					= $response['labellink'];
		$rmaReturnoptions = $response["returnoptions"]['returnoption'];
		if(isset($rmaReturnoptions[0]))
		{
			foreach($rmaReturnoptions as $ro)
			{
				if(strtoupper($ro['selected'])=='Y')
				{
					$rma['rma_returnprompt']		= $ro['prompt'];
					$rma['rma_returnlogo']			= $ro['logourl'];
					if(isset($ro['optionurl']))		$rma['rma_returnurl']			= $ro['optionurl']; else $rma['rma_returnurl']='';
					if(isset($ro['instruction']))	$rma['rma_returninstruction']	= $ro['instruction']; else $rma['rma_returninstruction']='';
				}
			}
		}
		
		$rmamodel->setData($rma);
		$rmamodel->save();
		$rmaid 									= $rmamodel->getRmaId();
		
		$raddressmodel 							= Mage::getSingleton('rma/raddress');
		$raddress['rma_id'] 					= $rmaid;
		$raddress['rma_customer_identifier'] 	= $response['customer']['identifier'];
		$raddress['rma_customer_name'] 			= $response['customer']['name'];
		$raddress['rma_customer_street'] 		= $response['customer']['street'];
		$raddress['rma_customer_houseno'] 		= $response['customer']['houseno'];
		$raddress['rma_customer_postal'] 		= $response['customer']['postal'];
		$raddress['rma_customer_city'] 			= $response['customer']['city'];
		$raddress['rma_customer_country'] 		= $response['customer']['country'];
		$raddress['rma_customer_email'] 		= $response['customer']['email'];
		
		if(isset($response['customer']['residental'])) $raddress['rma_customer_residental'] = $response['customer']['residental']; 	else $raddress['rma_customer_residental']='';
		if(isset($response['customer']['phone']))$raddress['rma_customer_phone'] 			= $response['customer']['phone']; 		else $raddress['rma_customer_phone']='';
		if(isset($response['customer']['company']))$raddress['rma_customer_company'] 		= $response['customer']['company']; 	else $raddress['rma_customer_company']='';
		
		$raddressmodel->setData($raddress);
		$raddressmodel->save();
		
		
		
		$rmaConditions = $response["questions"]['question'];
		if(isset($rmaConditions[0]))
		{
			foreach($rmaConditions as $c)
			{
				$rconditionsmodel 							= Mage::getSingleton('rma/rconditions');
				$rcondition['rma_conditions_questioncode'] 	= $c['questioncode'];
				$rcondition['rma_conditions_questiondesc'] 	= $c['questiondesc'];
				$rcondition['rma_conditions_itemcode'] 		= $c['questionvalue'];
				$rcondition['rma_conditions_itemdesc'] 		= $c['questionvalue'];
				$rcondition['rma_id'] 						= $rmaid;
				$rconditionsmodel->setData($rcondition);
				$rconditionsmodel->save();
				

			}
		}

		$rmaProducts = $response["products"]['product'];
		$rmaReutil = $response["reutilization"];
		if(isset($rmaProducts[0]))
		{
			$lineNo=0;
			foreach($order->getAllItems() as $key=>$item)
			{
				$allOptions = $item->getData('product_options');
				$options = unserialize($allOptions);
				if(!isset($options['info_buyRequest']['product']) || $item->getProductId()==$options['info_buyRequest']['product'])
				{
					$lineNo++;
					foreach($rmaProducts as $rmaproduct)
					{
						$rmaItems=false;
						if(isset($rmaproduct["lineno"]) && $rmaproduct["lineno"] == $lineNo)
						{
							$item->setData('qty_returning', $item->getData('qty_returning') + $rmaproduct["qty"]);
							$item->save();
							
							$ritemsmodel 								= Mage::getSingleton('rma/ritems');
							$rmaItems['rma_items_rma_id']				= $rmaid;
							$rmaItems['rma_items_lineno']				= $lineNo;
							$rmaItems['rma_items_product_model']		= $rmaproduct["model"]; 
							$rmaItems['rma_items_order_item_id']		= $item->getItemId();
							$rmaItems['rma_items_order_parent_item_id']	= 0;
							$rmaItems['rma_items_rma_status_code']		= 'rma_created';
					        $rmaItems['rma_items_qty_returning']		= $rmaproduct["qty"];
							$rmaItems['rma_items_createdate']			= date('Y-m-d H:i:s');
							$rmaItems['rma_items_updatedate']			= date('Y-m-d H:i:s');
							$rmaItems['rma_item_qty_returned']			= 0;
							$ritemsmodel->setData($rmaItems);
							$ritemsmodel->save();
							
							$rmaitemsid 								= $ritemsmodel->getRmaItemsId();
							/*
							$rreutilmodel 								= Mage::getSingleton('rma/rreutil');
							$rmaReutil['rma_id']						= $rmaid;
							$rmaReutil['rma_items_id']					= $rmaitemsid;
							$rmaReutil['rma_reutilstrategy']			= $rmaReutil[''];
							$rmaReutil['rma_reutilamounttype']			= $rmaid;
							$rmaReutil['rma_reutilamount']				= $rmaid;
							$rmaReutil['rma_vatamount']					= $rmaid;
							$rmaReutil['rma_reutilccy']					= $rmaid;
							$rmaReutil['rma_reutilclass']				= $rmaid;
							$rmaReutil['rma_reutillineno']				= $lineNo;
					
							$ritemsmodel->setData($rmaItems);
							$ritemsmodel->save();*/
						}
					}	
				}						
			}
		} else {
			//TODO ERROR MESSAGE
		}
	}

	private function getSession($key)				//Functie om data uit een sessie te halen
	{
		$key = strtolower($key);
		$session = Mage::getSingleton('core/session')->getReturnModuleSession();
		if(isset($session[$key]) && !empty($session[$key]))return $session[$key];else return false;
	}
	
	private function addToSession($key,$data)		//Functie om data aan een bestande sessie toe te voegen of aan te passen.
	{
		$key = strtolower($key);
		$session = Mage::getSingleton('core/session')->getReturnModuleSession();
		if(!isset($session[$key]))$session[$key]=$data; else {
			if(is_array($session[$key])) $session[$key] = array_merge($session[$key],$data); else $session[$key]=$data;
		}
		Mage::getSingleton('core/session')->setReturnModuleSession($session);
		return $this->sessionStarted=true;
	}
	
	private function abort($error='')
	{
		$this->resetSession();
		Mage::getSingleton('customer/session')->addError($this->translate('systemError').' - '.$error);
		$this->redirectTo('index');
	}

	
	public function __destruct()
	{
		
	}
	
	
	
	public function noRoute(){
		
		$this->getResponse()->setHeader('HTTP/1.1','404 Not Found');
	    $this->getResponse()->setHeader('Status','404 File not found');
	
	    $pageId = Mage::getStoreConfig(Mage_Cms_Helper_Page::XML_PATH_NO_ROUTE_PAGE);
		
	    $this->_forward('defaultNoRoute');
	}

}


