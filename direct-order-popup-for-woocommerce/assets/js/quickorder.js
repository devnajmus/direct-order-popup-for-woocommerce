/**
 *  Direct Order Popup Checkout scripts - FIXED VARIATION SYSTEM
 */
jQuery(function ($) {
  "use strict";

  // Cart model
  const cart = {
    items: [],
    subtotal: 0,
    shipping: 0,
    total: 0,
  };

  // Initialize
  function init() {
    bindEvents();
  }

  // Bind event handlers
  function bindEvents() {
    $(document).on("click", ".DOPW-quick-order-btn", openPopup);
    $(document).on("click", ".DOPW-close, .DOPW-cancel", closePopup);
    $(document).on("click", ".DOPW-popup", function (e) {
      if ($(e.target).is(".DOPW-popup")) closePopup();
    });
    $(document).on("click", ".DOPW-qty-btn.plus", increaseQuantity);
    $(document).on("click", ".DOPW-qty-btn.minus", decreaseQuantity);
    $(document).on("change", ".DOPW-quantity", updateQuantity);
    $(document).on("click", ".DOPW-remove-product", removeProduct);
    $(document).on("change", "#DOPW-shipping-method", updateShipping);
    $(document).on("submit", "#DOPW-order-form", handleSubmit);
    $(document).on("click", ".DOPW-place-order", function (e) {
      const $btn = $(this);
      if ($btn.prop("disabled")) return;
      $("#DOPW-order-form").trigger("submit");
    });
  }

  // Open popup
  function openPopup(e) {
    e && e.preventDefault();
    const $btn = $(this);
    const productId = $btn.data("product-id");
    const productType = $btn.data("product-type") || "simple";

    if (!productId) return;

    const product = {
      id: String(productId),
      id_raw: productId,
      type: productType,
      title: $btn.data("product-title") || "",
      price: parseFloat($btn.data("product-price") || 0) || 0,
      priceFormatted: $btn.data("product-price-formatted") || null,
      image: $btn.data("product-image-url") || "",
      quantity: 1,
      variation_id: 0,
      variation_valid: false,
      original_title: $btn.data("product-title") || "",
      original_price: parseFloat($btn.data("product-price") || 0) || 0,
    };

    $("#DOPW-popup").fadeIn(200);
    $("body").addClass("DOPW-open");

    if (product.type === "variable") {
      fetchVariationsAndRender(product.id_raw, function () {
        const existing = cart.items.find(
          (it) => String(it.id) === String(product.id)
        );
        if (existing) {
          existing.quantity += 1;
        } else {
          cart.items.push(product);
        }
        updateProductList();
        updateTotals();
        updatePlaceOrderButton();
      });
    } else {
      const existing = cart.items.find(
        (it) => String(it.id) === String(product.id)
      );
      if (existing) {
        existing.quantity += 1;
      } else {
        cart.items.push(product);
      }
      updateProductList();
      updateTotals();
      updatePlaceOrderButton();
    }
  }

  // Update product list
  function updateProductList() {
    const $list = $("#DOPW-product-list");
    $list.empty();

    cart.items.forEach((item, index) => {
      const rowTotal =
        (parseFloat(item.price) || 0) * (parseInt(item.quantity) || 0);
      let variationInfo = "";
      let statusClass = "";
      let displayTitle = item.title;

      if (item.type === "variable") {
        if (
          item.variation_attributes &&
          Object.values(item.variation_attributes).some((val) => val !== "")
        ) {
          const attrDisplay = [];
          Object.keys(item.variation_attributes).forEach((attrKey) => {
            const attrValue = item.variation_attributes[attrKey];
            if (attrValue && attrValue !== "") {
              const attrName = attrKey
                .replace(/(^attribute_pa_|^attribute_)/i, "")
                .replace(/_/g, " ");
              attrDisplay.push(`${attrName}: ${attrValue}`);
            }
          });

          variationInfo =
            '<div class="DOPW-variation-info">' +
            attrDisplay.join(", ") +
            "</div>";
          statusClass = item.variation_valid
            ? "DOPW-variation-valid"
            : "DOPW-variation-invalid";
        } else {
          variationInfo =
            '<div class="DOPW-variation-info DOPW-variation-required">Please select variation</div>';
          statusClass = "DOPW-variation-required";
          displayTitle = item.original_title;
        }
      }

      const $row = $(
        '<div class="DOPW-product-row ' +
          statusClass +
          '" data-product-id="' +
          item.id +
          '" data-product-raw-id="' +
          item.id_raw +
          '" data-item-index="' +
          index +
          '">' +
          '  <div class="DOPW-product-image"><img src="' +
          (item.image || "") +
          '" alt="' +
          escapeHtml(displayTitle) +
          '"></div>' +
          '  <div class="DOPW-product-info">' +
          "    <h5 class='DOPW-product-title'>" +
          escapeHtml(displayTitle) +
          "</h5>" +
          variationInfo +
          '    <div class="DOPW-product-price">' +
          formatPrice(item.price) +
          "</div>" +
          "  </div>" +
          '  <div class="DOPW-quantity-wrapper">' +
          '    <button type="button" class="DOPW-qty-btn minus">-</button>' +
          '    <input type="number" class="DOPW-quantity" value="' +
          (item.quantity || 1) +
          '" min="1" max="999">' +
          '    <button type="button" class="DOPW-qty-btn plus">+</button>' +
          "  </div>" +
          '  <div class="DOPW-row-total">' +
          formatPrice(rowTotal) +
          "</div>" +
          '  <button type="button" class="DOPW-remove-product">&times;</button>' +
          "</div>"
      );

      $list.append($row);
    });
  }

  // Quantity functions
  function increaseQuantity() {
    const $row = $(this).closest(".DOPW-product-row");
    const $input = $row.find(".DOPW-quantity");
    $input.val(Math.max(1, parseInt($input.val() || 0) + 1)).trigger("change");
  }

  function decreaseQuantity() {
    const $row = $(this).closest(".DOPW-product-row");
    const $input = $row.find(".DOPW-quantity");
    const v = parseInt($input.val() || 0);
    if (v > 1) $input.val(v - 1).trigger("change");
  }

  function updateQuantity() {
    const $row = $(this).closest(".DOPW-product-row");
    const id = String($row.data("product-id"));
    const rawId = $row.data("product-raw-id");
    const itemIndex = $row.data("item-index");

    let val = parseInt($(this).val(), 10);
    if (isNaN(val) || val < 1) val = 1;
    $(this).val(val);

    const item =
      cart.items[itemIndex] ||
      cart.items.find(
        (it) => String(it.id) === id || String(it.id_raw) === String(rawId)
      );
    if (item) {
      item.quantity = val;
      updateProductList();
      updateTotals();
    }
  }

  function removeProduct() {
    const $row = $(this).closest(".DOPW-product-row");
    const id = String($row.data("product-id"));
    const rawId = $row.data("product-raw-id");

    cart.items = cart.items.filter(
      (it) => String(it.id) !== id && String(it.id_raw) !== String(rawId)
    );
    $row.fadeOut(200, function () {
      $(this).remove();
      updateTotals();
      updatePlaceOrderButton();

      const hasVariableProducts = cart.items.some(
        (it) => it.type === "variable"
      );
      if (!hasVariableProducts) {
        $("#DOPW-variation-selectors").empty();
      }
    });
  }

  function updateShipping() {
    const cost = parseFloat($(this).find(":selected").data("cost") || 0) || 0;
    cart.shipping = cost;
    $(".DOPW-shipping-amount").text(formatPrice(cost));
    updateTotals();
  }

  function updateTotals() {
    cart.subtotal = 0;
    cart.items.forEach((item) => {
      cart.subtotal +=
        (parseFloat(item.price) || 0) * (parseInt(item.quantity) || 0);
    });
    const $sel = $("#DOPW-shipping-method");
    if ($sel.length) {
      cart.shipping =
        parseFloat($sel.find(":selected").data("cost") || cart.shipping || 0) ||
        0;
    }
    cart.total = cart.subtotal + (cart.shipping || 0);
    $(".DOPW-subtotal-amount").text(formatPrice(cart.subtotal));
    $(".DOPW-shipping-amount").text(formatPrice(cart.shipping));
    $(".DOPW-total-amount").text(formatPrice(cart.total));
  }

  // Fetch variations
  function fetchVariationsAndRender(productId, callback) {
    // FIXED: Get ajaxurl from localized script data
    const ajaxUrl =
      typeof DOPWAjax !== "undefined" && DOPWAjax.ajaxurl
        ? DOPWAjax.ajaxurl
        : typeof DOPWData !== "undefined" && DOPWData.ajaxurl
        ? DOPWData.ajaxurl
        : "";

    if (!ajaxUrl) {
      console.error(
        "DOPW Error: Ajax URL not found. Please ensure wp_localize_script is configured correctly."
      );
      if (callback) callback();
      return;
    }

    const nonce =
      typeof DOPWAjax !== "undefined" && DOPWAjax.nonce ? DOPWAjax.nonce : "";

    $.post(
      ajaxUrl,
      {
        action: "DOPW_get_variations",
        product_id: productId,
        nonce: nonce,
      },
      function (res) {
        if (res && res.success && res.data) {
          renderVariationForm(
            productId,
            res.data.attributes,
            res.data.variations
          );
        }
        if (callback) callback();
      }
    );
  }

  // Render variation form with improved matching
  function renderVariationForm(productId, attributes, variations) {
    const $wrap = $("#DOPW-variation-selectors");
    $wrap.empty();

    if (!attributes || Object.keys(attributes).length === 0) return;

    window.DOPWVariationsData = {
      productId: productId,
      attributes: attributes,
      variations: variations,
    };

    let html =
      '<div class="DOPW-variations-wrapper" data-product-id="' +
      productId +
      '">';
    html += '<table class="variations" cellspacing="0"><tbody>';

    Object.keys(attributes).forEach((attrKey) => {
      const opts = attributes[attrKey] || [];
      const cleaned = attrKey
        .replace(/(^attribute_pa_|^attribute_|^pa_)/i, "")
        .replace(/_/g, " ");
      const fieldId = `DOPW_attribute_${attrKey.replace(/[^a-z0-9_-]/gi, "_")}`;

      html += '<tr class="DOPW-variation-row">';
      html +=
        '<td class="label"><label for="' +
        fieldId +
        '">' +
        escapeHtml(cleaned) +
        "</label></td>";
      html += '<td class="value">';
      html +=
        '<select id="' +
        fieldId +
        '" name="' +
        attrKey +
        '" data-attribute-name="' +
        attrKey +
        '" class="DOPW-attribute-select">';
      html += '<option value="">Choose an option</option>';

      opts.forEach((opt) => {
        const value = opt && opt.slug ? String(opt.slug) : String(opt);
        const text = opt && opt.label ? opt.label : String(opt);
        html +=
          '<option value="' +
          escapeHtml(value) +
          '">' +
          escapeHtml(text) +
          "</option>";
      });

      html += "</select>";
      html += "</td>";
      html += "</tr>";
    });

    html += "</tbody></table>";
    html += "</div>";

    $wrap.append(html);

    $wrap.find(".DOPW-attribute-select").on("change", function () {
      handleVariationChange(productId, attributes, variations);
    });

    handleVariationChange(productId, attributes, variations);
  }

  // Handle variation selection changes
  function handleVariationChange(productId, attributes, variations) {
    const $selects = $(".DOPW-attribute-select");
    const selected = {};
    let allSelected = true;

    $selects.each(function () {
      const $select = $(this);
      const attrName = $select.data("attribute-name");
      const value = $select.val();

      if (!value || value === "") {
        allSelected = false;
      } else {
        selected["attribute_" + attrName] = String(value).toLowerCase().trim();
      }
    });

    if (!allSelected) {
      applyMatchedVariation(null, productId);
      return;
    }

    let matched = null;
    for (const variation of variations) {
      if (!variation.attributes) continue;

      let isMatch = true;

      for (const selectedKey in selected) {
        const selectedValue = selected[selectedKey];
        const variationValue = String(variation.attributes[selectedKey] || "")
          .toLowerCase()
          .trim();

        if (variationValue === "" || variationValue === selectedValue) continue;
        isMatch = false;
        break;
      }

      if (isMatch) {
        for (const varKey in variation.attributes) {
          const varValue = String(variation.attributes[varKey] || "").trim();
          if (varValue !== "" && !selected[varKey]) {
            isMatch = false;
            break;
          }
        }
      }

      if (isMatch) {
        matched = variation;
        break;
      }
    }

    applyMatchedVariation(matched, productId);
  }

  // Apply matched variation data to cart item
  function applyMatchedVariation(matched, productId) {
    const cartItem = cart.items.find(
      (it) =>
        String(it.id_raw) === String(productId) ||
        String(it.id) === String(productId)
    );
    if (!cartItem) return;

    if (!matched) {
      cartItem.variation_valid = false;
      cartItem.variation_id = 0;
      cartItem.variation_attributes = {};
      cartItem.price = cartItem.original_price || 0;
      cartItem.title = cartItem.original_title || cartItem.title;
    } else {
      cartItem.variation_id = matched.variation_id || 0;
      cartItem.variation_attributes = matched.attributes || {};
      cartItem.price = parseFloat(
        matched.display_price || matched.price || cartItem.original_price || 0
      );

      const attrDisplay = [];
      if (matched.attributes) {
        Object.keys(matched.attributes).forEach(function (k) {
          const v = matched.attributes[k];
          if (v && v !== "") {
            const name = k
              .replace(/(^attribute_pa_|^attribute_|^pa_)/i, "")
              .replace(/_/g, " ");
            attrDisplay.push(name + ": " + v);
          }
        });
      }

      cartItem.title =
        cartItem.original_title +
        (attrDisplay.length ? " - " + attrDisplay.join(", ") : "");
      cartItem.variation_valid =
        matched.is_in_stock !== false && matched.is_purchasable !== false;

      if (matched.image && matched.image.src) {
        cartItem.image = matched.image.src;
      }
    }

    updateProductList();
    updateTotals();
    updatePlaceOrderButton();
  }

  function updatePlaceOrderButton() {
    const hasItems = cart.items.length > 0;
    const allVariationsValid = cart.items.every(
      (it) => it.type !== "variable" || (it.variation_valid && it.variation_id)
    );
    $(".DOPW-place-order").prop("disabled", !hasItems || !allVariationsValid);
  }

  // Submit order
  function handleSubmit(e) {
    e.preventDefault();

    const shippingMethod = $("#DOPW-shipping-method").val();
    const paymentMethod = $('input[name="DOPW_payment_method"]:checked').val();
    const name = $("#DOPW-name").val().trim();
    const phone = $("#DOPW-phone").val().trim();
    const address = $("#DOPW-address").val().trim();
    const email = $("#DOPW-email").val().trim();

    const missingFields = [];
    if (!name) missingFields.push("Name");
    if (!phone) missingFields.push("Phone");
    if (!address) missingFields.push("Address");
    if (!shippingMethod) missingFields.push("Shipping Method");
    if (!paymentMethod) missingFields.push("Payment Method");
    if (missingFields.length > 0) {
      alert(`Please complete: ${missingFields.join(", ")}`);
      return;
    }
    if (cart.items.length === 0) {
      alert("Please add at least one product");
      return;
    }

    const invalidVariations = cart.items.filter(
      (it) =>
        it.type === "variable" && (!it.variation_valid || !it.variation_id)
    );
    if (invalidVariations.length > 0) {
      alert(
        `Please select variations for: ${invalidVariations
          .map((it) => it.original_title)
          .join(", ")}`
      );
      return;
    }

    const itemsPayload = cart.items.map((it) => ({
      id: it.id_raw,
      quantity: it.quantity,
      price: it.price,
      product_type: it.type,
      ...(it.type === "variable" &&
        it.variation_id && {
          variation_id: it.variation_id,
          variation_attributes: it.variation_attributes || {},
        }),
    }));

    const nonce =
      $("#DOPW-nonce").val() ||
      (typeof DOPWAjax !== "undefined" && DOPWAjax.nonce ? DOPWAjax.nonce : "");

    // FIXED: Get ajaxurl from localized script data
    const ajaxUrl =
      typeof DOPWAjax !== "undefined" && DOPWAjax.ajaxurl
        ? DOPWAjax.ajaxurl
        : typeof DOPWData !== "undefined" && DOPWData.ajaxurl
        ? DOPWData.ajaxurl
        : "";

    if (!ajaxUrl) {
      console.error(
        "DOPW Error: Ajax URL not found. Please ensure wp_localize_script is configured correctly."
      );
      alert("Configuration error. Please contact support.");
      return;
    }

    const $submitBtn = $(".DOPW-place-order");
    const originalText = $submitBtn.text();
    $submitBtn.prop("disabled", true).text("Placing Order...");

    $.ajax({
      url: ajaxUrl,
      type: "POST",
      data: {
        action: "DOPW_process_order",
        nonce,
        customer: { name, phone, email, address },
        order: {
          items: itemsPayload,
          shipping_method: shippingMethod,
          payment_method: paymentMethod,
          subtotal: cart.subtotal,
          shipping: cart.shipping,
          total: cart.total,
        },
      },
      dataType: "json",
      timeout: 30000,
    })
      .done((response) => {
        if (response.success) {
          console.log("🎉 Order ID:", response.data.order_id);
          alert(`Order placed successfully! ID: ${response.data.order_id}`);
          resetForm();
          closePopup();
        } else {
          const errorMessage =
            response.data?.message || "Failed to place order";
          alert(`Order failed: ${errorMessage}`);
        }
      })
      .fail(() => {
        alert("An error occurred while placing the order.");
      })
      .always(() => {
        $submitBtn.prop("disabled", false).text(originalText);
      });
  }

  // Helpers
  function escapeHtml(str) {
    if (!str && str !== 0) return "";
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function formatPrice(price) {
    price = parseFloat(price) || 0;
    const data =
      typeof DOPWData !== "undefined"
        ? DOPWData
        : typeof woocommerce_params !== "undefined"
        ? woocommerce_params
        : null;
    if (typeof accounting !== "undefined") {
      if (data)
        return accounting.formatMoney(price, {
          symbol: data.currency_symbol || "$",
          decimal: data.decimal_separator || ".",
          thousand: data.thousand_separator || ",",
          precision: parseInt(data.decimals) || 2,
          format: data.price_format || "%s%v",
        });
      return accounting.formatMoney(price, { symbol: "$", precision: 2 });
    }
    return price.toFixed(2);
  }

  function resetForm() {
    $("#DOPW-order-form")[0].reset();
    cart.items = [];
    cart.subtotal = 0;
    cart.shipping = 0;
    cart.total = 0;
    $("#DOPW-variation-selectors").empty();
    updateProductList();
    updateTotals();
    updatePlaceOrderButton();
  }

  function closePopup() {
    $("#DOPW-popup").fadeOut(200);
    $("body").removeClass("DOPW-open");
  }

  init();
});
