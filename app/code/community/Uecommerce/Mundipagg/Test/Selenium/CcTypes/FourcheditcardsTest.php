<?php

class Uecommerce_Mundipagg_Test_Selenium_CcTypes_FourcreditcardisTest extends Uecommerce_Mundipagg_Test_Selenium_CcTypes {

    public function setUp() {
        $this->_paymentType = 'fourcreditcards';
        $this->_ccLength = 4;
        parent::setUp();
        
    }
    
    public function testFourCreditcardsRegistered() {
        $this->markTestSuiteSkipped('Test');
        $this->_isLogged = false;
        
        $this->_values = array(
            1 => '3,74',
            2 => '3,74',
            3 => '3,74',
            4 => '5,00'
        );
        $this->runAllCcFlagsValidations();
        $this->continueBuy();
    }
    
    /**
     * @depends testFourCreditcardsRegistered
     */
    public function testFourCreditcardsLogged(){
        $this->_isLogged = true;
        $this->_values = array(
            1 => '3,74',
            2 => '3,74',
            3 => '3,74',
            4 => '5,00'
        );
        //$this->runCardonfile();
        $this->runAllCcFlagsValidations();
        $this->continueBuy();
    }

}
