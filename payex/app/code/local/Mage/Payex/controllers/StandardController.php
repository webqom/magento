<?php
/**
 * PayEx module for Magento
 * Created by Ravindra Pratap Singh, ravisinghengg@gmail.com
 *
 **/

class Mage_Payex_StandardController extends Mage_Core_Controller_Front_Action
{
    protected function _expireAjax()
    {
        if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1','403 Session Expired');
            exit;
        }
    }

    /**
     * Get singleton with payex online order transaction information
     *
     * @return Mage_Payex_Model_Standard
     */
    public function getStandard()
    {
        return Mage::getSingleton('payex/standard');
    }

    /**
     * When a customer chooses PayEx on Checkout/Payment page
     *
     */
    public function redirectAction()
    {
		
    //
		// Load layout
		//
		$this->loadLayout();
		$this->getLayout()->getBlock('content')->append($this->getLayout()->createBlock('payex/standard_redirect'));
		$this->renderLayout();
    
    }
    
    public function addToStock()
    {
    
    //
		// Load the payment object
		//
		$payment = Mage::getModel('payex/standard');
      
		//
    // Load teh session object
    //
		$session = Mage::getSingleton('checkout/session');
		$session->setPayexStandardQuoteId($session->getQuoteId());
      
		//
		// Load the order object
		//
		$order = Mage::getModel('sales/order');
		$order->loadByIncrementId($session->getLastRealOrderId());
      
		//
    // add items back on stock
    //
    	if (((int)$payment->getConfigData('handlestock')) == 1) {
			if(!isset($_SESSION['stock_removed']) || $_SESSION['stock_removed'] != $session->getLastRealOrderId()) {
				$items = $order->getAllItems();
				if ($items) {
					foreach($items as $item) {
					  $quantity = $item->getQtyOrdered();
					  $product_id = $item->getProductId();
					  
					  $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product_id);
					  $stock->setQty($stock->getQty()+$quantity);
					  $stock->save();
					  continue;
					}
			   } 
			   
			   $_SESSION['stock_removed'] = $session->getLastRealOrderId();
			}
		}
    }
    
    //
    // Changes the order status after payment is made
    //
    public function setOrderStatusAfterPayment()
    {
    
    //
		// Load the payment object
		//
		
    $standard = Mage::getModel('payex/standard');
      
		//
		// Load the order object from the get orderid parameter
		//
		
    $order = Mage::getModel('sales/order');
		$order->loadByIncrementId($_GET['orderID']);
      
		//
		// Set the status to the new payex status after payment
		// and save to database
		//
		
    $order->addStatusToHistory($standard->getConfigData('order_status_after_payment'), '', true);
		$order->setStatus($standard->getConfigData('order_status_after_payment'));
		$order->save();
    
    }
    
    //
    // Remove from stock (if used)
    //
    public function removeFromStock()
    {
		//
		// Load the payment object
		//
		$standard = Mage::getModel('payex/standard');
      
		//
		// Load the order object from the get orderid parameter
		//
		$order = Mage::getModel('sales/order');
		$order->loadByIncrementId($_GET['orderID']);
      
        $items = $order->getAllItems();
	    if ($items) {
			foreach($items as $item) {
				$quantity = $item->getQtyOrdered();
				$product_id = $item->getProductId();
				$stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product_id);
				$stock->setQty($stock->getQty()-$quantity);
				$stock->save();
				continue;                        
			}
		}            
    }

    /**
     * When a customer cancel payment from payex.
     */
    public function cancelAction()
    {
      $session = Mage::getSingleton('checkout/session');
  		$session->setPayexStandardQuoteId($session->getQuoteId());
  		$this->_redirect('checkout/cart');
    }
	
	 /**
     * When a customer fails payment from payex.
    **/
    public function failedAction()
    {
	  //
		// Load layout
		//
		$this->loadLayout();
		$this->getLayout()->getBlock('content')->append($this->getLayout()->createBlock('payex/standard_failed'));
		$this->renderLayout();
    }
	 
	  /**
     * When a customer returns from payex.
    **/
    public function completeAction()
    {
    
    $session = Mage::getSingleton('checkout/session');
		$session->setPayexStandardQuoteId($session->getQuoteId());
		$standard = Mage::getModel('payex/standard');
		
		// Get PayEx url and check if ?WSDL exists in url
		$payexurl = $standard->getConfigData('payexurl');
		
    if(!strstr($payexurl, "WSDL"))
		
    	$payexurl .= "?WSDL";
			
		$orderRef = urldecode($_GET['orderRef']);
		
		$accountNumber = $standard->getConfigData('merchantnumber');
		
		$hash = "" . $accountNumber . $orderRef . $standard->getConfigData('md5key');
		$hash = (strlen($standard->getConfigData('md5key'))>0) ? MD5($hash) : ''; 	
		$param = array('accountNumber' => $accountNumber, 'orderRef' => $orderRef, 'hash' => $hash);
		
		try {
			$proxy = new SoapClient($payexurl, array('trace'=>1));
			
			// First check if wsdl file is enabled then check if file exists
			/*if (MODULE_PAYMENT_PAYEXWINDOW_WSDL_FILE>0)
				if (file_exists('includes/modules/payment/payex_pxorder.wsdl'))
					$client->wsdlFile = 'includes/modules/payment/payex_pxorder.wsdl';*/
			
			try {
				// Call the SOAP method
				$result = $proxy->__call('Complete', array('parameters' => $param));

				$response = $result->CompleteResult;
				$response = $this->clean_up_response($response);
				//echo '<pre>'; print_r($response); die;
				// if bank declined transaction / credit check failed
				if ($response->status->code != "OK" || $response->status->errorCode != "OK" || $response->status->description != "OK") {
					Mage::register('payment_error', "bank");
					Mage::register('code', $response->status->errorCode);
					Mage::register('desc', $response->status->description);
					$this->failedAction();
					return;
				}
				// If transactionStatus is 1 , Send transaction callbak to PayPal //
				
				$accountNumber = $standard->getConfigData('merchantnumber');
		    $transactionNumber = $response->transactionNumber;
		    $hash = "" . $accountNumber . $transactionNumber . $standard->getConfigData('md5key');
		    $hash = (strlen($standard->getConfigData('md5key'))>0) ? MD5($hash) : ''; 	
		    $param = array('accountNumber' => $accountNumber, 'transactionNumber' => $transactionNumber, 'hash' => $hash);
				
				if($response->transactionStatus == 1 ){
        
        // Call the SOAP method
				$result = $proxy->__call('GetTransactionDetails2', array('parameters' => $param));

				$response = $result->GetTransactionDetails2Result;
				$response = $this->clean_up_response($response);
				//echo '<pre>'; print_r($response); die;
				// if bank declined transaction / credit check failed
				if ($response->status->code != "OK" || $response->status->errorCode != "OK" || $response->status->description != "OK") {
					Mage::register('payment_error', "Transaction Callbak Error!!");
					Mage::register('code', $response->status->errorCode);
					Mage::register('desc', $response->status->description);
					$this->failedAction();
					return;
				} 
        
        }
				// End of Transaction callback method //
				
				// Check if there is an error from thirdparty
				if ($response->transactionStatus == 5) { 
					if (isset($response->errorDetails) && isset($response->errorDetails->transactionThirdPartyError))
						$errorCode = $response->errorDetails->transactionThirdPartyError;
					
					if ($errorCode != "") {
						Mage::register('payment_error', "thirdparty");
						Mage::register('code', $errorCode);
					}
					else {
						Mage::register('payment_error', "thirdparty");
					}
					$this->failedAction();
					return;
				}
				$this->successAction($response);
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

    public function successAction($response)
    {   
        $session = Mage::getSingleton('checkout/session');
        $session->setPayexStandardQuoteId($session->getQuoteId());
		
        $order = Mage::getModel('sales/order');
        $standard = Mage::getModel('payex/standard');
        $standard->setStatus('APPROVED');
	      $standard->manualInstall();
			
        //
        // Load the order number
        if (Mage::getSingleton('checkout/session')->getLastOrderId() && (isset($_GET["orderID"])) && Mage::getSingleton('checkout/session')->getLastOrderId() == $_GET["orderID"]) {
			$order->load(Mage::getSingleton('checkout/session')->getLastOrderId());
        } else {
			if (isset($_GET["orderID"])) {
				$order->loadByIncrementId((int)$_GET["orderID"]);
			} else {
				echo "<h1>An error occured!</h1>";
				echo "No orderid was supplied to the system!";
				exit();
            }
        }
        
        //
        // Validate the order and send email confirmation if enabled
        if(!$order->getId()){
			echo "<h1>An error occured!</h1>";
			echo "The order id was not known to the system";
			exit();
        }
        
        // If client has selected INVOICE then clientinfo (name, address, city) must be retrieved from PayEx and updated on order 
		if ($response->paymentMethod == "INVOICE") {
		    // Get PayEx url and check if ?WSDL exists in url
			$payexurl = $standard->getConfigData('payexurl');
			if(!strstr($payexurl, "WSDL"))
				$payexurl .= "?WSDL";
			
			$accountNumber = $standard->getConfigData('merchantnumber');
			
			$hash = "" . $accountNumber . $response->transactionRef . $standard->getConfigData('md5key');
			$hash = (strlen($standard->getConfigData('md5key'))>0) ? MD5($hash) : ''; 	
			$param = array('accountNumber' => $accountNumber, 'transactionRef' => $response->transactionRef, 'hash' => $hash);
			
			try {
				$proxy = new SoapClient($payexurl, array('trace'=>1));
				
				// First check if wsdl file is enabled then check if file exists
				/*if (MODULE_PAYMENT_PAYEXWINDOW_WSDL_FILE>0)
					if (file_exists('includes/modules/payment/payex_pxorder.wsdl'))
						$client->wsdlFile = 'includes/modules/payment/payex_pxorder.wsdl';*/
				
				try {
					// Call the SOAP method
					$result2 = $proxy->__call('GetTransactionDetails2', array('parameters' => $param));

					$response2 = $result2->GetTransactionDetailsResult2;
					$response2 = $this->clean_up_response($response2);
					
					if ($response2->status->code != "OK" || $response2->status->errorCode != "OK" || $response2->status->description != "OK") {
						$desc2 = $response2->status->description;
						$code2 = $response2->status->errorCode;
						$order->addStatusToHistory($order->getStatus(), $this->__('PAYEX_LABEL_12') . $code2 . '<br />' . $desc2);
						$order->save();
					}
					else {
						$billing = $order->getBillingAddress();
						$name = $response2->invoice->customerName;
						$billing->setFirstname(substr($name, 0, strpos($name, ' ')));
						$billing->setLastname(substr($name, strpos($name, ' ')+1, strlen($name)));
						$billing->setStreet($response2->invoice->customerStreetAddress);
						$billing->setCity($response2->invoice->customerCity);
						$billing->setPostcode($response2->invoice->customerPostNumber);
						$billing->setCountryId($response2->invoice->customerCountry);
						$billing->save();
						$order->save();
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
		
		$write = Mage::getSingleton('core/resource')->getConnection('core_write');
		$write->query('update payex_order_status set transactionNumber = "' . $response->transactionNumber . '", ' .
			'transactionRef = "' . $response->transactionRef . '" where orderid = "' . $_GET['orderID'] . '"');
		
		//
		// Remove items from stock if either not yet removed or only if stock handling is enabled
		//														    									
		$read = Mage::getSingleton('core/resource')->getConnection('core_read');
		$row = $read->fetchRow("select * from payex_order_status where orderid = '" . $_GET['orderID'] . "'");
		if ($row['status'] == '0') {
			//
			// Remove items from stock as the payment now has been made
			//
			$this->removeFromStock();
		}
		
		//
		// Change order to status paid
		//
		$this->setOrderStatusAfterPayment();
		
		//
		// Send email order confirmation
		// 
		$order->load($order->getId());
		$order->sendOrderUpdateEmail();
        
        //
        // Save the order into the payex_order_status table
        // IMPORTANT to update the status as 1 to ensure that the stock is handled correctly!
        //
        $write->query('update payex_order_status set orderRef = "' . ((isset($_GET['orderRef'])) ? $_GET['orderRef'] : '0') . '", status = 1, ' .
    									'date = "' . date('Y-m-d') . '" where orderid = "' . $_GET['orderID'] . '"');
    	
		$this->_redirect('checkout/onepage/success');
    }
    
    function clean_up_response($response) {
		return simplexml_load_string(html_entity_decode($response));
	}
}
