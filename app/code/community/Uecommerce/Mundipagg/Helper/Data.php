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

class Uecommerce_Mundipagg_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
    * Get extension version
    */
    public function getExtensionVersion() 
    {
        return (string) Mage::getConfig()->getNode()->modules->Uecommerce_Mundipagg->version;
    }

    /**
     * Return issuer
     * @param varchar $cardType
     */
    public function issuer($cardType) 
    {
        if ( $cardType == '') {
            return '';
        } else {
            $issuers = array(
                'VI' => 'Visa',
                'MC' => 'Mastercard',
                'AE' => 'Amex',
                'DI' => 'Diners',
                'HI' => 'Hipercard',
                'EL' => 'Elo',
            );
            
            foreach ($issuers as $key => $issuer) {
                if ($key == $cardType) {
                    return $issuer;
                }
            }
        }
    }

    /**
    * Get credit cards number
    */
    public function getCreditCardsNumber($payment_method)
    {  
        $num = 1;

        switch ($payment_method) {
            case 'mundipagg_creditcardoneinstallment':
                $num = 0;
                break;
                
            case 'mundipagg_creditcard':
                $num = 1;
                break;

            case 'mundipagg_twocreditcards':
                $num = 2;
                break;

            case 'mundipagg_threecreditcards':
                $num = 3;
                break;

            case 'mundipagg_fourcreditcards':
                $num = 4;
                break;

            case 'mundipagg_fivecreditcards':
                $num = 5;
                break;
        }

        return $num;
    }

    /**
    * Return payment method
    */
    public function getPaymentMethod($num)
    {
        $method = '';

        switch ($num) {
            case '0':
                $method = 'mundipagg_creditcardoneinstallment';
                break;
            case '1':
                $method = 'mundipagg_creditcard';
                break;
            case '2':
                $method = 'mundipagg_twocreditcards';
                break;
            case '3':
                $method = 'mundipagg_threecreditcards';
                break;
            case '4':
                $method = 'mundipagg_fourcreditcards';
                break;
            case '5':
                $method = 'mundipagg_fivecreditcards';
                break;
        }

        return $method;
    }

    public function validateExpDate($expYear, $expMonth)
    {
        $date = Mage::app()->getLocale()->date();
        if (!$expYear || !$expMonth || ($date->compareYear($expYear) == 1)
            || ($date->compareYear($expYear) == 0 && ($date->compareMonth($expMonth) == 1))
        ) {
            return false;
        }
        return true;
    }

    /**
     * Validate credit card number
     *
     * @param   string $cc_number
     * @return  bool
     */
    public function validateCcNum($ccNumber)
    {
        $cardNumber = strrev($ccNumber);
        $numSum = 0;

        for ($i=0; $i<strlen($cardNumber); $i++) {
            $currentNum = substr($cardNumber, $i, 1);

            /**
             * Double every second digit
             */
            if ($i % 2 == 1) {
                $currentNum *= 2;
            }

            /**
             * Add digits of 2-digit numbers together
             */
            if ($currentNum > 9) {
                $firstNum = $currentNum % 10;
                $secondNum = ($currentNum - $firstNum) / 10;
                $currentNum = $firstNum + $secondNum;
            }

            $numSum += $currentNum;
        }

        /**
         * If the total has no remainder it's OK
         */
        return ($numSum % 10 == 0);
    }

    /**
    * Validate CPF
    */ 
    public function validateCPF($cpf)
    {   
        // Verifiva se o número digitado contém todos os digitos
        $cpf = preg_replace('[\D]', '', $cpf);
        
        // Verifica se nenhuma das sequências abaixo foi digitada, caso seja, retorna falso
        if (strlen($cpf) != 11 || 
            $cpf == '00000000000' || 
            $cpf == '11111111111' || 
            $cpf == '22222222222' || 
            $cpf == '33333333333' || 
            $cpf == '44444444444' || 
            $cpf == '55555555555' || 
            $cpf == '66666666666' || 
            $cpf == '77777777777' || 
            $cpf == '88888888888' || 
            $cpf == '99999999999') {
            return false;
        } else {   // Calcula os números para verificar se o CPF é verdadeiro
            for ($t = 9; $t < 11; $t++) {
                for ($d = 0, $c = 0; $c < $t; $c++) {
                    $d += $cpf{$c} * (($t + 1) - $c);
                }

                $d = ((10 * $d) % 11) % 10;

                if ($cpf{$c} != $d) {
                    return false;
                }
            }

            return true;
        }
    }

    /**
    * Validate CNPJ
    */
    public function validateCNPJ($value)
    { 
        $cnpj = str_replace(array("-"," ","/","."), "", $value);
        $digitosIguais = 1;

        if (strlen($cnpj) < 14 && strlen($cnpj) < 15) {
            return false;
        }
        for ($i = 0; $i < strlen($cnpj) - 1; $i++) {
 
            if ($cnpj{$i} != $cnpj{$i + 1}) {
                $digitosIguais = 0;
                break;
            }
        }
        
        if (!$digitosIguais) {
            $tamanho = strlen($cnpj) - 2;
            $numeros = substr($cnpj, 0, $tamanho);
            $digitos = substr($cnpj, $tamanho);
            $soma = 0;
            $pos = $tamanho - 7;
            for ($i = $tamanho; $i >= 1; $i--) {
                $soma += $numeros{$tamanho - $i} * $pos--;
                if ($pos < 2) {
                    $pos = 9;
                }
            }
            $resultado = ($soma % 11 < 2 ? 0 : 11 - $soma % 11);
            if ($resultado != $digitos{0}) {
                return false;
            }
            $tamanho = $tamanho + 1;
            $numeros = substr($cnpj, 0, $tamanho);
            $soma = 0;
            $pos = $tamanho - 7;
            for ($i = $tamanho; $i >= 1; $i--) {
                $soma += $numeros{$tamanho - $i} * $pos--;
                if ($pos < 2) {
                    $pos = 9;
                }
            }
            $resultado = ($soma % 11 < 2 ? 0 : 11 - $soma % 11);
            if ($resultado != $digitos{1}) {
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * Retorna o valor de uma parcela
     * - valor total a ser parcelado
     * - taxa de juros
     * - numero de prestacoes
     *
     * Thanks to Fillipe Almeida Dutra
     */
    public function calcInstallmentValue($total, $interest, $periods)
    {
        /* 
         * Formula do coeficiente:
         * 
         * juros / ( 1 - 1 / (1 + i)^n )
         * 
         */
        
        // calcula o coeficiente, seguindo a formula acima
        $coefficient = pow((1 + $interest), $periods);
        $coefficient = 1 / $coefficient;
        $coefficient = 1 - $coefficient;
        $coefficient = $interest / $coefficient;
        
        // retorna o valor da parcela
        return ($total * $coefficient);
    }

    /**
    * Apply telephone mask
    */ 
    public function applyTelephoneMask($string)
    {
        $string = preg_replace('[\D]', '', $string);

        $length = strlen($string);

        switch ($length) {
            case 10:
                $mask = '(##)########';
                break;

            case 11:
                $mask = '(##)#########';
                break;
            
            default:
                return '';
        }

        for($i=0;$i<strlen($string);$i++) {
            $mask[strpos($mask,"#")] = $string[$i];
        }
       
        return '55'.$mask;
    }
}