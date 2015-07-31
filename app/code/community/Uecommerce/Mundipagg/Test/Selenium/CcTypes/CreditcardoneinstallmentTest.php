<?php

class Uecommerce_Mundipagg_Test_Selenium_CcTypes_CreditcardoneinstallmentTest extends Uecommerce_Mundipagg_Test_Selenium_CcTypes {

    public function setUp() {
        $this->_paymentType = 'creditcardoneinstallment';
        parent::setUp();
        
    }
    
    

    public function testCreditcardoneinstallmentRegistered() {
//        $this->markTestSkipped();
//        return false;
        $this->_isLogged = false;
        $this->runAllCcFlagsValidations();
        $this->continueBuy();
    }
    
    /**
     * @depends testCreditcardoneinstallmentRegistered
     */
    public function testCreditcardoneinstallmentLogged(){
        $this->_isLogged = true;
        $this->runCardonfile();
        $this->continueBuy();
    }

}
