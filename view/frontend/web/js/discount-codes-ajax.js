/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'jquery-ui-modules/widget'
], function ($) {
    'use strict';

    $.widget('mage.discountCodeAjax', {
        options: {
        },

        /** @inheritdoc */
        _create: function () {
            this.couponCode = $(this.options.couponCodeSelector);
            this.removeCoupon = $(this.options.removeCouponSelector);

            $(this.element).submit(function() {
                let formElement = $(this);
                try {
                    if (formElement.valid()) {
                        $.ajax({
                            url: $(this).attr('action'),
                            method: 'POST',
                            data: $(this).serialize(),
                            success: function (response) {
                                $('.dynamic-coupon-msg').remove();
                                formElement.append('<p class="' +  response.status + ' message dynamic-coupon-msg">' + response.msg + '</p>');
                                $('.dynamic-coupon-msg').delay(5000).fadeOut('slow');
                            }
                        });
                    }
                } catch (e) {
                    return true;
                }
                return false;
            });


            $('.quickcart-content-wrapper').on('click', this.options.applyButton, $.proxy(function () {
                this.couponCode.attr('data-validate', '{required:true}');
                this.removeCoupon.attr('value', '0');
                try {
                    this.element.validation().trigger('submit');}
                catch (e) {
                    window.location.reload();
                }
            }, this));

            $('.quickcart-content-wrapper').on('click', this.options.cancelButton, $.proxy(function () {
                this.couponCode.removeAttr('data-validate');
                this.removeCoupon.attr('value', '1');
                this.element.trigger('submit');
            }, this));
        }
    });

    return $.mage.discountCode;
});
