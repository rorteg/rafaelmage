<?php

class Uecommerce_Mundipagg_Test_Selenium_CcTypes_FivecreditcardisTest extends Uecommerce_Mundipagg_Test_Selenium_CcTypes {

    public function setUp() {
        $this->_paymentType = 'fivecreditcards';
        $this->_ccLength = 5;
        parent::setUp();
        
    }
    
    public function testFiveCreditcardsRegistered() {
        $this->markTestSuiteSkipped('Test');
        $this->_isLogged = false;
        
        $this->_values = array(
            1 => '2,22',
            2 => '3,00',
            3 => '3,00',
            4 => '3,00',
            5 => '5,00'
        );
        $this->runAllCcFlagsValidations();
        $this->continueBuy();
    }
    
    /**
     * @depends testFiveCreditcardsRegistered
     */
    public function testFiveCreditcardsLogged(){
        $this->_isLogged = true;
        $this->_values = array(
            1 => '2,22',
            2 => '3,00',
            3 => '3,00',
            4 => '3,00',
            5 => '5,00'
        );
        //$this->runCardonfile();
        $this->runAllCcFlagsValidations();
        $this->continueBuy();
    }

}
