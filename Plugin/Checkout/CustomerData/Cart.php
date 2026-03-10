<?php

namespace WeltPixel\QuickCart\Plugin\Checkout\CustomerData;

use Magento\Framework\UrlInterface;
use WeltPixel\QuickCart\Helper\Data as QuickCartHelper;
use Magento\Framework\View\LayoutInterface;
use Magento\Quote\Model\Cart\CartTotalRepository;

class Cart
{
    /**
     * @var QuickCartHelper
     */
    protected $quickCartHelper;

    /**
     * @var LayoutInterface
     */
    protected $layout;

    /**
     * Url Builder
     *
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var CartTotalRepository
     */
    protected $cartTotalRepository;


    /**
     * @param QuickCartHelper $quickCartHelper
     * @param LayoutInterface $layout
     * @param UrlInterface $urlBuilder
     * @param CartTotalRepository $cartTotalRepository
     */
    public function __construct(
        QuickCartHelper $quickCartHelper,
        LayoutInterface $layout,
        UrlInterface $urlBuilder,
        CartTotalRepository $cartTotalRepository
    ) {
        $this->quickCartHelper = $quickCartHelper;
        $this->layout = $layout;
        $this->urlBuilder = $urlBuilder;
        $this->cartTotalRepository = $cartTotalRepository;
    }

    /**
     * @param \Magento\Checkout\CustomerData\Cart $subject
     * @param array $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetSectionData(\Magento\Checkout\CustomerData\Cart $subject, $result)
    {
        if (!$this->quickCartHelper->quicartIsEnabled()) {
            return $result;
        }

        $quickCartMessageEnabled = false;
        $quickCartMessageContent = '';
        if ($this->quickCartHelper->isQuickCartMessageEnabled()) {
            $quickCartMessageEnabled = true;
            $quickCartMessageContent = $this->quickCartHelper->getQuickCartMessageContentForDisplay();
        }

        $result['weltpixel_quickcart_message_enabled'] = $quickCartMessageEnabled;
        $result['weltpixel_quickcart_message_content'] = $quickCartMessageContent;

        if ($this->quickCartHelper->isCMSCsutomBlockEnabled()) {
            $cmsBlockContent = $this->layout
                ->createBlock('Magento\Cms\Block\Block')
                ->setBlockId($this->quickCartHelper->getCMSCustomBlockIdentifier())
                ->toHtml();

            $result['weltpixel_quickcart_cmsblock'] = $cmsBlockContent;
        }

        $quickCartCarouselEnabled = false;
        $quickCartCarouselContent = '';
        if ($this->quickCartHelper->isCarouselEnabled()) {
            $quickCartCarouselEnabled = true;
            $abstractProductBlock = $this->layout->createBlock('\Magento\Catalog\Block\Product\AbstractProduct', 'wpQuickCartAbstractProduct');
            $carouselContentBlock = $this->layout->createBlock('\WeltPixel\QuickCart\Block\CarouselContent')
                ->setTemplate('WeltPixel_QuickCart::carousel/content.phtml')
                ->setProductViewModel($abstractProductBlock);

            /** @var \Magento\Framework\Pricing\Render $priceRender */
            $priceRender = $this->layout->getBlock('product.price.render.default');
            if (!$priceRender) {
                $this->layout->createBlock(
                    \Magento\Framework\Pricing\Render::class,
                    'product.price.render.default',
                    ['data' => ['price_render_handle' => 'catalog_product_prices']]
                );
            }

            $quickCartCarouselContent = $carouselContentBlock->toHtml();
        }

        $result['weltpixel_quickcart_carousel_enabled'] = $quickCartCarouselEnabled;
        $result['weltpixel_quickcart_carousel_content'] = $quickCartCarouselContent;

        $isCouponCodeDisplayEnabled = false;
        $isGrandTotalDisplayEnabled = false;
        $hasCouponCode = false;
        $discountAmount = false;
        $couponCode = $this->quickCartHelper->getCouponCodeForQuote();
        if ($this->quickCartHelper->isCouponCodeEnabled()) {
            $isCouponCodeDisplayEnabled = true;
            $hasCouponCode = (bool) strlen($couponCode);
            if ($hasCouponCode) {
                $discountAmount = $this->quickCartHelper->getDiscountAmount($this->cartTotalRepository);
            }
        }

        if ($this->quickCartHelper->isGrandTotalEnabled()) {
            $discountAmount = $this->quickCartHelper->getDiscountAmount($this->cartTotalRepository);
            $isGrandTotalDisplayEnabled = true;
            $grandTotal = $this->quickCartHelper->getGrandTotal($this->cartTotalRepository);
            $result['weltpixel_quickcart_grand_total'] = $grandTotal;
        }

        $result['weltpixel_quickcart_grand_total_enabled'] = $isGrandTotalDisplayEnabled;
        $result['weltpixel_quickcart_coupon_enabled'] = $isCouponCodeDisplayEnabled;
        $result['weltpixel_quickcart_has_coupon_code'] = $hasCouponCode;
        $result['weltpixel_quickcart_coupon_code'] = $couponCode;
        $result['weltpixel_quickcart_discount_amount'] = $discountAmount;
        $result['weltpixel_quickcart_coupon_url'] = $this->urlBuilder->getUrl('weltpixel_quickcart/cart/couponPost');

        return $result;
    }
}
