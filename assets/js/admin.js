/**
 * Admin JavaScript pro Distance Based Shipping plugin.
 *
 * Soubor: assets/js/admin.js
 */

;(function ($) {
  "use strict"

  /**
   * Hlavní admin objekt
   */
  const DBSAdmin = {
    /**
     * Inicializace
     */
    init: function () {
      this.bindEvents()
      this.initTooltips()
      this.initValidation()
    },

    /**
     * Navázání event handlerů
     */
    bindEvents: function () {
      // Test vzdálenosti modal
      this.initDistanceTestModal()

      // API klíč validace
      this.initApiKeyValidation()

      // Cache management
      this.initCacheManagement()

      // Store management
      this.initStoreManagement()

      // Rule management
      this.initRuleManagement()

      // Form validation
      this.initFormValidation()

      // Settings management
      this.initSettingsManagement()
    },

    /**
     * Inicializace distance test modalu
     */
    initDistanceTestModal: function () {
      const modal = $("#dbs-test-distance-modal")
      const openBtn = $("#dbs-test-distance-btn")
      const closeBtn = $(".dbs-modal-close")
      const form = $("#dbs-test-distance-form")

      // Otevření modalu
      openBtn.on("click", function () {
        modal.show()
        $("#test-origin").focus()
      })

      // Zavření modalu
      closeBtn.on("click", function () {
        modal.hide()
        $("#dbs-test-result").hide()
        form[0].reset()
      })

      // Kliknutí mimo modal
      modal.on("click", function (e) {
        if (e.target === this) {
          $(this).hide()
          $("#dbs-test-result").hide()
          form[0].reset()
        }
      })

      // ESC klávesa
      $(document).on("keydown", function (e) {
        if (e.key === "Escape" && modal.is(":visible")) {
          modal.hide()
          $("#dbs-test-result").hide()
          form[0].reset()
        }
      })

      // Odeslání formuláře
      form.on("submit", function (e) {
        e.preventDefault()
        DBSAdmin.handleDistanceTest()
      })
    },

    /**
     * Zpracování test vzdálenosti
     */
    handleDistanceTest: function () {
      const origin = $("#test-origin").val().trim()
      const destination = $("#test-destination").val().trim()
      const submitBtn = $('#dbs-test-distance-form button[type="submit"]')
      const resultDiv = $("#dbs-test-result")
      const resultContent = $("#dbs-test-result-content")

      if (!origin || !destination) {
        this.showNotice("Zadejte prosím obě adresy.", "error")
        return
      }

      // AJAX požadavek
      $.ajax({
        url: dbsAdminAjax.ajaxUrl,
        type: "POST",
        data: {
          action: "dbs_test_distance",
          nonce: dbsAdminAjax.nonce,
          origin: origin,
          destination: destination,
        },
        beforeSend: function () {
          submitBtn.prop("disabled", true).text("Počítám...")
          resultDiv.hide()
        },
        success: function (response) {
          if (response.success) {
            resultContent.html(
              '<div class="dbs-api-test-result success">' +
                "<p><strong>Vzdálenost:</strong> " +
                response.data.formatted_distance +
                "</p>" +
                "<p><strong>Jednotka:</strong> " +
                response.data.distance_unit +
                "</p>" +
                "<p><strong>Stav:</strong> Úspěšně vypočítáno</p>" +
                "</div>"
            )
          } else {
            resultContent.html(
              '<div class="dbs-api-test-result error">' +
                "<p><strong>Chyba:</strong> " +
                response.data +
                "</p>" +
                "</div>"
            )
          }
          resultDiv.show()
        },
        error: function () {
          resultContent.html(
            '<div class="dbs-api-test-result error">' +
              "<p><strong>Chyba:</strong> Nastala chyba při komunikaci se serverem.</p>" +
              "</div>"
          )
          resultDiv.show()
        },
        complete: function () {
          submitBtn.prop("disabled", false).text("Vypočítat vzdálenost")
        },
      })
    },

    /**
     * Inicializace API klíč validace
     */
    initApiKeyValidation: function () {
      $(".dbs-test-api-key").on("click", function () {
        const button = $(this)
        const service = button.data("service")
        const apiKey = $("#dbs_" + service + "_api_key")
          .val()
          .trim()

        if (!apiKey) {
          DBSAdmin.showNotice("Zadejte prosím API klíč.", "error")
          return
        }

        // Odstranění předchozích výsledků
        button.siblings(".dbs-api-test-result").remove()

        $.ajax({
          url: dbsAdminAjax.ajaxUrl,
          type: "POST",
          data: {
            action: "dbs_validate_api_key",
            nonce: dbsAdminAjax.nonce,
            service: service,
            api_key: apiKey,
          },
          beforeSend: function () {
            button.prop("disabled", true).text("Testuje se...")
          },
          success: function (response) {
            const resultClass = response.success ? "success" : "error"
            const resultText = response.success
              ? "API klíč je platný!"
              : "API klíč není platný nebo služba není dostupná."

            button.after(
              '<div class="dbs-api-test-result ' +
                resultClass +
                '">' +
                resultText +
                "</div>"
            )
          },
          error: function () {
            button.after(
              '<div class="dbs-api-test-result error">Nastala chyba při testování API klíče.</div>'
            )
          },
          complete: function () {
            button.prop("disabled", false).text("Otestovat API klíč")
          },
        })
      })
    },

    /**
     * Inicializace cache managementu
     */
    initCacheManagement: function () {
      $('[id^="dbs-clear-"][id$="-cache-btn"]').on("click", function () {
        const button = $(this)
        const cacheType = button.data("cache-type")

        if (!confirm("Opravdu chcete vymazat cache?")) {
          return
        }

        $.ajax({
          url: dbsAdminAjax.ajaxUrl,
          type: "POST",
          data: {
            action: "dbs_clear_cache",
            nonce: dbsAdminAjax.nonce,
            cache_type: cacheType,
          },
          beforeSend: function () {
            button.prop("disabled", true).text("Mazání...")
          },
          success: function (response) {
            if (response.success) {
              DBSAdmin.showNotice(response.data.message, "success")
            } else {
              DBSAdmin.showNotice("Chyba: " + response.data, "error")
            }
          },
          error: function () {
            DBSAdmin.showNotice("Nastala chyba při mazání cache.", "error")
          },
          complete: function () {
            button.prop("disabled", false)
            // Obnovení původního textu
            const originalTexts = {
              all: "Vymazat veškerou cache",
              distance: "Vymazat cache vzdáleností",
              geocoding: "Vymazat cache geokódování",
            }
            button.text(originalTexts[cacheType] || "Vymazat cache")
          },
        })
      })
    },

    /**
     * Inicializace store managementu
     */
    initStoreManagement: function () {
      // Aktualizace všech souřadnic
      $("#dbs-update-all-coordinates").on("click", function () {
        const button = $(this)

        if (
          !confirm(
            "Opravdu chcete aktualizovat souřadnice všech obchodů? Toto může chvíli trvat."
          )
        ) {
          return
        }

        $.ajax({
          url: dbsAdminAjax.ajaxUrl,
          type: "POST",
          data: {
            action: "dbs_update_store_coordinates",
            nonce: dbsAdminAjax.nonce,
          },
          beforeSend: function () {
            button.prop("disabled", true).text("Aktualizuje se...")
          },
          success: function (response) {
            if (response.success) {
              DBSAdmin.showNotice(response.data.message, "success")
              setTimeout(() => location.reload(), 1000)
            } else {
              DBSAdmin.showNotice("Chyba: " + response.data.message, "error")
            }
          },
          error: function () {
            DBSAdmin.showNotice(
              "Nastala chyba při komunikaci se serverem.",
              "error"
            )
          },
          complete: function () {
            button
              .prop("disabled", false)
              .text("Aktualizovat všechny souřadnice")
          },
        })
      })

      // Aktualizace souřadnic jednotlivého obchodu
      $(".dbs-update-coordinates").on("click", function (e) {
        e.preventDefault()

        const link = $(this)
        const storeId = link.data("store-id")
        const coordinatesSpan = $(
          '.dbs-coordinates[data-store-id="' + storeId + '"]'
        )

        $.ajax({
          url: dbsAdminAjax.ajaxUrl,
          type: "POST",
          data: {
            action: "dbs_update_store_coordinates",
            nonce: dbsAdminAjax.nonce,
            store_id: storeId,
          },
          beforeSend: function () {
            link.text("Aktualizuje se...")
          },
          success: function (response) {
            if (response.success) {
              const lat = parseFloat(response.data.latitude).toFixed(6)
              const lng = parseFloat(response.data.longitude).toFixed(6)
              coordinatesSpan.text(lat + ", " + lng)
              DBSAdmin.showNotice(
                "Souřadnice byly úspěšně aktualizovány.",
                "success"
              )
            } else {
              DBSAdmin.showNotice("Chyba: " + response.data, "error")
            }
          },
          error: function () {
            DBSAdmin.showNotice(
              "Nastala chyba při komunikaci se serverem.",
              "error"
            )
          },
          complete: function () {
            link.text("Aktualizovat souřadnice")
          },
        })
      })

      // Geokódování adresy v store formuláři
      $("#dbs-geocode-address").on("click", function () {
        const button = $(this)
        const address = $("#store_address").val().trim()

        if (!address) {
          DBSAdmin.showNotice("Zadejte prosím adresu obchodu.", "error")
          return
        }

        $.ajax({
          url: dbsAdminAjax.ajaxUrl,
          type: "POST",
          data: {
            action: "dbs_geocode_address",
            nonce: dbsAdminAjax.nonce,
            address: address,
          },
          beforeSend: function () {
            button.prop("disabled", true).text("Geokóduje se...")
            $("#dbs-geocoding-result").hide()
          },
          success: function (response) {
            if (response.success) {
              $("#store_latitude").val(response.data.latitude)
              $("#store_longitude").val(response.data.longitude)

              $("#dbs-geocoding-result-content").html(
                '<div class="dbs-geocoding-success">' +
                  "<p><strong>Úspěch!</strong> " +
                  response.data.message +
                  "</p>" +
                  "<p><strong>Zeměpisná šířka:</strong> " +
                  response.data.latitude +
                  "</p>" +
                  "<p><strong>Zeměpisná délka:</strong> " +
                  response.data.longitude +
                  "</p>" +
                  "</div>"
              )
            } else {
              $("#dbs-geocoding-result-content").html(
                '<div class="dbs-geocoding-error">' +
                  "<p><strong>Chyba!</strong> " +
                  response.data +
                  "</p>" +
                  "</div>"
              )
            }
            $("#dbs-geocoding-result").show()
          },
          error: function () {
            $("#dbs-geocoding-result-content").html(
              '<div class="dbs-geocoding-error">' +
                "<p><strong>Chyba!</strong> Nastala chyba při komunikaci se serverem.</p>" +
                "</div>"
            )
            $("#dbs-geocoding-result").show()
          },
          complete: function () {
            button.prop("disabled", false).text("Ověřit a geokódovat adresu")
          },
        })
      })
    },

    /**
     * Inicializace rule managementu
     */
    initRuleManagement: function () {
      // Kalkulátor sazeb
      $("#calculate_rate").on("click", function () {
        DBSAdmin.calculateShippingRate()
      })

      // Rate calculation
      $("#base_rate, #per_km_rate").on("input", function () {
        calculateRate();
      });

      function calculateRate() {
        const baseRate = parseFloat($("#base_rate").val()) || 0;
        const perKmRate = parseFloat($("#per_km_rate").val()) || 0;
        const distance = parseFloat($("#test_distance").val()) || 0;

        const totalRate = baseRate + (perKmRate * distance);
        $("#calculated_rate").text(totalRate.toFixed(2));
      }

      // Výběr/zrušení výběru kategorií
      $("#select_all_categories").on("click", function () {
        $('input[name="product_categories[]"]').prop("checked", true)
      })

      $("#deselect_all_categories").on("click", function () {
        $('input[name="product_categories[]"]').prop("checked", false)
      })

      // Výběr/zrušení výběru dopravních tříd
      $("#select_all_classes").on("click", function () {
        $('input[name="shipping_classes[]"]').prop("checked", true)
      })

      $("#deselect_all_classes").on("click", function () {
        $('input[name="shipping_classes[]"]').prop("checked", false)
      })
    },

    /**
     * Kalkulátor dopravní sazby
     */
    calculateShippingRate: function () {
      const distance = parseFloat($("#calc_distance").val()) || 0
      const baseRate = parseFloat($("#base_rate").val()) || 0
      const perKmRate = parseFloat($("#per_km_rate").val()) || 0

      if (distance <= 0) {
        this.showNotice("Zadejte platnou vzdálenost.", "error")
        return
      }

      // Always use kilometers for calculation
      const totalRate = baseRate + (distance * perKmRate)

      $("#calc_total").text(totalRate.toFixed(2) + " Kč")
      $("#calc_result").show()
    },

    /**
     * Inicializace settings managementu
     */
    initSettingsManagement: function () {
      // Přepínání zobrazení API klíčů podle vybrané služby
      $('input[name="dbs_map_service"]').on("change", function () {
        const selectedService = $(this).val()

        $("#google-api-row, #bing-api-row").hide()

        if (selectedService === "google") {
          $("#google-api-row").show()
        } else if (selectedService === "bing") {
          $("#bing-api-row").show()
        }
      })

      // Trigger změny při načtení stránky
      $('input[name="dbs_map_service"]:checked').trigger("change")
    },

    /**
     * Inicializace form validace
     */
    initFormValidation: function () {
      // Store formulář validace
      $("#dbs-store-form").on("submit", function (e) {
        return DBSAdmin.validateStoreForm()
      })

      // Rule formulář validace
      $("#dbs-rule-form").on("submit", function (e) {
        return DBSAdmin.validateRuleForm()
      })
    },

    /**
     * Validace store formuláře
     */
    validateStoreForm: function () {
      const storeName = $("#store_name").val().trim()
      const storeAddress = $("#store_address").val().trim()

      if (!storeName) {
        this.showNotice("Název obchodu je povinný.", "error")
        $("#store_name").focus()
        return false
      }

      if (!storeAddress) {
        this.showNotice("Adresa obchodu je povinná.", "error")
        $("#store_address").focus()
        return false
      }

      // Validace souřadnic pokud jsou vyplněné
      const latitude = $("#store_latitude").val()
      const longitude = $("#store_longitude").val()

      if (latitude && (latitude < -90 || latitude > 90)) {
        this.showNotice("Zeměpisná šířka musí být mezi -90 a 90.", "error")
        $("#store_latitude").focus()
        return false
      }

      if (longitude && (longitude < -180 || longitude > 180)) {
        this.showNotice("Zeměpisná délka musí být mezi -180 a 180.", "error")
        $("#store_longitude").focus()
        return false
      }

      return true
    },

    /**
     * Validace rule formuláře
     */
    validateRuleForm: function () {
      const ruleName = $("#rule_name").val().trim()
      const distanceFrom = parseFloat($("#distance_from").val())
      const distanceTo = parseFloat($("#distance_to").val())

      if (!ruleName) {
        this.showNotice("Název pravidla je povinný.", "error")
        $("#rule_name").focus()
        return false
      }

      if (isNaN(distanceFrom) || distanceFrom < 0) {
        this.showNotice("Vzdálenost od musí být nezáporné číslo.", "error")
        $("#distance_from").focus()
        return false
      }

      if (distanceTo > 0 && distanceTo <= distanceFrom) {
        this.showNotice(
          "Vzdálenost do musí být větší než vzdálenost od.",
          "error"
        )
        $("#distance_to").focus()
        return false
      }

      // Kontrola sazeb - povolujeme nulové hodnoty pro dopravu zdarma
      const baseRate = parseFloat($("#base_rate").val()) || 0
      const perKmRate = parseFloat($("#per_km_rate").val()) || 0

      // Kontrola pouze záporných hodnot
      if (baseRate < 0 || perKmRate < 0) {
        this.showNotice(
          "Sazby nemohou být záporné.",
          "error"
        )
        $("#base_rate").focus()
        return false
      }

      return true
    },

    /**
     * Inicializace tooltipů
     */
    initTooltips: function () {
      // Jednoduché tooltip implementace
      $(".dbs-tooltip").hover(function () {
        const tooltip = $(this).attr("data-tooltip")
        if (tooltip) {
          $(this).attr("title", tooltip)
        }
      })
    },

    /**
     * Obecná validace
     */
    initValidation: function () {
      // Real-time validace pro číselné vstupy
      $('input[type="number"]').on("input", function () {
        const input = $(this)
        const min = parseFloat(input.attr("min"))
        const max = parseFloat(input.attr("max"))
        const value = parseFloat(input.val())

        input.removeClass("invalid")

        if (!isNaN(min) && value < min) {
          input.addClass("invalid")
        }

        if (!isNaN(max) && value > max) {
          input.addClass("invalid")
        }
      })

      // Validace email polí
      $('input[type="email"]').on("blur", function () {
        const input = $(this)
        const email = input.val().trim()
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

        input.removeClass("invalid")

        if (email && !emailRegex.test(email)) {
          input.addClass("invalid")
        }
      })
    },

    /**
     * Zobrazení upozornění
     */
    showNotice: function (message, type = "info") {
      // Odstranění existujících upozornění
      $(".dbs-admin-notice").remove()

      const noticeClass =
        "notice notice-" + type + " is-dismissible dbs-admin-notice"
      const notice = $(
        '<div class="' + noticeClass + '"><p>' + message + "</p></div>"
      )

      // Přidání na začátek wrap elementu
      $(".wrap").prepend(notice)

      // Auto-hide po 5 sekundách pro success zprávy
      if (type === "success") {
        setTimeout(function () {
          notice.fadeOut(function () {
            $(this).remove()
          })
        }, 5000)
      }

      // Scroll na vrch stránky
      $("html, body").animate(
        {
          scrollTop: 0,
        },
        300
      )
    },

    /**
     * Utility funkce pro AJAX
     */
    ajaxRequest: function (action, data = {}, options = {}) {
      const defaultOptions = {
        url: dbsAdminAjax.ajaxUrl,
        type: "POST",
        dataType: "json",
      }

      const ajaxData = $.extend(
        {
          action: action,
          nonce: dbsAdminAjax.nonce,
        },
        data
      )

      const ajaxOptions = $.extend(defaultOptions, options, {
        data: ajaxData,
      })

      return $.ajax(ajaxOptions)
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
  }

  /**
   * Inicializace při načtení DOM
   */
  $(document).ready(function () {
    DBSAdmin.init()
  })

  /**
   * Export do globálního scope pro použití v jiných skriptech
   */
  window.DBSAdmin = DBSAdmin
})(jQuery)
