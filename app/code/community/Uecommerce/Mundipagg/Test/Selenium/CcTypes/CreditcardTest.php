<?php

class Uecommerce_Mundipagg_Test_Selenium_CcTypes_CreditcardTest extends Uecommerce_Mundipagg_Test_Selenium_CcTypes {

    public function setUp() {
        $this->_paymentType = 'creditcard';
        parent::setUp();
        
    }
    
    public function testCreditcardRegistered() {
        $this->_isLogged = false;
        $this->runAllCcFlagsValidations();
        $this->continueBuy();
    }
    
    /**
     * @depends testCreditcardRegistered
     */
    public function testCreditcardLogged(){
        $this->_isLogged = true;
        //$this->runCardonfile();
        $this->runAllCcFlagsValidations();
        $this->continueBuy();
    }

}
