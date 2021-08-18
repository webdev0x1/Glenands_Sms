/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 define([
    'jquery',
    'ko',
    'Magento_Ui/js/form/form',
    'Glenands_Sms/js/action/login',
    'Magento_Customer/js/customer-data',
    'Magento_Customer/js/model/authentication-popup',
    'mage/translate',
    'mage/url',
    'Magento_Ui/js/modal/alert',
    'Magento_Checkout/js/model/full-screen-loader',
    'mage/validation'
], function ($, ko, Component, loginAction, customerData, authenticationPopup, $t, url, alert, fullScreenLoader) {
    'use strict';

    return Component.extend({
        registerUrl: window.authenticationPopup.customerRegisterUrl,
        forgotPasswordUrl: window.authenticationPopup.customerForgotPasswordUrl,
        autocomplete: window.authenticationPopup.autocomplete,
        modalWindow: null,
        isLoading: ko.observable(false),
        isLoadingPhone: ko.observable(false),
        isLoadingOtp: ko.observable(false),
        defaults: {
            template: 'Glenands_Sms/authentication-popup'
        },

        /**
         * Init
         */
        initialize: function () {
            var self = this;

            this._super();
            url.setBaseUrl(window.authenticationPopup.baseUrl);
            loginAction.registerLoginCallback(function () {
                self.isLoading(false);
                self.isLoadingPhone(false);
                self.isLoadingOtp(false);
            });
        },
        
        
        /**
         * Initializes observable properties of instance
         *
         * @returns {Object} Chainable.
         */
         initObservable: function () {
            this._super()
                .observe(['email', 'phone']);

            return this;
        },

        /** Init popup login window */
        setModalElement: function (element) {
            if (authenticationPopup.modalWindow == null) {
                authenticationPopup.createPopUp(element);
            }
        },

        /** Is login form enabled for current customer */
        isActive: function () {
            var customer = customerData.get('customer');

            return customer() == false; //eslint-disable-line eqeqeq
        },

        /** Show login popup window */
        showModal: function () {
            if (this.modalWindow) {
                $(this.modalWindow).modal('openModal');
            } else {
                alert({
                    content: $t('Guest checkout is disabled.')
                });
            }
        },

        /**
         * Provide login action
         *
         * @return {Boolean}
         */
        login: function (formUiElement, event) {
            var loginData = {},
                formElement = $(event.currentTarget),
                formDataArray = formElement.serializeArray();

            event.stopPropagation();
            formDataArray.forEach(function (entry) {
                loginData[entry.name] = entry.value;
                loginData['username'] = $('#customer-email').val();
            });

            if (formElement.validation() &&
                formElement.validation('isValid')
            ) {
                this.isLoading(true);
                this.isLoadingPhone(true);
                loginAction(loginData);
            }

            return false;
        },
        /**
         * Provide otp action
         *
         * @return {Boolean}
         */
        otp: function (formUiElement, event) {
            var loginData = {},
                formElement = $(event.currentTarget),
                formDataArray = formElement.serializeArray();

            event.stopPropagation();
            formDataArray.forEach(function (entry) {
                loginData[entry.name] = entry.value;
                loginData['username'] = $('#customer-email').val();
                loginData['password'] = $('#pass').val();
                if($('#customer-phone'))
                    loginData['phone_number'] = $('#customer-phone').val();
            });

            if (formElement.validation() &&
                formElement.validation('isValid')
            ) {
                if($('#customer-phone').val() != null || $('#customer-phone').val() != '') {
                    this.isLoading(true);
                    this.isLoadingPhone(true);
                    this.isLoadingOtp(true);
                    fullScreenLoader.startLoader();
                } else {
                    //fullScreenLoader.stopLoader();
                    this.isLoadingPhone(false);
                    this.isLoadingOtp(false);
                    this.isLoading(false);
                }
                
                loginAction(loginData);
            }

            return false;
        }
    });
});
