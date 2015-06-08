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

class Uecommerce_Mundipagg_Block_Standard_Form extends Mage_Payment_Block_Form
{
    protected function _construct() 
    {
        parent::_construct();

    	$this->setTemplate('mundipagg/form.phtml');

        // Get Customer Credit Cards Saved On File
        if ($this->helper('customer')->isLoggedIn()) {
            $entityId = Mage::getSingleton('customer/session')->getId();

            $ccsCollection = Mage::getResourceModel('mundipagg/cardonfile_collection')
                ->addEntityIdFilter($entityId)
                ->addExpiresAtFilter();

            $this->setCcs($ccsCollection);
        } else if (Mage::getSingleton('adminhtml/session_quote')->getQuote()->getCustomerId()) { 
            $entityId = Mage::getSingleton('adminhtml/session_quote')->getQuote()->getCustomerId();

            $ccsCollection = Mage::getResourceModel('mundipagg/cardonfile_collection')
                ->addEntityIdFilter($entityId)
                ->addExpiresAtFilter();

            $this->setCcs($ccsCollection);
        } else {
            $this->setCcs(array());
        }
    }
    
    /**
     * Return Standard model
     */
    public function getStandard() 
    {
    	return Mage::getModel('mundipagg/standard');
    }

    /**
    * Get installments 
    *
    * Thanks to Fillipe Almeida Dutra
    */
    public function getInstallments($ccType = null)
    {
        $session = Mage::getSingleton('admin/session');

        if ($session->isLoggedIn()) {
            $quote = Mage::getSingleton('adminhtml/session_quote')->getQuote();
        } else {
            $quote = Mage::getModel('checkout/session')->getQuote();
        }

        $quote->setMundipaggInterest(0.0);
        $quote->setMundipaggBaseInterest(0.0);
        $quote->setTotalsCollectedFlag(false)->collectTotals();
        $quote->save();

        return Mage::helper('mundipagg/installments')->getInstallmentForCreditCardType($ccType);

        // Payment Methods
        $paymentMethods = $this->getStandard()->getPaymentMethods();

        $removeFirstInstallment = false;

        if (in_array('mundipagg_creditcardoneinstallment', $paymentMethods)) {
            $removeFirstInstallment = true;
        }

        $session = Mage::getSingleton('admin/session');

        if ($session->isLoggedIn()) {
            $quote = Mage::getSingleton('adminhtml/session_quote')->getQuote();
            
            if($quote->isVirtual()) {
                $data = $quote->getBillingAddress();
            } else {
                $data = $quote->getShippingAddress();
            }

            $baseGrandTotal = $data->getBaseGrandTotal();
        } else {
            $quote = Mage::getSingleton('checkout/cart')->getQuote();

            if($quote->isVirtual()) {
                $data = $quote->getBillingAddress();
            } else {
                $data = $quote->getShippingAddress();
            }

            $baseGrandTotal = $data->getBaseGrandTotal();
        }

        // pega dados de parcelamento
        $maxInstallments = intval(Mage::getStoreConfig('payment/mundipagg_standard/parcelamento_max')) ? intval(Mage::getStoreConfig('payment/mundipagg_standard/parcelamento_max')) : 12;
        $minInstallmentValue = floatval(Mage::getStoreConfig('payment/mundipagg_standard/parcelamento_min')) ? floatval(Mage::getStoreConfig('payment/mundipagg_standard/parcelamento_min')) : '';
        
        // Não pode parcelar
        if($baseGrandTotal < $minInstallmentValue) {
            $maxInstallments = 1;
        }

        // Não ter valor mínimo para parcelar OU Parcelar a partir de um valor mínimo
        if($minInstallmentValue == 0) {
            $maxInstallments = $maxInstallments;
        }

        // Parcelar a partir de um valor mínimo
        if($minInstallmentValue > 0 && $baseGrandTotal >= $minInstallmentValue) {
            $maxInstallments = $maxInstallments;
        }                                       

        // Por faixa de valores
        if($minInstallmentValue == '') {
            $p = 1;

            for($p = 1; $p <= $maxInstallments; $p++) {
                if($p == 1) {
                    $de         = 0;
                    $parcelaDe = 0;
                } else {
                    $de         = 'parcelamento_de'.$p;
                    $parcelaDe = Mage::getStoreConfig('payment/mundipagg_standard/'.$de);
                }

                $ate = 'parcelamento_ate'.$p;
                
                if($p < $maxInstallments) {
                    $parcelaAte = Mage::getStoreConfig('payment/mundipagg_standard/'.$ate);
                } else {
                    $parcelaAte = '1000000000';
                }
                
                if($parcelaDe >= 0 && $parcelaAte >= $parcelaDe) {
                    if($baseGrandTotal >= $parcelaDe AND $baseGrandTotal <= $parcelaAte) {
                        $maxInstallments = $p;
                    }
                } else {
                    $maxInstallments = $p-1;
                }
            }
        }

        // Atualiza taxa de juros para 0 caso o usuario tenha voltado na navegacao
        $quote = Mage::getSingleton('checkout/cart')->getQuote();
        $quote->setMundipaggInterest(0.0);
        $quote->setMundipaggBaseInterest(0.0);
        $quote->setTotalsCollectedFlag(false)->collectTotals();
        $quote->save();
        
        // pega dados de juros
        $wI = Mage::getStoreConfig('payment/mundipagg_standard/installment_without_interest');

        $withoutInterest = intval($wI) ? intval($wI) : 12;
        
        $interest = 0;
        
        // pega valores do pedido
        if ($session->isLoggedIn()) {
            $total = Mage::getSingleton('adminhtml/session_quote')->getQuote()->getShippingAddress()->getBaseGrandTotal();
        } else {
            $total = Mage::getSingleton('checkout/cart')->getQuote()->getGrandTotal();
        }

        $installments = array();
        
        for($i = 1; $i <= $maxInstallments; $i++) {
            $orderTotal = $baseGrandTotal;
            $installmentValue = round($orderTotal / $i, 2);

            // caso nao haja juros na parcela
            if($i <= $withoutInterest) {
                $installmentValue = $orderTotal / $i;
            } else { 
                // caso haja juros
                $interest = Mage::helper('mundipagg')->getJurosParcelaEscolhida($i)/100;

                //Mage::log($i.': interest: '.$interest);
                //Mage::log($i. ': total: '. $orderTotal);

                $installmentValue = Mage::helper('mundipagg')->calcInstallmentValue($orderTotal, $interest, $i);
                //$orderTotal = $i * $installmentValue;
            }
            
            // confere se a parcela nao está abaixo do minimo
            if($minInstallmentValue >= 0 && $installmentValue < $minInstallmentValue) {
                break;
            }
            
            // confere se a parcela não está abaixo do minimo
            if($minInstallmentValue >= 0 && $installmentValue < $minInstallmentValue) {
                break;
            }

            //Mage::log($i.': installmentValue: '. $installmentValue);
            
            // monta o texto da parcela
            if($i == 1) {
                $label = "À vista (" . Mage::helper('core')->currency(($baseGrandTotal), true, false) . ")";
            } else {
                if($interest > 0) {
                    $label = $i . "x com juros (" . Mage::helper('core')->currency(($installmentValue), true, false) . " cada)";
                } else {
                    $label = $i . "x sem juros (" . Mage::helper('core')->currency(($installmentValue), true, false) . " cada)";
                }
            }
            
            // adiciona no array de parcelas
            if ($i == 1  && $removeFirstInstallment == true) {
                
            } else {
                $installments[] = array("num" => $i, "label" => $this->htmlEscape($label));
            }
        }
        
        // caso o valor da parcela minima seja maior do que o valor da compra, 
        // deixa somente opção à vista
        if($minInstallmentValue > $baseGrandTotal) {
            $label = "À vista (" . Mage::helper('core')->currency(($baseGrandTotal), true, false) . ")";
            $installments[] = array("num" => 1, "label" => $this->htmlEscape($label));
        }
        
        return $installments;
    }
}
