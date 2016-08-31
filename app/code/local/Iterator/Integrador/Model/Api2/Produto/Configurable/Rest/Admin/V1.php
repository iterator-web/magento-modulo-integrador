<?php
 /**
 * Iterator Sistemas Web
 *
 * NOTAS SOBRE LICENÇA
 *
 * Este arquivo de código-fonte está em vigência dentro dos termos da EULA.
 * Ao fazer uso deste arquivo em seu produto, automaticamente você está 
 * concordando com os termos do Contrato de Licença de Usuário Final(EULA)
 * propostos pela empresa Iterator Sistemas Web.
 *
 * =================================================================
 *                      MÓDULO INTEGRADOR REST
 * =================================================================
 * Este produto foi desenvolvido para o Ecommerce Magento de forma a
 * possibilitar que sejam disponibilizados novos métodos para a REST
 * API que por padrão não existem no Magento e se fazem necessários 
 * para a integração com diversos ERP de mercado. 
 * Através deste módulo a loja virtual do contratante do serviço
 * passará a conter diversas novas funcionalidades para integrações
 * via REST API do Magento com seus softwares ERP.
 * =================================================================
 *
 * @category   Iterator
 * @package    Iterator_Integrador
 * @author     Ricardo Auler Barrientos <contato@iterator.com.br>
 * @copyright  Copyright (c) Iterator Sistemas Web - CNPJ: 19.717.703/0001-63
 * @license    O Produto é protegido por leis de direitos autorais, bem como outras leis de propriedade intelectual.
 */

class Iterator_Integrador_Model_Api2_Produto_Configurable_Rest_Admin_V1 extends Mage_Catalog_Model_Api2_Product_Rest {
    
    /**
     * Busca código passado por parametro no SKU dos produtos cadastrados e retorna a opção encontrada.
     *
     * @throws Mage_Api2_Exception
     * @return array
     */
    protected function _retrieve() {
        $sku = $this->getRequest()->getParam('id');
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
     * Retorna lista com todos os atributos disponíveis para o produto configurable indicado.
     *
     * @throws Mage_Api2_Exception
     * @return array
     */
    protected function _retrieveCollection() {
        $product = $this->_getProduct();
        $productAttributeOptions = $product->getTypeInstance()->getConfigurableAttributesAsArray($product);
        return $productAttributeOptions;
    }
    
    /**
     * Faz a associação entre o produto configurable com os simples vinculados e altera os preços se necessário.
     *
     * @param array $data
     */
    protected function _update(array $data) {
        $product = $this->_getProduct();
        if(!$product) {
            $this->_critical('Integrador ITERATOR API: O produto indicado é inválido.', Mage_Api2_Model_Server::HTTP_BAD_REQUEST);
        } else {
            $this->associateProducts($product, $data['associated_skus'], $data['price_changes']);
        }
    }
    
    /**
     * Os códigos deste método são pertencentes ao seguinte módulo: https://github.com/jreinke/magento-improve-api/blob/master/app/code/community/Bubble/Api/Helper/Catalog/Product.php
     * 
     * @param Mage_Catalog_Model_Product $product
     * @param array $simpleSkus
     * @param array $priceChanges
     * @return Bubble_Api_Helper_Catalog_Product
     */
    private function associateProducts(Mage_Catalog_Model_Product $product, $simpleSkus, $priceChanges = array(), $configurableAttributes = array()) {
        if (!empty($simpleSkus)) {
            $newProductIds = Mage::getModel('catalog/product')->getCollection()
                ->addFieldToFilter('sku', array('in' => (array) $simpleSkus))
                ->addFieldToFilter('type_id', Mage_Catalog_Model_Product_Type::TYPE_SIMPLE)
                ->addFieldToFilter('status', 1)
                ->getAllIds();
            $oldProductIds = Mage::getModel('catalog/product_type_configurable')->setProduct($product)->getUsedProductCollection()
                ->addAttributeToSelect('*')
                ->addFilterByRequiredOptions()
                ->getAllIds();
            $usedProductIds = array_diff($newProductIds, $oldProductIds);
            if (!empty($newProductIds) && $product->isConfigurable()) {
                $this->_initConfigurableAttributesData($product, $newProductIds, $priceChanges, $configurableAttributes);
            }
            
            if (!empty($usedProductIds) && $product->isGrouped()) {
                $relations = array_fill_keys($usedProductIds, array('qty' => 0, 'position' => 0));
                $product->setGroupedLinkData($relations);
            }
        }
        return $this;
    }
    
    /**
     * Os códigos deste método são pertencentes ao seguinte módulo: https://github.com/jreinke/magento-improve-api/blob/master/app/code/community/Bubble/Api/Helper/Catalog/Product.php
     * 
     * @param Mage_Catalog_Model_Product $mainProduct
     * @param array $simpleProductIds
     * @param array $priceChanges
     * @return Bubble_Api_Helper_Catalog_Product
     */
    private function _initConfigurableAttributesData(Mage_Catalog_Model_Product $mainProduct, $simpleProductIds, $priceChanges = array(), $configurableAttributes = array()) {
        if (!$mainProduct->isConfigurable() || empty($simpleProductIds)) {
            return $this;
        }
        $mainProduct->setConfigurableProductsData(array_flip($simpleProductIds));
        $productType = $mainProduct->getTypeInstance(true);
        $productType->setProduct($mainProduct);
        $attributesData = $productType->getConfigurableAttributesAsArray();
        if (empty($attributesData)) {
            // Auto generation if configurable product has no attribute
            $attributeIds = array();
            foreach ($productType->getSetAttributes() as $attribute) {
                if ($productType->canUseAttribute($attribute)) {
                    $attributeIds[] = $attribute->getAttributeId();
                }
            }
            $productType->setUsedProductAttributeIds($attributeIds);
            $attributesData = $productType->getConfigurableAttributesAsArray();
        }
        if (!empty($configurableAttributes)){
            foreach ($attributesData as $idx => $val) {
                if (!in_array($val['attribute_id'], $configurableAttributes)) {
                    unset($attributesData[$idx]);
                }
            }
        }
        $products = Mage::getModel('catalog/product')->getCollection()
            ->addIdFilter($simpleProductIds);
        if (count($products)) {
            foreach ($attributesData as &$attribute) {
                $attribute['label'] = $attribute['frontend_label'];
                $attributeCode = $attribute['attribute_code'];
                foreach ($products as $product) {
                    $product->load($product->getId());
                    $optionId = $product->getData($attributeCode);
                    $isPercent = 0;
                    $priceChange = 0;
                    if (!empty($priceChanges) && isset($priceChanges[$attributeCode])) {
                        $optionText = $product->getResource()
                            ->getAttribute($attribute['attribute_code'])
                            ->getSource()
                            ->getOptionText($optionId);
                        if (isset($priceChanges[$attributeCode][$optionText])) {
                            if (false !== strpos($priceChanges[$attributeCode][$optionText], '%')) {
                                $isPercent = 1;
                            }
                            $priceChange = preg_replace('/[^0-9\.,-]/', '', $priceChanges[$attributeCode][$optionText]);
                            $priceChange = (float) str_replace(',', '.', $priceChange);
                        }
                    }
                    if($priceChange > 0) {
                        $attribute['values'][$optionId] = array(
                            'value_index' => $optionId,
                            'is_percent' => $isPercent,
                            'pricing_value' => $priceChange,
                        );
                    }
                }
            }
            $mainProduct->setConfigurableAttributesData($attributesData);
            $mainProduct->save();
        }
        return $this;
    }
}

?>