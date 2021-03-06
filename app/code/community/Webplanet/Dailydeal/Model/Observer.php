<?php

class Webplanet_Dailydeal_Model_Observer
{

    CONST QUOTE_ITEM_OPTION_CODE = 'webplanet_dailydeal';
    CONST ORDER_ITEM_OPTION_CODE = 'webplanet_dailydeal';

    /**
     *
     * @param Varien_Event_Observer $event
     */
    public function onCatalogProductCollectionLoadAfter($event)
    {

        return $this;
        $collection = $event->getData('collection');
        $ids = $collection->getLoadedIds();

        Mage::helper('dailydeal')->updateProductCollectionData($collection);
    }
    
    public function onAfterProductLoad(Varien_Event_Observer $event)
    {
        $product = $event->getProduct();
        
        $helper = Mage::helper('dailydeal');
        $deal = $helper->getCurrentDealForProduct($product);
        /* @var $deal Webplanet_Dailydeal_Model_Deal */
        if(!$deal) {
            return;
        }
        
        if(false === $deal->isAvailable()) {
            return;
        }
            
        
        $product->setCurrentDailydeal($deal);
//        $product->getResource()->getAttribute('special_price')->setStoreLabel(Mage::helper('core')->__('Deal Price:'));
        $product->setSpecialPrice($deal->getDealPrice());
//        var_dump($product->getResource()->getAttribute('special_price')->getStoreLabel());exit;
        
//        var_dump($deal, $event->getProduct());exit;
    }

    public function onAfterCartProductAdd(\Varien_Event_Observer $event)
    {
//        $quoteItem = $event->getData('quote_item');
//        /* @var $quoteItem Mage_Sales_Model_Quote_Item */
////        var_dump($quoteItem);exit;
//        $helper = Mage::helper('dailydeal');
//        $deal = $helper->getCurrentDealForProduct($quoteItem->getProduct());
//
//        if (null === $deal) {
//            // no deal found for this product, ignore
//            return $this;
//        }
//        
//        $product = $event->getData('product');
////        var_dump($product);exit;
//        $product->setIsSuperMode(true);
//        $product->setSpecialPrice($deal->getDealPrice());
//        $quoteItem->getProduct()->setFinalPrice($deal->getDealPrice());
//        $quoteItem->setSpecialPrice($deal->getDealPrice());
//        $quoteItem->setCustomPrice($deal->getDealPrice());
//        
//        $quoteItem->save();
    }
    
    public function onAfterQuoteProductAdd(\Varien_Event_Observer $event)
    {
//        $quoteItem = $event->getData('quote_item');
//        $product = $event->getData('product');
//        /* @var $quoteItem Mage_Sales_Model_Quote_Item */
////        var_dump($quoteItem);exit;
//        $helper = Mage::helper('dailydeal');
//        $deal = $helper->getCurrentDealForProduct($product);
//
//        if (null === $deal) {
//            // no deal found for this product, ignore
//            return $this;
//        }
//        
//        
////        var_dump($product);exit;
//        $product->setIsSuperMode(true);
//        $product->setSpecialPrice($deal->getDealPrice());
//        $product->setFinalPrice($deal->getDealPrice());
    }
    
    /**
     *
     * @param Varien_Event_Observer $event
     */
    public function onSalesQuoteAddItem(\Varien_Event_Observer $event)
    {
        $quoteItem = $event->getData('quote_item');
        /* @var $quoteItem Mage_Sales_Model_Quote_Item */
        
        if($quoteItem->getParentItem()) {
            $quoteItem = $quoteItem->getParentItem();
        }

        $helper = Mage::helper('dailydeal');
        $deal = $helper->getCurrentDealForProduct($quoteItem->getProduct());

        if (null === $deal || false === $deal->isAvailable()) {
            // no deal found for this product, ignore
            return $this;
        }

        // @todo - detect if the product actually has a current deal going on
        // @todo - add option to order - http://stackoverflow.com/questions/9412074/magento-quote-order-product-item-attribute-based-on-user-input or use the code below if it does not work

        /*
          // this actually works & populates into order item
          $info_buyRequest = $quoteItem->getOptionByCode('info_buyRequest');

          $value = is_array($info_buyRequest->value) ? $info_buyRequest->value : unserialize($info_buyRequest->value);

          $value['super_attribute'][] = array(151, date('Y-m-d'));
          $value['super_attribute'][] = array(152, 'test');

          $info_buyRequest->value = serialize($value);
         * 
         */
//        var_dump($quoteItem);exit;
        $quoteItem->getProduct()->setIsSuperMode(true);
        $quoteItem->getProduct()->setSpecialPrice($deal->getDealPrice());
        $quoteItem->getProduct()->setFinalPrice($deal->getDealPrice());
        $quoteItem->setSpecialPrice($deal->getDealPrice());
        $quoteItem->setCustomPrice($deal->getDealPrice());
        $quoteItem->setOriginalCustomPrice($deal->getDealPrice());
        
//        var_dump($quoteItem);exit;
//        $quoteItem->set($value)
        // add a message to additional_options so it is displayed on the cart page
        $additionalOptions = $quoteItem->getOptionByCode('additional_options');

        if (!$additionalOptions) {
            $additionalOptions = new Mage_Sales_Model_Quote_Item_Option();
            $additionalOptions->setCode('additional_options');
            $quoteItem->addOption($additionalOptions);
        }

        $additionalOptionsValue = $additionalOptions->getData('value');

        if (!$additionalOptionsValue) {
            $additionalOptionsValue = array();
        } elseif (is_string($additionalOptionsValue)) {
            $additionalOptionsValue = unserialize($additionalOptionsValue);
        }

        $additionalOptionsValue = array(
            array('label' => 'Daily Deal Special',
                'value' => Mage::helper('checkout')->formatPrice($deal->getDealPrice())
                . ' instead of ' . Mage::helper('checkout')->formatPrice($quoteItem->getProduct()->getPrice()))
        );

        $additionalOptions->setValue(serialize($additionalOptionsValue));


        // save deal info with the quote item
        $dailyDealOptions = new Mage_Sales_Model_Quote_Item_Option();
        $dailyDealOptions->setCode(self::QUOTE_ITEM_OPTION_CODE);
        $dailyDealOptions->setValue(serialize(array('deal_date' => date('Y-m-d'), 'product_price' => $quoteItem->getProduct()->getPrice(), 'deal_price' => $quoteItem->getPrice())));
        $quoteItem->addOption($dailyDealOptions);
        
        return;

        $dailyDealOption = new Mage_Sales_Model_Quote_Item_Option();
        $dailyDealOption->setCode('additional_options');
        //$dailyDealOption->setCode('info_buyRequest');


        $optionData = array(
            array('label' => 'Daily Deal Special')
        );
        $dailyDealOption->setValue(serialize($optionData));


        //$dailyDealOption->setdata('webplanet_daily_deal_date', date('Y-m-d'));

        $quoteItem->addOption($dailyDealOption);



        // a:5:{s:4:"uenc";s:72:"aHR0cDovL3h0cmVtZW51dHJpdGlvbi5sb2NhbC9vaC15ZWFoLXByb3RlaW4tYmFycy5odG1s";s:7:"product";s:4:"1046";s:15:"related_product";s:0:"";s:15:"super_attribute";a:1:{i:497;s:2:"55";}s:3:"qty";s:1:"1";}
    }

    public function onSalesConvertQuoteItemToOrderItem(Varien_Event_Observer $observer)
    {
        try {
            // update deal_qty_sold 
            $helper = Mage::helper('dailydeal');

            $quoteItem = $observer->getItem();
            $deal = $helper->getCurrentDealForProduct($quoteItem->getProduct());

            if (null !== $deal) {
                //return $this;
                if ($dailydealOptions = $quoteItem->getOptionByCode(self::QUOTE_ITEM_OPTION_CODE)) {
                    $orderItem = $observer->getOrderItem();
                    $options = $orderItem->getProductOptions();
                    $options[self::ORDER_ITEM_OPTION_CODE] = unserialize($dailydealOptions->getValue());
                    $orderItem->setProductOptions($options);
                }

                // save Daily Deal options to order item
                $orderItem = $observer->getOrderItem();
                $options = $orderItem->getProductOptions();
                $options['additional_options'][] = array(
                    'label' => 'Daily Deal Special',
                    'value' => Mage::helper('checkout')->formatPrice($quoteItem->getPrice())
                    . ' instead of ' . Mage::helper('checkout')->formatPrice($quoteItem->getProduct()->getPrice())
                );

                $orderItem->setProductOptions($options);

                $deal->setData('deal_qty_sold', $deal->getData('deal_qty_sold') + 1);
                $deal->save();
            }
        } catch (Exception $e) {
            echo $e;
            exit;
        }
        return $this;
    }

}
