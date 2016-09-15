<?php
 /**
 * Iterator Sistemas Web
 *
 * NOTAS SOBRE LICEN�A
 *
 * Este arquivo de c�digo-fonte est� em vig�ncia dentro dos termos da EULA.
 * Ao fazer uso deste arquivo em seu produto, automaticamente voc� est� 
 * concordando com os termos do Contrato de Licen�a de Usu�rio Final(EULA)
 * propostos pela empresa Iterator Sistemas Web.
 *
 * =================================================================
 *                      M�DULO INTEGRADOR REST
 * =================================================================
 * Este produto foi desenvolvido para o Ecommerce Magento de forma a
 * possibilitar que sejam disponibilizados novos m�todos para a REST
 * API que por padr�o n�o existem no Magento e se fazem necess�rios 
 * para a integra��o com diversos ERP de mercado. 
 * Atrav�s deste m�dulo a loja virtual do contratante do servi�o
 * passar� a conter diversas novas funcionalidades para integra��es
 * via REST API do Magento com seus softwares ERP.
 * =================================================================
 *
 * @category   Iterator
 * @package    Iterator_Integrador
 * @author     Ricardo Auler Barrientos <contato@iterator.com.br>
 * @copyright  Copyright (c) Iterator Sistemas Web - CNPJ: 19.717.703/0001-63
 * @license    O Produto � protegido por leis de direitos autorais, bem como outras leis de propriedade intelectual.
 */

class Iterator_Integrador_Model_Api2_Atributo_Opcao_Rest_Admin_V1 extends Mage_Catalog_Model_Api2_Product_Rest {
    
    /**
     * Cria op��o para o atributo indicado
     * Os par�metros esperados s�o:
     * {
     *  'value': int, - Valor referente a op��o de atributo que ser� cadastrada
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
            $this->_critical(utf8_encode('Integrador ITERATOR API: O par�metro "value" n�o pode ser nulo na cria��o da op��o de atributo.'), Mage_Api2_Model_Server::HTTP_BAD_REQUEST);
        } else {
            $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', $attributeCode);
            if($attribute && $attribute->usesSource()) {
                $optionId = $attribute->getSource()->getOptionId($value);
                $options['value'][$optionId] = array(
                    0 => $value
                );
                $attribute->setOption($options)->save();
            } else {
                $this->_critical(utf8_encode('Integrador ITERATOR API: O atributo indicado � inv�lido.'), Mage_Api2_Model_Server::HTTP_BAD_REQUEST);
            }
        }
    }
    
    /**
     * Retorna o id da op��o do atributo, a partir do code do atributo e do label da op��o do atributo, passados por parametro.
     *
     * @throws Mage_Api2_Exception
     * @return array
     */
    protected function _retrieve() {
        $attributeCode = $this->getRequest()->getParam('attribute_code');
        $label = html_entity_decode(str_replace('%20', ' ', $this->getRequest()->getParam('label'))); // Converte os caracteres especiais do html em texto e substitui os caracteres %20 por espa�os em branco.
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
     * Retorna lista com todos as op��es dispon�veis para o atributo indicado.
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