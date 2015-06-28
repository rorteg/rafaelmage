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

class Uecommerce_Mundipagg_Model_Source_Frequency extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
    public function getAllOptions()
    {
        return array(
            array('value' => '', 'label' => 'Nenhuma'),
            array('value' => 'semanal', 'label' => 'Semanal'),
            array('value' => 'mensal', 'label' => 'Mensal'),
            array('value' => 'trimestral', 'label' => 'Trimestral'),
            array('value' => 'semestral', 'label' => 'Semestral'),
            array('value' => 'anual', 'label' => 'Anual')
        );
    }

    public function toOptionArray()
    {
        return $this->getAllOptions();
    }
}
