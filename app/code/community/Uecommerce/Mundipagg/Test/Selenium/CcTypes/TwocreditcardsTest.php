<?php

class Uecommerce_Mundipagg_Test_Selenium_CcTypes_TwocreditcardsTest extends Uecommerce_Mundipagg_Test_Selenium_CcTypes {

    public function setUp() {
        $this->_paymentType = 'twocreditcards';
        $this->_ccLength = 2;
        parent::setUp();
        
    }
    
    public function testTwoCreditcardsRegistered() {
        $this->_isLogged = false;
        
        $this->_values = array(
            1 => '5',
//            2 => '6,22'
        );
        $this->runAllCcFlagsValidations();
        $this->continueBuy();
    }
    
    /**
     * @depends testTwoCreditcardsRegistered
     */
    public function testTwoCreditcardsLogged(){
        $this->_isLogged = true;
        $this->_values = array(
            1 => '5',
//            2 => '6,22'
        );
        //$this->runCardonfile();
        $this->runAllCcFlagsValidations();
        $this->continueBuy();
    }

}
