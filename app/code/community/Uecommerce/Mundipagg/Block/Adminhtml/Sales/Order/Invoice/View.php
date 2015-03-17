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

class Uecommerce_Mundipagg_Block_Adminhtml_Sales_Order_Invoice_View extends Mage_Adminhtml_Block_Sales_Order_Invoice_View
{
	public function __construct()
    {
    	parent::__construct();

    	$this->_removeButton('void');

    	$orderPayment = $this->getInvoice()->getOrder()->getPayment();

    	/*
    	Mage::log('_isAllowedAction("creditmemo"): '.$this->_isAllowedAction('creditmemo'), null, 'Uecommerce_Mundipagg.log');
    	Mage::log('canRefundPartialPerInvoice: '.$orderPayment->canRefundPartialPerInvoice(), null, 'Uecommerce_Mundipagg.log' );
        Mage::log('canRefund: '.$orderPayment->canRefund(), null, 'Uecommerce_Mundipagg.log');
        Mage::log('orderPayment->getAmountPaid(): '.$orderPayment->getAmountPaid(), null, 'Uecommerce_Mundipagg.log');
        Mage::log('orderPayment->getAmountRefunded(): '.$orderPayment->getAmountRefunded(), null, 'Uecommerce_Mundipagg.log');
        Mage::log('orderPayment->canRefund(): '.$this->getInvoice()->canRefund(), null, 'Uecommerce_Mundipagg.log' );
        Mage::log('this->getInvoice()->getIsUsedForRefund: '.$this->getInvoice()->getIsUsedForRefund(), null, 'Uecommerce_Mundipagg.log' );
        */

        if ($this->_isAllowedAction('creditmemo') ) {
            if (($orderPayment->canRefundPartialPerInvoice()
                && $this->getInvoice()->canRefund()
                && $orderPayment->getAmountPaid() > $orderPayment->getAmountRefunded())
                || ($orderPayment->canRefund() && !$this->getInvoice()->getIsUsedForRefund())) {
                $this->_addButton('capture', array( // capture?
                    'label'     => Mage::helper('sales')->__('Credit Memo'),
                    'class'     => 'go',
                    'onclick'   => 'setLocation(\''.$this->getCreditMemoUrl().'\')'
                    )
                );
            }
        }
    }
}