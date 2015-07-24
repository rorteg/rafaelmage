<?php

class Uecommerce_Mundipagg_Test_Selenium_CheckoutTest extends Codex_Xtest_Xtest_Selenium_TestCase
{

    protected static $_customerEmail;
    protected static $_customerPassword;
    
   public function setUp()
    {
        parent::setUp();

//        $customerConfig = self::getSeleniumConfig('checkout/customer');
//        self::$_customerEmail = $customerConfig['email'];
//
//        // Delete Testcustomer
//        $customerCol = Mage::getModel('customer/customer')->getCollection();
//        $customerCol->addFieldToFilter('email', self::$_customerEmail );
//        $customerCol->walk('delete');
//
//        // Create new one
//        $customer = Mage::getModel('customer/customer');
//        $customer->setData($customerConfig);
//        self::$_customerPassword = $customer->generatePassword();
//        $customer->setStore( current( Mage::app()->getStores() ) ); // TODO
//        $customer->setPassword( self::$_customerPassword );
//        $customer->validate();
//        $customer->setConfirmation(null);
//        $customer->save();
//
//        $customer->load( $customer->getId() );
//        $customer->setConfirmation(null);
//        $customer->save();
//
//        $_custom_address = array (
//            'firstname' => 'Test',
//            'lastname' => 'Test',
//            'street' => array (
//                '0' => 'Sample address part1',
//            ),
//            'city' => 'Paderborn',
//            'region_id' => '',
//            'region' => '88',
//            'postcode' => '33100',
//            'country_id' => 'DE',
//            'telephone' => '0000111',
//        );
//        $customAddress = Mage::getModel('customer/address');
//        $customAddress->setData($_custom_address)
//            ->setCustomerId($customer->getId())
//            ->setIsDefaultBilling('1')
//            ->setIsDefaultShipping('1')
//            ->setSaveInAddressBook('1');
//        $customAddress->save();
        //$customer = Mage::getModel('customer/customer')->loadByEmail('mundipagg@mundipagg.com');
//        if($customer->getId()){
//            $customer->setIsDeleteable(true);
//            $customer->delete();
//        }
    }

   
   public function testCheckout()
   {
       $this->url('http://127.0.0.1:8085/index.php');
       $value = 'test';
       $element = $this->byId('search');
       $element->value($value);
       $button = $this->findElementsByCssSelector('.button', $this->byId('search_mini_form') );
       foreach($button as $b){
           if($b->displayed()){
               $b->click();
           }
       }
       $this->assertContains('?q='.$value, $this->url() );
       $productsByList = $this->findElementsByCssSelector('.btn-cart');
       foreach($productsByList as $btn){
           if($btn->displayed()){
               $btn->click();
           }
       }
       
       $this->byCssSelector('.btn-proceed-checkout')->click();
       
       $this->assertContains('checkout/onepage/', $this->url() );
       
       $this->byId('login:register')->click();
       $this->byId('onepage-guest-register-button')->click();
       
       $this->byId('billing:firstname')->value('Test');
       $this->byId('billing:lastname')->value('Test');

       $this->byId('billing:email')->value('mundipagg@mundipagg.com');
       $this->byId('billing:street1')->value('Rua Tal');
       $this->byId('billing:city')->value('Curitiba');
       $this->byId('billing:country_id')->value('BR');
       $this->byId('billing:country_id')->value('PR');
       $this->byId('billing:telephone')->value('9999999999');
       $this->byId('billing:customer_password')->value('123test');
       $this->byId('billing:confirm_password')->value('123test');
       $this->byCssSelector('.button')->click();
   }
   
   public function findElementsByCssSelector( $selector, \PHPUnit_Extensions_Selenium2TestCase_Element $root_element = null )
    {
        if( !$root_element )
        {
            $root_element = $this;
        }
        return $root_element->elements( $this->using('css selector')->value( $selector ) );
    }
   

} 
