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

class Iterator_Integrador_Model_Api2_Pedido_Rest_Admin_V1 extends Mage_Sales_Model_Api2_Order_Rest {
    
    /**
     * Adiciona ao pedido do Magento, o nњmero do pedido de referъncia no ERP.
     *
     * @param array $data
     */
    protected function _update(array $data) {
        $incrementId = $this->getRequest()->getParam('increment_id');
        $order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);
        if($data['codigo_orcamento']) {
            $order->setCustomerNote($data['codigo_orcamento']);
        } else if($data['codigo_venda']) {
            $order->setExtOrderId($data['codigo_venda']);
        }
        $order->save();
    }
    
    /**
     * Retorna lista com todos os pedidos existentes para o status passado via parametro. customer_note igual a null significa que o pedido ainda nуo foi integrado no ERP.
     *
     * @throws Mage_Api2_Exception
     * @return array
     */
    protected function _retrieveCollection() {
        $status = $this->getRequest()->getParam('status');
        $collection = $this->_getCollectionForRetrieve();
        $collection->addFieldToFilter('status', $status);

        if ($this->_isPaymentMethodAllowed()) {
            $this->_addPaymentMethodInfo($collection);
        }
        if ($this->_isGiftMessageAllowed()) {
            $this->_addGiftMessageInfo($collection);
        }
        $this->_addTaxInfo($collection);
        
        $ordersData = array();

        foreach ($collection->getItems() as $order) {
            $ordersData['items'][] = $order->toArray();
            $ids[$order->getId()] = null;
        }
        if ($ordersData) {
            foreach ($this->_getAddresses(array_keys($ids)) as $orderId => $addresses) {
                $ordersData['items'][]['addresses'] = $addresses;
            }
            foreach ($this->_getItems(array_keys($ids)) as $orderId => $items) {
                $ordersData['items'][]['order_items'] = $items;
            }
            foreach ($this->_getComments(array_keys($ids)) as $orderId => $comments) {
                $ordersData['items'][]['order_comments'] = $comments;
            }
        }
        //Mage::log($ordersData['items'], null, 'rest.log');
        return $ordersData['items'];
    }
}

?>