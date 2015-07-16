<?php
/**
 * Uecommerce
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Uecommerce EULA.
 * It is also available through the world-wide-web at this URL:
 * http://www.uecommerce.com.br/
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extension
 * to newer versions in the future. If you wish to customize the extension
 * for your needs please refer to http://www.uecommerce.com.br/ for more information
 *
 * @category   Uecommerce
 * @package    Uecommerce_Mundipagg
 * @copyright  Copyright (c) 2012 Uecommerce (http://www.uecommerce.com.br/)
 * @license    http://www.uecommerce.com.br/
 */

/**
 * Mundipagg Payment module
 *
 * @category   Uecommerce
 * @package    Uecommerce_Mundipagg
 * @author     Uecommerce Dev Team
 */

class Uecommerce_Mundipagg_Model_Api extends Uecommerce_Mundipagg_Model_Standard
{
	public function __construct()
	{
		parent::_construct();
	}

    /**
     * Credit Card Transaction
     */
	public function creditCardTransaction($order, $data, Uecommerce_Mundipagg_Model_Standard $standard) 
	{
		try {
			// Installments configuration
			$installment = $standard->getParcelamento();
			$qtdParcelasMax = $standard->getParcelamentoMax();

			// Get Webservice URL
			$url = $standard->getURL();
						
			// Set Data
			$_request = array();
			$_request["Order"] = array();
			$_request["Order"]["OrderReference"] = $order->getIncrementId();

			if ($standard->getEnvironment() != 'production') {
				$_request["Order"]["OrderReference"] = md5(date('Y-m-d H:i:s')); // Identificação do pedido na loja
			}

			/*
			* Append transaction (multi credit card payments)
			* When one of Credit Cards has not been authorized and we try with a new one)
			*/
			if ($orderReference = $order->getPayment()->getAdditionalInformation('OrderReference')) {
				$_request["Order"]["OrderReference"] = $orderReference;
			}

			// Collection
			$_request["CreditCardTransactionCollection"] = array();

			$creditcardTransactionCollection = array();

			// Partial Payment (we use this reference in order to authorize the rest of the amount)
			if ($order->getPayment()->getAdditionalInformation('OrderReference')) {
				$_request["CreditCardTransactionCollection"]["OrderReference"] = $order->getPayment()->getAdditionalInformation('OrderReference');
			}

			$baseGrandTotal = str_replace(',', '.', $order->getBaseGrandTotal());
			$amountInCentsVar = intval(strval(($baseGrandTotal*100)));

			// CreditCardOperationEnum : if more than one payment method we use AuthOnly and then capture if all are ok
			$helper = Mage::helper('mundipagg');

            $num = $helper->getCreditCardsNumber($data['payment_method']);

			if ( $num > 1 ) {
				$creditCardOperationEnum = 'AuthOnly';
			} else {
				$creditCardOperationEnum = $standard->getCreditCardOperationEnum();
			}

			foreach ($data['payment'] as $i => $paymentData) {
				$creditcardTransactionData = new stdclass();

				// We check if user is not cheating with installments
				/*if ($installment == 1) {
					if ($paymentData['InstallmentCount'] > $qtdParcelasMax) {
						$paymentData['InstallmentCount'] = $qtdParcelasMax;
					}
				} else {
					$paymentData['InstallmentCount'] = 1;	
				}*/

				$creditcardTransactionData->CreditCard = new stdclass();
				$creditcardTransactionData->Options = new stdclass();

				// InstantBuyKey payment
				if (isset($paymentData['card_on_file_id'])) {
					$token = Mage::getModel('mundipagg/cardonfile')->load($paymentData['card_on_file_id']);

					if ($token->getId() && $token->getEntityId() == $order->getCustomerId()) {
						$creditcardTransactionData->CreditCard->InstantBuyKey = $token->getToken();
						$creditcardTransactionData->CreditCard->CreditCardBrand = $token->getCcType();
						$creditcardTransactionData->CreditCardOperation = $creditCardOperationEnum; /** Tipo de operação: AuthOnly | AuthAndCapture | AuthAndCaptureWithDelay  */
						$creditcardTransactionData->AmountInCents = intval(strval(($paymentData['AmountInCents']))); // Valor da transação
						$creditcardTransactionData->InstallmentCount = $paymentData['InstallmentCount']; // Nº de parcelas
						$creditcardTransactionData->Options->CurrencyIso = "BRL"; //Moeda do pedido
					}
				} else { // Credit Card
					$creditcardTransactionData->CreditCard->CreditCardNumber = $paymentData['CreditCardNumber']; // Número do cartão 
					$creditcardTransactionData->CreditCard->HolderName = $paymentData['HolderName']; // Nome do cartão
					$creditcardTransactionData->CreditCard->SecurityCode = $paymentData['SecurityCode']; // Código de segurança
					$creditcardTransactionData->CreditCard->ExpMonth = $paymentData['ExpMonth']; // Mês Exp
					$creditcardTransactionData->CreditCard->ExpYear = $paymentData['ExpYear']; // Ano Exp 
					$creditcardTransactionData->CreditCard->CreditCardBrand = $paymentData['CreditCardBrandEnum']; // Bandeira do cartão : Visa ,MasterCard ,Hipercard ,Amex */
					$creditcardTransactionData->CreditCardOperation = $creditCardOperationEnum; /** Tipo de operação: AuthOnly | AuthAndCapture | AuthAndCaptureWithDelay  */
					$creditcardTransactionData->AmountInCents = intval(strval(($paymentData['AmountInCents']))); // Valor da transação
					$creditcardTransactionData->InstallmentCount = $paymentData['InstallmentCount']; // Nº de parcelas
					$creditcardTransactionData->Options->CurrencyIso = "BRL"; //Moeda do pedido
				}

				// BillingAddress
				if ($standard->getClearsale() == 1) {
					$addy = $this->buyerBillingData($order, $data, $_request, $standard);

					$creditcardTransactionData->CreditCard->BillingAddress = $addy['AddressCollection'][0];
				}

				if ($standard->getEnvironment() != 'production') {
					$creditcardTransactionData->Options->PaymentMethodCode = $standard->getPaymentMethodCode(); // Código do meio de pagamento 
				}

				// Verificamos se tem o produto de teste da Cielo no carrinho
				foreach ($order->getItemsCollection() as $item) {
					if ($item->getSku() == $standard->getCieloSku() && $standard->getEnvironment() == 'production') {
						$creditcardTransactionData->Options->PaymentMethodCode = 5; // Código do meio de pagamento  Cielo
					}
                                        /**
                                         * @todo Implementar Recorrência.
                                         */
				}

				$creditcardTransactionCollection[] = $creditcardTransactionData;
			}
                        
                        /**
                         * @todo Implementar looping para recorrencias a serem adicionadas em $creditcardTransactionCollection
                         */

			$_request["CreditCardTransactionCollection"] = $this->ConvertCreditcardTransactionCollectionFromRequest($creditcardTransactionCollection, $standard);
			
			// Buyer data
			$_request["Buyer"] = array();
			$_request["Buyer"] = $this->buyerBillingData($order, $data, $_request, $standard);

			// Cart data
			$_request["ShoppingCartCollection"] = array();
			$_request["ShoppingCartCollection"] = $this->cartData($order, $data, $_request, $standard);

			if ($standard->getDebug() == 1) {
				$_logRequest = $_request;

				foreach ($_request["CreditCardTransactionCollection"] as $key => $paymentData) {
					if (isset($_logRequest["CreditCardTransactionCollection"][$key]["CreditCard"]["CreditCardNumber"])) {
						$_logRequest["CreditCardTransactionCollection"][$key]["CreditCard"]["CreditCardNumber"] = 'xxxxxxxxxxxxxxxx';
					}

					if (isset($_logRequest["CreditCardTransactionCollection"][$key]["CreditCard"]["SecurityCode"])) {
						$_logRequest["CreditCardTransactionCollection"][$key]["CreditCard"]["SecurityCode"] = 'xxx';
					}

					if (isset($_logRequest["CreditCardTransactionCollection"][$key]["CreditCard"]["ExpMonth"])) {
						$_logRequest["CreditCardTransactionCollection"][$key]["CreditCard"]["ExpMonth"] = 'xx';
					}

					if (isset($_logRequest["CreditCardTransactionCollection"][$key]["CreditCard"]["ExpYear"])) {
						$_logRequest["CreditCardTransactionCollection"][$key]["CreditCard"]["ExpYear"] = 'xx';
					}
				}
			}

			// Data
			$dataToPost = json_encode($_request);

			if ($standard->getDebug() == 1) {
				Mage::log('Uecommerce_Mundipagg: '. Mage::helper('mundipagg')->getExtensionVersion(), null, 'Uecommerce_Mundipagg.log');
				Mage::log(print_r($_logRequest,1), null, 'Uecommerce_Mundipagg.log');
			}

			// Send payment data to MundiPagg
			$ch = curl_init();

			// Header
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'MerchantKey: '.$standard->getMerchantKey().''));

			// Set the url, number of POST vars, POST data
			curl_setopt($ch, CURLOPT_URL, $url);

			curl_setopt($ch, CURLOPT_POSTFIELDS, $dataToPost);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			// Execute post
			$_response = curl_exec($ch);

			// Close connection
			curl_close($ch);

			if ($standard->getDebug() == 1) {
				Mage::log('Uecommerce_Mundipagg: '. Mage::helper('mundipagg')->getExtensionVersion(), null, 'Uecommerce_Mundipagg.log');
				Mage::log(print_r($_response,1), null, 'Uecommerce_Mundipagg.log');
			}

			// Is there an error?
			$xml 	   = simplexml_load_string($_response);
			$json 	   = json_encode($xml);
			$dataR     = array();
			$dataR 	   = json_decode($json, true);

			if ($standard->getDebug() == 1) {
				Mage::log('Uecommerce_Mundipagg: '. Mage::helper('mundipagg')->getExtensionVersion(), null, 'Uecommerce_Mundipagg.log');
				Mage::log(print_r($dataR,1), null, 'Uecommerce_Mundipagg.log');
			}

			if (isset($dataR['ErrorReport']) && !empty($dataR['ErrorReport'])) {
				$_errorItemCollection = $dataR['ErrorReport']['ErrorItemCollection'];

				// Return errors
				return array(
					'error' 			  	=> 1, 
					'ErrorCode'				=> '', 
					'ErrorDescription' 		=> '', 
					'OrderKey'				=> isset($dataR['OrderResult']['OrderKey']) ? $dataR['OrderResult']['OrderKey'] : null,
					'OrderReference'		=> isset($dataR['OrderResult']['OrderReference']) ? $dataR['OrderResult']['OrderReference'] : null,
					'ErrorItemCollection' 	=> $_errorItemCollection, 
					'result'				=> $dataR,
				);
			}

			// Transactions colllection
			$creditCardTransactionResultCollection = $dataR['CreditCardTransactionResultCollection'];

			// Only 1 transaction
			if (count($xml->CreditCardTransactionResultCollection->CreditCardTransactionResult) == 1) {
				if ((string)$creditCardTransactionResultCollection['CreditCardTransactionResult']['Success'] == 'true') {
					$trans = $creditCardTransactionResultCollection['CreditCardTransactionResult'];

					// We save Card On File
					if ($data['customer_id'] != 0 && isset($data['payment'][1]['token']) && $data['payment'][1]['token'] == 'new') {
						$cardonfile = Mage::getModel('mundipagg/cardonfile');

						$cardonfile->setEntityId($data['customer_id']);
						$cardonfile->setAddressId($data['address_id']);
						$cardonfile->setCcType($data['payment'][1]['CreditCardBrandEnum']);
						$cardonfile->setCreditCardMask($trans['CreditCard']['MaskedCreditCardNumber']);
						$cardonfile->setExpiresAt(date("Y-m-t", mktime(0, 0, 0, $data['payment'][1]['ExpMonth'], 1, $data['payment'][1]['ExpYear'])));
						$cardonfile->setToken($trans['CreditCard']['InstantBuyKey']);
						$cardonfile->setActive(1);

						$cardonfile->save();
					}

					return array(
						'success' 			=> true, 
						'message'			=> 1,
						'returnMessage'		=> urldecode($creditCardTransactionResultCollection['CreditCardTransactionResult']['AcquirerMessage']), 
						'OrderKey'			=> $dataR['OrderResult']['OrderKey'],
						'OrderReference'	=> $dataR['OrderResult']['OrderReference'],
						'result'			=> $xml
					);
				} else {
					return array(
						'error' 			=> 1, 
						'ErrorCode'			=> $creditCardTransactionResultCollection['CreditCardTransactionResult']['AcquirerReturnCode'], 
						'ErrorDescription' 	=> urldecode($creditCardTransactionResultCollection['CreditCardTransactionResult']['AcquirerMessage']), 
						'OrderKey'			=> $dataR['OrderResult']['OrderKey'],
						'OrderReference'	=> $dataR['OrderResult']['OrderReference'],
						'result'			=> $xml
					);
				}
			} else { // More than 1 transaction
				$allTransactions = $creditCardTransactionResultCollection['CreditCardTransactionResult'];

				// We remove other transactions made before
				$actualTransactions 	= count($data['payment']);
				$totalTransactions 		= count($creditCardTransactionResultCollection['CreditCardTransactionResult']);
				$transactionsToDelete 	= $totalTransactions - $actualTransactions;
				
				if ($totalTransactions > $actualTransactions) {
					for ($i=0;$i<=($transactionsToDelete - 1);$i++) {
						unset($allTransactions[$i]);
					}

					// Reorganize array indexes from 0
					$allTransactions = array_values($allTransactions);
				}

				// We save Cards On File for current transaction(s)
				foreach ($allTransactions as $key => $trans) {
					if ($data['customer_id'] != 0 && isset($data['payment'][$key + 1]['token']) && $data['payment'][$key + 1]['token'] == 'new') {
						$cardonfile = Mage::getModel('mundipagg/cardonfile');

						$cardonfile->setEntityId($data['customer_id']);
						$cardonfile->setAddressId($data['address_id']);
						$cardonfile->setCcType($data['payment'][$key + 1]['CreditCardBrandEnum']);
						$cardonfile->setCreditCardMask($trans['CreditCard']['MaskedCreditCardNumber']);
						$cardonfile->setExpiresAt(date("Y-m-t", mktime(0, 0, 0, $data['payment'][$key + 1]['ExpMonth'], 1, $data['payment'][$key + 1]['ExpYear'])));
						$cardonfile->setToken($trans['CreditCard']['InstantBuyKey']);
						$cardonfile->setActive(1);

						$cardonfile->save();
					}
				}

				// Result
				return array(
					'success' 			=> true, 
					'message'			=> 1,
					'OrderKey'			=> $dataR['OrderResult']['OrderKey'],
					'OrderReference'	=> $dataR['OrderResult']['OrderReference'],
					'result'			=> $xml,
				);	
			}
		} 
		catch (Exception $e) {
			//Redirect to Cancel page
			Mage::getSingleton('checkout/session')->setApprovalRequestSuccess('cancel');
			
			//Log error
			Mage::logException($e);

			//Mail error
			$this->mailError(print_r($e->getMessage(), 1));

			// Return error
			$approvalRequest['error'] 				= 'Error WS';
            $approvalRequest['ErrorCode'] 			= 'ErrorCode WS';
            $approvalRequest['ErrorDescription']	= 'ErrorDescription WS';
            $approvalRequest['OrderKey'] 			= '';
            $approvalRequest['OrderReference'] 		= '';

            return $approvalRequest;
		}
	}

	/**
	* Convert CreditcardTransaction Collection From Request
	*/
	public function ConvertCreditcardTransactionCollectionFromRequest($creditcardTransactionCollectionRequest, $standard) 
	{
		$newCreditcardTransCollection = array();
		$counter = 0;

		foreach($creditcardTransactionCollectionRequest as $creditcardTransItem) {
			$creditcardTrans = array();
			$creditcardTrans["AmountInCents"] = $creditcardTransItem->AmountInCents;

			if (isset($creditcardTransItem->CreditCard->CreditCardNumber)) {
				$creditcardTrans['CreditCard']["CreditCardNumber"] = $creditcardTransItem->CreditCard->CreditCardNumber;
			}

			if (isset($creditcardTransItem->CreditCard->HolderName)) {
				$creditcardTrans['CreditCard']["HolderName"] = $creditcardTransItem->CreditCard->HolderName;
			}

			if (isset($creditcardTransItem->CreditCard->SecurityCode)) {
				$creditcardTrans['CreditCard']["SecurityCode"] = $creditcardTransItem->CreditCard->SecurityCode;
			}

			if (isset($creditcardTransItem->CreditCard->ExpMonth)) {
				$creditcardTrans['CreditCard']["ExpMonth"] = $creditcardTransItem->CreditCard->ExpMonth;
			}

			if (isset($creditcardTransItem->CreditCard->ExpYear)) {
				$creditcardTrans['CreditCard']["ExpYear"] = $creditcardTransItem->CreditCard->ExpYear;
			}

			if (isset($creditcardTransItem->CreditCard->InstantBuyKey)) {
				$creditcardTrans['CreditCard']["InstantBuyKey"] = $creditcardTransItem->CreditCard->InstantBuyKey;
			}			

			$creditcardTrans['CreditCard']["CreditCardBrand"] 	= $creditcardTransItem->CreditCard->CreditCardBrand;
			$creditcardTrans["CreditCardOperation"] 			= $creditcardTransItem->CreditCardOperation;
			$creditcardTrans["InstallmentCount"] 				= $creditcardTransItem->InstallmentCount;
			$creditcardTrans['Options']["CurrencyIso"] 			= $creditcardTransItem->Options->CurrencyIso;
                        
                        /**
                         * @todo Implementar array de recorrencia caso exista.
                         */

			if ($standard->getEnvironment() != 'production') {
				$creditcardTrans['Options']["PaymentMethodCode"] = $creditcardTransItem->Options->PaymentMethodCode;
			}

			if ($standard->getClearsale() == 1) {
				$creditcardTrans['CreditCard']['BillingAddress'] = $creditcardTransItem->CreditCard->BillingAddress;

				unset($creditcardTrans['CreditCard']['BillingAddress']['AddressType']);
			}
			
			$newCreditcardTransCollection[$counter] = $creditcardTrans;
			$counter += 1;
		}
		
		return $newCreditcardTransCollection;
	}

	/**
	* Boleto transaction
	**/
	public function boletoTransaction($order, $data, Uecommerce_Mundipagg_Model_Standard $standard) 
	{
		try {
			// Get Webservice URL
			$url = $standard->getURL();
			
			// Set Data
			$_request = array();
			$_request["Order"] = array();
			$_request["Order"]["OrderReference"] = $order->getIncrementId();

			if ($standard->getEnvironment() != 'production') {
				$_request["Order"]["OrderReference"] = md5(date('Y-m-d H:i:s')); // Identificação do pedido na loja
			}

			$_request["BoletoTransactionCollection"] = array();

			$boletoTransactionCollection = new stdclass();

			for ($i=1;$i<=$data['boleto_parcelamento'];$i++) {
				$boletoTransactionData = new stdclass();

				if (!empty($data['boleto_dates'])) {
					$datePagamentoBoleto 				= $data['boleto_dates'][$i - 1];
					$now 								= strtotime(date('Y-m-d'));
				    $yourDate 							= strtotime($datePagamentoBoleto);
				    $datediff 							= $yourDate - $now;
				    $daysToAddInBoletoExpirationDate 	= floor($datediff/(60*60*24));
				} else {
					$daysToAddInBoletoExpirationDate 	= $standard->getDiasValidadeBoleto();
				}

				$baseGrandTotal = str_replace(',', '.', $order->getBaseGrandTotal());
				$amountInCentsVar = intval(strval((($baseGrandTotal/$data['boleto_parcelamento'])*100)));

				$boletoTransactionData->AmountInCents = $amountInCentsVar;
				$boletoTransactionData->Instructions  = $standard->getInstrucoesCaixa();
				
				if ($standard->getEnvironment() != 'production') {
					$boletoTransactionData->BankNumber = $standard->getBankNumber();
				}

				$boletoTransactionData->DocumentNumber  = '';

				$boletoTransactionData->Options = new stdclass();
 				$boletoTransactionData->Options->CurrencyIso = 'BRL';
				$boletoTransactionData->Options->DaysToAddInBoletoExpirationDate = $daysToAddInBoletoExpirationDate;
                                
	            $addy = $this->buyerBillingData($order, $data, $_request, $standard);

	            $boletoTransactionData->BillingAddress = $addy['AddressCollection'][0];
			
				$boletoTransactionCollection = array($boletoTransactionData);
			}
			
			$_request["BoletoTransactionCollection"] = $this->ConvertBoletoTransactionCollectionFromRequest($boletoTransactionCollection);
			
			// Buyer data
			$_request["Buyer"] = array();
			$_request["Buyer"] = $this->buyerBillingData($order, $data, $_request, $standard);

			// Cart data
			$_request["ShoppingCartCollection"] = array();
			$_request["ShoppingCartCollection"] = $this->cartData($order, $data, $_request, $standard);

			// Data
			$dataToPost = json_encode($_request);

			if ($standard->getDebug() == 1) {
				Mage::log('Uecommerce_Mundipagg: '. Mage::helper('mundipagg')->getExtensionVersion(), null, 'Uecommerce_Mundipagg.log');
				Mage::log(print_r($dataToPost,1), null, 'Uecommerce_Mundipagg.log');
			}
		
			// Send payment data to MundiPagg
			$ch = curl_init();

			// Header
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'MerchantKey: '.$standard->getMerchantKey().''));

			// Set the url, number of POST vars, POST data
			curl_setopt($ch, CURLOPT_URL, $url);

			curl_setopt($ch, CURLOPT_POSTFIELDS, $dataToPost);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			// Execute post
			$_response = curl_exec($ch);

			// Close connection
			curl_close($ch);

			if ($standard->getDebug() == 1) {
				Mage::log('Uecommerce_Mundipagg: '. Mage::helper('mundipagg')->getExtensionVersion(), null, 'Uecommerce_Mundipagg.log');
				Mage::log(print_r($_response,1), null, 'Uecommerce_Mundipagg.log');
			}

			// Is there an error?
			$xml 	   = simplexml_load_string($_response);
			$json 	   = json_encode($xml);
			$data      = array();
			$data 	   = json_decode($json, true);

			if ($standard->getDebug() == 1) {
				Mage::log('Uecommerce_Mundipagg: '. Mage::helper('mundipagg')->getExtensionVersion(), null, 'Uecommerce_Mundipagg.log');
				Mage::log(print_r($data,1), null, 'Uecommerce_Mundipagg.log');
			}

			// Error
			if (isset($data['ErrorReport']) && !empty($data['ErrorReport'])) {
				$_errorItemCollection = $data['ErrorReport']['ErrorItemCollection'];
				
				foreach ($_errorItemCollection as $errorItem) {
					$errorCode 			= $errorItem['ErrorCode'];
					$ErrorDescription 	= $errorItem['Description'];
				}
				
				return array(
					'error' 			=> 1, 
					'ErrorCode' 		=> $errorCode, 
					'ErrorDescription' 	=> Mage::helper('mundipagg')->__($ErrorDescription),
					'result'			=> $data
				);
			}

			// False
			if (isset($data['Success']) && (string)$data['Success'] == 'false') {
				return array(
					'error' 			=> 1, 
					'ErrorCode' 		=> 'WithError', 
					'ErrorDescription' 	=> 'WithError',
					'result'			=> $data
				);
			} else {
				// Success
				return array(
					'success' 			=> true, 
					'message' 			=> 0, 
					'OrderKey'			=> $data['OrderResult']['OrderKey'],
					'OrderReference'	=> $data['OrderResult']['OrderReference'],
					'result'			=> $data
				);
			}
		}
		catch (Exception $e) {
			//Redirect to Cancel page
			Mage::getSingleton('checkout/session')->setApprovalRequestSuccess('cancel');
			
			//Log error
			Mage::logException($e);

			//Mail error
			$this->mailError(print_r($e->getMessage(), 1));

			// Return error
			$approvalRequest['error'] 				= 'Error WS';
            $approvalRequest['ErrorCode'] 			= 'ErrorCode WS';
            $approvalRequest['ErrorDescription'] 	= 'ErrorDescription WS';
            $approvalRequest['OrderKey'] 			= '';
            $approvalRequest['OrderReference'] 		= '';

            return $approvalRequest;
		}
	}

	/**
	* Convert BoletoTransaction Collection From Request
	*/
	public function ConvertBoletoTransactionCollectionFromRequest($boletoTransactionCollectionRequest) 
	{
		$newBoletoTransCollection = array();
		$counter = 0;

		foreach($boletoTransactionCollectionRequest as $boletoTransItem) {
			$boletoTrans = array();
			$boletoTrans["AmountInCents"] = $boletoTransItem->AmountInCents;
            $boletoTrans["BankNumber"] = isset($boletoTransItem->BankNumber) ? $boletoTransItem->BankNumber : '';
			$boletoTrans["Instructions"] = $boletoTransItem->Instructions;
			$boletoTrans["DocumentNumber"] = $boletoTransItem->DocumentNumber;
			$boletoTrans["Options"]["CurrencyIso"] = $boletoTransItem->Options->CurrencyIso;
			$boletoTrans["Options"]["DaysToAddInBoletoExpirationDate"] = $boletoTransItem->Options->DaysToAddInBoletoExpirationDate;
            $boletoTrans['BillingAddress'] = $boletoTransItem->BillingAddress;
			
			$newBoletoTransCollection[$counter] = $boletoTrans;
			$counter += 1;
		}
		
		return $newBoletoTransCollection;
	}

	/**
	* Debit transaction
	**/
	public function debitTransaction($order, $data, Uecommerce_Mundipagg_Model_Standard $standard)
	{
		try {
			// Get Webservice URL
			$url = $standard->getURL();

			$baseGrandTotal = str_replace(',', '.', $order->getBaseGrandTotal());
			$amountInCentsVar = intval(strval(($baseGrandTotal*100)));

			// Set Data
			$_request = array();

			$_request = array();

			$_request["RequestKey"] = '00000000-0000-0000-0000-000000000000';
			$_request["AmountInCents"] = $amountInCentsVar;
			$_request['Bank'] = $data['Bank'];
			
			// Buyer data
			$_request["Buyer"] = array();
			$_request["Buyer"] = $this->buyerDebitBillingData($order, $data, $_request, $standard);

			// Order data
			$_request['InstallmentCount'] = '0';
			$_request["OrderKey"] = '00000000-0000-0000-0000-000000000000';
			$_request["OrderRequest"]['AmountInCents'] = $amountInCentsVar;
			$_request["OrderRequest"]['OrderReference'] = $order->getIncrementId();

			if ($standard->getEnvironment() != 'production') {
				$_request["OrderRequest"]["OrderReference"] = md5(date('Y-m-d H:i:s')); // Identificação do pedido na loja
			}

			$_request['PaymentMethod'] = null;
			$_request['PaymentType'] = null;

			// Data
			$dataToPost = json_encode($_request);

			if ($standard->getDebug() == 1) {
				Mage::log('Uecommerce_Mundipagg: '. Mage::helper('mundipagg')->getExtensionVersion(), null, 'Uecommerce_Mundipagg.log');
				Mage::log(print_r($dataToPost,1), null, 'Uecommerce_Mundipagg.log');
			}
		
			// Send payment data to MundiPagg
			$ch = curl_init();

			// Header
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'MerchantKey: '.$standard->getMerchantKey().''));

			// Set the url, number of POST vars, POST data
			curl_setopt($ch, CURLOPT_URL, $url);

			curl_setopt($ch, CURLOPT_POSTFIELDS, $dataToPost);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			// Execute post
			$_response = curl_exec($ch);

			// Close connection
			curl_close($ch);

			if ($standard->getDebug() == 1) {
				Mage::log('Uecommerce_Mundipagg: '. Mage::helper('mundipagg')->getExtensionVersion(), null, 'Uecommerce_Mundipagg.log');
				Mage::log(print_r($_response,1), null, 'Uecommerce_Mundipagg.log');
			}

			// Is there an error?
			$xml 	   = simplexml_load_string($_response);
			$json 	   = json_encode($xml);
			$data      = array();
			$data 	   = json_decode($json, true);

			if ($standard->getDebug() == 1) {
				Mage::log('Uecommerce_Mundipagg: '. Mage::helper('mundipagg')->getExtensionVersion(), null, 'Uecommerce_Mundipagg.log');
				Mage::log(print_r($data,1), null, 'Uecommerce_Mundipagg.log');
			}

			// Error
			if (isset($data['ErrorReport']) && !empty($data['ErrorReport'])) {
				$_errorItemCollection = $data['ErrorReport']['ErrorItemCollection'];
				
				foreach ($_errorItemCollection as $errorItem) {
					$errorCode 			= $errorItem['ErrorCode'];
					$ErrorDescription 	= $errorItem['Description'];
				}
				
				return array(
					'error' 			=> 1, 
					'ErrorCode' 		=> $errorCode, 
					'ErrorDescription' 	=> Mage::helper('mundipagg')->__($ErrorDescription),
					'result'			=> $data
				);
			}

			// False
			if (isset($data['Success']) && (string)$data['Success'] == 'false') {
				return array(
					'error' 			=> 1, 
					'ErrorCode' 		=> 'WithError', 
					'ErrorDescription' 	=> 'WithError',
					'result'			=> $data
				);
			} else {
				// Success
				return array(
					'success' 				=> true, 
					'message' 				=> 4, 
					'OrderKey'				=> $data['OrderKey'],
					'TransactionKey'		=> $data['TransactionKey'],
					'TransactionKeyToBank'	=> $data['TransactionKeyToBank'],
					'TransactionReference'	=> $data['TransactionReference'],
					'result'				=> $data
				);
			}
		}
		catch (Exception $e) {
			//Redirect to Cancel page
			Mage::getSingleton('checkout/session')->setApprovalRequestSuccess('cancel');
			
			//Log error
			Mage::logException($e);

			//Mail error
			$this->mailError(print_r($e->getMessage(), 1));

			// Return error
			$approvalRequest['error'] 				= 'Error WS';
            $approvalRequest['ErrorCode'] 			= 'ErrorCode WS';
            $approvalRequest['ErrorDescription'] 	= 'ErrorDescription WS';
            $approvalRequest['OrderKey'] 			= '';
            $approvalRequest['OrderReference'] 		= '';

            return $approvalRequest;
		}
	}

	/**
	* Set buyer data
	*/
	public function buyerBillingData($order, $data, $_request, $standard) 
	{
		if ($order->getData()) {
                        $gender = null;
			if ($order->getCustomerGender()) {
				$gender = $order->getCustomerGender();
			}

			if($order->getCustomerIsGuest() == 0) {
				$customer = Mage::getModel('customer/customer')->load($order->getCustomerId());

				$gender = $customer->getGender();

				$createdAt = explode(' ', $customer->getCreatedAt());
				$updatedAt = explode(' ', $customer->getUpdatedAt());
                                $currentDateTime = Mage::getModel('core/date')->date('Y-m-d H:i:s');
                                if(!array_key_exists(1, $createdAt)){
                                    $createdAt = explode(' ', $currentDateTime);
                                }
                                
                                if(!array_key_exists(1, $updatedAt)){
                                    $updatedAt = explode(' ', $currentDateTime);
                                }
                               

				$createDateInMerchant = substr($createdAt[0].'T'.$createdAt[1], 0, 19);
				$lastBuyerUpdateInMerchant = substr($updatedAt[0].'T'.$updatedAt[1], 0, 19);
			} else {
				$createDateInMerchant = $lastBuyerUpdateInMerchant = date('Y-m-d').'T'.date('H:i:s');
			}

			switch ($gender) {
				case '1':
					$gender = 'M';
					break;
				
				case '2':
					$gender = 'F';
					break;
			}
		}

		$billingAddress = $order->getBillingAddress();
		$street 		= $billingAddress->getStreet();
		$regionCode 	= $billingAddress->getRegionCode();

		if ($billingAddress->getRegionCode() == '') {
			$regionCode = 'RJ';
		}

		$telephone = Mage::helper('mundipagg')->applyTelephoneMask($billingAddress->getTelephone());

		if ($billingAddress->getTelephone() == '') {
			$telephone = '55(21)88888888';
		}

		// In case we doesn't have CPF or CNPJ informed we set default value for MundiPagg (required field)
		$data['DocumentNumber'] = isset($data['TaxDocumentNumber']) ? $data['TaxDocumentNumber'] : $order->getCustomerTaxvat();

		$invalid = 0;

		if (Mage::helper('mundipagg')->validateCPF($data['DocumentNumber'])) {
            $data['PersonType']      = 'Person';
            $data['DocumentType'] = 'CPF';
            $data['DocumentNumber']   = $data['DocumentNumber'];
        } else {
        	$invalid++;
        }
        
        // We verify if a CNPJ is informed
        if (Mage::helper('mundipagg')->validateCNPJ($data['DocumentNumber'])) {
            $data['PersonTypeEnum'] = 'Company';
            $data['DocumentType'] = 'CNPJ';
            $data['DocumentNumber'] = $data['DocumentNumber'];
        } else {
        	$invalid++;
        }

		if ($invalid == 2) {
			$data['DocumentNumber'] = '00000000000';
			$data['DocumentType'] 	= 'CPF';
			$data['PersonType'] 	= 'Person';
		}

		// Request
		if($gender == 'M' || $gender == 'F') {
			$_request["Buyer"]["Gender"] 		= $gender;
		}

		$_request["Buyer"]["DocumentNumber"] 			= preg_replace('[\D]', '', $data['DocumentNumber']);
		$_request["Buyer"]["DocumentType"] 				= $data['DocumentType'];
		$_request["Buyer"]["Email"] 					= $order->getCustomerEmail();
		$_request["Buyer"]["EmailType"] 				= 'Personal';
		$_request["Buyer"]["Name"] 						= $order->getCustomerName();
		$_request["Buyer"]["PersonType"] 				= $data['PersonType'];
		$_request["Buyer"]["MobilePhone"] 				= $telephone;
		$_request["Buyer"]['BuyerCategory']				= 'Normal';
		$_request["Buyer"]['FacebookId']				= '';
		$_request["Buyer"]['TwitterId']					= '';
		$_request["Buyer"]['BuyerReference']			= '';
		$_request["Buyer"]['CreateDateInMerchant']		= $createDateInMerchant;
		$_request["Buyer"]['LastBuyerUpdateInMerchant']	= $lastBuyerUpdateInMerchant;
		
		// Address
		$address = array();
		$address['AddressType'] = 'Residential';
		$address['City']		= $billingAddress->getCity();
		$address['District'] 	= isset($street[3])?$street[3]:'xxx';
		$address['Complement'] 	= isset($street[2])?$street[2]:'';
		$address['Number'] 		= isset($street[1])?$street[1]:'0';
		$address['State'] 		= $regionCode;
		$address['Street'] 		= isset($street[0])?$street[0]:'xxx';
		$address['ZipCode']		= preg_replace('[\D]', '', $billingAddress->getPostcode());
		$address['Country'] 	= 'Brazil';
		
		$_request["Buyer"]["AddressCollection"] = array();
		$_request["Buyer"]["AddressCollection"] = array($address);

		return $_request["Buyer"];
	}

	/**
	* Set buyer data
	*/
	public function buyerDebitBillingData($order, $data, $_request, $standard) 
	{
		if ($order->getData()) {
			if ($order->getCustomerGender()) {
				$gender = $order->getCustomerGender();
			} else {
				$customerId = $order->getCustomerId(); 

				$customer = Mage::getModel('customer/customer')->load($customerId);

				$gender = $customer->getGender();
			}

			switch ($gender) {
				case '1':
					$gender = 'M';
					break;
				
				case '2':
					$gender = 'F';
					break;
			}
		}

		$billingAddress = $order->getBillingAddress();
		$street 		= $billingAddress->getStreet();
		$regionCode 	= $billingAddress->getRegionCode();

		if ($billingAddress->getRegionCode() == '') {
			$regionCode = 'RJ';
		}

		$telephone = Mage::helper('mundipagg')->applyTelephoneMask($billingAddress->getTelephone());

		if ($billingAddress->getTelephone() == '') {
			$telephone = '55(21)88888888';
		}

		// In case we doesn't have CPF or CNPJ informed we set default value for MundiPagg (required field)
		$data['DocumentNumber'] = isset($data['TaxDocumentNumber']) ? $data['TaxDocumentNumber'] : $order->getCustomerTaxvat();

		$invalid = 0;

		if (Mage::helper('mundipagg')->validateCPF($data['DocumentNumber'])) {
            $data['PersonType']      = 'Person';
            $data['DocumentType'] = 'CPF';
            $data['DocumentNumber']   = $data['DocumentNumber'];
        } else {
        	$invalid++;
        }
        
        // We verify if a CNPJ is informed
        if (Mage::helper('mundipagg')->validateCNPJ($data['DocumentNumber'])) {
            $data['PersonTypeEnum'] = 'Company';
            $data['DocumentType'] = 'CNPJ';
            $data['DocumentNumber'] = $data['DocumentNumber'];
        } else {
        	$invalid++;
        }

		if ($invalid == 2) {
			$data['DocumentNumber'] = '00000000000';
			$data['DocumentType'] 	= 'CPF';
			$data['PersonType'] 	= 'Person';
		}

		// Request
		if($gender == 'M' || $gender == 'F') {
			$_request["Buyer"]["Gender"] 		= $gender;
		}

		$_request["Buyer"]["DocumentNumber"] 	= preg_replace('[\D]', '', $data['DocumentNumber']);
		$_request["Buyer"]["DocumentType"] 		= $data['DocumentType'];
		$_request["Buyer"]["Email"] 			= $order->getCustomerEmail();
		$_request["Buyer"]["EmailType"] 		= 'Personal';
		$_request["Buyer"]["Name"] 				= $order->getCustomerName();
		$_request["Buyer"]["PersonType"] 		= $data['PersonType'];
		$_request["Buyer"]["MobilePhone"] 		= $telephone;
		$_request["Buyer"]['BuyerCategory']		= 'Normal';
		$_request["Buyer"]['FacebookId']		= '';
		$_request["Buyer"]['TwitterId']			= '';
		$_request["Buyer"]['BuyerReference']	= '';
		
		// Address
		$address = array();
		$address['AddressTypeEnum'] = 'Residential';
		$address['City']			= $billingAddress->getCity();
		$address['District'] 		= isset($street[3])?$street[3]:'xxx';
		$address['Complement'] 		= isset($street[2])?$street[2]:'';
		$address['Number'] 			= isset($street[1])?$street[1]:'0';
		$address['State'] 			= $regionCode;
		$address['Street'] 			= isset($street[0])?$street[0]:'xxx';
		$address['ZipCode']			= preg_replace('[\D]', '', $billingAddress->getPostcode());
		
		$_request["Buyer"]["BuyerAddressCollection"] = array();
		$_request["Buyer"]["BuyerAddressCollection"] = array($address);

		return $_request["Buyer"];
	}

	/**
	* Set cart data
	*/
	public function cartData($order, $data, $_request, $standard)
	{
		$baseGrandTotal = round($order->getBaseGrandTotal(),2);
		$baseDiscountAmount = round($order->getBaseDiscountAmount(),2);

		if (abs($order->getBaseDiscountAmount()) > 0) {
			$totalWithoutDiscount = $baseGrandTotal + abs($baseDiscountAmount);
		
			$discount = round(($baseGrandTotal / $totalWithoutDiscount),4);
		} else {
			$discount = 1;
		}

		$shippingDiscountAmount = round($order->getShippingDiscountAmount(),2);

		if (abs($shippingDiscountAmount) > 0) {
			$totalShippingWithoutDiscount = round($order->getBaseShippingInclTax(),2);
			$totalShippingWithDiscount = $totalShippingWithoutDiscount - abs($shippingDiscountAmount);
		
			$shippingDiscount = round(($totalShippingWithDiscount / $totalShippingWithoutDiscount),4);
		} else {
			$shippingDiscount = 1;
		}

		$items = array();

		foreach ($order->getItemsCollection() as $item) {
                        
			if($item->getParentItemId() == '') {
                                
				$items[$item->getItemId()]['sku'] 	= $item->getProductId();
				$items[$item->getItemId()]['name'] 	= $item->getName();
                                $items[$item->getItemId()]['description'] = $item->getProduct()->load($item->getProductId())->getShortDescription();
	            $items[$item->getItemId()]['qty'] 	= round($item->getQtyOrdered(),0);
	            $items[$item->getItemId()]['price'] = $item->getBasePrice();
        	}
        }

        $i = 0;

        $shipping = intval(strval(($order->getBaseShippingInclTax()*$shippingDiscount*100)));

        $_request["ShoppingCartCollection"]["FreightCostInCents"] = $shipping;

        foreach ($items as $itemId) {
			//if ($standard->getClearsale() == 1) {
				$unitCostInCents = intval(strval(($itemId['price']*$discount*100)));
                                
                                $_request["ShoppingCartCollection"]["ShoppingCartItemCollection"][$i]["Description"] = empty($itemId['description']) || ($itemId['description'] == '')?$itemId['name']:$itemId['description'];
				$_request["ShoppingCartCollection"]["ShoppingCartItemCollection"][$i]["ItemReference"] 	= $itemId['sku'];
        		$_request["ShoppingCartCollection"]["ShoppingCartItemCollection"][$i]["Name"] 			= $itemId['name'];
            	$_request["ShoppingCartCollection"]["ShoppingCartItemCollection"][$i]["Quantity"] 		= $itemId['qty'];
        		$_request["ShoppingCartCollection"]["ShoppingCartItemCollection"][$i]["UnitCostInCents"]= $unitCostInCents;
        	//}

        	$totalInCents = intval(strval(($itemId['qty']*$itemId['price']*$discount*100)));

        	$_request["ShoppingCartCollection"]["ShoppingCartItemCollection"][$i]["TotalCostInCents"] = $totalInCents;

            $i++;
        }

        // Delivery address
        if ($order->getIsVirtual()) {
	        $addy = $order->getBillingAddress();
		} else {
			$addy = $order->getShippingAddress();
		}

		$street 	= $addy->getStreet();
		$regionCode = $addy->getRegionCode();

		if ($addy->getRegionCode() == '') {
			$regionCode = 'RJ';
		}

		$address = array();
		$address['City'] 		= $addy->getCity();
		$address['District'] 	= isset($street[3])?$street[3]:'xxx';
		$address['Complement'] 	= isset($street[2])?$street[2]:'';
		$address['Number'] 		= isset($street[1])?$street[1]:'0';
		$address['State'] 		= $regionCode;
		$address['Street'] 		= isset($street[0])?$street[0]:'xxx';
		$address['ZipCode'] 	= preg_replace('[\D]', '', $addy->getPostcode());
		$address['Country'] 	= 'Brazil';
                $address['AddressType'] = "Shipping";

		$_request["ShoppingCartCollection"]["DeliveryAddress"] = array();

		$_request["ShoppingCartCollection"]["DeliveryAddress"] = $address;
                
        return array($_request["ShoppingCartCollection"]);
	}

	/**
	* Manage Order Request: capture / void / refund
	**/
	public function manageOrderRequest($data, Uecommerce_Mundipagg_Model_Standard $standard) 
	{
		try {
			// Get Webservice URL
			$url = $standard->getURL().'/'.$data['ManageOrderOperationEnum'];

			unset($data['ManageOrderOperationEnum']);

			// Get store key
			$key = $standard->getMerchantKey();

			if ($standard->getDebug() == 1) {
				Mage::log('Uecommerce_Mundipagg: '. Mage::helper('mundipagg')->getExtensionVersion(), null, 'Uecommerce_Mundipagg.log');
				Mage::log(print_r($data,1), null, 'Uecommerce_Mundipagg.log');
			}

			$dataToPost = json_encode($data);

			// Send payment data to MundiPagg
			$ch = curl_init();

			// Header
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'MerchantKey: '.$key.''));

			// Set the url, number of POST vars, POST data
			curl_setopt($ch, CURLOPT_URL, $url);

			curl_setopt($ch, CURLOPT_POSTFIELDS, $dataToPost);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			// Execute post
			$_response = curl_exec($ch);

			// Close connection
			curl_close($ch);

			if ($standard->getDebug() == 1) {
				Mage::log('Uecommerce_Mundipagg: '. Mage::helper('mundipagg')->getExtensionVersion(), null, 'Uecommerce_Mundipagg.log');
				Mage::log(print_r($_response,1), null, 'Uecommerce_Mundipagg.log');
			}

			// Return
			return array('result' => simplexml_load_string($_response));
		}
		catch (Exception $e) {
			//Redirect to Cancel page
			Mage::getSingleton('checkout/session')->setApprovalRequestSuccess(false);
			
			//Log error
			Mage::logException($e);

			//Mail error
			$this->mailError(print_r($e->getMessage(), 1));

			// Throw Exception
			Mage::throwException(Mage::helper('mundipagg')->__('Payment Error'));
		}
	}

	/**
     * Process order
     * @param $order
     * @param $data
     */
	public function processOrder($postData) 
	{
		$standard = Mage::getModel('mundipagg/standard');
		
		if ($standard->getConfigData('debug') == 1) {
			Mage::log('xmlStatusNotification', null, 'Uecommerce_Mundipagg.log');
			Mage::log( print_r($postData['xmlStatusNotification'],1), null, 'Uecommerce_Mundipagg.log');
		}

		try {
			if (isset($postData['xmlStatusNotification'])) {
				$xmlStatusNotificationString 	= htmlspecialchars_decode($postData['xmlStatusNotification']);
				$xml 							= simplexml_load_string($xmlStatusNotificationString);
				$json 							= json_encode($xml);
				$data 							= json_decode($json, true);

				$orderReference = $data['OrderReference'];
				
				if (!empty($data['BoletoTransaction'])) {
					$status 				= $data['BoletoTransaction']['BoletoTransactionStatus'];
					$transactionKey 		= $data['BoletoTransaction']['TransactionKey'];
					$capturedAmountInCents 	= $data['BoletoTransaction']['AmountPaidInCents'];
				}

				if (!empty($data['CreditCardTransaction'])) {
					$status 				= $data['CreditCardTransaction']['CreditCardTransactionStatus'];
					$transactionKey 		= $data['CreditCardTransaction']['TransactionKey'];
					$capturedAmountInCents 	= $data['CreditCardTransaction']['CapturedAmountInCents'];
				}

				if (!empty($data['OnlineDebitTransaction'])) {
					$status 				= $data['OnlineDebitTransaction']['OnlineDebitTransactionStatus'];
					$transactionKey 		= $data['OnlineDebitTransaction']['TransactionKey'];
					$capturedAmountInCents 	= $data['OnlineDebitTransaction']['AmountPaidInCents'];
				}
				
				$order = Mage::getModel('sales/order');
				$order->loadByIncrementId($orderReference);

				if (!$order->getId()) {
					return 'KO';
				}

				// We check if transactionKey exists in database
				$t = 0;

				$transactions = Mage::getModel('sales/order_payment_transaction')
					->getCollection()
			        ->addAttributeToFilter('order_id', array('eq' => $order->getEntityId()));

			    foreach ($transactions as $key => $transaction) {
			    	$orderTransactionKey = $transaction->getAdditionalInformation('TransactionKey');

			    	// transactionKey found
			    	if ($orderTransactionKey == $transactionKey) {
			    		$t++;
			    		continue;
			    	}
			    }

			    // transactionKey has been found so we can proceed
			    if ($t > 0) {
			    	switch($status) {
		                case 'Captured':
		                case 'Paid':
		                case 'OverPaid':
		                case 'Overpaid':

							if ($order->canUnhold()) {
								$order->unhold();
							}

							if (!$order->canInvoice()) {
								return 'OK';
							}

							// Partial invoice
							$epsilon = 0.00001;

							if ($order->canInvoice() && abs($order->getGrandTotal() - $capturedAmountInCents*0.01) > $epsilon) {
								$baseTotalPaid = $order->getTotalPaid();

								// If there is already a positive baseTotalPaid value it's not the first transaction
								if ($baseTotalPaid > 0) {
									$baseTotalPaid += $capturedAmountInCents*0.01;
									
									$order->setTotalPaid(0);
								} else {
									$baseTotalPaid = $capturedAmountInCents*0.01;
									
									$order->setTotalPaid($baseTotalPaid);
								}

								// Can invoice only if total captured amount is equal to GrandTotal
								if(abs($order->getGrandTotal() - $baseTotalPaid) < $epsilon) {
									$result = $this->createInvoice($order, $data, $baseTotalPaid, $status);

									return $result;
								} else {
									$order->save();

									return 'OK';
								}
							}
							
							// Create invoice
							if ($order->canInvoice() && abs($capturedAmountInCents*0.01-$order->getGrandTotal()) < $epsilon) {
								$result = $this->createInvoice($order, $data, $order->getGrandTotal(), $status);

								return $result;
							}

							return 'KO';

		                    break;

		                case 'UnderPaid':
		                case 'Underpaid':
		                	if ($order->canUnhold()) {
								$order->unhold();
							}

		                	$order->addStatusHistoryComment('Captured offline amount of R$'.$capturedAmountInCents*0.01, false);
							$order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, 'underpaid');
							$order->setBaseTotalPaid($capturedAmountInCents*0.01);
							$order->setTotalPaid($capturedAmountInCents*0.01);
							$order->save();

							return 'OK';
		                	break;

		                case 'NotAuthorized':
		                	return 'OK';
		                	break;

		                case 'Canceled':
		                case 'Refunded':
		                case 'Voided':
		                 		if ($order->canUnhold()) {
									$order->unhold();
								}

								$ok = 0;
								$invoices = array();
								$canceledInvoices = array();

								foreach ($order->getInvoiceCollection() as $invoice) {
									// We check if invoice can be refunded
								    if ($invoice->canRefund()) {
								    	$invoices[] = $invoice;
								    }

								    // We check if invoice has already been canceled
									if ($invoice->isCanceled()) {
								    	$canceledInvoices[] = $invoice;
								    }								    
								}

								// Refund invoices and Credit Memo
								if (!empty($invoices)) {
									$service = Mage::getModel('sales/service_order', $order);
									
									foreach ($invoices as $invoice) {
										$invoice->setState(Mage_Sales_Model_Order_Invoice::STATE_CANCELED);
										$invoice->save();

										$creditmemo = $service->prepareInvoiceCreditmemo($invoice);
										$creditmemo->setOfflineRequested(true);
										$creditmemo->register()->save();
									}
									
									// Close order
									$order->setData('state', 'closed');
									$order->setStatus('closed');
									$order->save();

									// Return
									$ok++;
								} 

								// Credit Memo
								if (!empty($canceledInvoices)) {
									$service = Mage::getModel('sales/service_order', $order);
									
									foreach ($invoices as $invoice) {
										$creditmemo = $service->prepareInvoiceCreditmemo($invoice);
										$creditmemo->setOfflineRequested(true);
										$creditmemo->register()->save();
									}
									
									// Close order
									$order->setData('state', Mage_Sales_Model_Order::STATE_CLOSED);
									$order->setStatus(Mage_Sales_Model_Order::STATE_CLOSED);
									$order->save();

									// Return
									$ok++;
								} 

								if (empty($invoices) && empty($canceledInvoices)) {
									// Cancel order
									$order->cancel()->save();

									// Return
									$ok++;
								}

								if ($ok > 0) {
									return 'OK';
								} else {
									return 'KO';
								}
		                    break;

		                // For other status we add comment to history
		                default:
		                	$order->addStatusHistoryComment($status, false);
							$order->save();

							return 'KO';
		            } 
		        } else {
		        	return 'KO';
		        }
            } else {
            	return 'KO';
            } 
		} 
		catch (Exception $e) {
			//Log error
			Mage::logException($e);

			//Mail error
			$this->mailError(print_r($e->getMessage(), 1));

			return 'KO';
		}
    }

    /**
    * Create invoice
    */
    private function createInvoice($order, $data, $totalPaid, $status)
    {
    	$invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
							
		if (!$invoice->getTotalQty()) {
			$order->addStatusHistoryComment('Cannot create an invoice without products.', false);
			$order->save();
			return 'KO';
		}

		$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
		$invoice->register();
		$invoice->getOrder()->setCustomerNoteNotify(true); 
		$invoice->getOrder()->setIsInProcess(true);
		$invoice->setCanVoidFlag(true);

		$transactionSave = Mage::getModel('core/resource_transaction')
			->addObject($invoice)
			->addObject($invoice->getOrder());
		$transactionSave->save();

		// Send invoice email if enabled
		if (Mage::helper('sales')->canSendNewInvoiceEmail($order->getStoreId())) {
			$invoice->sendEmail(true);
    		$invoice->setEmailSent(true);
    	}

    	$order->setBaseTotalPaid($totalPaid);
		$order->addStatusHistoryComment('Captured offline', false);
		
		$payment = $order->getPayment();

		$payment->setAdditionalInformation('OrderStatusEnum', $data['OrderStatus']);

		if ($payment->getAdditionalInformation('PaymentMethod') == 'mundipagg_creditcard') {
			$payment->setAdditionalInformation('CreditCardTransactionStatusEnum', $data['CreditCardTransaction']['CreditCardTransactionStatus']);
		}

		if ($payment->getAdditionalInformation('PaymentMethod') == 'mundipagg_boleto') {
			$payment->setAdditionalInformation('BoletoTransactionStatusEnum', $data['BoletoTransaction']['BoletoTransactionStatus']);
		}

		if (isset($data['OnlineDebitTransaction']['BankPaymentDate'])) {
			$payment->setAdditionalInformation('BankPaymentDate', $data['OnlineDebitTransaction']['BankPaymentDate']);
		}

		if (isset($data['OnlineDebitTransaction']['BankName'])) {
			$payment->setAdditionalInformation('BankName', $data['OnlineDebitTransaction']['BankName']);
		}

		if (isset($data['OnlineDebitTransaction']['Signature'])) {
			$payment->setAdditionalInformation('Signature', $data['OnlineDebitTransaction']['Signature']);
		}

		if (isset($data['OnlineDebitTransaction']['TransactionIdentifier'])) {
			$payment->setAdditionalInformation('TransactionIdentifier', $data['OnlineDebitTransaction']['TransactionIdentifier']);
		}

		$payment->save();

		if ($status == 'OverPaid' ||  $status == 'Overpaid') {
			$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, 'overpaid');
		} else {
			$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);
		}

		$order->save();

		return 'OK';
    }

	/**
	 * Mail error to Mage::getStoreConfig('trans_email/ident_custom1/email')
	 * @param string $message
	 */
	public function mailError($message = '') 
	{
		//Send email
		$mail = Mage::getModel('core/email');
	    $mail->setToName(Mage::getStoreConfig('trans_email/ident_custom1/name'));
	    $mail->setToEmail(Mage::getStoreConfig('trans_email/ident_custom1/email'));
	    $mail->setBody($message);
	    $mail->setSubject('=?utf-8?B?'.base64_encode(Mage::getStoreConfig('system/store/name').' - erro').'?=');
	    $mail->setFromEmail(Mage::getStoreConfig('trans_email/ident_sales/email'));
	    $mail->setFromName(Mage::getStoreConfig('trans_email/ident_sales/name'));
	    $mail->setType('html');
			
		$mail->send();
	}
}
