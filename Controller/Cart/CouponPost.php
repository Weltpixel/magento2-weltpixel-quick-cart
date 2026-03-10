<?php
namespace WeltPixel\QuickCart\Controller\Cart;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CouponPost extends \Magento\Checkout\Controller\Cart implements HttpPostActionInterface
{
    /**
     * Sales quote repository
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\SalesRule\Model\CouponFactory
     */
    protected $couponFactory;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\SalesRule\Model\CouponFactory $couponFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\SalesRule\Model\CouponFactory $couponFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Framework\Json\Helper\Data $jsonHelper
    ) {
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart
        );
        $this->couponFactory = $couponFactory;
        $this->quoteRepository = $quoteRepository;
        $this->jsonHelper = $jsonHelper;
    }

    /**
     * Initialize coupon
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $responseData = ['msg' => ''];
        $couponCode = $this->getRequest()->getParam('remove') == 1
            ? ''
            : trim($this->getRequest()->getParam('coupon_code', ''));

        $cartQuote = $this->cart->getQuote();
        $oldCouponCode = $cartQuote->getCouponCode() ?? '';

        $codeLength = strlen($couponCode);
        if (!$codeLength && !strlen($oldCouponCode)) {
            return $this->jsonResponse($responseData);
        }

        try {
            $isCodeLengthValid = $codeLength && $codeLength <= \Magento\Checkout\Helper\Cart::COUPON_CODE_MAX_LENGTH;

            $itemsCount = $cartQuote->getItemsCount();
            if ($itemsCount) {
                $cartQuote->getShippingAddress()->setCollectShippingRates(true);
                $cartQuote->setCouponCode($isCodeLengthValid ? $couponCode : '')->collectTotals();
                $this->quoteRepository->save($cartQuote);
            }

            if ($codeLength) {
                $escaper = $this->_objectManager->get(\Magento\Framework\Escaper::class);
                $coupon = $this->couponFactory->create();
                $coupon->load($couponCode, 'code');
                if (!$itemsCount) {
                    if ($isCodeLengthValid && $coupon->getId()) {
                        $this->_checkoutSession->getQuote()->setCouponCode($couponCode)->save();
                            $responseData['msg'] = __(
                                'You used coupon code "%1".',
                                $escaper->escapeHtml($couponCode)
                            );
                            $responseData['status'] = 'success';
                    } else {
                        $responseData['msg'] = __(
                            'The coupon code "%1" is not valid.',
                            $escaper->escapeHtml($couponCode)
                        );
                        $responseData['status'] = 'error';
                    }
                } else {
                    if ($isCodeLengthValid && $coupon->getId() && $couponCode == $cartQuote->getCouponCode()) {
                        $responseData['msg'] = __(
                                'You used coupon code "%1".',
                                $escaper->escapeHtml($couponCode)
                            );
                        $responseData['status'] = 'success';
                    } else {
                        $responseData['msg'] = __(
                            'The coupon code "%1" is not valid.',
                            $escaper->escapeHtml($couponCode)
                        );
                        $responseData['status'] = 'error';
                    }
                }
            } else {
                $responseData['msg'] = __('You canceled the coupon code.');
                $responseData['status'] = 'success';
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $responseData['msg'] = $this->messageManager->addErrorMessage($e->getMessage());
            $responseData['status'] = 'error';
        } catch (\Exception $e) {
            $responseData['msg'] =  $this->messageManager->addErrorMessage(__('We cannot apply the coupon code.'));
            $responseData['status'] = 'error';
        }

        return $this->jsonResponse($responseData);
    }


    /**
     * Create json response
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function jsonResponse($response = '')
    {
        if (strpos( $this->_redirect->getRefererUrl(), "checkout/cart") !== false) {
            if ($response['status'] == 'success') {
                $this->messageManager->addSuccessMessage($response['msg']);
            } else {
                $this->messageManager->addErrorMessage($response['msg']);
            }
            return $this->_goBack();
        }
        return $this->getResponse()->representJson(
            $this->jsonHelper->jsonEncode($response)
        );
    }
}
