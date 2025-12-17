/**
 * Login as Customer popup handler for customer grid
 */
define([
    'jquery',
    'Magento_Ui/js/modal/confirm',
    'mage/translate',
    'mage/template',
    'underscore',
    'Magento_Ui/js/modal/alert'
], function ($, confirm, $t, template, _, alert) {
    'use strict';

    /**
     * Show login as customer confirmation popup for grid
     *
     * @param {String} url - Login URL
     * @param {Number} customerId - Customer ID
     * @param {String} fetchOptionsUrl - URL to fetch store options
     */
    return function (url, customerId, fetchOptionsUrl) {
        // First fetch the store options for this customer
        $.ajax({
            url: fetchOptionsUrl,
            type: 'GET',
            data: { customer_id: customerId },
            dataType: 'json',
            showLoader: true,
            success: function (response) {
                var content = '<div class="message message-warning">' + 
                    $t('Actions taken while in "Login as Customer" will affect actual customer data.') + 
                    '</div>';
                var title = $t('You are about to Login as Customer');
                var storeOptions = response.options || [];
                
                // If there are multiple store options, show the select dropdown
                if (storeOptions.length > 0) {
                    title = $t('Login as Customer: Select Store');
                    var selectHtml = '<div class="admin__field field-lac-store-id">' +
                        '<label class="admin__field-label" for="lac-confirmation-popup-store-id">' +
                        '<span>' + $t('Store') + '</span>' +
                        '</label>' +
                        '<div class="admin__field-control">' +
                        '<select id="lac-confirmation-popup-store-id" class="admin__control-select">';
                    
                    _.each(storeOptions, function(option) {
                        var disabled = option.disabled ? ' disabled="disabled"' : '';
                        var selected = option.selected ? ' selected="selected"' : '';
                        selectHtml += '<option value="' + option.value + '"' + disabled + selected + '>' + 
                            _.escape(option.label) + '</option>';
                    });
                    
                    selectHtml += '</select></div></div>';
                    content = selectHtml + content;
                }
                
                // Show confirmation popup
                confirm({
                    title: title,
                    content: content,
                    modalClass: 'confirm lac-confirm',
                    actions: {
                        confirm: function () {
                            var storeId = $('#lac-confirmation-popup-store-id').val();
                            var formKey = $('input[name="form_key"]').val();
                            var params = {};
                            
                            if (storeId) {
                                params.store_id = storeId;
                            }
                            
                            if (formKey) {
                                params.form_key = formKey;
                            }
                            
                            $.ajax({
                                url: url,
                                type: 'POST',
                                dataType: 'json',
                                data: params,
                                showLoader: true,
                                success: function (data) {
                                    var messages = data.messages || [];
                                    
                                    if (data.message) {
                                        messages.push(data.message);
                                    }
                                    
                                    if (data.redirectUrl) {
                                        window.open(data.redirectUrl);
                                    } else if (messages.length) {
                                        messages = messages.map(function (message) {
                                            return _.escape(message);
                                        });
                                        
                                        alert({
                                            content: messages.join('<br>')
                                        });
                                    }
                                },
                                error: function (jqXHR) {
                                    alert({
                                        content: _.escape(jqXHR.responseText)
                                    });
                                }
                            });
                        }
                    },
                    buttons: [{
                        text: $t('Cancel'),
                        class: 'action-secondary action-dismiss',
                        click: function (event) {
                            this.closeModal(event);
                        }
                    }, {
                        text: $t('Login as Customer'),
                        class: 'action-primary action-accept',
                        click: function (event) {
                            this.closeModal(event, true);
                        }
                    }]
                });
            },
            error: function () {
                alert({
                    content: $t('Unable to fetch store options.')
                });
            }
        });
        
        return false;
    };
});
