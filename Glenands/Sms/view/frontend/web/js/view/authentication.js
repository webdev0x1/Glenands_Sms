/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Ui/js/form/form',
    'Glenands_Sms/js/action/login',
    'Magento_Customer/js/model/customer',
    'mage/validation',
    'Magento_Checkout/js/model/authentication-messages',
    'Magento_Checkout/js/model/full-screen-loader'
], function ($, Component, loginAction, customer, validation, messageContainer, fullScreenLoader) {
    'use strict';

    var checkoutConfig = window.checkoutConfig;

    return Component.extend({
        isGuestCheckoutAllowed: checkoutConfig.isGuestCheckoutAllowed,
        isCustomerLoginRequired: checkoutConfig.isCustomerLoginRequired,
        registerUrl: checkoutConfig.registerUrl,
        isLoading: false,
        isLoadingPhone: false,
        forgotPasswordUrl: checkoutConfig.forgotPasswordUrl,
        autocomplete: checkoutConfig.autocomplete,
        defaults: {
            template: 'Glenands_Sms/authentication'
        },

        /**
         * Is login form enabled for current customer.
         *
         * @return {Boolean}
         */
        isActive: function () {
            return !customer.isLoggedIn();
        },

        /**
         * Initializes observable properties of instance
         *
         * @returns {Object} Chainable.
         */
         initObservable: function () {
            this._super()
                .observe(['emailcustom', 'phone']);

            return this;
        },

        /**
         * Provide login action.
         *
         * @param {HTMLElement} logi    nForm
         */
        login: function (loginForm) {
            var loginData = {},
                formDataArray = $(loginForm).serializeArray();

            formDataArray.forEach(function (entry) {
                loginData[entry.name] = entry.value;
                loginData['username'] = $('#login-email').val();
                loginData['password'] = $('#pass').val();
                if($('#customer-phone'))
                    loginData['phone_number'] = $('#customer-phone').val();
            });

            if ($(loginForm).validation() &&
                $(loginForm).validation('isValid')
            ) {
                fullScreenLoader.startLoader();
                loginAction(loginData, checkoutConfig.checkoutUrl, undefined, messageContainer).always(function () {
                    fullScreenLoader.stopLoader();
                });
            }
        },

        /**
         * Log in form submitting callback.
         *
         * @param {HTMLElement} otpForm, loginForm - form element.
         */
         otp: function (otpForm) {
             
            var otpData = {},
                formDataArray = $(otpForm).serializeArray();

            formDataArray.forEach(function (entry) {
                otpData[entry.name] = entry.value;
                loginData['username'] = $('#login-email').val();
                loginData['password'] = $('#pass').val();
                if($('#customer-phone'))
                    loginData['phone_number'] = $('#customer-phone').val();
            });

            if ($(otpForm).validation() && $(otpForm).validation('isValid')) {
                fullScreenLoader.startLoader();
                
                loginAction(otpData).always(function () {
                   fullScreenLoader.stopLoader();
                });
            }
        }
    });
});
