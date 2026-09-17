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

        $escaper = $this->_objectManager->get(\Magento\Framework\Escaper::class);

        try {
            $isCodeLengthValid = $codeLength && $codeLength <= \Magento\Checkout\Helper\Cart::COUPON_CODE_MAX_LENGTH;

            $itemsCount = $cartQuote->getItemsCount();
            if ($itemsCount) {
                $cartQuote->getShippingAddress()->setCollectShippingRates(true);
                $cartQuote->setCouponCode($isCodeLengthValid ? $couponCode : '')->collectTotals();
                $this->quoteRepository->save($cartQuote);
            }

            if ($codeLength) {
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
            /**
             * addErrorMessage() returns the message manager, not the message, so assigning its
             * return value here sent {"msg":{}} to the browser and the shopper saw nothing on the
             * one path where something had gone wrong. The text is assigned instead, and the
             * queueing is left to jsonResponse(), which already adds it on the referer branch and
             * would otherwise add it twice. It is escaped because the coupon widget appends the
             * value as html.
             */
            $responseData['msg'] = $escaper->escapeHtml($e->getMessage());
            $responseData['status'] = 'error';
        } catch (\Exception $e) {
            $responseData['msg'] = __('We cannot apply the coupon code.');
            $responseData['status'] = 'error';
        }

        return $this->jsonResponse($responseData);
    }


    /**
     * Create json response
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function jsonResponse($response = [])
    {
        if (strpos($this->_redirect->getRefererUrl() ?? '', "checkout/cart") !== false) {
            /**
             * Not every path through execute() sets a status. The early return for "no coupon
             * submitted and none stored" passes only an empty msg, so reading $response['status']
             * unconditionally raised Undefined array key here, which this install promotes to an
             * exception - a 500 on an endpoint any visitor can reach. A response with nothing to
             * say now goes back without queueing a message at all, and the status is only
             * consulted when there is a message to classify.
             */
            $message = $response['msg'] ?? '';
            if ($message !== '' && $message !== null) {
                if (($response['status'] ?? '') === 'success') {
                    $this->messageManager->addSuccessMessage($message);
                } else {
                    $this->messageManager->addErrorMessage($message);
                }
            }

            return $this->_goBack();
        }

        return $this->getResponse()->representJson(
            $this->jsonHelper->jsonEncode($response)
        );
    }
}
