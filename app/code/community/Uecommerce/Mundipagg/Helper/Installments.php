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

    class Uecommerce_Mundipagg_Helper_Installments extends Mage_Core_Helper_Abstract
    {

        protected function _fixQty($qty)
        {
            return (!empty($qty) ? (float)$qty : null);
        }

        public function getInstallments($store = null, $ccType = "installments") {
            $value = Mage::getStoreConfig("payment/mundipagg_standard/".$ccType, $store);
            $value = $this->_unserializeValue($value);
            return $value;
        }

        protected function _unserializeValue($value)
        {
            if (is_string($value) && !empty($value)) {
                return unserialize($value);
            } else {
                return array();
            }
        }

        protected function _isEncodedArrayFieldValue($value)
        {
            if (!is_array($value)) {
                return false;
            }
            unset($value['__empty']);
            foreach ($value as $_id => $row) {
                if (!is_array($row) || !array_key_exists('installment_boundary', $row) || !array_key_exists('installment_frequency', $row ) || !array_key_exists('installment_interest', $row )) {
                    return false;
                }
            }
            return true;
        }

        protected function _decodeArrayFieldValue(array $value)
        {
            $result = array();
            unset($value['__empty']);
            foreach ($value as $_id => $row) {
                if (!is_array($row) || !array_key_exists('installment_boundary', $row) || !array_key_exists('installment_frequency', $row) || !array_key_exists('installment_interest', $row)) {
                    continue;
                }
                //$currency = $row['installment_currency'];
                $boundary = $row['installment_boundary'];
                $frequency = $row['installment_frequency'];
                $interest = $row['installment_interest'];
                $result[] = array($boundary,$frequency,$interest);
            }
            return $result;
        }

        protected function _encodeArrayFieldValue(array $value)
        {
            $result = array();
            foreach ($value as $triplet){

                //$currency = (isset($triplet[0])) ? $triplet[0] : "";
                $boundary = (isset($triplet[0])) ? $triplet[0] : "";
                $frequency = (isset($triplet[1])) ? $triplet[1] : "";
                $interest = (isset($triplet[2])) ? $triplet[2] : "";

                $_id = Mage::helper('core')->uniqHash('_');
                $result[$_id] = array(
                    //'installment_currency' => $currency,
                    'installment_boundary' => $boundary,
                    'installment_frequency' => $frequency,
                    'installment_interest' => $interest
                );
            }
            return $result;
        }

        protected function _serializeValue($value)
        {
            return serialize($value);
        }

        public function makeArrayFieldValue($value)
        {
            $value = $this->_unserializeValue($value);
            if (!$this->_isEncodedArrayFieldValue($value)) {
                $value = $this->_encodeArrayFieldValue($value);
            }
            return $value;
        }

        public function makeStorableArrayFieldValue($value)
        {
            if ($this->_isEncodedArrayFieldValue($value)) {
                $value = $this->_decodeArrayFieldValue($value);
            }
            $value = $this->_serializeValue($value);
            return $value;
        }

        public function getConfigValue($amount, $store = null, $ccType = "installments")
        {
            $value = $this->getInstallments($store, $ccType);

            if ($this->_isEncodedArrayFieldValue($value)) {
                $value = $this->_decodeArrayFieldValue($value);
            }
            $cur_minimal_boundary = -1;
            $resulting_freq = 1;
            foreach ($value as $row) {
                list($boundary,$frequency) = $row;

                    if($amount <= $boundary && ($boundary <= $cur_minimal_boundary || $cur_minimal_boundary == -1) ) {
                        $cur_minimal_boundary = $boundary;
                        $resulting_freq = $frequency;
                    }
                    if($boundary == "" && $cur_minimal_boundary == -1){
                        $resulting_freq = $frequency;
                    }


            }
            return $resulting_freq;
        }

        public function isInstallmentsEnabled($store = null){
            $value = Mage::getStoreConfig("payment/mundipagg_standard/enable_installments", $store);
            return $value;
        }

        public function getInstallmentForCreditCardType($ccType = null,$amount = null) {


            $quote = (Mage::getModel('checkout/type_onepage') !== false)? Mage::getModel('checkout/type_onepage')->getQuote(): Mage::getModel('checkout/session')->getQuote();

            //$currency = $quote->getQuoteCurrencyCode();
            if(!$amount) {
                $amount = (double)$quote->getGrandTotal();
            }
            $amount = str_replace(',','.',$amount);
            $ccTypeInstallments = "installments_".$ccType;

            $all_installments = $this->getInstallments(null, $ccTypeInstallments);

            if(empty($all_installments)) {
                $ccTypeInstallments = null;
            } else {
                $max_installments = $this->getConfigValue($amount, null, $ccTypeInstallments);
            }

            // Fallback to the default installments if creditcard type has no one configured
            if($ccTypeInstallments == null) {
                $max_installments = $this->getConfigValue($amount, null);
                $all_installments = $this->getInstallments();
            }

            // result array here
            for($i=1;$i<=$max_installments;$i++){

                // check if installment has extra interest
                $key = $i-1;
                if(!array_key_exists($key,$all_installments)){
                    $all_installments[$key] = array();
                }
                $installment = $all_installments[$key];
                if(isset($installment[2]) && $installment[2] > 0) {
                    $total_amount_with_interest = $amount + ($amount * ($installment[2] / 100));
                } else {
                    $total_amount_with_interest = $amount;
                }

                $partial_amount = ((double)$total_amount_with_interest)/$i;
                $result[(string)$i] = $i."x ".Mage::helper('core')->formatPrice($partial_amount, false);
            }
            return $result;
        }
    }