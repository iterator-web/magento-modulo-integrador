<?php
 /**
 * Iterator Sistemas Web
 *
 * NOTAS SOBRE LICENЧA
 *
 * Este arquivo de cѓdigo-fonte estс em vigъncia dentro dos termos da EULA.
 * Ao fazer uso deste arquivo em seu produto, automaticamente vocъ estс 
 * concordando com os termos do Contrato de Licenчa de Usuсrio Final(EULA)
 * propostos pela empresa Iterator Sistemas Web.
 *
 * =================================================================
 *                      MгDULO INTEGRADOR REST
 * =================================================================
 * Este produto foi desenvolvido para o Ecommerce Magento de forma a
 * possibilitar que sejam disponibilizados novos mщtodos para a REST
 * API que por padrуo nуo existem no Magento e se fazem necessсrios 
 * para a integraчуo com diversos ERP de mercado. 
 * Atravщs deste mѓdulo a loja virtual do contratante do serviчo
 * passarс a conter diversas novas funcionalidades para integraчѕes
 * via REST API do Magento com seus softwares ERP.
 * =================================================================
 *
 * @category   Iterator
 * @package    Iterator_Integrador
 * @author     Ricardo Auler Barrientos <contato@iterator.com.br>
 * @copyright  Copyright (c) Iterator Sistemas Web - CNPJ: 19.717.703/0001-63
 * @license    O Produto щ protegido por leis de direitos autorais, bem como outras leis de propriedade intelectual.
 */

class Iterator_Integrador_Model_Api2_Atributo_Opcao_Rest_Admin_V1 extends Mage_Catalog_Model_Api2_Product_Rest {
    
    /**
     * Cria opчуo para o atributo indicado
     * Os parтmetros esperados sуo:
     * {
     *  'value': int, - Valor referente a opчуo de atributo que serс cadastrada
     * }
     *
     * @param array $data
     * @return string
     */
    protected function _create(array $data) {
        $attributeCode = $this->getRequest()->getParam('attribute_code');
        $options = array();
        $value = $data['value'];
        if(!$value) {
            $this->_critical(utf8_encode('Integrador ITERATOR API: O parтmetro "value" nуo pode ser nulo na criaчуo da opчуo de atributo.'), Mage_Api2_Model_Server::HTTP_BAD_REQUEST);
        } else {
            $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', $attributeCode);
            if($attribute && $attribute->usesSource()) {
                $optionId = $attribute->getSource()->getOptionId($value);
                $options['value'][$optionId] = array(
                    0 => $value
                );
                $attribute->setOption($options)->save();
            } else {
                $this->_critical(utf8_encode('Integrador ITERATOR API: O atributo indicado щ invсlido.'), Mage_Api2_Model_Server::HTTP_BAD_REQUEST);
            }
        }
    }
    
    /**
     * Retorna o id da opчуo do atributo, a partir do code do atributo e do label da opчуo do atributo, passados por parametro.
     *
     * @throws Mage_Api2_Exception
     * @return array
     */
    protected function _retrieve() {
        $attributeCode = $this->getRequest()->getParam('attribute_code');
        $label = html_entity_decode(str_replace('%20', ' ', $this->getRequest()->getParam('label'))); // Converte os caracteres especiais do html em texto e substitui os caracteres %20 por espaчos em branco.
        $result = array();
        $_product = Mage::getModel('catalog/product');
        $attr = $_product->getResource()->getAttribute($attributeCode);
        if ($attr->usesSource()) {
            $optionId = $attr->getSource()->getOptionId($label);
            if($optionId) {
                $result = array(
                    'value' => $optionId,
                    'label' => $label
                );
            }
        }
        if (empty($result)) {
            $this->_critical('Attribute option not found', Mage_Api2_Model_Server::HTTP_NOT_FOUND);
        }
        return $result;
    }
    
    /**
     * Retorna lista com todos as opчѕes disponэveis para o atributo indicado.
     *
     * @throws Mage_Api2_Exception
     * @return array
     */
    protected function _retrieveCollection() {
        $attributeCode = $this->getRequest()->getParam('attribute_code');
        $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', $attributeCode);
        $allOptions = $attribute->getSource()->getAllOptions(true, true);
        return $allOptions;
    }
}

?>