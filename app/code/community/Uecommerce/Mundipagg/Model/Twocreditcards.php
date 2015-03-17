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

class Uecommerce_Mundipagg_Model_Twocreditcards extends Uecommerce_Mundipagg_Model_Standard
{
    /**
     * Availability options
     */
    protected $_code = 'mundipagg_twocreditcards';
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
    protected $_canFetchTransactionInfo = true;
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
                $this->setParcelamento($standard->getConfigData('parcelamento'));
                $this->setParcelamentoMax($standard->getConfigData('parcelamento_max'));
                $this->setPaymentAction($standard->getConfigData('payment_action'));
                $this->setPaymentMethodCode(1);
                $this->setBankNumber(341);
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
        parent::assignData($data);

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

        return $this;
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