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

class Iterator_Integrador_Model_Api2_Produto_Configurable_Rest_Admin_V1 extends Mage_Catalog_Model_Api2_Product_Rest {
    
    /**
     * Busca cѓdigo passado por parametro no SKU dos produtos cadastrados e retorna a opчуo encontrada.
     *
     * @throws Mage_Api2_Exception
     * @return array
     */
    protected function _retrieve() {
        $sku = $this->getRequest()->getParam('sku');
        /** @var $collection Mage_Catalog_Model_Resource_Product_Collection */
        $collection = Mage::getResourceModel('catalog/product_collection');
        $store = $this->_getStore();
        $collection->setStoreId($store->getId());
        $collection->addAttributeToSelect(array_keys(
            $this->getAvailableAttributes($this->getUserType(), Mage_Api2_Model_Resource::OPERATION_ATTRIBUTE_READ)
        ));
        $collection->addFieldToFilter('sku', array('like'=>'%'.$sku.'%'));
        $this->_applyCategoryFilter($collection);
        $this->_applyCollectionModifiers($collection);
        $product = $collection->load()->getFirstItem();
        if(!$product->getId()) {
            $this->_critical('Product not found', Mage_Api2_Model_Server::HTTP_NOT_FOUND);
        } else {
            return $product;
        }
    }
    
    /**
     * Retorna lista com todos os atributos disponэveis para o produto configurable indicado.
     *
     * @throws Mage_Api2_Exception
     * @return array
     */
    protected function _retrieveCollection() {
        $productId = $this->getRequest()->getParam('product_id');
        $product = Mage::getModel('catalog/product')->load($productId);
        $productAttributeOptions = $product->getTypeInstance()->getConfigurableAttributesAsArray($product);
        return $productAttributeOptions;
    }
}

?>