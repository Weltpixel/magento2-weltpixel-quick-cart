<?php

namespace WeltPixel\QuickCart\Helper;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as SalesRuleCollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Escaper;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var PriceHelper
     */
    protected $priceHelper;

    /**
     * @var array
     */
    protected $_quickcartOptions;

    /**
     * @var SalesRuleCollectionFactory
     */
    protected $salesRuleCollectionFactory;

    /**
     * @var \Magento\Tax\Model\Config
     */
    protected $taxConfig;

    /**
     * @var \Magento\Checkout\Helper\Data
     */
    protected $checkoutHelper;

    protected  $freeShippingOptions = [
        [
            'flag' => 'carriers/freeshipping/active',
            'flag_free_shipping' => 'carriers/freeshipping/active',
            'treshold' => 'carriers/freeshipping/free_shipping_subtotal'
        ],
        [
            'flag' => 'carriers/ups/active',
            'flag_free_shipping' => 'carriers/ups/free_shipping_enable',
            'treshold' => 'carriers/ups/free_shipping_subtotal'
        ],
        [
            'flag' => 'carriers/usps/active',
            'flag_free_shipping' => 'carriers/usps/free_shipping_enable',
            'treshold' => 'carriers/usps/free_shipping_subtotal'
        ],
        [
            'flag' => 'carriers/fedex/active',
            'flag_free_shipping' => 'carriers/fedex/free_shipping_enable',
            'treshold' => 'carriers/fedex/free_shipping_subtotal'
        ],
        [
            'flag' => 'carriers/dhl/active',
            'flag_free_shipping' => 'carriers/dhl/free_shipping_enable',
            'treshold' => 'carriers/dhl/free_shipping_subtotal'
        ]
    ];

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * Tags a merchant may keep in the two cart messages.
     *
     * The escaper drops script, img, iframe, embed, object, audio, video and source outright,
     * and keeps only the id, class, href, title and style attributes on whatever survives,
     * which is what lets the amount_needed span this helper injects through unchanged.
     */
    const MESSAGE_ALLOWED_TAGS = ['span', 'div', 'p', 'a', 'b', 'strong', 'em', 'i', 'u', 'small', 'br'];


    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param CheckoutSession $checkoutSession
     * @param PriceHelper $priceHelper
     * @param SalesRuleCollectionFactory $salesRuleCollectionFactory
     * @param \Magento\Tax\Model\Config $taxConfig
     * @param \Magento\Checkout\Helper\Data $checkoutHelper
     * @param PriceCurrencyInterface $priceCurrency
     * @param Escaper $escaper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        CheckoutSession $checkoutSession,
        PriceHelper $priceHelper,
        SalesRuleCollectionFactory $salesRuleCollectionFactory,
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        PriceCurrencyInterface $priceCurrency,
        Escaper $escaper
    ) {
        parent::__construct($context);

        $this->_storeManager = $storeManager;
        $this->checkoutSession = $checkoutSession;
        $this->priceHelper = $priceHelper;
        $this->salesRuleCollectionFactory = $salesRuleCollectionFactory;
        $this->taxConfig = $taxConfig;
        $this->checkoutHelper = $checkoutHelper;
        $this->priceCurrency =  $priceCurrency;
        $this->escaper = $escaper;
        $this->_quickcartOptions = $this->scopeConfig->getValue('weltpixel_quick_cart', ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check if QuickCart is enabled
     *
     * @return mixed
     */
    public function quicartIsEnabled()
    {
        return $this->scopeConfig->getValue(
            'weltpixel_quick_cart/general/enable',
            ScopeInterface::SCOPE_STORE,
            $this->getStoreId()
        );
    }

    /**
     * Check if should open mini-cart after an item was added
     *
     * @return mixed
     */
    public function openMinicart()
    {
        if ($this->quicartIsEnabled()) {
            return $this->scopeConfig->getValue(
                'weltpixel_quick_cart/general/open_minicart',
                ScopeInterface::SCOPE_STORE,
                $this->getStoreId()
            );
        }

        return false;
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    /**
     * @param int $storeId
     * @return mixed
     */
    public function getHeaderHeight($storeId = 0)
    {
        if ($storeId) {
            $value = $this->scopeConfig->getValue('weltpixel_quick_cart/header/header_height', ScopeInterface::SCOPE_STORE, $storeId);
        } else {
            $value = $this->_option('header', 'header_height', '50px');
        }

        return $this->_cssLength($value, '50px');
    }

    /**
     * @param int $storeId
     * @return mixed
     */
    public function getHeaderBackground($storeId = 0)
    {
        if ($storeId) {
            $value = $this->scopeConfig->getValue('weltpixel_quick_cart/header/header_background', ScopeInterface::SCOPE_STORE, $storeId);
        } else {
            $value = $this->_option('header', 'header_background', '#000000');
        }

        return $this->_cssColor($value, '#000000');
    }

    /**
     * @param int $storeId
     * @return mixed
     */
    public function getHeaderTextColor($storeId = 0)
    {
        if ($storeId) {
            $value = $this->scopeConfig->getValue('weltpixel_quick_cart/header/header_text_color', ScopeInterface::SCOPE_STORE, $storeId);
        } else {
            $value = $this->_option('header', 'header_text_color', '#FFFFFF');
        }

        return $this->_cssColor($value, '#FFFFFF');
    }

    /**
     * @param int $storeId
     * @return mixed
     */
    public function getSubtotalBackground($storeId = 0)
    {
        if ($storeId) {
            $value = $this->scopeConfig->getValue('weltpixel_quick_cart/footer/subtotal_background', ScopeInterface::SCOPE_STORE, $storeId);
        } else {
            $value = $this->_option('footer', 'subtotal_background', '#F6F6F6');
        }

        return $this->_cssColor($value, '#F6F6F6');
    }

    /**
     * @param int $storeId
     * @return mixed
     */
    public function getSubtotalTextColor($storeId = 0)
    {
        if ($storeId) {
            $value = $this->scopeConfig->getValue('weltpixel_quick_cart/footer/subtotal_text_color', ScopeInterface::SCOPE_STORE, $storeId);
        } else {
            $value = $this->_option('footer', 'subtotal_text_color', '#000000');
        }

        return $this->_cssColor($value, '#000000');
    }

    /**
     * @param int $storeId
     * @return mixed
     */
    public function getGrandTotalBackground($storeId = 0)
    {
        if ($storeId) {
            $value = $this->scopeConfig->getValue('weltpixel_quick_cart/footer/grandtotal_background', ScopeInterface::SCOPE_STORE, $storeId);
        } else {
            $value = $this->_option('footer', 'grandtotal_background', '#F6F6F6');
        }

        return $this->_cssColor($value, '#F6F6F6');
    }

    /**
     * @param int $storeId
     * @return mixed
     */
    public function getGrandTotalTextColor($storeId = 0)
    {
        if ($storeId) {
            $value = $this->scopeConfig->getValue('weltpixel_quick_cart/footer/grandtotal_text_color', ScopeInterface::SCOPE_STORE, $storeId);
        } else {
            $value = $this->_option('footer', 'grandtotal_text_color', '#000000');
        }

        return $this->_cssColor($value, '#000000');
    }

    /**
     * @param int $storeId
     * @return boolean
     */
    public function isQuickCartMessageEnabled($storeId = 0)
    {
        if ($storeId) {
            return $this->scopeConfig->getValue('weltpixel_quick_cart/minicart_message/enable', ScopeInterface::SCOPE_STORE, $storeId);
        } else {
            return $this->_option('minicart_message', 'enable', 0);
        }
    }

    /**
     * @param int $storeId
     * @return string
     */
    public function getQuickCartMessageContent($storeId = 0)
    {
        if ($storeId) {
            return $this->scopeConfig->getValue('weltpixel_quick_cart/minicart_message/content', ScopeInterface::SCOPE_STORE, $storeId);
        } else {
            return $this->_option('minicart_message', 'content', '');
        }
    }

    /**
     * @param int $storeId
     * @return boolean
     */
    public function isQuickCartFreeShippingIntegrationEnabled($storeId = 0)
    {
        if ($storeId) {
            return $this->scopeConfig->getValue('weltpixel_quick_cart/minicart_message/free_shipping_integration', ScopeInterface::SCOPE_STORE, $storeId);
        } else {
            return $this->_option('minicart_message', 'free_shipping_integration', 0);
        }
    }

    /**
     * @param int $storeId
     * @return string
     */
    public function getQuickCartFreeShippingMessageContent($storeId = 0)
    {
        if ($storeId) {
            return $this->scopeConfig->getValue('weltpixel_quick_cart/minicart_message/free_shipping_content', ScopeInterface::SCOPE_STORE, $storeId);
        } else {
            return $this->_option('minicart_message', 'free_shipping_content', '');
        }
    }

    /**
     * @param int $storeId
     * @return string
     */
    public function getQuickCartMessageTextColor($storeId = 0)
    {
        if ($storeId) {
            $value = $this->scopeConfig->getValue('weltpixel_quick_cart/minicart_message/text_color', ScopeInterface::SCOPE_STORE, $storeId);
        } else {
            $value = $this->_option('minicart_message', 'text_color', '#FF0000');
        }

        return $this->_cssColor($value, '#FF0000');
    }

    /**
     * @param int $storeId
     * @return string
     */
    public function getQuickCartMessageFontSize($storeId = 0)
    {
        if ($storeId) {
            $value = $this->scopeConfig->getValue('weltpixel_quick_cart/minicart_message/font_size', ScopeInterface::SCOPE_STORE, $storeId);
        } else {
            $value = $this->_option('minicart_message', 'font_size', '16px');
        }

        return $this->_cssLength($value, '16px');
    }

    /**
     * @param int $storeId
     * @return string
     */
    public function getQuickCartMessageCustomCss($storeId = 0)
    {
        if ($storeId) {
            return trim($this->scopeConfig->getValue('weltpixel_quick_cart/minicart_message/custom_css', ScopeInterface::SCOPE_STORE, $storeId) ?? '');
        } else {
            return trim((string)$this->_option('minicart_message', 'custom_css', ''));
        }
    }

    /**
     * @param int $storeId
     * @return boolean
     */
    public function isShoppingCartMessageEnabled($storeId = 0)
    {
        if ($storeId) {
            return $this->scopeConfig->getValue('weltpixel_quick_cart/shoppingcart_message/enable', ScopeInterface::SCOPE_STORE, $storeId);
        } else {
            return $this->_option('shoppingcart_message', 'enable', 0);
        }
    }

    /**
     * @param int $storeId
     * @return string
     */
    public function getShoppingCartMessageContent($storeId = 0)
    {
        if ($storeId) {
            return $this->scopeConfig->getValue('weltpixel_quick_cart/shoppingcart_message/content', ScopeInterface::SCOPE_STORE, $storeId);
        } else {
            return $this->_option('shoppingcart_message', 'content', '');
        }
    }

    /**
     * @param int $storeId
     * @return boolean
     */
    public function isShoppingCartFreeShippingIntegrationEnabled($storeId = 0)
    {
        if ($storeId) {
            return $this->scopeConfig->getValue('weltpixel_quick_cart/shoppingcart_message/free_shipping_integration', ScopeInterface::SCOPE_STORE, $storeId);
        } else {
            return $this->_option('shoppingcart_message', 'free_shipping_integration', 0);
        }
    }

    /**
     * @param int $storeId
     * @return string
     */
    public function getShoppingCartFreeShippingMessageContent($storeId = 0)
    {
        if ($storeId) {
            return $this->scopeConfig->getValue('weltpixel_quick_cart/shoppingcart_message/free_shipping_content', ScopeInterface::SCOPE_STORE, $storeId);
        } else {
            return $this->_option('shoppingcart_message', 'free_shipping_content', '');
        }
    }

    /**
     * @param int $storeId
     * @return string
     */
    public function getShoppingCartMessageTextColor($storeId = 0)
    {
        if ($storeId) {
            $value = $this->scopeConfig->getValue('weltpixel_quick_cart/shoppingcart_message/text_color', ScopeInterface::SCOPE_STORE, $storeId);
        } else {
            $value = $this->_option('shoppingcart_message', 'text_color', '#FF0000');
        }

        return $this->_cssColor($value, '#FF0000');
    }

    /**
     * @param int $storeId
     * @return string
     */
    public function getShoppingCartMessageFontSize($storeId = 0)
    {
        if ($storeId) {
            $value = $this->scopeConfig->getValue('weltpixel_quick_cart/shoppingcart_message/font_size', ScopeInterface::SCOPE_STORE, $storeId);
        } else {
            $value = $this->_option('shoppingcart_message', 'font_size', '30px');
        }

        return $this->_cssLength($value, '30px');
    }

    /**
     * @param int $storeId
     * @return string
     */
    public function getShoppingCartMessageCustomCss($storeId = 0)
    {
        if ($storeId) {
            return trim($this->scopeConfig->getValue('weltpixel_quick_cart/shoppingcart_message/custom_css', ScopeInterface::SCOPE_STORE, $storeId) ?? '');
        } else {
            return trim((string)$this->_option('shoppingcart_message', 'custom_css', ''));
        }
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getQuickCartMessageContentForDisplay()
    {
        $quickCartMessageContent = $this->getQuickCartMessageContent();
        if ($this->isQuickCartFreeShippingIntegrationEnabled() && $this->isAtleastOneFreeShippingMethodEnabled()) {
            $freeShippingFromCartRuleApplied = $this->_checkIfFreeShippingFromCartRuleApplied();
            $totals = $this->checkoutSession->getQuote()->getTotals();
            $subtotalData = $totals['subtotal'];
            $subtotal = $subtotalData->getValue();

            if ($this->getTaxCalculationPriceIncludesTax() && $subtotalData->getValueInclTax()) {
                $subtotal = $subtotalData->getValueInclTax();
            } elseif ( $subtotalData->getValueExclTax()) {
                $subtotal = $subtotalData->getValueExclTax();
            }

            $minimumOrderAmount = $this->_getFreeShippingMinimumOrderAmount();
            $freeShippingLimit = $this->priceHelper->currency($minimumOrderAmount, false, false) - $subtotal;
            if ($freeShippingFromCartRuleApplied || $freeShippingLimit <= 0) {
                $quickCartMessageContent = $this->getQuickCartFreeShippingMessageContent();
            } else {
                $formattedPrice = $this->priceHelper->currency($freeShippingLimit, true, false);
                $quickCartMessageContent = str_replace(['{amount_needed}'], ["<span id='quickcart-amount-needed'>" . $formattedPrice . "</span>"], $quickCartMessageContent);
            }
        }

        return $this->escaper->escapeHtml($quickCartMessageContent, self::MESSAGE_ALLOWED_TAGS);
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getShoppingCartMessageContentForDisplay()
    {
        $shoppingCartMessageContent = $this->getShoppingCartMessageContent();
        if ($this->isShoppingCartFreeShippingIntegrationEnabled() && $this->isAtleastOneFreeShippingMethodEnabled()) {
            $freeShippingFromCartRuleApplied = $this->_checkIfFreeShippingFromCartRuleApplied();
            $totals = $this->checkoutSession->getQuote()->getTotals();
            $subtotalData = $totals['subtotal'];
            $subtotal = $subtotalData->getValue();

            if ($this->getTaxCalculationPriceIncludesTax() && $subtotalData->getValueInclTax()) {
                $subtotal = $subtotalData->getValueInclTax();
            } elseif ( $subtotalData->getValueExclTax()) {
                $subtotal = $subtotalData->getValueExclTax();
            }

            $minimumOrderAmount = $this->_getFreeShippingMinimumOrderAmount();
            $freeShippingLimit = $this->priceHelper->currency($minimumOrderAmount, false, false) - $subtotal;
            if ($freeShippingFromCartRuleApplied || $freeShippingLimit <= 0) {
                $shoppingCartMessageContent = $this->getShoppingCartFreeShippingMessageContent();
            } else {
                $formattedPrice = $this->priceCurrency->format($freeShippingLimit);
                $shoppingCartMessageContent = str_replace(['{amount_needed}'], ["<span id='shoppingcart-amount-needed'>" . $formattedPrice . "</span>"], $shoppingCartMessageContent);
            }
        }

        return $this->escaper->escapeHtml($shoppingCartMessageContent, self::MESSAGE_ALLOWED_TAGS);
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function _checkIfFreeShippingFromCartRuleApplied()
    {
        $appliedRuleIds = $this->checkoutSession->getQuote()->getAppliedRuleIds();
        if ($appliedRuleIds) {
            $salesRuleCollection = $this->salesRuleCollectionFactory->create();
            $salesRuleCollection->addFieldToFilter('rule_id', ['in' => explode(",", $appliedRuleIds)]);
            $salesRuleCollection->addFieldToFilter('simple_free_shipping', ['in' => [1,2]]);
            return ($salesRuleCollection->getSize()) ? true : false;
        }

        return false;
    }

    /**
     * @return bool
     */
    protected function isAtleastOneFreeShippingMethodEnabled()
    {
        foreach ($this->freeShippingOptions as $shippingOption) {
            if ($this->scopeConfig->getValue($shippingOption['flag'], ScopeInterface::SCOPE_STORE) &&
                $this->scopeConfig->getValue($shippingOption['flag_free_shipping'], ScopeInterface::SCOPE_STORE)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return mixed|null
     */
    protected function _getFreeShippingMinimumOrderAmount()
    {
        $minimumOrderAmount = null;

        foreach ($this->freeShippingOptions as $shippingOption) {
            if (($this->scopeConfig->getValue($shippingOption['flag'], ScopeInterface::SCOPE_STORE) &&
                $this->scopeConfig->getValue($shippingOption['flag_free_shipping'], ScopeInterface::SCOPE_STORE))) {
                if (is_null($minimumOrderAmount)) {
                    $minimumOrderAmount = $this->scopeConfig->getValue($shippingOption['treshold'], ScopeInterface::SCOPE_STORE);
                }
                $minimumOrderAmount = min($minimumOrderAmount, $this->scopeConfig->getValue($shippingOption['treshold'], ScopeInterface::SCOPE_STORE));
            }
        }
        return $minimumOrderAmount;
    }

    /**
     * @return mixed
     */
    protected function getTaxCalculationPriceIncludesTax() {
        return $this->scopeConfig->getValue('tax/calculation/price_includes_tax', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getQuickCartItemWrapperHeight() {
        $height = 'calc(100% - 255px) !important;';
        if ($this->taxConfig->displayCartSubtotalBoth()) {
            $height = 'calc(100% - 280px) !important;';
        }
        return $height;
    }

    /**
     * @param int $storeId
     * @return boolean
     */
    public function getQuickCartQtyType($storeId = null) {
        return $this->scopeConfig->getValue('weltpixel_quick_cart/shopping_cart_content/qty_button_type', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param int $storeId
     * @return boolean
     */
    public function getCartQtyType($storeId = null) {
        return $this->scopeConfig->getValue('weltpixel_quick_cart/shopping_cart_content/qty_button_type_cart_page', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }


    public function getQtyTemplateForQuickCartCart() {
        $template = 'WeltPixel_QuickCart/minicart/item/default.html';
        $qtyType = $this->getQuickCartQtyType();
        switch ($qtyType) {
            case \WeltPixel\QuickCart\Model\Config\Source\QuantitySignTypes::QTY_PLUSMINUS:
                $template = 'WeltPixel_QuickCart/minicart/item/default_plus_minus';
                break;
            case \WeltPixel\QuickCart\Model\Config\Source\QuantitySignTypes::QTY_DEFAULT:
                $template = 'WeltPixel_QuickCart/minicart/item/default_input';
                break;
        }

        return $template;
    }

    /**
     * @param int $storeId
     * @return string
     */
    public function getCMSCustomBlockIdentifier($storeId = null)
    {
        return $this->scopeConfig->getValue('weltpixel_quick_cart/shopping_cart_content/custom_block_quick_cart', ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param int $storeId
     * @return bool
     */
    public function isCMSCsutomBlockEnabled($storeId = null)
    {
        return $this->scopeConfig->getValue('weltpixel_quick_cart/shopping_cart_content/custom_block_quick_cart_enable', ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param int $storeId
     * @return bool
     */
    public function isCouponCodeEnabled($storeId = null)
    {
        return $this->scopeConfig->getValue('weltpixel_quick_cart/shopping_cart_content/enable_coupon_codes', ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param int $storeId
     * @return bool
     */
    public function isGrandTotalEnabled($storeId = null)
    {
        return $this->scopeConfig->getValue('weltpixel_quick_cart/shopping_cart_content/enable_grand_total', ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param int $storeId
     * @return string
     */
    public function getQuickCartDisplayOptions($storeId = null)
    {
        return $this->scopeConfig->getValue('weltpixel_quick_cart/general/open_minicart_display_options', ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return int
     */
    public function getMobileBreakPoint($storeId = null)
    {
        $wpMobileBreakpoint = str_replace("px", "", $this->scopeConfig->getValue('weltpixel_frontend_options/breakpoints/screen__m', ScopeInterface::SCOPE_STORE, $storeId) ?? '');
        if (isset($wpMobileBreakpoint) && strlen($wpMobileBreakpoint)) {
            return $wpMobileBreakpoint;
        }

        return 768;
    }

    /**
     * @param int $storeId
     * @return bool
     */
    public function isQuickCartDiscountedPriceEnabled($storeId = null)
    {
        return $this->scopeConfig->getValue('weltpixel_quick_cart/shopping_cart_content/discount_display_mode_enable', ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param int $storeId
     * @return bool
     */
    public function getQuickCartDiscountDisplayMode($storeId = null)
    {
        return $this->scopeConfig->getValue('weltpixel_quick_cart/shopping_cart_content/discount_display_mode', ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param int $storeId
     * @return bool
     */
    public function isCarouselEnabled($storeId = null)
    {
        return (bool) ($this->_moduleManager->isEnabled('WeltPixel_OwlCarouselSlider') &&
            $this->scopeConfig->getValue('weltpixel_quick_cart/carousel_options/display_carousel', ScopeInterface::SCOPE_STORE, $storeId));
    }

    /**
     * @param int $storeId
     * @return string
     */
    public function getCarouselType($storeId = null)
    {
        return $this->scopeConfig->getValue('weltpixel_quick_cart/carousel_options/carousel_type', ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param int $storeId
     * @return string
     */
    public function getCarouselDisplayFor($storeId = null)
    {
        return $this->scopeConfig->getValue('weltpixel_quick_cart/carousel_options/carousel_displayfor', ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param int $storeId
     * @return string
     */
    public function getCarouselTitle($storeId = null)
    {
        return $this->scopeConfig->getValue('weltpixel_quick_cart/carousel_options/carousel_title', ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param string $storeId
     * @return string
     */
    public function getCarouselTitleColor($storeId = null)
    {
        return $this->_cssColor(
            $this->scopeConfig->getValue('weltpixel_quick_cart/carousel_options/carousel_title_color', ScopeInterface::SCOPE_STORE, $storeId),
            '#000000'
        );
    }

    /**
     * @param string $storeId
     * @return string
     */
    public function getCarouselTitleFontSize($storeId = null)
    {
        return $this->_cssLength(
            $this->scopeConfig->getValue('weltpixel_quick_cart/carousel_options/carousel_title_fontsize', ScopeInterface::SCOPE_STORE, $storeId),
            '18px'
        );
    }

    /**
     * @param string $storeId
     * @return string
     */
    public function getCarouselArrowsBorderRadius($storeId = null)
    {
        $result = trim($this->scopeConfig->getValue('weltpixel_quick_cart/carousel_options/carousel_arrows_borderradius', ScopeInterface::SCOPE_STORE, $storeId) ?? '');
        if (strlen($result)) {
            return $this->_cssLength($result, '0');
        }
        return '0';
    }

    /**
     * @param string $storeId
     * @return string
     */
    public function getCarouselArrowsBackground($storeId = null)
    {
        $result = trim($this->scopeConfig->getValue('weltpixel_quick_cart/carousel_options/carousel_arrows_background', ScopeInterface::SCOPE_STORE, $storeId) ?? '');
        if (strlen($result)) {
            return $this->_cssColor($result, 'transparent');
        }
        return 'transparent';
    }

    /**
     * @param string $storeId
     * @return string
     */
    public function getCarouselArrowsColor($storeId = null)
    {
        $result = trim($this->scopeConfig->getValue('weltpixel_quick_cart/carousel_options/carousel_arrows_color', ScopeInterface::SCOPE_STORE, $storeId) ?? '');
        if (strlen($result)) {
            return $this->_cssColor($result, 'transparent');
        }
        return 'transparent';
    }

    /**
     * @param string $storeId
     * @return string
     */
    public function getCarouselArrowsHoverBackground($storeId = null)
    {
        $result = trim($this->scopeConfig->getValue('weltpixel_quick_cart/carousel_options/carousel_arrows_hover_background', ScopeInterface::SCOPE_STORE, $storeId) ?? '');
        if (strlen($result)) {
            return $this->_cssColor($result, 'transparent');
        }
        return 'transparent';
    }

    /**
     * @param string $storeId
     * @return string
     */
    public function getCarouselArrowsHoverColor($storeId = null)
    {
        $result = trim($this->scopeConfig->getValue('weltpixel_quick_cart/carousel_options/carousel_arrows_hover_color', ScopeInterface::SCOPE_STORE, $storeId) ?? '');
        if (strlen($result)) {
            return $this->_cssColor($result, 'transparent');
        }
        return 'transparent';
    }

    /**
     * @param string $storeId
     * @return string
     */
    public function getCarouselTitleAlignment($storeId = null)
    {
        return $this->scopeConfig->getValue('weltpixel_quick_cart/carousel_options/carousel_title_align', ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param int $storeId
     * @return string
     */
    public function getCarouselItemDesktop($storeId = null)
    {
        return $this->scopeConfig->getValue('weltpixel_quick_cart/carousel_options/carousel_item_desktop', ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param int $storeId
     * @return string
     */
    public function getCarouselItemMobile($storeId = null)
    {
        return $this->scopeConfig->getValue('weltpixel_quick_cart/carousel_options/carousel_item_mobile', ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCouponCodeForQuote()
    {
        return $this->checkoutSession->getQuote()->getCouponCode() ?? '';
    }

    /**
     * @param \Magento\Quote\Model\Cart\CartTotalRepository $cartTotalRepository
     * @return float|null|false
     */
    public function getDiscountAmount($cartTotalRepository)
    {
        $quoteId = null;
        if ($this->checkoutSession->getQuote()) {
            $quoteId = $this->checkoutSession->getQuote()->getId();
        }
        if (!$quoteId) {
            return false;
        }
        $totals = $cartTotalRepository->get($quoteId);
        $discountAmount = $totals->getDiscountAmount();
        if ($discountAmount == 0.0) {
            return false;
        }
        return $this->checkoutHelper->formatPrice($discountAmount);
    }

    /**
     * @param $cartTotalRepository
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getGrandTotal()
    {
        $grandTotal = 0;
        $totals = $this->checkoutSession->getQuote()->getTotals();
        if (isset($totals['grand_total'])) {
            $displayExcludingTax = (int)$this->taxConfig->displayCartSubtotalExclTax();
            if ($displayExcludingTax) {
                $grandTotal = $totals['grand_total']->getValueExclTax() ?: $totals['grand_total']->getValue();
            } else {
                $grandTotal = $totals['grand_total']->getValueInclTax() ?: $totals['grand_total']->getValue();
            }
        }

        return $this->checkoutHelper->formatPrice($grandTotal);
    }

    /**
     * Read one value out of the configuration cached in the constructor.
     *
     * That cache is a single scopeConfig->getValue() of the whole weltpixel_quick_cart section, so
     * it is null on a store where the section was never written, and a group or a leaf is simply
     * absent when only part of it was. Twenty one getters indexed two levels into it directly, and
     * this install promotes the resulting warning to an exception, so any of those shapes took the
     * header down on every page rather than falling back.
     *
     * @param string $group
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function _option($group, $key, $default = null)
    {
        if (!is_array($this->_quickcartOptions)) {
            return $default;
        }

        if (!isset($this->_quickcartOptions[$group]) || !is_array($this->_quickcartOptions[$group])) {
            return $default;
        }

        return $this->_quickcartOptions[$group][$key] ?? $default;
    }

    /**
     * Keep a configured colour inside a css value.
     *
     * These values are written straight into a <style> block by the minicart template, where
     * escapeHtml is the wrong tool: it leaves ; { } and : untouched, so it does not stop a value
     * from closing the declaration and opening a rule of its own. An allowlist does, and every
     * shape css actually accepts for a colour is cheap to describe.
     *
     * @param mixed $value
     * @param string $fallback
     * @return string
     */
    protected function _cssColor($value, $fallback)
    {
        if (!is_scalar($value)) {
            return $fallback;
        }

        $value = trim((string)$value);
        if ($value === '') {
            return $fallback;
        }

        /** #rgb, #rgba, #rrggbb, #rrggbbaa */
        if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $value)) {
            return $value;
        }

        /** A bare keyword: transparent, inherit, currentColor, red. No punctuation is possible. */
        if (preg_match('/^[a-zA-Z]+$/', $value)) {
            return $value;
        }

        /** rgb() rgba() hsl() hsla(), whose argument list cannot contain ; { } or : */
        if (preg_match('/^(?:rgba?|hsla?)\(\s*[0-9a-zA-Z.,%\s\/+-]+\s*\)$/', $value)) {
            return $value;
        }

        return $fallback;
    }

    /**
     * Keep a configured length inside a css value. Same reasoning as _cssColor().
     *
     * @param mixed $value
     * @param string $fallback
     * @return string
     */
    protected function _cssLength($value, $fallback)
    {
        if (!is_scalar($value)) {
            return $fallback;
        }

        $value = trim((string)$value);
        if ($value === '') {
            return $fallback;
        }

        $length = '(?:auto|inherit|initial|unset|0|[+-]?\d+(?:\.\d+)?(?:px|em|rem|%|vh|vw|pt|ex|ch))';
        if (preg_match('/^' . $length . '$/i', $value)) {
            return $value;
        }

        /** calc(), whose expression cannot contain ; { } or : either */
        if (preg_match('/^calc\(\s*[0-9a-zA-Z.%\s()+*\/-]+\s*\)$/i', $value)) {
            return $value;
        }

        return $fallback;
    }

    /**
     * @return false|\Magento\Csp\Helper\CspNonceProvider
     */
    public function getCspNonceProvider()
    {
        if (class_exists(\Magento\Csp\Helper\CspNonceProvider::class)) {
            return  \Magento\Framework\App\ObjectManager::getInstance()->get(\Magento\Csp\Helper\CspNonceProvider::class);
        }

        return false;
    }

}
