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
 * @copyright  Copyright (c) 2014 Uecommerce (http://www.uecommerce.com.br/)
 * @license    http://www.uecommerce.com.br/
 */

/**
 * Mundipagg Payment module
 *
 * @category   Uecommerce
 * @package    Uecommerce_Mundipagg
 * @author     Uecommerce Dev Team
 */

class Uecommerce_Mundipagg_Model_Quote_Address_Interest extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
    /** 
     * Constructor that should initiaze 
     */
    public function __construct()
    {
        $this->setCode('interest');
    }

    /**
     * Used each time when collectTotals is invoked
     * 
     * @param Mage_Sales_Model_Quote_Address $address
     * @return Your_Module_Model_Total_Custom
     */
    
	public function collect(Mage_Sales_Model_Quote_Address $address)
	{
        //if ($address->getData('address_type') == 'billing') return $this;


		$this->_setAddress($address);

        parent::collect($address);

        $quote = $address->getQuote();
        $amount = $quote->getInterest();

        if($amount > 0){
            $this->_setBaseAmount(0.00);
            $this->_setAmount(0.00);

            $address->setInterest($quote->getInterest());
            $quote->setInterest(0);
            $address->setGrandTotal($address->getGrandTotal() + $amount);
            $address->setBaseGrandTotal($address->getBaseGrandTotal() + $amount);

        }


		
		return $this;
	}

    /**
     * Used each time when totals are displayed
     * 
     * @param Mage_Sales_Model_Quote_Address $address
     * @return Your_Module_Model_Total_Custom
     */
    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        if ($address->getInterest() != 0) 
        {
            $address->addTotal(array
            (
                'code' => $this->getCode(),
                'title' => Mage::helper('mundipagg')->__('Interest'),
                'value' => $address->getInterest()
            ));
        }
    }

    protected function getTotalsInterest($installments, $ccType, Mage_Sales_Model_Quote_Address $address){

        $numberOfInstallments = $installments;
        $currentAmount = $address->getQuote()->getInterest();

        if($numberOfInstallments > 0)
        {

            $ccTypeInstallments = "installments_".$ccType;

            $all_installments = Mage::helper('mundipagg/installments')->getInstallments(null, $ccTypeInstallments);
            if(empty($all_installments)) {
                $all_installments = Mage::helper('mundipagg/installments')->getInstallments();
            }

            $installmentKey = $numberOfInstallments - 1;

            $installment = $all_installments[$installmentKey];

            if($installment != null && is_array($installment)) {

                // check if interest rate is filled in
                if(isset($installment[2]) && $installment[2] > 0) {
                    $this->_setAmount(0);
                    $this->_setBaseAmount(0);

                    $interestRate = $installment[2];
                    $grandTotal = $address->getGrandTotal();
                    $fee = ($grandTotal / 100) * $interestRate;

                    $balance = $fee - $currentAmount;

                    $address->setInterest($address->getQuote()->getStore()->convertPrice($balance));
                    $address->setBaseInterest($balance);

                    $address->setGrandTotal($address->getGrandTotal() + $address->getInterest());
                    $address->setBaseGrandTotal($address->getBaseGrandTotal() + $address->getBaseInterest());

                    //Mage::log($address->getGrandTotal());

                    return $this;
                }
            }
        }
    }
}
