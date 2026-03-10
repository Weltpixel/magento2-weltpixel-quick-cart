<?php
namespace WeltPixel\QuickCart\Observer;

use Magento\Framework\Event\ObserverInterface;

class ReplaceTemplates implements ObserverInterface
{
    const XML_PATH_QUICKCART_ENABLED = 'weltpixel_quick_cart/general/enable';
    const XML_PATH_QUICKCART_QTY_BUTTON_TYPE_ON_CART = 'weltpixel_quick_cart/shopping_cart_content/qty_button_type_cart_page';

    /**
    * @var \Magento\Framework\App\Config\ScopeConfigInterface
    */
    protected $scopeConfig;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Add Custom QuickCart layout handle
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Framework\View\Element\Template $block */
        $block = $observer->getBlock();

        $blockTemplate = $block->getTemplate() ?? '';
        $isEnabled = $this->scopeConfig->getValue(self::XML_PATH_QUICKCART_ENABLED, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        if ($isEnabled) {
            $qtyButtonTypeOnCart = $this->scopeConfig->getValue(self::XML_PATH_QUICKCART_QTY_BUTTON_TYPE_ON_CART, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            if (strpos($blockTemplate, 'cart/item/default.phtml') !== false) {
                switch ($qtyButtonTypeOnCart) {
                    case \WeltPixel\QuickCart\Model\Config\Source\QuantitySignTypes::QTY_ARROWS:
                        $block->setTemplate('WeltPixel_QuickCart::cart/item/qty_arrows.phtml');
                        break;
                    case \WeltPixel\QuickCart\Model\Config\Source\QuantitySignTypes::QTY_PLUSMINUS:
                        $block->setTemplate('WeltPixel_QuickCart::cart/item/qty_plus_minus.phtml');
                        break;
                }
            }

        }

        return $this;
    }
}
