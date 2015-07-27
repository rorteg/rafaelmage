<?php

class Uecommerce_Mundipagg_Test_Selenium_BoletoTest extends Uecommerce_Mundipagg_Test_Selenium_Abstract {

    public function setUp() {
        $this->_installmentActive=false;
        $this->_additionalSaveSettings['payment/mundipagg_boleto/active'] = '1';
        parent::setUp();
        
    }
    
   
    public function testBoletoRegistered(){
        $this->_isLogged = false;
        $this->runMundipagg();
        $this->setBoleto();
        
    }
    
    /**
     * @depends testBoletoRegistered
     */
    public function testBoletoLogged(){
        $this->_isLogged = true;
        $this->runMundipagg();
        $this->setBoleto();
    }
    
    protected function setBoleto(){
        $this->clickButtonByContainer('shipping-method-buttons-container');
        sleep(self::$_defaultSleep);
        $this->byId('p_method_mundipagg_boleto')->click();
        $this->byId('mundipagg_boleto_boleto_taxvat')->value(self::$_custmerTest['taxvat']);
        $this->clickButtonByContainer('payment-buttons-container');
        sleep(self::$_defaultSleep);
        $this->clickButtonByContainer('review-buttons-container');
        sleep(self::$_defaultSleep);
        
        $elements = $this->byCssSelector('.pagSucess')->elements($this->using('css selector')->value('a'));
        foreach($elements as $element){
            $element->click();
        }
        sleep(self::$_defaultSleep);
        $this->assertContains('mundipagg',$this->url());
        
    }
    
}
