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

class Uecommerce_Mundipagg_Model_Threecreditcards extends Uecommerce_Mundipagg_Model_Standard
{
    /**
     * Availability options
     */
    protected $_code = 'mundipagg_threecreditcards';
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

        parent::assignData($data);
        
        

        $cctype1 = $data[$this->_code.'_3_1_cc_type'];

        if (isset($data[$this->_code.'_token_3_1']) && $data[$this->_code.'_token_3_1'] != 'new') {
            $parcelsNumber1 = $data[$this->_code.'_credito_parcelamento_3_1'];
            $cardonFile = Mage::getModel('mundipagg/cardonfile')->load($data[$this->_code.'_token_3_1']);
            $cctype1 = Mage::getSingleton('mundipagg/source_cctypes')->getCcTypeForLabel($cardonFile->getCcType());
            $value1 = $data[$this->_code.'_value_3_1'];
        } else {
            $parcelsNumber1 = $data[$this->_code.'_new_credito_parcelamento_3_1'];
            $value1 = $data[$this->_code.'_new_value_3_1'];
        }

        $cctype2 = $data[$this->_code.'_3_2_cc_type'];

        if (isset($data[$this->_code.'_token_3_2']) && $data[$this->_code.'_token_3_2'] != 'new') {
            $parcelsNumber2 = $data[$this->_code.'_credito_parcelamento_3_2'];
            $cardonFile = Mage::getModel('mundipagg/cardonfile')->load($data[$this->_code.'_token_3_2']);
            $cctype2 = Mage::getSingleton('mundipagg/source_cctypes')->getCcTypeForLabel($cardonFile->getCcType());
            $value2 = $data[$this->_code.'_value_3_2'];
        } else {
            $parcelsNumber2 = $data[$this->_code.'_new_credito_parcelamento_3_2'];
            $value2 = $data[$this->_code.'_new_value_3_2'];
        }

        $cctype3 = $data[$this->_code.'_3_3_cc_type'];

        if (isset($data[$this->_code.'_token_3_3']) && $data[$this->_code.'_token_3_3'] != 'new') {
            $parcelsNumber3 = $data[$this->_code.'_credito_parcelamento_3_3'];
            $cardonFile = Mage::getModel('mundipagg/cardonfile')->load($data[$this->_code.'_token_3_3']);
            $cctype3 = Mage::getSingleton('mundipagg/source_cctypes')->getCcTypeForLabel($cardonFile->getCcType());
            $value3 = $data[$this->_code.'_value_3_3'];
        } else {
            $parcelsNumber3 = $data[$this->_code.'_new_credito_parcelamento_3_3'];
            $value3 = $data[$this->_code.'_new_value_3_3'];
        }

        $interest1 = 0;
        $interest2 = 0;
        $interest3 = 0;
        $interestInformation = array();
        if(Mage::app()->getRequest()->getActionName() == 'partialPost'){
            $keyCode = $this->_code.'_partial';
            $interestInformation = $info->getAdditionalInformation('mundipagg_interest_information');
        }else{
            $keyCode = $this->_code;
        }
        
        if($cctype1) {
            $interest1 = Mage::helper('mundipagg/installments')->getInterestForCard($parcelsNumber1 , $cctype1, $value1);
            $interestInformation[$keyCode.'_3_1'] = new Varien_Object();
            $interestInformation[$keyCode.'_3_1']->setInterest(str_replace(',','.',$interest1))->setValue(str_replace(',','.',$value1));
        }

        if($cctype2) {
            $interest2 = Mage::helper('mundipagg/installments')->getInterestForCard($parcelsNumber2 , $cctype2, $value2);
            $interestInformation[$keyCode.'_3_2'] = new Varien_Object();
            $interestInformation[$keyCode.'_3_2']->setInterest(str_replace(',','.',$interest2))->setValue(str_replace(',','.',$value2));
        }

        if($cctype3) {
            $interest3 = Mage::helper('mundipagg/installments')->getInterestForCard($parcelsNumber3 , $cctype3, $value3);
            $interestInformation[$keyCode.'_3_3'] = new Varien_Object();
            $interestInformation[$keyCode.'_3_3']->setInterest(str_replace(',','.',$interest3))->setValue(str_replace(',','.',$value3));
        }

        $interest = $interest1+$interest2+$interest3;

        if ($interest > 0) {
            $info->setAdditionalInformation('mundipagg_interest_information', array());
            $info->setAdditionalInformation('mundipagg_interest_information',$interestInformation);
            $this->applyInterest($info, $interest);
            
        } else {
            // If none of Cc parcels doens't have interest we reset interest
            $this->resetInterest($info);
        }

        return $this;
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
        $this->setCreditCardOperationEnum('AuthAndCapture');
        
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();

        parent::authorize($payment, $order->getBaseTotalDue());
    }
}