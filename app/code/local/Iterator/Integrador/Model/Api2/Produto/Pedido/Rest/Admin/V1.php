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

class Iterator_Integrador_Model_Api2_Produto_Pedido_Rest_Admin_V1 extends Mage_Catalog_Model_Api2_Product_Rest {
    
    /**
     * Busca os pedidos a partir do SKU do produto passado por parametro e retorna a quantidade deste produto reservada em pedidos pendentes.
     *
     * @throws Mage_Api2_Exception
     * @return int
     */
    protected function _retrieve() {
        $sku = $this->getRequest()->getParam('sku');
        $totalReservado = 0;
        $orderItemCollection = Mage::getModel('sales/order_item')->getCollection();
        $orderItemCollection->getSelect()->group('main_table.item_id')
                ->joinLeft(array('order' => $orderItemCollection->getTable('sales/order')),
                'main_table.order_id = order.entity_id', 
                array('prod_id' => 'main_table.product_id', 'order_date' => 'order.created_at', 'increment_id' => 'order.increment_id', 'status' => 'order.status'));
        $orderItemCollection->addFieldToFilter('order.status', array('eq' => 'pending'));
        $orderItemCollection->addFieldToFilter('main_table.sku', array('eq' => $sku));
        foreach($orderItemCollection as $orderItem) {
            if($orderItem->getProductType() == 'simple') {
                $totalReservado += (int)$orderItem->getQtyOrdered();
            }
        }
        
        return $totalReservado;
    }
}

?>