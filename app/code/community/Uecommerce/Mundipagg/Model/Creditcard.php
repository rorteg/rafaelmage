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

class Uecommerce_Mundipagg_Model_Creditcard extends Uecommerce_Mundipagg_Model_Standard
{
    /**
     * Availability options
     */
    protected $_code = 'mundipagg_creditcard';
    protected $_formBlockType = 'mundipagg/standard_form';
    protected $_infoBlockType = 'mundipagg/info';
    protected $_isGateway = true;
    protected $_canOrder  = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = false;
    protected $_canRefund = true;
    protected $_canVoid = true;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = true;
    protected $_canSaveCc = false;
    protected $_canFetchTransactionInfo = false;
    protected $_canManageRecurringProfiles = false;
    protected $_allowCurrencyCode = array('BRL', 'USD', 'EUR');
    protected $_isInitializeNeeded = true;

    public function __construct()
    {
        $standard = Mage::getModel('mundipagg/standard');

        switch ($standard->getEnvironment())
        {
            case 'localhost':
            case 'development':
            case 'staging':
            default:
                $this->setmerchantKey(trim($standard->getConfigData('merchantKeyStaging')));
                $this->setUrl(trim($standard->getConfigData('apiUrlStaging')));
                $this->setClearsale($standard->getConfigData('clearsale'));
                $this->setPaymentMethodCode(1);
                $this->setBankNumber(341);
                $this->setParcelamento($standard->getConfigData('parcelamento'));
                $this->setParcelamentoMax($standard->getConfigData('parcelamento_max'));
                $this->setPaymentAction($standard->getConfigData('payment_action'));
                $this->setDebug($standard->getConfigData('debug'));
                $this->setEnvironment($standard->getConfigData('environment'));
                $this->setCieloSku($standard->getConfigData('cielo_sku'));
                break;

            case 'production':
                $this->setmerchantKey(trim($standard->getConfigData('merchantKeyProduction')));
                $this->setUrl(trim($standard->getConfigData('apiUrlProduction')));
                $this->setClearsale($standard->getConfigData('clearsale'));
                $this->setParcelamento($standard->getConfigData('parcelamento'));
                $this->setParcelamentoMax($standard->getConfigData('parcelamento_max'));
                $this->setPaymentAction($standard->getConfigData('payment_action'));
                $this->setDebug($standard->getConfigData('debug'));
                $this->setEnvironment($standard->getConfigData('environment'));
                $this->setCieloSku($standard->getConfigData('cielo_sku'));
                break;
        }
    }

    /**
     * Armazena as informações passadas via formulário no frontend
     * @access public
     * @param array $data
     * @return Uecommerce_Mundipagg_Model_Standard
     */
    public function assignData($data) 
    {
        $info = $this->getInfoInstance();
        $this->resetInterest($info);
        if (isset($data[$this->_code.'_token_1_1']) && $data[$this->_code.'_token_1_1'] != 'new') {
            $parcelsNumber = $data[$this->_code.'_credito_parcelamento_1_1'];
        } else {
            $parcelsNumber = $data[$this->_code.'_new_credito_parcelamento_1_1'];
        }

        $cctype = $data[$this->_code.'_1_1_cc_type'];

        $interest = Mage::helper('mundipagg/installments')->getInterestForCard($parcelsNumber , $cctype);

        if ($interest > 0) {
            $this->applyInterest($info, $interest);
        } else {
            // If none of Cc parcels doens't have interest we reset interest
            $info = $this->resetInterest($info);
        }

        //$this->applyInterest($info,1000.0000);
        /* if (!($data instanceof Varien_Object)) {
             $data = new Varien_Object($data);
         }

         $info = $this->getInfoInstance();

         // Reset interests
         $interest = 0;
         $info = $this->resetInterest($info);

         if (isset($data[$this->_code.'_token_1_1']) && $data[$this->_code.'_token_1_1'] != 'new') {
             $parcelsNumber = $data[$this->_code.'_credito_parcelamento_1_1'];
         } else {
             $parcelsNumber = $data[$this->_code.'_new_credito_parcelamento_1_1'];
         }

         $withoutInterest = Mage::getStoreConfig('payment/mundipagg_standard/installment_without_interest');
         $interestValue = Mage::helper('mundipagg')->getJurosParcelaEscolhida($parcelsNumber)/100;

         // we check if there are interests for this parcel
         if($parcelsNumber > $withoutInterest) {
             $installmentValue = Mage::helper('mundipagg')->calcInstallmentValue($info->getQuote()->getGrandTotal(), $interestValue, $parcelsNumber);

             $installmentValue = round($installmentValue, 2);
             $interest = ($installmentValue * $parcelsNumber) - $info->getQuote()->getGrandTotal();

             //Mage::log('interest total: '.$interest);
         }

         // Apply interest
         if ($interest > 0) {
             $this->applyInterest($info, $interest);
         } else {
             // If none of Cc parcels doens't have interest we reset interest
             $info = $this->resetInterest($info);
         }*/

        parent::assignData($data);
    }

    /**
     * Prepare info instance for save
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function prepareSave()
    {
        parent::prepareSave();
    }

    /**
     * Instantiate state and set it to state object
     *
     * @param string $paymentAction
     * @param Varien_Object
     */
    public function initialize($paymentAction, $stateObject)
    {
        $standard = Mage::getModel('mundipagg/standard');

        switch($standard->getConfigData('payment_action')) {
            case 'order':
                $this->setCreditCardOperationEnum('AuthAndCapture');

                $paymentAction = $orderAction = 'order';
                break;

            case 'authorize':
                $this->setCreditCardOperationEnum('AuthOnly');

                $paymentAction = $orderAction = 'authorize';
                break;

            case 'authorize_capture':
                $this->setCreditCardOperationEnum('AuthAndCaptureWithDelay');

                $paymentAction = $orderAction = 'authorize_capture';
                break;
        }

        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();

        switch ($paymentAction) {
            case Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE:
                parent::authorize($payment, $order->getBaseTotalDue());
                break;

            case Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE:
                parent::authorize($payment, $order->getBaseTotalDue());
                break;

            case $orderAction:
                parent::order($payment, $order->getBaseTotalDue());
                break;

            default:
                parent::order($payment, $order->getBaseTotalDue());
                break;
        }
    }
}
