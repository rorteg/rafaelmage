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
        if ($address->getData('address_type') == 'billing') return $this;

		$this->_setAddress($address);

        parent::collect($address);

        $quote = $address->getQuote();

        if ($address->getAllItems()) {

            $payment = $quote->getPayment();

            if($payment && !empty($payment)) {

                //$paymentMethod = $quote->getPayment()->getMethod();



            }

        }
		
		if($ammount = $address->getQuote()->getInterest())
		{
			$this->_setBaseAmount($ammount);
			$this->_setAmount($address->getQuote()->getStore()->convertPrice($ammount, false));
			$address->setInterest($ammount);
		}
		else
		{
			$this->_setBaseAmount(0.00);
			$this->_setAmount(0.00);
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
}
