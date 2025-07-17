/**
 * Frontend JavaScript pro Distance Based Shipping plugin.
 *
 * Soubor: assets/js/frontend.js
 */

;(function ($) {
  "use strict"

  /**
   * Hlavní frontend objekt
   */
  const DBSFrontend = {
    /**
     * Inicializace
     */
    init: function () {
      this.bindEvents()
      this.initShippingCalculator()
      this.initCheckoutIntegration()
      this.initCartIntegration()
      this.initSmartAddressHandling()
    },

    /**
     * Navázání event handlerů
     */
    bindEvents: function () {
      // Shipping kalkulátor
      $(document).on(
        "submit",
        ".dbs-shipping-calculator form",
        this.handleShippingCalculation
      )

      // Checkout integrace
      $(document.body).on("updated_checkout", this.handleCheckoutUpdate)

      // Address změny s debounce
      $(document).on(
        "change input",
        'input[name^="billing_"], input[name^="shipping_"]',
        this.debounce(this.handleAddressChange, 1500)
      )

      // Cart quantity changes - invalidate shipping cache
      $(document).on("change", ".qty", this.handleQuantityChange)
      $(document).on("click", ".plus, .minus", this.handleQuantityButtonClick)

      // Cart page specific events - handled by general shipping calculator

      // Responzivní handling
      $(window).on("resize", this.debounce(this.handleResize, 250))
      
      // Načtení uložených shipping dat při načtení checkout stránky
      $(document.body).on("updated_checkout", this.handleCheckoutShippingMethods)
    },

    /**
     * Inicializace shipping kalkulátoru
     */
    initShippingCalculator: function () {
      // Přidání kalkulátoru na vhodné místo
      this.insertShippingCalculator()

      // Inicializace autocomplete pro adresy
      this.initAddressAutocomplete()
    },

    /**
     * Inicializace cart integrace
     */
    initCartIntegration: function () {
      // Cart kalkulátor se přidává v insertShippingCalculator()
      
      // Načíst uloženou adresu při načtení cart stránky
      if ($("body").hasClass("woocommerce-cart")) {
        this.loadSavedAddress()
        this.checkExistingShipping()
        this.initializeQuantityTracking()
      }
    },

    /**
     * Načte uloženou adresu z session storage
     */
    loadSavedAddress: function () {
      const savedAddress = sessionStorage.getItem('dbs_last_address')
      if (savedAddress) {
        $('.dbs-shipping-calculator textarea[name="destination"]').val(savedAddress)
      }
    },

    /**
     * Zkontroluje, zda už je aplikovaná shipping sazba
     */
    checkExistingShipping: function () {
      // Zkontrolovat, zda už je shipping fee v cart
      const cartFees = $('.woocommerce-cart .cart-fee')
      if (cartFees.length > 0) {
        // Zobrazit status, že shipping je už aplikovaný
        const statusDiv = $('.dbs-cart-shipping-status')
        if (statusDiv.length > 0) {
          const feeText = cartFees.first().text()
          const statusHTML = `
            <div class="dbs-cart-status-success">
              <div class="dbs-status-header">
                <span class="dbs-status-icon">✅</span>
                <strong>Doprava je již aplikována</strong>
              </div>
              <div class="dbs-status-details">
                <p><strong>Aplikovaná doprava:</strong> ${feeText}</p>
              </div>
              <div class="dbs-status-actions">
                <button type="button" class="dbs-change-address-btn">Změnit adresu</button>
                <button type="button" class="dbs-remove-shipping-btn">Odstranit dopravu</button>
              </div>
            </div>
          `
          statusDiv.html(statusHTML).show()
          
          // Event handlers pro tlačítka
          statusDiv.find('.dbs-change-address-btn').on('click', function() {
            statusDiv.hide()
            $('.dbs-shipping-calculator form textarea').focus()
          })
          
          statusDiv.find('.dbs-remove-shipping-btn').on('click', function() {
            DBSFrontend.removeShippingFromCart()
            statusDiv.hide()
            $('.dbs-cart-shipping-message').remove()
          })
        }
      }
    },

    /**
     * Inicializace checkout integrace
     */
    initCheckoutIntegration: function () {
      if ($("body").hasClass("woocommerce-checkout")) {
        this.addCheckoutShippingInfo()
        this.initCheckoutAddressAutoFill()
        
        // Načíst uložená shipping data z cart stránky
        this.loadStoredShippingData()
      }
    },

    /**
     * Inicializace smart address handling
     */
    initSmartAddressHandling: function () {
      // Auto-calculate shipping when address is complete
      $(document).on("blur", 'input[name^="billing_"], input[name^="shipping_"]', this.debounce(this.handleAddressBlur, 1000))
      
      // Real-time address validation and auto-calculation
      $(document).on("input", 'input[name^="billing_"], input[name^="shipping_"]', this.debounce(this.handleAddressInput, 2000))
      
      // Auto-calculate when address fields are complete
      $(document).on("change", 'input[name^="billing_"], input[name^="shipping_"]', this.debounce(this.handleAddressChange, 1500))
      
      // Auto-calculate when country/state changes
      $(document).on("change", 'select[name^="billing_"], select[name^="shipping_"]', this.debounce(this.handleAddressChange, 1000))
    },

    /**
     * Handler pro změnu množství v košíku
     */
    handleQuantityChange: function (event) {
      const $input = $(event.target)
      const quantity = parseInt($input.val()) || 0
      const oldQuantity = parseInt($input.data('old-quantity')) || 0
      
      // Uložit aktuální množství pro příští porovnání
      $input.data('old-quantity', quantity)
      
      // Pokud se množství významně změnilo, invalidovat shipping cache
      if (Math.abs(quantity - oldQuantity) > 0) {
        this.invalidateShippingCache('quantity_change', {
          product: $input.closest('.cart_item').find('.product-name').text().trim(),
          oldQuantity: oldQuantity,
          newQuantity: quantity
        })
      }
    },

    /**
     * Handler pro kliknutí na tlačítka plus/minus
     */
    handleQuantityButtonClick: function (event) {
      const $button = $(event.target)
      const $input = $button.siblings('.qty')
      
      // Počkat na změnu hodnoty v input
      setTimeout(() => {
        const quantity = parseInt($input.val()) || 0
        const oldQuantity = parseInt($input.data('old-quantity')) || 0
        
        // Uložit aktuální množství pro příští porovnání
        $input.data('old-quantity', quantity)
        
        // Pokud se množství změnilo, invalidovat shipping cache
        if (quantity !== oldQuantity) {
          this.invalidateShippingCache('quantity_button_click', {
            product: $input.closest('.cart_item').find('.product-name').text().trim(),
            oldQuantity: oldQuantity,
            newQuantity: quantity
          })
        }
      }, 100)
    },

    /**
     * Inicializuje sledování množství v košíku
     */
    initializeQuantityTracking: function () {
      // Inicializovat staré hodnoty množství pro všechny quantity inputy
      $('.qty').each(function() {
        const $input = $(this)
        const quantity = parseInt($input.val()) || 0
        $input.data('old-quantity', quantity)
      })
    },

    /**
     * Invaliduje shipping cache a vynutí přepočet
     */
    invalidateShippingCache: function (reason, details) {
      // AJAX požadavek pro invalidaci cache
      $.ajax({
        url: dbs_ajax.ajax_url,
        type: 'POST',
        data: {
          action: 'dbs_invalidate_shipping_cache',
          reason: reason,
          details: details,
          nonce: dbs_ajax.nonce
        },
        success: function (response) {
          if (response.success) {
            console.log('DBS: Shipping cache invalidated due to ' + reason, details)
            
            // Vynutit přepočet shipping metod
            if (typeof wc_checkout_params !== 'undefined') {
              $(document.body).trigger('update_checkout')
            }
          }
        },
        error: function (xhr, status, error) {
          console.error('DBS: Failed to invalidate shipping cache:', error)
        }
      })
    },

    /**
     * Přidání shipping informací na checkout stránku
     */
    addCheckoutShippingInfo: function () {
      const shippingInfo = `
        <div class="dbs-checkout-shipping-info" style="display: none;">
          <h3>📦 Informace o dopravě</h3>
          <div class="dbs-shipping-details">
            <p><strong>Nejbližší obchod:</strong> <span class="dbs-store-name">-</span></p>
            <p><strong>Vzdálenost:</strong> <span class="dbs-distance">-</span></p>
            <p><strong>Dopravní sazba:</strong> <span class="dbs-shipping-rate">-</span></p>
            <div class="dbs-address-standardization" style="display: none;">
              <p><strong>Adresa byla standardizována:</strong></p>
              <p class="dbs-original-address"></p>
              <p class="dbs-standardized-address"></p>
            </div>
          </div>
        </div>
      `

      // Vkládat pouze na checkout stránku, nikdy na cart
      if ($("body").hasClass("woocommerce-checkout")) {
        $(".woocommerce-billing-fields, .woocommerce-shipping-fields").after(shippingInfo)
      }
    },

    /**
     * Inicializace auto-fill adres na checkout
     */
    initCheckoutAddressAutoFill: function () {
      // Auto-fill shipping address from billing if "ship to different address" is unchecked
      $('input[name="ship_to_different_address"]').on("change", function() {
        if (!$(this).is(":checked")) {
          DBSFrontend.copyBillingToShipping()
        }
      })
    },

    /**
     * Kopírování billing adresy do shipping
     */
    copyBillingToShipping: function () {
      const billingFields = ['address_1', 'address_2', 'city', 'postcode', 'country', 'state']
      
      billingFields.forEach(function(field) {
        const billingValue = $('input[name="billing_' + field + '"]').val()
        if (billingValue) {
          $('input[name="shipping_' + field + '"]').val(billingValue)
        }
      })
    },

    /**
     * Vložení shipping kalkulátoru do stránky
     */
    insertShippingCalculator: function () {
      // Vkládat pouze na stránce produktu
      if ($('body').hasClass('single-product')) {
        const calculator = this.createShippingCalculatorHTML();
        // Vložení pomocí shortcode nebo na vhodné místo na stránce produktu
        $(".dbs-shipping-calculator-placeholder").replaceWith(calculator);
      }
    },

    /**
     * Vytvoření HTML pro shipping kalkulátor
     */
    createShippingCalculatorHTML: function () {
      return `
                <div class="dbs-shipping-calculator">
                    <h3>📦 Vypočítat dopravní náklady</h3>
                    <form class="dbs-calculator-form">
                        <div class="dbs-calculator-field">
                            <label for="dbs-calc-address">Dodací adresa:</label>
                            <textarea 
                                id="dbs-calc-address" 
                                name="destination" 
                                rows="3" 
                                placeholder="Zadejte úplnou adresu včetně města a PSČ..."
                                class="dbs-address-input"
                                required
                            ></textarea>
                        </div>
                        
                        <button type="submit" class="dbs-calculator-button">
                            <span class="dbs-button-text">Vypočítat dopravu</span>
                            <span class="dbs-spinner" style="display: none;">Počítám...</span>
                        </button>
                    </form>
                    <div class="dbs-shipping-results" style="display: none;"></div>
                    <div class="dbs-cart-shipping-status" style="display: none;"></div>
                </div>
            `
    },

    /**
     * Zpracování výpočtu dopravy
     */
    handleShippingCalculation: function (e) {
      e.preventDefault()

      const form = $(this)
      const calculator = form.closest(".dbs-shipping-calculator")
      const resultsDiv = calculator.find(".dbs-shipping-results")
      const statusDiv = calculator.find(".dbs-cart-shipping-status")
      const submitBtn = form.find('button[type="submit"]')
      const buttonText = submitBtn.find('.dbs-button-text')
      const spinner = submitBtn.find('.dbs-spinner')

      const destination = form.find('textarea[name="destination"]').val().trim()
      const cartTotal = parseFloat(form.find('input[name="cart_total"]').val()) || 0

      if (!destination) {
        DBSFrontend.showCalculatorError(
          resultsDiv,
          "Zadejte prosím dodací adresu."
        )
        return
      }

      // Zobrazit loading stav
      buttonText.hide()
      spinner.show()
      submitBtn.prop("disabled", true)
      resultsDiv.hide()
      statusDiv.hide()

      // AJAX požadavek
      $.ajax({
        url: dbsAjax.ajaxUrl,
        type: "POST",
        data: {
          action: "dbs_calculate_shipping",
          nonce: dbsAjax.nonce,
          destination: destination,
          cart_total: cartTotal,
          product_id: DBSFrontend.getCurrentProductId(),
        },
        success: function (response) {
          if (response.success) {
            DBSFrontend.showCalculatorResults(resultsDiv, response.data)
            
            // Automaticky vyplnit adresu do WooCommerce
            DBSFrontend.fillWooCommerceAddress(response.data)
            
            // Vybrat správnou shipping metodu
            DBSFrontend.selectShippingMethod(response.data)
            
            // Update checkout info if on checkout page
            if ($("body").hasClass("woocommerce-checkout")) {
              DBSFrontend.updateCheckoutShippingInfo(response.data)
            }
            
            // Zobrazit status na cart stránce
            if ($("body").hasClass("woocommerce-cart")) {
              DBSFrontend.showCartShippingStatus(statusDiv, response.data)
            }
            
            // Trigger WooCommerce update
            $(document.body).trigger('update_checkout')
            
            // Uložit adresu do session storage
            sessionStorage.setItem('dbs_last_address', destination)
            
          } else {
            DBSFrontend.showCalculatorError(
              resultsDiv,
              response.data || "Nepodařilo se vypočítat dopravní náklady."
            )
          }
        },
        error: function () {
          DBSFrontend.showCalculatorError(
            resultsDiv,
            "Došlo k chybě při komunikaci se serverem."
          )
        },
        complete: function () {
          buttonText.show()
          spinner.hide()
          submitBtn.prop("disabled", false)
        },
      })
    },

    /**
     * Vyplní adresu do WooCommerce formulářů
     */
    fillWooCommerceAddress: function (data) {
      if (!data.standardized_address) {
        return
      }

      // Robustní parsování adresy
      const address = data.standardized_address || data.original_address
      let street = ''
      let city = ''
      let postcode = ''
      let country = 'CZ'
      let state = ''

      // Pokus o rozpoznání PSČ (český formát)
      const pscMatch = address.match(/(\d{3} ?\d{2})/)
      if (pscMatch) {
        postcode = pscMatch[1].replace(/\s/g, '')
      }

      // Pokus o rozpoznání města (slovo před PSČ nebo za PSČ)
      if (pscMatch) {
        const before = address.substring(0, pscMatch.index).split(',').map(s => s.trim())
        const after = address.substring(pscMatch.index + pscMatch[1].length).split(',').map(s => s.trim())
        // Město bývá často poslední před PSČ nebo první za PSČ
        if (before.length > 0) {
          city = before[before.length - 1]
        }
        if (!city && after.length > 0) {
          city = after[0]
        }
      }

      // Pokus o rozpoznání státu
      if (address.match(/(Česko|Czech Republic|CZ)/i)) {
        country = 'CZ'
      } else if (address.match(/(Slovensko|Slovakia|SK)/i)) {
        country = 'SK'
      }

      // Ulice = první část před městem/PSČ
      if (city && address.indexOf(city) > 0) {
        street = address.substring(0, address.indexOf(city)).replace(/,?\s*$/, '')
      } else if (pscMatch && address.indexOf(pscMatch[1]) > 0) {
        street = address.substring(0, address.indexOf(pscMatch[1])).replace(/,?\s*$/, '')
      } else {
        street = address.split(',')[0]
      }

      // Vyplnit shipping fields
      if ($('input[name="shipping_address_1"]').length) {
        $('input[name="shipping_address_1"]').val(street).addClass('dbs-address-auto-filled')
        $('input[name="shipping_city"]').val(city).addClass('dbs-address-auto-filled')
        $('input[name="shipping_postcode"]').val(postcode).addClass('dbs-address-auto-filled')
        if (country) {
          $('select[name="shipping_country"]').val(country).trigger('change').addClass('dbs-address-auto-filled')
        }
        if (state) {
          $('select[name="shipping_state"]').val(state).trigger('change').addClass('dbs-address-auto-filled')
        }
        setTimeout(() => {
          $('.dbs-address-auto-filled').removeClass('dbs-address-auto-filled')
        }, 2000)
      }

      // Vyplnit billing fields pokud není zaškrtnuto "odeslat na jinou adresu"
      if (!$('input[name="ship_to_different_address"]').is(':checked')) {
        $('input[name="billing_address_1"]').val(street).addClass('dbs-address-auto-filled')
        $('input[name="billing_city"]').val(city).addClass('dbs-address-auto-filled')
        $('input[name="billing_postcode"]').val(postcode).addClass('dbs-address-auto-filled')
        if (country) {
          $('select[name="billing_country"]').val(country).trigger('change').addClass('dbs-address-auto-filled')
        }
        if (state) {
          $('select[name="billing_state"]').val(state).trigger('change').addClass('dbs-address-auto-filled')
        }
      }

      // Trigger WooCommerce address update
      $('body').trigger('update_checkout')
      
      // Uložit původní adresu do session storage pro zobrazení v "Shipping to"
      sessionStorage.setItem('dbs_original_address', data.original_address || data.standardized_address)
    },

    /**
     * Vybere správnou shipping metodu podle vypočítaných pravidel
     */
    selectShippingMethod: function (data) {
      if (!data.rates || data.rates.length === 0) {
        return
      }

      // Najít první plugin pravidlo nebo nejlevnější sazbu
      let selectedRate = data.selected_rate
      
      if (!selectedRate && data.rates && data.rates.length > 0) {
        // Nejprve zkusit najít plugin pravidlo
        const pluginRule = data.rates.find(rate => rate.is_plugin_rule === true)
        if (pluginRule) {
          selectedRate = pluginRule
        } else {
          // Jinak použít nejlevnější sazbu
          selectedRate = data.rates.reduce((min, rate) => {
            return rate.raw_cost < min.raw_cost ? rate : min
          })
        }
      }

      // Uložit data do session storage pro použití na checkout
      sessionStorage.setItem('dbs_shipping_data', JSON.stringify(data))
      sessionStorage.setItem('dbs_selected_rate', JSON.stringify(selectedRate))

      // Na cart stránce - aplikovat shipping sazbu přímo do cart
      if ($("body").hasClass("woocommerce-cart")) {
        // Aplikovat shipping sazbu do WooCommerce cart
        this.applyShippingToCart(selectedRate)
        
        // Zobrazit zprávu o vypočítané dopravě
        this.showCartShippingMessage(selectedRate)
      }

      // Na checkout stránce - vybrat shipping metodu
      if ($("body").hasClass("woocommerce-checkout")) {
        this.selectCheckoutShippingMethod(selectedRate)
      }
    },

    /**
     * Aplikuje shipping sazbu přímo do WooCommerce cart
     */
    applyShippingToCart: function (rate) {
      // Nejprve odstranit předchozí shipping fee
      this.removeShippingFromCart()
      
      // Zobrazit loading stav
      const loadingMessage = $('<div class="dbs-cart-loading">Aplikuji dopravu do košíku...</div>')
      $('.cart-collaterals').before(loadingMessage)
      
      // AJAX požadavek pro aplikování shipping sazby do cart
      $.ajax({
        url: dbsAjax.ajaxUrl,
        type: "POST",
        data: {
          action: "dbs_apply_shipping_to_cart",
          nonce: dbsAjax.nonce,
          rate_id: rate.id,
          rate_cost: rate.raw_cost,
          rate_label: rate.label
        },
        success: function (response) {
          loadingMessage.remove()
          
          if (response.success) {
            // Aktualizovat cart totals
            $(document.body).trigger('update_checkout')
            
            // Zobrazit potvrzení
            console.log('Shipping applied to cart:', response.data)
            
            // Zobrazit úspěšnou zprávu
            const successMessage = $(`
              <div class="dbs-cart-success">
                <p><strong>✅ Doprava byla úspěšně aplikována do košíku</strong></p>
                <p>${rate.label} - ${rate.cost}</p>
              </div>
            `)
            $('.cart-collaterals').before(successMessage)
            
            // Skrýt zprávu po 5 sekundách
            setTimeout(() => {
              successMessage.fadeOut()
            }, 5000)
            
          } else {
            console.error('Failed to apply shipping to cart:', response.data)
            
            // Zobrazit chybovou zprávu
            const errorMessage = $(`
              <div class="dbs-cart-error">
                <p><strong>❌ Chyba při aplikování dopravy</strong></p>
                <p>${response.data}</p>
              </div>
            `)
            $('.cart-collaterals').before(errorMessage)
          }
        },
        error: function () {
          loadingMessage.remove()
          console.error('Error applying shipping to cart')
          
          // Zobrazit chybovou zprávu
          const errorMessage = $(`
            <div class="dbs-cart-error">
              <p><strong>❌ Chyba při komunikaci se serverem</strong></p>
            </div>
          `)
          $('.cart-collaterals').before(errorMessage)
        }
      })
    },

    /**
     * Odstraní shipping fee z cart
     */
    removeShippingFromCart: function () {
      // AJAX požadavek pro odstranění shipping fee z cart
      $.ajax({
        url: dbsAjax.ajaxUrl,
        type: "POST",
        data: {
          action: "dbs_remove_shipping_from_cart",
          nonce: dbsAjax.nonce
        },
        success: function (response) {
          if (response.success) {
            // Aktualizovat cart totals
            $(document.body).trigger('update_checkout')
          }
        },
        error: function () {
          console.error('Error removing shipping from cart')
        }
      })
    },

    /**
     * Vybere shipping metodu na checkout stránce
     */
    selectCheckoutShippingMethod: function (rate) {
      // Počkat na načtení shipping metod
      const checkShippingMethods = () => {
        const shippingMethods = $('input[name^="shipping_method"]')
        
        if (shippingMethods.length > 0) {
          // Najít shipping metodu podle ID - priorita pro distance_based metody a plugin pravidla
          let targetMethod = shippingMethods.filter((index, element) => {
            const methodId = $(element).val()
            return methodId.includes('distance_based') || methodId.includes(rate.id) || (rate.is_plugin_rule && methodId.includes('distance_based'))
          })

          // Pokud nenajdeme distance_based metodu, zkusit najít jakoukoliv metodu
          if (targetMethod.length === 0) {
            targetMethod = shippingMethods.first()
          }

          if (targetMethod.length > 0) {
            // Vybrat metodu jako výchozí
            targetMethod.prop('checked', true).trigger('change')
            
            // Deaktivovat ostatní metody
            shippingMethods.not(targetMethod).prop('disabled', true)
            
            // Zobrazit zprávu
            this.showShippingMethodMessage(rate)
            
            // Trigger pro aktualizaci checkout
            $(document.body).trigger('update_checkout')
          } else {
            // Zkusit znovu za 500ms
            setTimeout(checkShippingMethods, 500)
          }
        } else {
          // Zkusit znovu za 500ms
          setTimeout(checkShippingMethods, 500)
        }
      }

      checkShippingMethods()
    },

    /**
     * Zobrazí zprávu o vypočítané dopravě na cart stránce
     */
    showCartShippingMessage: function (rate) {
      // Odstranit předchozí zprávy
      $('.dbs-cart-shipping-message').remove()
      
      const message = `
        <div class="dbs-cart-shipping-message">
          <p><strong>✅ Výchozí doprava aplikována:</strong> ${rate.label}</p>
          <p><strong>Cena:</strong> ${rate.cost}</p>
          <p><strong>Vzdálenost:</strong> ${rate.distance}</p>
          <p><em>Doprava byla přidána do košíku jako výchozí možnost.</em></p>
        </div>
      `
      
      // Vložit zprávu před cart totals
      $('.cart-collaterals').before(message)
    },

    /**
     * Zobrazí status shipping informací na cart stránce
     */
    showCartShippingStatus: function (statusDiv, data) {
      if (!data.rates || data.rates.length === 0) {
        return
      }

      const selectedRate = data.selected_rate || data.rates[0]
      
      const statusHTML = `
        <div class="dbs-cart-status-success">
          <div class="dbs-status-header">
            <span class="dbs-status-icon">✅</span>
            <strong>Doprava aplikována</strong>
          </div>
          <div class="dbs-status-details">
            <p><strong>Nejbližší obchod:</strong> ${data.store}</p>
            <p><strong>Vzdálenost:</strong> ${data.distance}</p>
            <p><strong>Dopravní sazba:</strong> ${selectedRate.label} - ${selectedRate.cost}</p>
          </div>
          ${data.address_standardized ? `
            <div class="dbs-address-standardization">
              <p><strong>Adresa byla standardizována:</strong></p>
              <p><small>Původní: ${data.original_address}</small></p>
              <p><small>Použitá: ${data.standardized_address}</small></p>
            </div>
          ` : ''}
          <div class="dbs-status-actions">
            <button type="button" class="dbs-change-address-btn">Změnit adresu</button>
            <button type="button" class="dbs-remove-shipping-btn">Odstranit dopravu</button>
          </div>
        </div>
      `
      
      statusDiv.html(statusHTML).show()
      
      // Event handlers pro tlačítka
      statusDiv.find('.dbs-change-address-btn').on('click', function() {
        statusDiv.hide()
        $('.dbs-shipping-calculator form textarea').focus()
      })
      
      statusDiv.find('.dbs-remove-shipping-btn').on('click', function() {
        DBSFrontend.removeShippingFromCart()
        statusDiv.hide()
        $('.dbs-cart-shipping-message').remove()
      })
    },

    /**
     * Načte uložená shipping data z session storage
     */
    loadStoredShippingData: function () {
      const storedData = sessionStorage.getItem('dbs_shipping_data')
      const selectedRate = sessionStorage.getItem('dbs_selected_rate')
      
      if (storedData && selectedRate) {
        try {
          const data = JSON.parse(storedData)
          const rate = JSON.parse(selectedRate)
          
          // Aktualizovat checkout shipping info
          this.updateCheckoutShippingInfo(data)
          
          // Vybrat shipping metodu
          this.selectCheckoutShippingMethod(rate)
          
          // Vyčistit session storage
          sessionStorage.removeItem('dbs_shipping_data')
          sessionStorage.removeItem('dbs_selected_rate')
        } catch (e) {
          console.error('Chyba při načítání uložených shipping dat:', e)
        }
      }
    },

    /**
     * Zpracuje shipping metody po aktualizaci checkout
     */
    handleCheckoutShippingMethods: function () {
      const storedData = sessionStorage.getItem('dbs_shipping_data')
      const selectedRate = sessionStorage.getItem('dbs_selected_rate')
      
      if (storedData && selectedRate) {
        try {
          const rate = JSON.parse(selectedRate)
          
          // Počkat na načtení shipping metod a pak je vybrat
          setTimeout(() => {
            DBSFrontend.selectCheckoutShippingMethod(rate)
          }, 1000)
        } catch (e) {
          console.error('Chyba při zpracování shipping metod:', e)
        }
      }
    },

    /**
     * Zobrazí zprávu o vybrané shipping metodě
     */
    showShippingMethodMessage: function (rate) {
      // Odstranit předchozí zprávy
      $('.dbs-shipping-method-selected').remove()
      
      const message = `
        <div class="dbs-shipping-method-selected">
          <p><strong>✅ Výchozí doprava (automaticky vybraná):</strong> ${rate.label}</p>
          <p><strong>Cena:</strong> ${rate.cost}</p>
          <p><strong>Vzdálenost:</strong> ${rate.distance}</p>
          <p><em>Ostatní možnosti dopravy byly deaktivovány.</em></p>
        </div>
      `
      
      // Vložit zprávu před shipping metody
      $('.woocommerce-shipping-methods').before(message)
    },

    /**
     * Parsuje adresu na komponenty
     */
    parseAddress: function (address) {
      // Pokus o parsování různých formátů adres
      const parts = address.split(',').map(part => part.trim())
      
      let street = ''
      let city = ''
      let postcode = ''
      let country = 'CZ'
      let state = ''
      
      if (parts.length >= 1) {
        street = parts[0]
      }
      
      if (parts.length >= 2) {
        // Druhá část může být město nebo PSČ
        const secondPart = parts[1]
        if (secondPart.match(/^\d{3}\s?\d{2}$/)) {
          // Je to PSČ
          postcode = secondPart.replace(/\s/g, '')
          if (parts.length >= 3) {
            city = parts[2]
          }
        } else {
          // Je to město
          city = secondPart
          if (parts.length >= 3) {
            const thirdPart = parts[2]
            if (thirdPart.match(/^\d{3}\s?\d{2}$/)) {
              postcode = thirdPart.replace(/\s/g, '')
            }
          }
        }
      }
      
      // Najít stát a zemi na konci
      for (let i = parts.length - 1; i >= 0; i--) {
        const part = parts[i].toLowerCase()
        if (part === 'česká republika' || part === 'czech republic' || part === 'cz') {
          country = 'CZ'
          break
        } else if (part === 'slovensko' || part === 'slovakia' || part === 'sk') {
          country = 'SK'
          break
        }
      }
      
      return {
        street: street,
        city: city,
        postcode: postcode,
        country: country,
        state: state
      }
    },

    /**
     * Zobrazení výsledků kalkulátoru
     */
    showCalculatorResults: function (resultsDiv, data) {
      let html = "<h4>✅ Dostupné možnosti dopravy</h4>"

      if (data.store) {
        html += '<div class="dbs-shipping-info">'
        html += "<p><strong>🏪 Nejbližší obchod:</strong> " + data.store + "</p>"
        html += "<p><strong>📏 Vzdálenost:</strong> " + data.distance + "</p>"
        
        // Přidání informací o hmotnosti a rozměrech balíčku
        if (data.package_info) {
          html += "<p><strong>⚖️ Hmotnost balíčku:</strong> " + data.package_info.weight_formatted + "</p>"
          html += "<p><strong>📦 Rozměry balíčku:</strong> " + data.package_info.dimensions_formatted + "</p>"
        }
        
        html += "</div>"
      }

      if (data.rates && data.rates.length > 0) {
        html += '<div class="dbs-shipping-rates">'

        data.rates.forEach(function (rate) {
          html += '<div class="dbs-shipping-rate">'
          html += '<div class="dbs-shipping-rate-info">'
          html += '<h5>' + rate.label + '</h5>'
          html += '<p class="dbs-rate-cost"><strong>Cena:</strong> ' + rate.cost + '</p>'
          html += '<p class="dbs-rate-distance"><strong>Vzdálenost:</strong> ' + rate.distance + '</p>'
          html += '</div>'
          html += '</div>'
        })

        html += '</div>'
      } else {
        html += '<p class="dbs-no-rates">Žádné dopravní sazby nebyly nalezeny pro tuto vzdálenost.</p>'
      }

      // Přidání informací o standardizaci adresy
      if (data.address_standardized) {
        html += '<div class="dbs-address-standardization">'
        html += '<h5>📝 Adresa byla standardizována:</h5>'
        html += '<p><strong>Původní:</strong> ' + data.original_address + '</p>'
        html += '<p><strong>Použitá:</strong> ' + data.standardized_address + '</p>'
        html += '</div>'
      }

      resultsDiv.html(html).show()
    },

    /**
     * Zobrazení výsledků pro cart
     */
    showCartShippingResults: function (resultsDiv, data) {
      let html = "<h4>✅ Výsledky výpočtu dopravy</h4>"

      if (data.store) {
        html += '<div class="dbs-shipping-info">'
        html += "<p><strong>🏪 Nejbližší obchod:</strong> " + data.store + "</p>"
        html += "<p><strong>📏 Vzdálenost:</strong> " + data.distance + "</p>"
        
        // Přidání informací o hmotnosti a rozměrech balíčku
        if (data.package_info) {
          html += "<p><strong>⚖️ Hmotnost balíčku:</strong> " + data.package_info.weight_formatted + "</p>"
          html += "<p><strong>📦 Rozměry balíčku:</strong> " + data.package_info.dimensions_formatted + "</p>"
        }
        
        html += "</div>"
      }

      if (data.rates && data.rates.length > 0) {
        html += '<div class="dbs-shipping-rates">'
        html += '<p><strong>Dopravní sazby:</strong></p>'
        
        data.rates.forEach(function (rate) {
          html += '<div class="dbs-shipping-rate">'
          html += '<p><strong>' + rate.label + ':</strong> ' + rate.cost + '</p>'
          html += '</div>'
        })

        html += '</div>'
      }

      // Přidání informací o standardizaci adresy
      if (data.address_standardized) {
        html += '<div class="dbs-address-standardization">'
        html += '<p><strong>📝 Adresa byla standardizována:</strong></p>'
        html += '<p>Původní: ' + data.original_address + '</p>'
        html += '<p>Použitá: ' + data.standardized_address + '</p>'
        html += '</div>'
      }

      resultsDiv.html(html).show()
    },

    /**
     * Aktualizace shipping informací na checkout stránce
     */
    updateCheckoutShippingInfo: function (data) {
      const infoDiv = $(".dbs-checkout-shipping-info")
      
      if (data.store) {
        infoDiv.find(".dbs-store-name").text(data.store)
      }
      
      if (data.distance) {
        infoDiv.find(".dbs-distance").text(data.distance)
      }
      
      if (data.rates && data.rates.length > 0) {
        const rateText = data.rates.map(rate => rate.label + ': ' + rate.cost).join(', ')
        infoDiv.find(".dbs-shipping-rate").text(rateText)
      }
      
      // Show address standardization info
      if (data.address_standardized) {
        infoDiv.find(".dbs-original-address").text(data.original_address)
        infoDiv.find(".dbs-standardized-address").text(data.standardized_address)
        infoDiv.find(".dbs-address-standardization").show()
      }
      
      infoDiv.show()
    },

    /**
     * Zobrazení chyby kalkulátoru
     */
    showCalculatorError: function (resultsDiv, message) {
      const html = "<h4>Chyba při výpočtu</h4><p>" + message + "</p>"
      resultsDiv.addClass("error").html(html).show()
    },

    /**
     * Vylepšení shipping metod o informace o vzdálenosti
     */
    enhanceShippingMethods: function () {
      $(".shipping_method").each(function () {
        const method = $(this)
        const methodId = method.val()

        if (methodId && methodId.indexOf("distance_based") !== -1) {
          // Přidání ikony nebo stylu pro distance-based metody
          method.closest("label").addClass("dbs-distance-method")

          // Přidání distance info pokud je dostupná
          const label = method.closest("label")
          if (!label.find(".dbs-distance-info").length) {
            label.append(
              '<span class="dbs-distance-info">Založeno na vzdálenosti</span>'
            )
          }
        }
      })
    },

    /**
     * Zpracování checkout update
     */
    handleCheckoutUpdate: function () {
      // Re-inicializace po AJAX update
      DBSFrontend.enhanceShippingMethods()
    },

    /**
     * Zpracování změny adresy
     */
    handleAddressChange: function () {
      const address = this.buildAddressFromCheckoutFields()
      
      if (address && address.length > 10) {
        // Zobrazit loading indikátor
        this.showAddressLoading()
        
        // Automaticky vypočítat shipping
        this.autoCalculateShipping(address)
      }
    },

    /**
     * Zpracování input adresy s validací
     */
    handleAddressInput: function () {
      const address = this.buildAddressFromCheckoutFields()
      
      if (address && address.length > 5) {
        // Validovat adresu v reálném čase
        this.validateAddress(address)
      }
    },

    /**
     * Zpracování blur event na adresních polích
     */
    handleAddressBlur: function () {
      const address = this.buildAddressFromCheckoutFields()
      
      if (address && address.length > 10) {
        // Automaticky vypočítat shipping při opuštění pole
        this.autoCalculateShipping(address)
      }
    },

    /**
     * Automatický výpočet dopravy
     */
    autoCalculateShipping: function (address) {
      // Debounce to avoid too many requests
      clearTimeout(DBSFrontend.autoCalculateTimeout)
      DBSFrontend.autoCalculateTimeout = setTimeout(function() {
        $.ajax({
          url: dbsAjax.ajaxUrl,
          type: "POST",
          data: {
            action: "dbs_calculate_shipping",
            nonce: dbsAjax.nonce,
            destination: address,
            cart_total: 0, // Will be calculated on server side
            product_id: DBSFrontend.getCurrentProductId(),
          },
          beforeSend: function() {
            // Zobrazit loading indikátor
            DBSFrontend.showAddressLoading()
          },
          success: function (response) {
            // Skrýt loading indikátor
            DBSFrontend.hideAddressLoading()
            
            if (response.success) {
              // Aktualizovat checkout shipping info
              if ($("body").hasClass("woocommerce-checkout")) {
                DBSFrontend.updateCheckoutShippingInfo(response.data)
              }
              
              // Aktualizovat cart shipping info
              if ($("body").hasClass("woocommerce-cart")) {
                DBSFrontend.updateCartShippingInfo(response.data)
              }
              
              // Trigger checkout update to refresh shipping methods
              $(document.body).trigger("update_checkout")
              
              // Zobrazit úspěšnou zprávu
              DBSFrontend.showAutoCalculationSuccess(response.data)
            } else {
              // Zobrazit chybovou zprávu
              DBSFrontend.showAutoCalculationError(response.data)
            }
          },
          error: function () {
            // Skrýt loading indikátor
            DBSFrontend.hideAddressLoading()
            
            // Silent fail for auto-calculation
            console.log('Auto-calculation failed silently')
          }
        })
      }, 2000) // 2 second delay
    },

    /**
     * Aktualizuje cart shipping informace
     */
    updateCartShippingInfo: function (data) {
      if (!data.rates || data.rates.length === 0) {
        return
      }

      const selectedRate = data.selected_rate || data.rates[0]
      
      // Aktualizovat status div
      const statusDiv = $('.dbs-cart-shipping-status')
      if (statusDiv.length > 0) {
        this.showCartShippingStatus(statusDiv, data)
      }
      
      // Aktualizovat cart totals
      $(document.body).trigger('update_checkout')
    },

    /**
     * Zobrazí úspěšnou zprávu o auto-calculating
     */
    showAutoCalculationSuccess: function (data) {
      // Odstranit předchozí zprávy
      $('.dbs-auto-calculation-success').remove()
      
      const message = `
        <div class="dbs-auto-calculation-success">
          <span class="dbs-success-icon">✅</span>
          <span>Doprava automaticky vypočítána: ${data.selected_rate ? data.selected_rate.label : 'N/A'}</span>
        </div>
      `
      
      // Vložit zprávu do checkout
      if ($("body").hasClass("woocommerce-checkout")) {
        $('.woocommerce-checkout-review-order').before(message)
      }
      
      // Vložit zprávu do cart
      if ($("body").hasClass("woocommerce-cart")) {
        $('.cart-collaterals').before(message)
      }
      
      // Automaticky skrýt po 5 sekundách
      setTimeout(() => {
        $('.dbs-auto-calculation-success').fadeOut(300, function() {
          $(this).remove()
        })
      }, 5000)
    },

    /**
     * Zobrazí chybovou zprávu o auto-calculating
     */
    showAutoCalculationError: function (error) {
      // Odstranit předchozí zprávy
      $('.dbs-auto-calculation-error').remove()
      
      const message = `
        <div class="dbs-auto-calculation-error">
          <span class="dbs-error-icon">❌</span>
          <span>Chyba při výpočtu dopravy: ${error}</span>
        </div>
      `
      
      // Vložit zprávu do checkout
      if ($("body").hasClass("woocommerce-checkout")) {
        $('.woocommerce-checkout-review-order').before(message)
      }
      
      // Vložit zprávu do cart
      if ($("body").hasClass("woocommerce-cart")) {
        $('.cart-collaterals').before(message)
      }
      
      // Automaticky skrýt po 5 sekundách
      setTimeout(() => {
        $('.dbs-auto-calculation-error').fadeOut(300, function() {
          $(this).remove()
        })
      }, 5000)
    },

    /**
     * Sestavení adresy z checkout polí
     */
    buildAddressFromCheckoutFields: function () {
      const addressParts = []

      // Preferujeme shipping adresu, fallback na billing
      const prefix = $('input[name="ship_to_different_address"]').is(":checked")
        ? "shipping_"
        : "billing_"

      const fields = [
        prefix + "address_1",
        prefix + "address_2",
        prefix + "city",
        prefix + "postcode",
        prefix + "country",
      ]

      fields.forEach(function (field) {
        const value = $(
          'input[name="' + field + '"], select[name="' + field + '"]'
        ).val()
        if (value && value.trim()) {
          addressParts.push(value.trim())
        }
      })

      return addressParts.join(", ")
    },

    /**
     * Inicializace address autocomplete
     */
    initAddressAutocomplete: function () {
      // Jednoduchá implementace - může být rozšířena o Google Places API
      $('.dbs-shipping-calculator textarea[name="destination"], .dbs-address-input').on(
        "input",
        function () {
          const input = $(this)
          const value = input.val()

          // Základní validace adresy
          if (value.length > 10) {
            input.removeClass("invalid")
          } else {
            input.addClass("invalid")
          }
        }
      )
    },

    /**
     * Zpracování resize
     */
    handleResize: function () {
      // Responzivní úpravy
      const calculator = $(".dbs-shipping-calculator")

      if ($(window).width() < 768) {
        calculator.addClass("mobile-layout")
      } else {
        calculator.removeClass("mobile-layout")
      }
    },

    /**
     * Zobrazení notifikace
     */
    showNotice: function (message, type = "info") {
      const noticeClass = "dbs-notice " + type
      const notice = $('<div class="' + noticeClass + '">' + message + "</div>")

      // Přidání na vrch kalkulátoru
      $(".dbs-shipping-calculator, .dbs-cart-shipping-calculator").prepend(notice)

      // Auto-hide po 5 sekundách
      setTimeout(function () {
        notice.fadeOut(function () {
          $(this).remove()
        })
      }, 5000)
    },

    /**
     * Utility funkce pro localStorage
     */
    saveToStorage: function (key, value) {
      try {
        localStorage.setItem("dbs_" + key, JSON.stringify(value))
      } catch (e) {
        // Ignorujeme chyby localStorage
      }
    },

    /**
     * Načtení z localStorage
     */
    loadFromStorage: function (key) {
      try {
        const item = localStorage.getItem("dbs_" + key)
        return item ? JSON.parse(item) : null
      } catch (e) {
        return null
      }
    },

    /**
     * Debounce funkce
     */
    debounce: function (func, wait, immediate) {
      let timeout
      return function executedFunction() {
        const context = this
        const args = arguments

        const later = function () {
          timeout = null
          if (!immediate) func.apply(context, args)
        }

        const callNow = immediate && !timeout

        clearTimeout(timeout)
        timeout = setTimeout(later, wait)

        if (callNow) func.apply(context, args)
      }
    },

    /**
     * Animace smooth scroll
     */
    smoothScrollTo: function (element, offset = 0) {
      if ($(element).length) {
        $("html, body").animate(
          {
            scrollTop: $(element).offset().top - offset,
          },
          500
        )
      }
    },

    /**
     * Získá ID aktuálního produktu na product detail page
     */
    getCurrentProductId: function () {
      // Zkusit získat z WooCommerce data
      if (typeof wc_add_to_cart_params !== 'undefined' && wc_add_to_cart_params.product_id) {
        return wc_add_to_cart_params.product_id
      }
      
      // Zkusit získat z URL
      const urlParams = new URLSearchParams(window.location.search)
      if (urlParams.has('product_id')) {
        return parseInt(urlParams.get('product_id'))
      }
      
      // Zkusit získat z body třídy (WooCommerce často přidává product-{id})
      const bodyClasses = document.body.className.split(' ')
      for (const className of bodyClasses) {
        if (className.startsWith('product-')) {
          const productId = className.replace('product-', '')
          if (!isNaN(productId)) {
            return parseInt(productId)
          }
        }
      }
      
      // Zkusit získat z data atributu na stránce
      const productElement = document.querySelector('[data-product_id]')
      if (productElement) {
        return parseInt(productElement.getAttribute('data-product_id'))
      }
      
      // Zkusit získat z WooCommerce product ID v meta tagu
      const metaProductId = document.querySelector('meta[property="product:retailer_item_id"]')
      if (metaProductId) {
        return parseInt(metaProductId.getAttribute('content'))
      }
      
      return 0 // Žádný produkt nenalezen
    },

    /**
     * Zpracuje formulář kalkulátoru
     */
  }

  /**
   * Shortcode support
   */
  function initShortcodes() {
    // Shortcode pro shipping kalkulátor
    $('[data-dbs-shortcode="calculator"]').each(function () {
      const container = $(this)
      const calculator = DBSFrontend.createShippingCalculatorHTML()
      container.html(calculator)
    })
  }

  /**
   * Avada theme kompatibilita
   */
  function initAvadaCompatibility() {
    // Avada specifické styly a funkce
    if (
      $("body").hasClass("avada-html") ||
      typeof window.avadaVars !== "undefined"
    ) {
      // Přidání Avada tříd pro lepší styling
      $(".dbs-shipping-calculator").addClass("fusion-form fusion-form-builder")
      $(".dbs-calculator-button").addClass("fusion-button")

      // Integrace s Avada lazy loading
      if (typeof window.avadaLazyLoad !== "undefined") {
        // Re-inicializace lazy load po přidání kalkulátoru
        setTimeout(function () {
          if (window.avadaLazyLoad.update) {
            window.avadaLazyLoad.update()
          }
        }, 100)
      }
    }
  }

  /**
   * Analytics tracking
   */
  function trackShippingCalculation(data) {
    // Google Analytics tracking
    if (typeof gtag !== "undefined") {
      gtag("event", "shipping_calculation", {
        event_category: "ecommerce",
        event_label: "distance_based_shipping",
        value: data.rates ? data.rates.length : 0,
      })
    }

    // Facebook Pixel tracking
    if (typeof fbq !== "undefined") {
      fbq("track", "InitiateCheckout", {
        content_category: "shipping_calculation",
      })
    }
  }

  /**
   * Performance monitoring
   */
  function measurePerformance(action, startTime) {
    if (typeof performance !== "undefined" && performance.now) {
      const endTime = performance.now()
      const duration = endTime - startTime

      console.log(`DBS ${action} took ${duration.toFixed(2)}ms`)

      // Odeslání do analytics pokud je trvání příliš dlouhé
      if (duration > 2000) {
        if (typeof gtag !== "undefined") {
          gtag("event", "timing_complete", {
            name: action,
            value: Math.round(duration),
          })
        }
      }
    }
  }

  /**
   * Error handling a reporting
   */
  function setupErrorHandling() {
    window.addEventListener("error", function (e) {
      if (e.filename && e.filename.indexOf("distance-shipping") !== -1) {
        console.error("DBS Error:", e.message, e.filename, e.lineno)

        // Volitelné reportování chyb
        if (typeof gtag !== "undefined") {
          gtag("event", "exception", {
            description: e.message,
            fatal: false,
          })
        }
      }
    })
  }

  /**
   * Cache management pro frontend
   */
  const DBSCache = {
    prefix: "dbs_cache_",
    ttl: 30 * 60 * 1000, // 30 minut

    set: function (key, data) {
      const item = {
        data: data,
        timestamp: Date.now(),
      }
      DBSFrontend.saveToStorage(this.prefix + key, item)
    },

    get: function (key) {
      const item = DBSFrontend.loadFromStorage(this.prefix + key)
      if (!item) return null

      if (Date.now() - item.timestamp > this.ttl) {
        this.remove(key)
        return null
      }

      return item.data
    },

    remove: function (key) {
      try {
        localStorage.removeItem("dbs_" + this.prefix + key)
      } catch (e) {
        // Ignorujeme chyby
      }
    },

    clear: function () {
      try {
        Object.keys(localStorage).forEach((key) => {
          if (key.startsWith("dbs_" + this.prefix)) {
            localStorage.removeItem(key)
          }
        })
      } catch (e) {
        // Ignorujeme chyby
      }
    },
  }

  /**
   * Progressive Web App podpora
   */
  function initPWASupport() {
    // Service Worker registrace
    if ("serviceWorker" in navigator) {
      navigator.serviceWorker
        .register("/wp-content/plugins/distance-based-shipping/sw.js")
        .then(function (registration) {
          console.log("DBS Service Worker registered:", registration)
        })
        .catch(function (error) {
          console.log("DBS Service Worker registration failed:", error)
        })
    }

    // Offline detection
    window.addEventListener("online", function () {
      $(".dbs-shipping-calculator").removeClass("offline")
      DBSFrontend.showNotice("Připojení obnoveno", "success")
    })

    window.addEventListener("offline", function () {
      $(".dbs-shipping-calculator").addClass("offline")
      DBSFrontend.showNotice(
        "Jste offline. Kalkulátor nemusí fungovat správně.",
        "warning"
      )
    })
  }

  /**
   * Accessibility vylepšení
   */
  function enhanceAccessibility() {
    // ARIA labely
    $(".dbs-shipping-calculator")
      .attr("role", "region")
      .attr("aria-label", "Kalkulátor dopravních nákladů")

    $(".dbs-shipping-results")
      .attr("role", "status")
      .attr("aria-live", "polite")

    // Klávesové zkratky
    $(document).on("keydown", function (e) {
      // Alt + S = focus na shipping kalkulátor
      if (e.altKey && e.key === "s") {
        e.preventDefault()
        $(".dbs-shipping-calculator textarea").focus()
      }
    })

    // Focus management
    $(".dbs-calculator-button").on("click", function () {
      setTimeout(function () {
        $(".dbs-shipping-results").focus()
      }, 100)
    })
  }

  /**
   * Inicializace při načtení DOM
   */
  $(document).ready(function () {
    const startTime = performance.now ? performance.now() : Date.now()

    DBSFrontend.init()
    initShortcodes()
    initAvadaCompatibility()
    setupErrorHandling()
    initPWASupport()
    enhanceAccessibility()

    measurePerformance("frontend_init", startTime)
  })

  /**
   * Inicializace po načtení všech zdrojů
   */
  $(window).on("load", function () {
    // Optimalizace pro rychlejší načítání
    setTimeout(function () {
      // Preload často používaných dat
      if (
        navigator.connection &&
        navigator.connection.effectiveType !== "slow-2g"
      ) {
        // Preload pouze na rychlejších připojeních
        DBSFrontend.preloadCommonAddresses()
      }
    }, 1000)
  })

  /**
   * Export do globálního scope
   */
  window.DBSFrontend = DBSFrontend
  window.DBSCache = DBSCache

  /**
   * jQuery plugin pro snadnou integraci
   */
  $.fn.dbsShippingCalculator = function (options) {
    const defaults = {
      autoSubmit: false,
      showDistance: true,
      enableCache: true,
    }

    const settings = $.extend(defaults, options)

    return this.each(function () {
      const $this = $(this)

      if (!$this.hasClass("dbs-shipping-calculator")) {
        const calculator = DBSFrontend.createShippingCalculatorHTML()
        $this.html(calculator).addClass("dbs-initialized")

        if (settings.autoSubmit) {
          $this.find("textarea, input").on("change", function () {
            const form = $(this).closest("form")
            if (form.find("textarea").val().trim()) {
              form.trigger("submit")
            }
          })
        }
      }
    })
  }
})(jQuery)

/**
 * Vanilla JS API pro použití bez jQuery
 */
window.DistanceBasedShipping = {
  calculateShipping: function (destination, cartTotal = 0, productId = 0) {
    return fetch(dbsAjax.ajaxUrl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams({
        action: "dbs_calculate_shipping",
        nonce: dbsAjax.nonce,
        destination: destination,
        cart_total: cartTotal,
        product_id: productId, // Přidáme product_id
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          return data.data
        } else {
          throw new Error(data.data || "Chyba při výpočtu dopravy")
        }
      })
  },

  init: function (container) {
    if (typeof jQuery !== "undefined") {
      jQuery(container).dbsShippingCalculator()
    } else {
      console.warn("jQuery není dostupné. Použijte jQuery verzi.")
    }
  },
}

/**
 * Wrap shipping method labels with dbs_shipping-rule class
 */
function wrapShippingMethodLabels() {
  document.querySelectorAll("label[for^='shipping_method_']").forEach((label) => {
    // Skip if already wrapped
    if (label.querySelector('.dbs_shipping-rule')) return;
    
    const html = label.innerHTML.trim();
    const priceSpan = label.querySelector('.woocommerce-Price-amount');

    if (!priceSpan) return;

    const priceHTML = priceSpan.outerHTML;
    const parts = html.split(priceHTML);

    if (parts.length === 2) {
      label.innerHTML = `<span class="dbs_shipping-rule">${parts[0]}</span>${priceHTML}`;
    }
  });
}

/**
 * Initialize shipping method label wrapping
 */
function initShippingMethodLabels() {
  // Run immediately
  wrapShippingMethodLabels();
  
  // Run on WooCommerce checkout updates
  if (typeof jQuery !== 'undefined') {
    jQuery(document.body).on('updated_checkout', function() {
      setTimeout(wrapShippingMethodLabels, 100);
    });
    
    // Run on shipping method changes
    jQuery(document.body).on('change', 'input[name^="shipping_method"]', function() {
      setTimeout(wrapShippingMethodLabels, 100);
    });
    
    // Run on cart updates
    jQuery(document.body).on('updated_cart_totals', function() {
      setTimeout(wrapShippingMethodLabels, 100);
    });
  }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initShippingMethodLabels);
} else {
  initShippingMethodLabels();
}
