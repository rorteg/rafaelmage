<?php

class Uecommerce_Mundipagg_Test_Selenium_CcTypes_ThreecreditcardisTest extends Uecommerce_Mundipagg_Test_Selenium_CcTypes {

    public function setUp() {
        $this->_paymentType = 'threecreditcards';
        $this->_ccLength = 3;
        parent::setUp();
        
    }
    
    public function testThreeCreditcardsRegistered() {
        $this->markTestSuiteSkipped('Test');
        $this->_isLogged = false;
        
        $this->_values = array(
            1 => '5,61',
            2 => '5,61',
            3 => '5,00'
        );
        $this->runAllCcFlagsValidations();
        $this->continueBuy();
    }
    
    /**
     * @depends testThreeCreditcardsRegistered
     */
    public function testThreeCreditcardsLogged(){
        $this->_isLogged = true;
        $this->_values = array(
            1 => '5,61',
            2 => '5,61',
            3 => '5,00'
        );
        //$this->runCardonfile();
        $this->runAllCcFlagsValidations();
        $this->continueBuy();
    }

}
