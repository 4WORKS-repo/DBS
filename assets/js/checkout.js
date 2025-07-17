/**
 * Checkout Calculator JavaScript
 * 
 * @package Distance_Based_Shipping
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Debounce function
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Checkout Calculator Class
    class CheckoutCalculator {
        constructor() {
            this.init();
        }

        init() {
            this.bindEvents();
            this.initializeAddressValidation();
            this.initializeAutoCalculation();
        }

        bindEvents() {
            // Calculate button
            $(document).on('click', '#dbs-checkout-calculate', (e) => {
                e.preventDefault();
                this.calculateShipping();
            });

            // Apply shipping button
            $(document).on('click', '.dbs-apply-shipping-btn', (e) => {
                e.preventDefault();
                this.applyShipping();
            });

            // Address input changes
            $(document).on('input', '#dbs-checkout-address', debounce((e) => {
                this.handleAddressInput(e);
            }, 500));

            // Address suggestions
            $(document).on('click', '.dbs-address-suggestion', (e) => {
                e.preventDefault();
                this.selectAddress($(e.target).text());
            });

            // Checkout form updates
            $(document.body).on('updated_checkout', () => {
                this.handleCheckoutUpdate();
            });

            // Checkout form validation
            $(document.body).on('checkout_error', () => {
                this.handleCheckoutError();
            });
        }

        initializeAddressValidation() {
            // Validace adresy při změně checkout formuláře
            $(document.body).on('change', 'input[name="billing_address_1"], input[name="billing_city"], input[name="billing_postcode"]', debounce(() => {
                this.validateCheckoutAddress();
            }, 1000));
        }

        initializeAutoCalculation() {
            // Automatický výpočet při načtení stránky, pokud je uložená adresa
            const savedAddress = $('#dbs-checkout-address').val();
            if (savedAddress) {
                this.calculateShipping();
            }
            
            // Automatický přepočet dopravy při změně množství
            this.initializeQuantityChangeHandler();
        }
        
        initializeQuantityChangeHandler() {
            // Sleduj změny množství v košíku
            $(document.body).on('change', 'input[name*="quantity"], .quantity input', debounce((e) => {
                this.handleQuantityChange(e);
            }, 1000));
            
            // Sleduj změny množství na checkout stránce
            $(document.body).on('updated_checkout', () => {
                this.handleCheckoutQuantityUpdate();
            });
            
            // Sleduj změny množství v cart
            $(document.body).on('updated_cart_totals', () => {
                this.handleCartQuantityUpdate();
            });
        }
        
        handleQuantityChange(e) {
            const quantity = parseInt($(e.target).val()) || 0;
            const productId = $(e.target).closest('tr, .cart-item').find('[name*="product_id"], [data-product_id]').val();
            
            if (quantity > 0 && productId) {
                console.log('DBS: Quantity changed - Product:', productId, 'Quantity:', quantity);
                this.recalculateShipping();
            }
        }
        
        handleCheckoutQuantityUpdate() {
            // Kontroluj, zda se změnilo množství na checkout stránce
            const currentQuantities = this.getCurrentQuantities();
            const previousQuantities = this.getPreviousQuantities();
            
            if (JSON.stringify(currentQuantities) !== JSON.stringify(previousQuantities)) {
                console.log('DBS: Checkout quantities changed');
                this.recalculateShipping();
                this.updatePreviousQuantities(currentQuantities);
            }
        }
        
        handleCartQuantityUpdate() {
            // Kontroluj, zda se změnilo množství v košíku
            const currentQuantities = this.getCurrentQuantities();
            const previousQuantities = this.getPreviousQuantities();
            
            if (JSON.stringify(currentQuantities) !== JSON.stringify(previousQuantities)) {
                console.log('DBS: Cart quantities changed');
                this.recalculateShipping();
                this.updatePreviousQuantities(currentQuantities);
            }
        }
        
        getCurrentQuantities() {
            const quantities = {};
            $('input[name*="quantity"], .quantity input').each(function() {
                const productId = $(this).closest('tr, .cart-item').find('[name*="product_id"], [data-product_id]').val();
                if (productId) {
                    quantities[productId] = parseInt($(this).val()) || 0;
                }
            });
            return quantities;
        }
        
        getPreviousQuantities() {
            return this.previousQuantities || {};
        }
        
        updatePreviousQuantities(quantities) {
            this.previousQuantities = quantities;
        }
        
        recalculateShipping() {
            // Získej aktuální adresu
            const address = this.getCurrentAddress();
            if (!address) {
                console.log('DBS: No address available for shipping recalculation');
                return;
            }
            
            console.log('DBS: Recalculating shipping for address:', address);
            
            // Automaticky přepočítej dopravu
            this.calculateShipping();
        }
        
        getCurrentAddress() {
            // Zkus získat adresu z checkout formuláře
            const billingAddress = $('input[name="billing_address_1"]').val();
            const billingCity = $('input[name="billing_city"]').val();
            const billingPostcode = $('input[name="billing_postcode"]').val();
            
            if (billingAddress && billingCity && billingPostcode) {
                return `${billingAddress}, ${billingCity}, ${billingPostcode}`;
            }
            
            // Zkus získat uloženou adresu
            const savedAddress = $('#dbs-checkout-address').val();
            if (savedAddress) {
                return savedAddress;
            }
            
            // Zkus získat adresu z session
            return null;
        }

        handleAddressInput(e) {
            const address = $(e.target).val();
            if (address.length < 3) {
                this.hideSuggestions();
                return;
            }

            this.showAddressSuggestions(address);
        }

        showAddressSuggestions(address) {
            const suggestionsContainer = $('#dbs-checkout-suggestions');
            
            $.ajax({
                url: dbs_checkout.ajax_url,
                type: 'POST',
                data: {
                    action: 'dbs_address_suggestions',
                    address: address,
                    nonce: dbs_checkout.nonce
                },
                success: (response) => {
                    if (response.success && response.data.suggestions.length > 0) {
                        let suggestionsHtml = '';
                        response.data.suggestions.forEach(suggestion => {
                            suggestionsHtml += `<div class="dbs-address-suggestion">${suggestion}</div>`;
                        });
                        suggestionsContainer.html(suggestionsHtml).show();
                    } else {
                        this.hideSuggestions();
                    }
                },
                error: () => {
                    this.hideSuggestions();
                }
            });
        }

        hideSuggestions() {
            $('#dbs-checkout-suggestions').hide();
        }

        selectAddress(address) {
            $('#dbs-checkout-address').val(address);
            this.hideSuggestions();
            this.calculateShipping();
        }

        calculateShipping() {
            const address = $('#dbs-checkout-address').val();
            
            if (!address) {
                this.showError('Zadejte prosím adresu pro výpočet dopravy.');
                return;
            }

            this.showLoading();
            this.hideError();
            this.hideResult();

            $.ajax({
                url: dbs_checkout.ajax_url,
                type: 'POST',
                data: {
                    action: 'dbs_checkout_calculator',
                    address: address,
                    nonce: dbs_checkout.nonce
                },
                success: (response) => {
                    this.hideLoading();
                    
                    if (response.success) {
                        this.showResult(response.data);
                    } else {
                        this.showError(response.data.message || 'Došlo k chybě při výpočtu dopravy.');
                    }
                },
                error: () => {
                    this.hideLoading();
                    this.showError('Došlo k chybě při komunikaci se serverem.');
                }
            });
        }

        applyShipping() {
            const shippingMethod = 'dbs_shipping';
            const shippingCost = parseFloat($('.dbs-cost-value').text().replace(/[^\d.,]/g, '').replace(',', '.'));

            if (!shippingCost || isNaN(shippingCost)) {
                this.showError('Nepodařilo se získat cenu dopravy.');
                return;
            }

            this.showLoading();

            $.ajax({
                url: dbs_checkout.ajax_url,
                type: 'POST',
                data: {
                    action: 'dbs_apply_checkout_shipping',
                    shipping_method: shippingMethod,
                    shipping_cost: shippingCost,
                    nonce: dbs_checkout.nonce
                },
                success: (response) => {
                    this.hideLoading();
                    
                    if (response.success) {
                        this.showSuccess(response.data.message);
                        this.updateCheckout();
                    } else {
                        this.showError(response.data.message || 'Nepodařilo se aplikovat dopravu.');
                    }
                },
                error: () => {
                    this.hideLoading();
                    this.showError('Došlo k chybě při aplikování dopravy.');
                }
            });
        }

        validateCheckoutAddress() {
            const address = $('input[name="billing_address_1"]').val();
            const city = $('input[name="billing_city"]').val();
            const postcode = $('input[name="billing_postcode"]').val();

            if (address && city && postcode) {
                const fullAddress = `${address}, ${city}, ${postcode}`;
                $('#dbs-checkout-address').val(fullAddress);
                
                // Automaticky přepočítej dopravu
                setTimeout(() => {
                    this.calculateShipping();
                }, 500);
            }
        }

        handleCheckoutUpdate() {
            // Aktualizuj zobrazení po změně checkout formuláře
            this.updateShippingInfo();
        }

        handleCheckoutError() {
            // Skryj loading při chybě checkout formuláře
            this.hideLoading();
        }

        updateCheckout() {
            // Aktualizuj checkout formulář
            $(document.body).trigger('update_checkout');
        }

        updateShippingInfo() {
            // Aktualizuj zobrazení shipping informací
            const savedAddress = $('#dbs-checkout-address').val();
            if (savedAddress) {
                $('.dbs-saved-address').text(savedAddress);
            }
        }

        showLoading() {
            const btn = $('#dbs-checkout-calculate');
            btn.prop('disabled', true);
            btn.find('.dbs-btn-text').hide();
            btn.find('.dbs-btn-loading').show();
        }

        hideLoading() {
            const btn = $('#dbs-checkout-calculate');
            btn.prop('disabled', false);
            btn.find('.dbs-btn-text').show();
            btn.find('.dbs-btn-loading').hide();
        }

        showResult(data) {
            const resultContainer = $('#dbs-checkout-result');
            
            // Aktualizuj hodnoty
            $('.dbs-distance-value').text(data.distance + ' km');
            $('.dbs-cost-value').text(this.formatPrice(data.cost));
            $('.dbs-delivery-value').text(data.delivery_time);
            
            // Zobraz výsledek
            resultContainer.show();
            
            // Scroll na výsledek
            $('html, body').animate({
                scrollTop: resultContainer.offset().top - 100
            }, 500);
        }

        hideResult() {
            $('#dbs-checkout-result').hide();
        }

        showError(message) {
            const errorContainer = $('#dbs-checkout-error');
            errorContainer.find('.dbs-error-message').text(message);
            errorContainer.show();
            
            // Skryj po 5 sekundách
            setTimeout(() => {
                this.hideError();
            }, 5000);
        }

        hideError() {
            $('#dbs-checkout-error').hide();
        }

        showSuccess(message) {
            // Zobraz success zprávu
            const successHtml = `<div class="dbs-success-message">${message}</div>`;
            $('#dbs-checkout-calculator').prepend(successHtml);
            
            // Skryj po 3 sekundách
            setTimeout(() => {
                $('.dbs-success-message').fadeOut();
            }, 3000);
        }

        formatPrice(price) {
            return new Intl.NumberFormat('cs-CZ', {
                style: 'currency',
                currency: 'CZK'
            }).format(price);
        }
    }

    // Initialize when document is ready
    $(document).ready(() => {
        if ($('#dbs-checkout-calculator').length) {
            new CheckoutCalculator();
        }
    });

    // Initialize on checkout update
    $(document.body).on('updated_checkout', () => {
        if ($('#dbs-checkout-calculator').length && !window.dbsCheckoutCalculator) {
            window.dbsCheckoutCalculator = new CheckoutCalculator();
        }
    });

})(jQuery); 