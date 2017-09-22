<?php
/**
 * PayEx module for Magento
 * Created by Ravindra Pratap Singh, ravisinghengg@gmail.com
 *
 **/
class Mage_Payex_Model_Standard extends Mage_Payment_Model_Method_Abstract
{
    protected $_code  = 'payex_standard';
    protected $_formBlockType = 'payex/standard_form';
        
     /**
     * Get Payex session namespace
     *
     * @return Mage_Payex_Model_Session
     */
    public function getSession()
    {
        return Mage::getSingleton('payex/session');
    }

    /**
     * Get checkout session namespace
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Get current quote
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }

    /**
     * Using internal pages for input payment data
     *
     * @return bool
     */
    public function canUseInternal()
    {
        return false;
    }

    /**
     * Using for multiple shipping address
     *
     * @return bool
     */
    public function canUseForMultishipping()
    {
        return false;
    }

	  public function createFormBlock($name)
    {
        $block = $this->getLayout()->createBlock('payex/standard_form', $name)
            ->setMethod('payex_standard')
            ->setPayment($this->getPayment())
            ->setTemplate('payex/standard/form.phtml');

        return $block;
    }

    public function validate()
    {
        parent::validate();
        return $this;
    }

    public function onOrderValidate(Mage_Sales_Model_Order_Payment $payment)
    {
       return $this;
    }

    public function onInvoiceCreate(Mage_Sales_Model_Invoice_Payment $payment)
    {        
       
    }

    /**
     * Get country collection
     * @return array
     */
    public function getCountryCollection()
    {
        $countryCollection = Mage::getModel('directory/country_api')->items();
        return $countryCollection;
    }

    /**
     * Get region collection
     * @param string $countryCode
     * @return array
     */
    public function getRegionCollection($countryCode)
    {
        $regionCollection = Mage::getModel('directory/region_api')->items($countryCode);
        return $regionCollection;
    }

	 public function getOrderPlaceRedirectUrl()
    {
    	return Mage::getUrl('payex/standard/redirect');
    	
    }
	
	  //
    // Simply return the url for the PayEx Payment window
    //
    
    public function getPayexUrl()
    {
    	return Mage::getUrl('payex/standard/redirect');
    }
    
  	public function manualInstall()
	  {
		$write = Mage::getSingleton('core/resource')->getConnection('core_write');
    //$write->query("drop table `payex_order_status`");
		$write->query("CREATE TABLE if not exists `payex_order_status` (
		  	`orderid` VARCHAR(45) NOT NULL,
		  	`orderRef` VARCHAR(45) NOT NULL,
			  `transactionNumber` VARCHAR(45) NOT NULL,
			  `transactionRef` VARCHAR(45) NOT NULL,
		  	`status` INTEGER UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = unpaid, 1 = paid',
		  	`date` VARCHAR(45) NOT NULL
				);");
	   }

     public function createHash($params)
      {
    	$params = $params.$this->getConfigData('md5key');
    	return md5($params);
      }

    //
	  // Initialize the Payment
	  //
	
	function initalize() {
	
  	//
		// Fetch order invoice info
		//
		
    $order = Mage::getModel('sales/order');
		$order->loadByIncrementId($this->getCheckout()->getLastRealOrderId());

    //
		// Send New Order Email
		//
		
		$order->sendNewOrderEmail();
		
		// Get PayEx url and check if ?WSDL exists in url
		$payexurl = $this->getConfigData('payexurl');
		
    if(!strstr($payexurl, "WSDL"))
			$payexurl .= "?WSDL";
		
		$purchaseOperation = ($this->getConfigData('instantcapture')>0) ? 'SALE' : 'AUTHORIZATION';
		 
		$price = round($order->getTotalDue() * 100);
		
		$priceArgList = '';
		
    $currency = $order->getOrderCurrency()->getCode();
	
		
		$vat = 0;
		
		
		// Get the id of the orders shipping address
    
    foreach ($order->getItemsCollection() as $item) {
            // Do something with $item here...
            $name[] = $item->getName();
            $price[] = $item->getPrice();
            $sku[] = $item->getSku();
            
     }
    
		$productNumber = $sku['0'];
    
    $description = $name['0'];
    
		$clientIPAddress = $_SERVER['REMOTE_ADDR'];
		
    $clientIdentifier = '';
		
		// To initialize payment menu
		$additionalValues = 'PAYMENTMENU=TRUE';
		
    $externalID = '';
		
		$view = 'PX';
		
    $agreementRef = '';

		$orderID = $this->getCheckout()->getLastRealOrderId();
		
    $_SESSION['order_id'] = $this->getCheckout()->getLastRealOrderId();

		$accountNumber = $this->getConfigData('merchantnumber');
		
		$returnUrl = Mage::getUrl('payex/standard/Complete') . '?&orderID=' . $orderID;
		
    // We add extra redirecturl if popup window is true
		if ($this->getConfigData('popup')>1)
			$returnUrl = "https://account.payex.com/pxGo2.asp?NEXT=" . urlencode($returnUrl);
		  $cancelUrl = Mage::getUrl('payex/standard/Cancel') . '?&orderID=' . $orderID;
		if ($this->getConfigData('popup')>1)
			$cancelUrl = "https://account.payex.com/pxGo2.asp?NEXT=" . urlencode($cancelUrl);
			

		$language = Mage::app()->getLocale()->getLocaleCode();
		//$language = Mage::app()->getStore()->_data['code'];
		
		//if ($language == "default")
		//	$language = Mage::app()->getStore()->_data['name'];

		switch (strtolower($language)) { 
			case 'dansk':
			case 'danish':
			case 'da_dk': 
			case 'da-dk':    $clientLanguage = "da-DK";
							 break;
			case 'svensk':
			case 'swedish':
			case 'sv_se':
			case 'sv-se':    $clientLanguage = "sv-SE";
							 break;
			case 'norsk':
			case 'norwegian':
			case 'nb_no':
			case 'nb-no':    $clientLanguage = "nb-NO";
							 break;
			case 'engelsk':
			case 'english':
			case 'en_us':
			case 'en-us':
							 $clientLanguage = "en-US";
							 break;
			default:
							 $clientLanguage = "";
		}

		$hash = "" . $accountNumber . $purchaseOperation . $price . $priceArgList . $currency . $vat . $orderID . $productNumber . $description . $clientIPAddress . $clientIdentifier . $additionalValues . $externalID . $returnUrl . $view . $agreementRef . $cancelUrl . $clientLanguage . $this->getConfigData('md5key');
		$hash = (strlen($this->getConfigData('md5key'))>0) ? MD5($hash) : ''; 	

		$param = array('accountNumber' => $accountNumber, 'purchaseOperation' => $purchaseOperation, 'price' => $price, 'priceArgList' => $priceArgList, 'currency' => $currency, 'vat' => $vat, 'orderID' => $orderID, 'productNumber' => $productNumber, 'description' => $description, 'clientIPAddress' => $clientIPAddress, 'clientIdentifier' => $clientIdentifier, 'additionalValues' => $additionalValues, 'externalID' => $externalID, 'returnUrl' => $returnUrl, 'view' => $view, 'agreementRef' => $agreementRef, 'cancelUrl' => $cancelUrl, 'clientLanguage' => $clientLanguage, 'hash' => $hash); 	
		//echo '<pre>'; print_r($param); 
		try {
			$proxy = new SoapClient($payexurl, array('trace'=>1));
			
			// First check if wsdl file is enabled then check if file exists
			/*if (MODULE_PAYMENT_PAYEXWINDOW_WSDL_FILE>0)
				if (file_exists('includes/modules/payment/payex_pxorder.wsdl'))
					$proxy->wsdlFile = 'includes/modules/payment/payex_pxorder.wsdl';*/
			                                 
			try {
				// Call the SOAP method
				$result = $proxy->__call('Initialize7', array('parameters' => $param));
				$response = $result->Initialize7Result;
				$response = $this->clean_up_response($response);
				
				if ($response->status->code != "OK" || $response->status->errorCode != "OK" || $response->status->description != "OK") {
					echo '<h2>Response error Initialize</h2><pre>';
					print_r($response->status);
					echo '</pre>';
					return false;
				}
				
				// Send orderlines to PayEx
				$items = $order->getItemsCollection();
				//echo '<pre>'; print_r($items); die;
				$i = 0;
				foreach ($items as $item) {
				  
					if ($item->getParentItem()) continue;
					
					$orderRef = (string) $response->orderRef;
					$itemNumber = (string) $i+1;
					
					$itemDescription1 = $item->getName();
					$itemDescription2 = "";
					$itemDescription3 = "";
					$itemDescription4 = "";
					$itemDescription5 = "";
					$quantity = $item->getQtyToInvoice();
					$originalPrice = $item->getOriginalPrice();
          $discountPrice = $item->getDiscountAmount();
				  
          $vatPrice = $item->getTaxAmount() * 100;             
					//$amount = round($item->getOriginalPrice() * $quantity * 100) + $vatPrice; 
					//$amount = round($item->getOriginalPrice() * $quantity * 100);
					//echo '<pre>'; print_r($item); die;
					// If there is any discount 
         
          if(!empty($discountPrice)){
            
            $totalAmount = ($originalPrice  * $quantity) - $discountPrice;
            
            $amount = round($totalAmount * 100);
            
          }else{                                                                   
            $amount = round($item->getOriginalPrice() * $quantity * 100);          
          }
         	
          $vatPercent = $item->getTaxPercent() * 100; 
					
					$hash = "" . $accountNumber . $orderRef . $itemNumber . $itemDescription1 . $itemDescription2 . $itemDescription3 . $itemDescription4 . $itemDescription5 . $quantity . $amount . $vatPrice . $vatPercent . $this->getConfigData('md5key');
					$hash = (strlen($this->getConfigData('md5key'))>0) ? MD5($hash) : '';
		  
					$param = array('accountNumber' => $accountNumber, 'orderRef' => $orderRef, 'itemNumber' => $itemNumber, 'itemDescription1' => $itemDescription1, 'itemDescription2' => $itemDescription2, 'itemDescription3' => $itemDescription3, 'itemDescription4' => $itemDescription4, 'itemDescription5' => $itemDescription5, 'quantity' => $quantity, 'amount' => $amount, 'vatPrice' => $vatPrice, 'vatPercent' => $vatPercent, 'hash' => $hash);
          
          try {
						// Call the SOAP method
						$result2 = $proxy->__call('AddSingleOrderLine', array('parameters' => $param));
						
						$response2 = $result2->AddSingleOrderLineResult;
						$response2 = $this->clean_up_response($response2);
					
						if ($response2->status->code != "OK" || $response2->status->errorCode != "OK" || $response2->status->description != "OK") {
							echo '<h2>Response error Orderlines</h2><pre>';
							print_r($response2->status);
							echo '</pre>';
							return;
						}
					}
					catch (Exception $e) {
						// Display the error
						echo '<h2>Error</h2><p>' . $e->getMessage() . '</p>';
					}
				}
				 
  				//Send shipping cost to PayEx
  				$orderRef = (string) $response->orderRef;
  				$itemNumber = (string) $i+1;
  				$itemDescription1 = $order->getShippingDescription();
  				$itemDescription2 = "";
  				$itemDescription3 = "";
  				$itemDescription4 = "";
  				$itemDescription5 = "";
				  $quantity = 1; 
				
				  
					
				
				// Get shipping tax
				
        $vatPrice = $order->getShippingTaxAmount() * 100;
				
        $amount = round($order->getShippingAmount() * 100 + $vatPrice);
				
        $vatPercent = $order->getShippingTaxAmount() / $order->getShippingAmount() * 10000;
				
				$hash = "" . $accountNumber . $orderRef . $itemNumber . $itemDescription1 . $itemDescription2 . $itemDescription3 . $itemDescription4 . $itemDescription5 . $quantity . $amount . $vatPrice . $vatPercent . $this->getConfigData('md5key');
				
        $hash = (strlen($this->getConfigData('md5key'))>0) ? MD5($hash) : '';
	  
				$param = array('accountNumber' => $accountNumber, 'orderRef' => $orderRef, 'itemNumber' => $itemNumber, 'itemDescription1' => $itemDescription1, 'itemDescription2' => $itemDescription2, 'itemDescription3' => $itemDescription3, 'itemDescription4' => $itemDescription4, 'itemDescription5' => $itemDescription5, 'quantity' => $quantity, 'amount' => $amount, 'vatPrice' => $vatPrice, 'vatPercent' => $vatPercent, 'hash' => $hash);
				
        
				
        try {
				
					// Call the SOAP method
					$result2 = $proxy->__call('AddSingleOrderLine', array('parameters' => $param));
					
					$response2 = $result2->AddSingleOrderLineResult;
					$response2 = $this->clean_up_response($response2);
				  //echo '<pre>'; print_r($response2);
					if ($response2->status->code != "OK" || $response2->status->errorCode != "OK" || $response2->status->description != "OK") {
						echo '<h2>Response error Shipping</h2><pre>';
						print_r($response2->status);
						echo '</pre>';
						return;
					}
					
					return $response->redirectUrl;
				}
				catch (Exception $e) {
					// Display the error
					echo '<h2>Error</h2><p>' . $e->getMessage() . '</p>';
				}				
			}
			catch (Exception $e) {
				// Display the error
				echo '<h2>Error</h2><p>' . $e->getMessage() . '</p>';
			}
				
		}
		catch (Exception $e) {
			// Display the error
			echo '<h2>Constructor error</h2><p>' . $e->getMessage() . '</p>';
		}
	}
	
	function clean_up_response($response) {
		return simplexml_load_string(html_entity_decode($response));
	}

}
