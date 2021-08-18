/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/storage',
    'Magento_Ui/js/model/messageList',
    'Magento_Customer/js/customer-data',
    'mage/translate',
    'Magento_Checkout/js/model/full-screen-loader',
    'Glenands_Sms/js/view/authentication-popup'
], function ($, storage, globalMessageList, customerData, $t, fullScreenLoader, popup) {
    'use strict';

    var callbacks = [],

        /**
         * @param {Object} loginData
         * @param {String} redirectUrl
         * @param {*} isGlobal
         * @param {Object} messageContainer
         */
        action = function (loginData, redirectUrl, isGlobal, messageContainer) {
            messageContainer = messageContainer || globalMessageList;
            if($('#customer-otp2').val() || $('#customer-otp1').val()) {
                $('.popup-otp .loading-mask').css('display', 'block');
            }
            if($('#customer-phone').val()) {
                $('.popup-phone .loading-mask').css('display', 'block');
            }
            
           // $('#cart-totals').trigger('processStart');
            return storage.post(
                'glenands/ajax/loginpost',
                JSON.stringify(loginData),
                isGlobal
            ).done(function (response) {
                if (response.errors) {
                    if(response.phoneRequired) {
                        if($('#customer-phone')) {
                           $('#customer-phone').val('');
                           $('#customer-phone').prop("disabled", false);
                        }
                        
                        $('#phonemodal').modal('openModal');
                        //popup.isLoading(false);
                        $('.popup-authentication .loading-mask').css('display', 'none');
                        $('.popup-phone .loading-mask').css('display', 'none');
                    } else {
                        messageContainer.addErrorMessage(response);
                        $("#modal1").modal("closeModal");  
                        $("#modal2").modal("closeModal");  
                        callbacks.forEach(function (callback) {
                            callback(loginData);
                        });
                    }
                } else {
                    callbacks.forEach(function (callback) {
                        callback(loginData);
                    });
                    customerData.invalidate(['customer']);

                    if(response.isOtpSuccess) {
                        alert(response.message);
                        $('#phonemodal').modal('closeModal');
                        $('.popup-phone .loading-mask').css('display', 'none');
                        var modelClass = '';
                        if($('body').hasClass('checkout-index-index')){
                            modelClass = '#modal1';
                        } else {
                            modelClass = '#modal2';
                        }
                        $(modelClass).modal('openModal');
                        $(modelClass).data('mage-modal').modal.on('modalopened', function() {
                            var zindx = $('.modals-overlay').css("z-index");
                            
                            if($('body').hasClass('checkout-index-index')){
                                $('.loading-mask').css('display', 'none');
                                //$('.modals-overlay').css("z-index", '902');
                            } else {
                                $('.modals-overlay').css("z-index", '902');
                            }
                            console.log('opened')
                        });

                        $(modelClass).data('mage-modal').modal.on('modalclosed', function() {
                            var zindx = $('.modals-overlay').css("z-index");
                            $('#customer-otp2').val('');
                            $('#customer-otp1').val('');
                            if($('body').hasClass('checkout-index-index')){
                                $('.loading-mask').css('display', 'none');
                               // $('.modals-overlay').css("z-index", '901'); 
                            } else {
                                $('.block-authentication .loading-mask').css('display', 'none');
                                $('.modals-overlay').css("z-index", '901');
                            }
                            console.log('closed')
                        });

                    } else if (response.redirectUrl) {
                        window.location.href = response.redirectUrl;
                    } else if (redirectUrl) {
                        window.location.href = redirectUrl;
                    }  else {   
                        location.reload();
                    }
                }
            }).fail(function () {
                messageContainer.addErrorMessage({
                    'message': $t('Could not authenticate. Please try again later')
                });
                $("#modal1").modal("closeModal");  
                $("#modal2").modal("closeModal");  

                callbacks.forEach(function (callback) {
                    callback(loginData);
                });
            });
        };

    /**
     * @param {Function} callback
     */
    action.registerLoginCallback = function (callback) {
        callbacks.push(callback);
    };

    return action;
});
